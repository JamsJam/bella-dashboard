import { Controller } from '@hotwired/stimulus';
import { refreshFlashMessages } from '../lib/refresh_flash_messages.js';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['collectionsList'];

    async change(event) {
        console.log('EVENT received', event.type, event.detail);

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
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': action.csrfToken || '',
                },
            });

            if (!response.ok) {
                throw new Error(`Unable to update category online state: ${response.status}`);
            }

            const data = await response.json();

            if (data.success !== true) {
                throw new Error(data.error || 'Category online update failed.');
            }

            this.dispatchToggleEvent(event.target, 'toggle:success', {
                id,
                checked: this.resolveCheckedState(data, checked),
                label: action.label,
            });

            this.reloadCollectionsList(data);
        } catch (error) {
            // console.error(error);
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

        console.log('EVENT DISPATCH', name, detail);

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

    reloadCollectionsList(data) {
        if (!this.hasCollectionsListTarget || typeof data.collectionsHtml !== 'string') {
            return;
        }

        this.collectionsListTarget.outerHTML = data.collectionsHtml;
    }
}
