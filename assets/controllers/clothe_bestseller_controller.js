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
            const body = {
                mode: action.mode || (checked ? 'add' : 'remove'),
                ids: action.ids || [],
                slug: action.slug || null,
            };

            const response = await fetch(action.url, {
                method: action.method || 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/vnd.turbo-stream.html, application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': action.csrfToken || '',
                },
                body: JSON.stringify(body),
            });

            const contentType = response.headers.get('Content-Type') || '';
            const responseBody = await response.text();

            if (contentType.includes('text/vnd.turbo-stream.html')) {
                this.renderTurboStream(responseBody);
                this.dispatchToggleEvent(event.target, 'toggle:error', {
                    id,
                    label: previousChecked ? payload?.on?.label : payload?.off?.label,
                });
                return;
            }

            const data = responseBody ? JSON.parse(responseBody) : {};
            if (!response.ok || data.success !== true) {
                throw new Error(data.message || data.error || `Unable to update bestseller state: ${response.status}`);
            }

            this.dispatchToggleEvent(event.target, 'toggle:success', {
                id,
                checked,
                label: action.label,
            });
        } catch (error) {
            console.error('Erreur lors de la mise a jour bestseller:', error);
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

    renderTurboStream(html) {
        if (window.Turbo?.renderStreamMessage) {
            window.Turbo.renderStreamMessage(html);
            return;
        }

        const documentFragment = document.createRange().createContextualFragment(html);
        const template = documentFragment.querySelector('template');
        const target = document.getElementById('modal-root');

        if (template && target) {
            target.replaceChildren(template.content.cloneNode(true));
        }
    }
}
