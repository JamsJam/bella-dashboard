import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['collection'];

    add() {
        const index = Number(this.collectionTarget.dataset.index || 0);
        const html = this.collectionTarget.dataset.prototype.replace(/__name__/g, String(index));
        const fieldset = document.createElement('fieldset');
        fieldset.className = 'collection-form__variant';
        fieldset.innerHTML = `${html}<button type="button" data-action="clothe-form#remove">Supprimer</button>`;
        this.collectionTarget.append(fieldset);
        this.collectionTarget.dataset.index = String(index + 1);
    }

    remove(event) {
        event.currentTarget.closest('.collection-form__variant')?.remove();
    }
}
