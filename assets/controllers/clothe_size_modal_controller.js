import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['size', 'confirmContainer'];

    confirmRemovedSizes(event) {
        const removedSizes = this.sizeTargets
            .filter((input) => input.dataset.existing === '1' && !input.checked)
            .map((input) => input.value);

        this.confirmContainerTarget.innerHTML = '';

        if (removedSizes.length === 0) {
            return;
        }

        const message = `Les tailles ${removedSizes.join(', ')} vont etre supprimees. Confirmer ?`;

        if (!window.confirm(message)) {
            event.preventDefault();
            return;
        }

        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'confirm_delete';
        input.value = '1';
        this.confirmContainerTarget.appendChild(input);
    }
}
