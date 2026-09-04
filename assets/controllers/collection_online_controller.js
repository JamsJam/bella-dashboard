import { Controller } from '@hotwired/stimulus';
import { refreshFlashMessages } from '../lib/refresh_flash_messages.js';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['clothesFrame'];

    async change(event) {
        if (!event.detail?.id) {
            return;
        }

        const { id, checked, previousChecked, payload = {} } = event.detail;
        const action = checked ? payload?.on : payload?.off;

        if (!action?.url) {
            this.dispatchToggleEvent(event.target, 'toggle:error', {
                id,
                label: previousChecked ? payload?.on?.label : payload?.off?.label,
            });
            this.dispatchToggleEvent(event.target, 'toggle:done', { id });

            return;
        }

        try {
            const response = await fetch(action.url, {
                method: action.method || 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': action.csrfToken || '',
                },
            });
            const data = await response.json();

            if (!response.ok || data.success !== true) {
                throw new Error(data.error || 'Collection online update failed.');
            }

            this.dispatchToggleEvent(event.target, 'toggle:success', {
                id,
                checked: this.resolveCheckedState(data, checked),
                label: action.label,
            });
            this.reloadClothesFrame(data);
        } catch {
            this.dispatchToggleEvent(event.target, 'toggle:error', {
                id,
                label: previousChecked ? payload?.on?.label : payload?.off?.label,
            });
        } finally {
            this.dispatchToggleEvent(event.target, 'toggle:done', { id });
            refreshFlashMessages();
        }
    }

    dispatchToggleEvent(target, name, detail) {
        if (!target) {
            return;
        }

        target.dispatchEvent(new CustomEvent(name, {
            bubbles: true,
            detail,
        }));
    }

    resolveCheckedState(data, fallback) {
        if (typeof data.checked === 'boolean') {
            return data.checked;
        }

        if (typeof data.isOnline === 'boolean') {
            return data.isOnline;
        }

        return fallback;
    }

    reloadClothesFrame(data) {
        if (!this.hasClothesFrameTarget || typeof data.clothesFrameUrl !== 'string') {
            return;
        }

        if (this.clothesFrameTarget.getAttribute('src') === data.clothesFrameUrl) {
            this.clothesFrameTarget.reload();

            return;
        }

        this.clothesFrameTarget.src = data.clothesFrameUrl;
    }
}
