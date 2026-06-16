import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [
        'collection',
        'collectionItems',
        'contentField',
        'contentTypeSelect',
        'imageField',
        'preview',
        'previewFrame',
        'section',
    ];

    connect() {
        this.contentTypeSelectTargets.forEach((select) => {
            this.updateContentFields(select);
        });
    }

    contentTypeSelectTargetConnected(select) {
        this.updateContentFields(select);
    }

    contentFieldTargetConnected(field) {
        const section = this.findClosestSection(field);
        const select = this.contentTypeSelectTargets.find((target) => this.findClosestSection(target) === section);

        if (select) {
            this.updateContentFields(select);
        }
    }

    imageFieldTargetConnected(field) {
        if (!field.dataset.configContentField) {
            return;
        }

        const section = this.findClosestSection(field);
        const select = this.contentTypeSelectTargets.find((target) => this.findClosestSection(target) === section);

        if (select) {
            this.updateContentFields(select);
        }
    }

    addCollectionItem(event) {
        const collection = this.findClosestTarget(this.collectionTargets, event.currentTarget);
        const items = this.collectionItemsTargets.find((target) => target.parentElement === collection);
        const prototype = collection?.dataset.prototype;

        if (!collection || !items || !prototype) {
            return;
        }

        const index = Number(collection.dataset.index || 0);
        collection.dataset.index = String(index + 1);
        items.insertAdjacentHTML('beforeend', prototype.replace(/__name__/g, String(index)));
    }

    removeCollectionItem(event) {
        this.findClosestTarget(this.sectionTargets, event.currentTarget)?.remove();
    }

    toggleContentFields(event) {
        this.updateContentFields(event.currentTarget);
    }

    preview(event) {
        const input = event.currentTarget;
        const field = this.findClosestTarget(this.imageFieldTargets, input);
        const previewFrame = this.previewFrameTargets.find((target) => field?.contains(target));
        const preview = this.previewTargets.find((target) => field?.contains(target));
        const file = input.files?.[0];

        if (!preview || !file) {
            return;
        }

        preview.src = URL.createObjectURL(file);
        preview.hidden = false;
        preview.style.display = '';
        if (previewFrame) {
            previewFrame.hidden = false;
            previewFrame.style.display = '';
        }
    }

    updateContentFields(select) {
        const section = this.findClosestSection(select);
        if (!section) {
            return;
        }

        this.getContentFields(section).forEach((field) => {
            const isVisible = field.dataset.configContentField === select.value;

            field.hidden = !isVisible;
            field.style.display = isVisible ? '' : 'none';
            field.setAttribute('aria-hidden', String(!isVisible));
        });
    }

    async upload(event) {
        const input = event.currentTarget;
        const imageKind = input.dataset.imageKind;
        const field = this.findClosestTarget(this.imageFieldTargets, input);

        if (!input.files?.length || !imageKind || !field) {
            return;
        }

        const formData = new FormData(this.element);
        formData.set('_cropper_upload', imageKind);
        input.disabled = true;

        try {
            const response = await fetch(this.element.action, {
                method: this.element.method || 'POST',
                body: formData,
                headers: {
                    'Accept': 'text/html',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok && response.status !== 422) {
                throw new Error(`Cropper upload failed with status ${response.status}.`);
            }

            const template = document.createElement('template');
            template.innerHTML = (await response.text()).trim();
            const replacement = template.content.firstElementChild;

            if (replacement) {
                field.replaceWith(replacement);
            }
        } finally {
            input.disabled = false;
        }
    }

    findClosestTarget(targets, element) {
        return targets
            .filter((target) => target.contains(element))
            .reduce((closest, target) => {
                if (!closest || closest.contains(target)) {
                    return target;
                }

                return closest;
            }, null);
    }

    findClosestSection(element) {
        return this.findClosestTarget(this.sectionTargets, element);
    }

    getContentFields(section) {
        return [...new Set([...this.contentFieldTargets, ...this.imageFieldTargets])]
            .filter((field) => field.dataset.configContentField)
            .filter((field) => this.findClosestSection(field) === section);
    }
}
