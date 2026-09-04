import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['input', 'label'];

    static values = {
        id: String,
        checked: Boolean,
        eventName: { type: String, default: 'toggle:change' },
        payload: { type: Object, default: {} },
    };

    connect() {
        this.stableChecked = this.hasInputTarget ? this.inputTarget.checked : this.checkedValue;
        this.previousChecked = this.stableChecked;
    }

    onChange() {
        if (!this.hasInputTarget || this.inputTarget.disabled) {
            return;
        }

        this.previousChecked = this.stableChecked;
        this.setLoading(true);

        this.dispatchCustomEvent(this.eventNameValue, {
            id: this.idValue,
            checked: this.inputTarget.checked,
            previousChecked: this.previousChecked,
            payload: this.payloadValue,
        });
    }

    success(event) {
        console.log('EVENT received', event.type, event.detail);

        if (!this.matchesEvent(event)) {
            return;
        }

        if (this.hasInputTarget) {
            const checked = typeof event.detail?.checked === 'boolean'
                ? event.detail.checked
                : this.inputTarget.checked;

            this.inputTarget.checked = checked;
            this.stableChecked = checked;
        }

        this.updateLabel(event.detail?.label);
        this.setLoading(false);
    }

    error(event) {
        console.log('EVENT received', event.type, event.detail);

        if (!this.matchesEvent(event)) {
            return;
        }

        if (this.hasInputTarget) {
            this.inputTarget.checked = this.previousChecked;
            this.stableChecked = this.previousChecked;
        }
        console.log(event)
        this.updateLabel(event.detail?.label);
        this.setLoading(false);
    }

    done(event) {
        console.log('EVENT received', event.type, event.detail);

        if (!this.matchesEvent(event)) {
            return;
        }

        this.setLoading(false);
    }

    setLoading(isLoading) {
        this.element.classList.toggle('is-loading', isLoading);
        this.element.setAttribute('aria-busy', String(isLoading));

        if (this.hasInputTarget) {
            this.inputTarget.disabled = isLoading;
        }
    }

    dispatchCustomEvent(name, detail) {
        console.log('EVENT DISPATCH', name, detail);

        this.element.dispatchEvent(new CustomEvent(name, {
            bubbles: true,
            detail,
        }));
    }

    updateLabel(label) {
        if (label && this.hasLabelTarget) {
            this.labelTarget.textContent = label;
        }
    }

    matchesEvent(event) {
        if (event.detail?.global === true) {
            return true;
        }

        return Boolean(event.detail?.id) && event.detail.id === this.idValue;
    }
}
