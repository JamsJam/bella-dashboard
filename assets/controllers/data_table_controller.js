import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['search', 'body'];

    static values = {
        url: String,
        sort: String,
        direction: String,
    };

    connect() {
        this.debounceTimer = null;
    }

    search() {
        clearTimeout(this.debounceTimer);
        this.debounceTimer = setTimeout(() => {
            this.refresh();
        }, 250);
    }

    sort(event) {
        const nextSort = event.currentTarget.dataset.sort;

        if (this.sortValue === nextSort) {
            this.directionValue = this.directionValue === 'asc' ? 'desc' : 'asc';
        } else {
            this.sortValue = nextSort;
            this.directionValue = 'asc';
        }

        this.refresh();
    }

    async refresh() {
        const url = new URL(this.urlValue, window.location.origin);
        url.searchParams.set('search', this.searchTarget.value || '');
        url.searchParams.set('sort', this.sortValue);
        url.searchParams.set('direction', this.directionValue);

        const response = await fetch(url.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            throw new Error(`Unable to refresh table: ${response.status}`);
        }

        const data = await response.json();
        this.bodyTarget.innerHTML = data.html || '';
        this.updateSortIndicators();
    }

    updateSortIndicators() {
        this.element.querySelectorAll('[data-sort]').forEach((button) => {
            const indicator = button.querySelector('.data-table__sort-indicator');

            if (!indicator) {
                return;
            }

            indicator.textContent = button.dataset.sort === this.sortValue
                ? (this.directionValue === 'asc' ? '↑' : '↓')
                : '';
        });
    }
}
