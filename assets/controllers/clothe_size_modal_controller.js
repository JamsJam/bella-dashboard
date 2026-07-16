import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['size', 'stocksContainer', 'confirmContainer'];

    connect() {
        this.stockValues = new Map();
        this.rememberStockValues();
    }

    toggleStock(event) {
        if (!this.sizeTargets.includes(event.target)) {
            return;
        }

        this.rememberStockValues();
        this.renderStockInputs();
    }

    rememberStockValues() {
        this.stocksContainerTarget.querySelectorAll('.size-stock').forEach((row) => {
            this.stockValues.set(row.dataset.size, row.querySelector('input').value);
        });

        this.sizeTargets.forEach((input) => {
            if (!this.stockValues.has(input.value)) {
                this.stockValues.set(input.value, input.dataset.stock || '0');
            }
        });
    }

    renderStockInputs() {
        this.stocksContainerTarget.querySelectorAll('.size-stock').forEach((row) => row.remove());

        this.sizeTargets.filter((input) => input.checked).forEach((sizeInput) => {
            const row = document.createElement('label');
            const label = document.createElement('span');
            const stockInput = document.createElement('input');

            row.className = 'size-stock';
            row.dataset.size = sizeInput.value;
            label.textContent = sizeInput.value;

            stockInput.type = 'number';
            stockInput.name = `stocks[${sizeInput.value}]`;
            stockInput.min = '0';
            stockInput.step = '1';
            stockInput.value = this.stockValues.get(sizeInput.value) || '0';
            stockInput.setAttribute('aria-label', `Stock de la taille ${sizeInput.value}`);

            row.append(label, stockInput);
            this.stocksContainerTarget.append(row);
        });
    }

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
