import { Controller } from '@hotwired/stimulus';

const DISMISS_DELAY = 5000;

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['item'];

    static values = {
        reloadUrl: String,
        timeouts: Array,
    };

    connect() {
        // this.timeouts = this.itemTargets.map((item) => (
        //     window.setTimeout(() => item.remove(), DISMISS_DELAY)
        // ));
    }
    
    itemTargetConnected() {
        this.timeoutsValue.push(window.setTimeout(() => {
            this.itemTargets.forEach((item) => item.remove());
        }, DISMISS_DELAY));

    }

    itemTargetDisconnected() {
        this.timeoutsValue.forEach((timeout) => window.clearTimeout(timeout));

    }

    close(event) {
        event.currentTarget.closest('[data-flash-target="item"]')?.remove();
    }

    reload() {
        if (!this.hasReloadUrlValue) {
            return;
        }

        if (this.element.getAttribute('src') === this.reloadUrlValue) {
            this.element.reload();

            // return; 
        }

        this.element.src = this.reloadUrlValue;
    }
}
