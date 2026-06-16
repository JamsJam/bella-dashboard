import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = [
        'categorySelect',
        'newCategoryField',
        'clothesList',
        'clotheTemplate',
    ];

    connect() {
        this.clotheIndex = 0;
        this.toggleNewCategoryField();
    }

    toggleNewCategoryField() {
        if (!this.hasCategorySelectTarget || !this.hasNewCategoryFieldTarget) {
            return;
        }

        const shouldShow = this.categorySelectTarget.value === '__new__';
        this.newCategoryFieldTarget.hidden = !shouldShow;

        const input = this.newCategoryFieldTarget.querySelector('input');
        if (input) {
            input.required = shouldShow;

            if (!shouldShow) {
                input.value = '';
            }
        }
    }

    showClotheFields() {
        if (!this.hasClotheTemplateTarget || !this.hasClothesListTarget) {
            return;
        }

        const html = this.clotheTemplateTarget.innerHTML.replaceAll('__INDEX__', String(this.clotheIndex));
        const fragment = document.createRange().createContextualFragment(html);
        this.clothesListTarget.appendChild(fragment);
        this.clotheIndex += 1;
    }

    confirmClotheModal(event) {
        const modal = event.currentTarget.closest('.collection-form__clothe-modal');
        if (!modal) {
            return;
        }

        const colorSelect = modal.querySelector('[data-collection-form-color-select]');
        const color = colorSelect?.selectedOptions?.[0]?.textContent?.trim() || 'Vetement';
        const summary = document.createElement('div');
        summary.className = 'collection-form__clothe-summary';
        summary.innerHTML = `
            <strong>${this.escapeHtml(color)}</strong>
            <button type="button" data-action="collection-form#removeClotheSummary">Retirer</button>
        `;

        modal.hidden = true;
        modal.classList.remove('modal-backdrop');
        modal.parentElement?.insertBefore(summary, modal);
    }

    removeClotheModal(event) {
        event.currentTarget.closest('.collection-form__clothe-modal')?.remove();
    }

    removeClotheSummary(event) {
        const summary = event.currentTarget.closest('.collection-form__clothe-summary');
        const modal = summary?.nextElementSibling;

        if (modal?.classList.contains('collection-form__clothe-modal')) {
            modal.remove();
        }

        summary?.remove();
    }

    toggleNewColorField(event) {
        const modal = event.currentTarget.closest('.collection-form__clothe-modal') || this.element;
        const colorSelect = event.currentTarget;
        const nameField = modal.querySelector('[data-collection-form-new-color-name]');
        const hexField = modal.querySelector('[data-collection-form-new-color-hex]');

        if (!nameField || !hexField) {
            return;
        }

        const shouldShow = colorSelect.value === '__new__';
        nameField.hidden = !shouldShow;
        hexField.hidden = !shouldShow;

        const nameInput = nameField.querySelector('input');
        if (nameInput) {
            nameInput.required = shouldShow;

            if (!shouldShow) {
                nameInput.value = '';
            }
        }
    }

    renderImageOrder(event) {
        const input = event.currentTarget;
        const modal = input.closest('.collection-form__clothe-modal') || this.element;
        const imageOrder = modal.querySelector('[data-collection-form-image-order]');

        if (!imageOrder) {
            return;
        }

        imageOrder.innerHTML = '';

        Array.from(input.files).forEach((file, index) => {
            const item = document.createElement('div');
            item.className = 'collection-form__image-item';
            item.innerHTML = `
                <span>${this.escapeHtml(file.name)}</span>
                <div>
                    <button type="button" data-index="${index}" data-direction="-1">Monter</button>
                    <button type="button" data-index="${index}" data-direction="1">Descendre</button>
                </div>
            `;

            item.querySelectorAll('button').forEach((button) => {
                button.addEventListener('click', () => {
                    this.moveImage(input, Number(button.dataset.index), Number(button.dataset.direction));
                });
            });

            imageOrder.appendChild(item);
        });
    }

    moveImage(input, index, direction) {
        const files = Array.from(input.files);
        const nextIndex = index + direction;

        if (nextIndex < 0 || nextIndex >= files.length) {
            return;
        }

        [files[index], files[nextIndex]] = [files[nextIndex], files[index]];

        const dataTransfer = new DataTransfer();
        files.forEach((file) => dataTransfer.items.add(file));
        input.files = dataTransfer.files;
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }

    escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }
}
