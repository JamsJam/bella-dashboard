import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['search', 'filter', 'body', 'pagination'];

    static values = {
        url: String,
        sort: String,
        direction: String,
        page: { type: Number, default: 1 },
    };

    connect() {
        this.debounceTimer = null;
    }

    search() {
        clearTimeout(this.debounceTimer);
        this.debounceTimer = setTimeout(() => {
            this.refresh(true);
        }, 250);
    }

    filter() {
        this.refresh(true);
    }

    sort(event) {
        const nextSort = event.currentTarget.dataset.sort;

        if (this.sortValue === nextSort) {
            this.directionValue = this.directionValue === 'asc' ? 'desc' : 'asc';
        } else {
            this.sortValue = nextSort;
            this.directionValue = 'asc';
        }

        this.refresh(true);
    }

    paginate(event) {
        this.pageValue = Number(event.currentTarget.dataset.page || 1);
        this.refresh();
    }

    async refresh(resetPage = false) {
        if (resetPage) {
            this.pageValue = 1;
        }

        const url = new URL(this.urlValue, window.location.origin);
        url.searchParams.set('search', this.searchTarget.value || '');
        url.searchParams.set('sort', this.sortValue);
        url.searchParams.set('direction', this.directionValue);
        url.searchParams.set('page', String(this.pageValue));
        this.filterTargets.forEach((filter) => {
            if (filter.value) {
                url.searchParams.set(filter.name, filter.value);
            } else {
                url.searchParams.delete(filter.name);
            }
        });

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
        if (this.hasPaginationTarget) {
            this.paginationTarget.innerHTML = data.pagination || '';
        }
        this.pageValue = Number(data.page || 1);
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
                : '↕';
        });
    }
}
