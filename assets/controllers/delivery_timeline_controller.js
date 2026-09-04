import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['days', 'loading', 'viewport'];

    static values = {
        url: String,
        startDate: String,
        endDate: String,
    };

    connect() {
        this.loadingBefore = false;
        this.loadingAfter = false;
        this.scrollTimer = null;

        requestAnimationFrame(() => {
            const today = this.daysTarget.querySelector('[data-today]');
            if (today) {
                this.viewportTarget.scrollTop = today.offsetTop
                    - (this.viewportTarget.clientHeight - today.offsetHeight) / 2;
            }
        });
    }

    scroll() {
        clearTimeout(this.scrollTimer);
        this.scrollTimer = setTimeout(() => this.loadAtEdges(), 80);
    }

    loadAtEdges() {
        const viewport = this.viewportTarget;
        const maximumScroll = viewport.scrollHeight - viewport.clientHeight;

        if (viewport.scrollTop <= 40) {
            this.load('before');
        }

        if (maximumScroll - viewport.scrollTop <= 40) {
            this.load('after');
        }
    }

    async load(direction) {
        const loadingProperty = direction === 'before' ? 'loadingBefore' : 'loadingAfter';
        if (this[loadingProperty]) {
            return;
        }

        this[loadingProperty] = true;
        this.loadingTarget.hidden = false;
        const previousHeight = this.viewportTarget.scrollHeight;
        const url = new URL(this.urlValue, window.location.origin);
        url.searchParams.set('direction', direction);
        url.searchParams.set('boundary', direction === 'before' ? this.startDateValue : this.endDateValue);

        try {
            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!response.ok) {
                throw new Error(`Unable to load delivery days: ${response.status}`);
            }

            const data = await response.json();
            this.daysTarget.insertAdjacentHTML(
                direction === 'before' ? 'afterbegin' : 'beforeend',
                data.html || '',
            );

            if (direction === 'before') {
                this.startDateValue = data.startDate;
                this.viewportTarget.scrollTop += this.viewportTarget.scrollHeight - previousHeight;
            } else {
                this.endDateValue = data.endDate;
            }
        } finally {
            this[loadingProperty] = false;
            this.loadingTarget.hidden = !(this.loadingBefore || this.loadingAfter);
        }
    }
}
