import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'zone', 'preview', 'placeholder'];

    disconnect() {
        this.revokePreview();
    }

    open(event) {
        event.preventDefault();
        this.inputTarget.click();
    }

    dragOver(event) {
        event.preventDefault();
        event.dataTransfer.dropEffect = 'copy';
        this.zoneTarget.classList.add('is-drag-over');
    }

    dragLeave(event) {
        if (!this.zoneTarget.contains(event.relatedTarget)) {
            this.zoneTarget.classList.remove('is-drag-over');
        }
    }

    drop(event) {
        event.preventDefault();
        this.zoneTarget.classList.remove('is-drag-over');

        const file = Array.from(event.dataTransfer.files || []).find((item) => item.type.startsWith('image/'));
        if (!file) {
            return;
        }

        const transfer = new DataTransfer();
        transfer.items.add(file);
        this.inputTarget.files = transfer.files;
        this.upload(file);
    }

    select() {
        const [file] = this.inputTarget.files;
        if (file) {
            this.upload(file);
        }
    }

    upload(file) {
        this.revokePreview();
        this.previewUrl = URL.createObjectURL(file);
        this.previewTarget.src = this.previewUrl;
        this.previewTarget.hidden = false;
        this.placeholderTarget.hidden = true;
        this.element.requestSubmit();
    }

    revokePreview() {
        if (this.previewUrl) {
            URL.revokeObjectURL(this.previewUrl);
            this.previewUrl = null;
        }
    }
}
