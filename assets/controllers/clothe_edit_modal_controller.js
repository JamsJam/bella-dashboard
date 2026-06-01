import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['category', 'collection'];

    connect() {
        this.filterCollections();
    }

    filterCollections() {
        const categoryId = this.categoryTarget.value;
        let selectedOptionIsAvailable = false;
        let firstAvailableOption = null;

        Array.from(this.collectionTarget.options).forEach((option) => {
            const isAvailable = option.dataset.categoryId === categoryId;

            option.hidden = !isAvailable;
            option.disabled = !isAvailable;

            if (isAvailable && firstAvailableOption === null) {
                firstAvailableOption = option;
            }

            if (isAvailable && option.selected) {
                selectedOptionIsAvailable = true;
            }
        });

        if (!selectedOptionIsAvailable && firstAvailableOption !== null) {
            firstAvailableOption.selected = true;
        }
    }
}
