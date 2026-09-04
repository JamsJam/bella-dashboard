import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = {
        previewUrl: String,
    };

    async preview() {
        if (!this.hasPreviewUrlValue) {
            return;
        }

        const response = await fetch(this.previewUrlValue, {
            method: 'POST',
            body: new FormData(this.element),
            headers: {
                'Accept': 'text/vnd.turbo-stream.html',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            console.error(`Unable to preview size guide: ${response.status}`);
            return;
        }

        const html = await response.text();

        if (window.Turbo?.renderStreamMessage) {
            window.Turbo.renderStreamMessage(html);
            return;
        }

        const documentFragment = document.createRange().createContextualFragment(html);
        const template = documentFragment.querySelector('template');
        const target = document.getElementById('clothe-size-guide-table');

        if (template && target) {
            target.replaceWith(template.content.firstElementChild);
        }
    }
}
