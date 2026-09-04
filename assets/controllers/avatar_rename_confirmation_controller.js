import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    cancel() {
        this.element.remove();
    }

    async accept(event) {
        event.preventDefault();
        const form = event.currentTarget;
        const response = await fetch(form.action, {
            method: 'POST',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: new FormData(form),
        });

        if (!response.ok) {
            return;
        }

        const data = await response.json().catch(() => ({}));
        this.element.remove();
        window.dispatchEvent(new CustomEvent('avatar-rename:confirmation-accepted', {
            detail: data,
        }));
    }
}
