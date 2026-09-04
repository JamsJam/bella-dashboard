import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['collection'];

    connect() {
        this.element.querySelectorAll('[data-clothe-color-select]').forEach((select) => {
            this.updateColorCreationFields(select);
        });
    }

    add() {
        const index = Number(this.collectionTarget.dataset.index || 0);
        const html = this.collectionTarget.dataset.prototype.replace(/__name__/g, String(index));
        const fieldset = document.createElement('fieldset');
        fieldset.className = 'collection-form__variant';
        fieldset.innerHTML = `
            <legend>Nouvelle variante</legend>
            ${html}
            <button type="button" class="clothe-create__remove" data-action="clothe-form#remove">
                Supprimer cette variante
            </button>
        `;
        this.collectionTarget.append(fieldset);
        this.collectionTarget.dataset.index = String(index + 1);
        const colorSelect = fieldset.querySelector('[data-clothe-color-select]');
        if (colorSelect) {
            this.updateColorCreationFields(colorSelect);
        }
    }

    remove(event) {
        event.currentTarget.closest('.collection-form__variant')?.remove();
    }

    toggleColorCreation(event) {
        this.updateColorCreationFields(event.currentTarget);
    }

    updateColorCreationFields(select) {
        const variant = select.closest('.collection-form__variant');
        if (!variant) {
            return;
        }

        const isCreating = select.value === '';
        variant.querySelectorAll('[data-clothe-new-color-field]').forEach((field) => {
            field.hidden = !isCreating;
        });

        const nameInput = variant.querySelector('[data-clothe-new-color-name]');
        if (nameInput) {
            nameInput.required = isCreating;
        }
    }
}
