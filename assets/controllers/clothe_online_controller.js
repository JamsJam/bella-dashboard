import { Controller } from '@hotwired/stimulus';
import { refreshFlashMessages } from '../lib/refresh_flash_messages.js';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
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
                throw new Error(data.error || 'Clothe online update failed.');
            }

            const canonicalChecked = this.resolveCheckedState(data, checked);

            this.dispatchToggleEvent(event.target, 'toggle:success', {
                id,
                checked: canonicalChecked,
                label: action.label,
            });
            this.updateStatusLabel(id, canonicalChecked);
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

    updateStatusLabel(toggleId, isOnline) {
        const clotheId = String(toggleId).replace('clothe-online-', '');
        const status = this.element.querySelector(`[data-clothe-online-status-id="${CSS.escape(clotheId)}"]`);

        if (status) {
            status.textContent = isOnline ? 'En ligne' : 'Hors ligne';
        }
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
}
