import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['item', 'list'];

    moveUp(event) {
        const item = this.findItem(event.currentTarget);
        const previous = item?.previousElementSibling;

        if (!item || !previous?.matches('.clothe-image-order__item')) {
            return;
        }

        previous.before(item);
    }

    moveDown(event) {
        const item = this.findItem(event.currentTarget);
        const next = item?.nextElementSibling;

        if (!item || !next?.matches('.clothe-image-order__item')) {
            return;
        }

        next.after(item);
    }

    remove(event) {
        this.findItem(event.currentTarget)?.remove();
    }

    findItem(element) {
        return this.itemTargets.find((item) => item.contains(element));
    }
}
