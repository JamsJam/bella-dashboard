import { Controller } from '@hotwired/stimulus';
import { refreshFlashMessages } from '../lib/refresh_flash_messages.js';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['searchInput', 'list', 'filters', 'selectionActions', 'selectedCount'];
    static values = {
        searchUrl: String,
        updateFilterUrl: String,
        filterPrototype: String,
        optionsPrototype: String,
        cardsPrototype: String,
        detailUrlTemplate: String,
        deleteUrlTemplate: String,
        renameUrlTemplate: String,
        deleteCsrfToken: String,
        renameCsrfToken: String,
        resourceKey: String,
        resourceParamName: String,
        noResultsLabel: String,
        detailActionLabel: String,
        renameConfirmMessage: String,
        deleteConfirmMessage: String,
        extraSearchParams: Object,
    };

    connect() {
        this.debounceTimer = null;
        this.enhanceColorFilters();
        this.performSearch();
    }

    initialize() {
        
    }

    onSearchInput() {
        clearTimeout(this.debounceTimer);
        this.debounceTimer = setTimeout(() => {
            this.performSearch();
        }, 300);
    }

    //? =================================
    //! ======== Filter Handling ========
    //? =================================

    async onFilterChange(event) {
        this.updateColorSwatch(event.target);
        if (event.target.name === this.resourceParamNameValue && this.updateFilterUrlValue) {
            await this.renderFilters(event.target.value || this.resourceKeyValue);
        }

        await this.performSearch();
    }

    async renderFilters(part) {
        const filters = await this.fetchFilters(part);
        
        this.filtersTarget.innerHTML = filters.map(filter => {
            const optionsHtml = filter.options.map(option => {
                return this.optionsPrototypeValue
                    .replaceAll('__VALUE__', this.escapeHtml(option.value))
                    .replaceAll('__LABEL__', this.escapeHtml(option.label))
                    .replaceAll('__COLOR__', this.escapeHtml(option.color || ''))
                    .replaceAll('__SELECTED__', String(option.value) === String(filter.selected) ? ' selected' : '')
                }).join('');

            return this.filterPrototypeValue
                .replaceAll('__ID__', this.escapeHtml(filter.id))
                .replaceAll('__LABEL__', this.escapeHtml(filter.label))
                .replaceAll('__IS_COLOR__', filter.isColor ? 'true' : 'false')
                .replaceAll('__OPTIONS__', optionsHtml)
        }).join('');

        this.enhanceColorFilters();

    }

    enhanceColorFilters() {
        this.filtersTarget.querySelectorAll('[data-color-filter="true"] select').forEach((select) => {
            const control = select.closest('[data-color-filter="true"]');
            if (!control || control.dataset.colorEnhanced === 'true') return;

            control.dataset.colorEnhanced = 'true';
            select.hidden = true;

            const dropdown = document.createElement('details');
            dropdown.className = 'filter__color-dropdown';
            const summary = document.createElement('summary');
            summary.className = 'filter__color-summary';
            const selectedDot = document.createElement('span');
            selectedDot.className = 'filter__color-option-dot';
            selectedDot.setAttribute('aria-hidden', 'true');
            const selectedLabel = document.createElement('span');
            selectedLabel.className = 'filter__color-option-label';
            summary.append(selectedDot, selectedLabel);

            const menu = document.createElement('div');
            menu.className = 'filter__color-menu';
            Array.from(select.options).forEach((option) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'filter__color-option';
                button.dataset.value = option.value;
                const dot = document.createElement('span');
                dot.className = 'filter__color-option-dot';
                this.applyColor(dot, option.dataset.color || '');
                const label = document.createElement('span');
                label.textContent = option.textContent.trim();
                button.append(dot, label);
                button.addEventListener('click', () => {
                    select.value = option.value;
                    dropdown.open = false;
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                });
                menu.append(button);
            });

            dropdown.append(summary, menu);
            control.append(dropdown);
            this.updateColorSwatch(select);
        });
    }

    updateColorSwatch(select) {
        const control = select?.closest('[data-color-filter="true"]');
        const dot = control?.querySelector('.filter__color-summary .filter__color-option-dot');
        const label = control?.querySelector('.filter__color-option-label');
        if (!dot || !label) return;

        const option = select.selectedOptions[0];
        this.applyColor(dot, option?.dataset.color || '');
        label.textContent = option?.textContent.trim() || '';
    }

    applyColor(dot, color) {
        const isValid = /^#[0-9a-f]{6}$/i.test(color);
        dot.hidden = !isValid;
        dot.style.backgroundColor = isValid ? color : '';
    }

    async fetchFilters(part) {
        const response = await fetch(`${this.updateFilterUrlValue}?part=${encodeURIComponent(part)}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        const data = await response.json();
        return Array.from(data.filters);
    }


    //? =================================
    //! ======== Search Handling ========
    //? =================================

    async performSearch() {
        const params = new URLSearchParams();

        params.set('search', this.searchInputTarget.value || '');

        Object.entries(this.extraSearchParamsValue || {}).forEach(([label, value]) => {
            if (label && value !== null && value !== '') {
                params.set(label, value);
            }
        });

        this.filtersTarget.querySelectorAll('[data-filter-label]').forEach(element => {
            const label = element.getAttribute('data-filter-label');
            const value = element.value;

            if (label && value) {
                params.set(label, value);
            }
        });

        try {
            const response = await fetch(`${this.searchUrlValue}?${params}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            this.renderCards(data.items || []);
        } catch (error) {
            console.error('Erreur lors de la recherche:', error);
        }
    }
    
    
    
    //? =================================
    //! ======== Card Rendering ========
    //? =================================
    renderCards(items) {
        const cards = Array.isArray(items) ? items : Object.values(items).flat();


        if (cards.length === 0) {
            this.listTarget.innerHTML = `<li><p class="no-results">${this.escapeHtml(this.noResultsLabelValue || 'Aucun resultat trouve')}</p></li>`;

            this.listTarget.classList.add('product-grid-catalogue__notFounded')
            this.updateSelectionActions();
            
            return;
        }
        this.listTarget.classList.remove('product-grid-catalogue__notFounded')

        this.listTarget.innerHTML = cards.map((card) => {
            const name = card.name || `Element ${card.id}`;
            const detailUrl = this.buildDetailUrl(this.currentResourceKey(), card);


            return`<li>
                ${this.cardsPrototypeValue
                    .replaceAll('__ID__', this.escapeHtml(card.id ))
                    .replaceAll('__TITLE__', this.escapeHtml(name))
                    .replaceAll('__IMAGE_STACK__', this.renderImageStack(card, name))
                    .replaceAll('__STATUS_BADGE__', this.renderStatusBadge(card))
                    .replaceAll('__IMAGE_ALT__', this.escapeHtml(name))
                    .replaceAll('__DETAIL_URL__', this.escapeHtml(detailUrl))
                    .replaceAll('__DETAIL_LABEL__', this.escapeHtml(this.detailActionLabelValue || 'Voir les details'))
                }</li>`}).join('');

        this.updateSelectionActions();
    }

    renderImageStack(card, name) {
        const images = this.resolveCardImages(card);

        if (images.length === 0) {
            return '<span class="product-grid-card__placeholder">Aucune image</span>';
        }

        return images.map((image) => (
            `<img src="${this.escapeHtml(image)}" alt="${this.escapeHtml(name)}" class="product-grid-card__image">`
        )).join('');
    }

    resolveCardImages(card) {
        if (Array.isArray(card.imageUrls) && card.imageUrls.length > 0) {
            return card.imageUrls.filter(Boolean);
        }

        if (card.images && typeof card.images === 'object') {
            return Object.values(card.images).filter(Boolean);
        }

        if (card.imageUrl) {
            return [card.imageUrl];
        }

        if (card.image) {
            return [card.image];
        }

        return [];
    }

    renderStatusBadge(card) {
        const statusLabels = {
            draft: 'Brouillon',
            publishable: 'Publiable',
            scheduled: 'Planifié',
            online: 'En ligne',
            offline: 'Hors ligne',
            archived: 'Archivé',
        };

        if (card.publicationStatus && statusLabels[card.publicationStatus]) {
            const status = this.escapeHtml(card.publicationStatus);
            return `<span class="product-grid-card__status-list"><span class="badge badge--${status}">${statusLabels[card.publicationStatus]}</span></span>`;
        }

        if (card.isOnline === true || card.isOnline === false) {
            const status = card.isOnline ? 'online' : 'offline';
            const label = card.isOnline ? 'En ligne' : 'Hors ligne';
            return `<span class="product-grid-card__status-list"><span class="badge badge--${status}">${label}</span></span>`;
        }

        return '';
    }

    onSelectProduct(event) {
        const interactiveElement = event.target.closest('a, button');

        if (interactiveElement) {
            return;
        }

        const card = event.currentTarget.closest('.product-grid-card');
        this.toggleCardSelection(card);
    }

    onSelectProductWithKeyboard(event) {
        if (event.key !== 'Enter' && event.key !== ' ') {
            return;
        }

        event.preventDefault();
        this.toggleCardSelection(event.currentTarget.closest('.product-grid-card'));
    }

    toggleCardSelection(card) {
        if (!card) {
            return;
        }

        const isSelected = !card.classList.contains('is-selected');

        this.setCardSelection(card, isSelected);
        this.updateSelectionActions();
    }

    setCardSelection(card, isSelected) {
        const checkbox = card.querySelector('.product-grid-card__checkbox');

        card.classList.toggle('is-selected', isSelected);
        card.setAttribute('aria-pressed', String(isSelected));

        if (checkbox) {
            checkbox.checked = isSelected;
        }
    }

    selectAllProducts() {
        this.listTarget.querySelectorAll('.product-grid-card').forEach((card) => {
            this.setCardSelection(card, true);
        });

        this.updateSelectionActions();
    }

    clearSelection() {
        this.listTarget.querySelectorAll('.product-grid-card.is-selected').forEach((card) => {
            this.setCardSelection(card, false);
        });

        this.updateSelectionActions();
    }

    selectedCards() {
        return Array.from(this.listTarget.querySelectorAll('.product-grid-card.is-selected'));
    }

    selectedProducts() {
        return this.selectedCards()
            .map((card) => ({
                id: card.dataset.productId,
                resource: this.currentResourceKey(),
                card,
            }))
            .filter((product) => product.id !== undefined && product.id !== '');
    }

    selectedProductIds() {
        return this.selectedProducts().map((product) => product.id);
    }

    updateSelectionActions() {
        if (!this.hasSelectionActionsTarget) {
            return;
        }

        const selectedCount = this.selectedCards().length;

        this.selectionActionsTarget.hidden = selectedCount === 0;

        if (this.hasSelectedCountTarget) {
            this.selectedCountTarget.textContent = String(selectedCount);
        }
    }

    async onRenameSelection() {
        const selectedProducts = this.selectedProducts();

        if (selectedProducts.length === 0) {
            return;
        }

        if (!window.confirm(this.confirmMessage(this.renameConfirmMessageValue, selectedProducts.length))) {
            return;
        }

        const results = await Promise.allSettled(selectedProducts.map((product) => this.queueProductForRename(product)));

        results
            .filter((result) => result.status === 'rejected')
            .forEach((result) => console.error('Erreur lors de la remise en renommage:', result.reason));

        this.updateSelectionActions();
    }

    async onDeleteSelection() {
        const selectedProducts = this.selectedProducts();

        if (selectedProducts.length === 0) {
            return;
        }

        if (!window.confirm(this.confirmMessage(this.deleteConfirmMessageValue, selectedProducts.length))) {
            return;
        }

        const results = await Promise.allSettled(selectedProducts.map((product) => this.deleteProduct(product)));

        results
            .filter((result) => result.status === 'rejected')
            .forEach((result) => console.error('Erreur lors de la suppression:', result.reason));

        this.updateSelectionActions();
    }

    async onBestsellerSelection(event) {
        await this.updateHighlightSelection(event, 'bestseller');
    }

    async onFeaturedSelection(event) {
        await this.updateHighlightSelection(event, 'featured');
    }

    async updateHighlightSelection(event, type) {
        event.preventDefault();

        const selectedIds = this.selectedProductIds();
        if (selectedIds.length === 0) {
            return;
        }

        const trigger = event.currentTarget;
        const url = type === 'bestseller' ? trigger.dataset.bestsellerUrl : trigger.dataset.featuredUrl;
        const csrfToken = type === 'bestseller'
            ? trigger.dataset.bestsellerCsrfToken || ''
            : trigger.dataset.featuredCsrfToken || '';

        if (!url) {
            console.error(`${type} update URL is missing.`);
            return;
        }

        trigger.setAttribute('aria-busy', 'true');

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/vnd.turbo-stream.html, application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    mode: 'add',
                    ids: selectedIds,
                }),
            });

            const contentType = response.headers.get('Content-Type') || '';
            const body = await response.text();

            if (contentType.includes('text/vnd.turbo-stream.html')) {
                this.renderTurboStream(body);
                return;
            }

            const data = body ? JSON.parse(body) : {};
            if (!response.ok || data.success !== true) {
                throw new Error(data.message || data.error || `Unable to update ${type}: ${response.status}`);
            }

            this.clearSelection();
            await this.performSearch();
        } catch (error) {
            console.error(`Erreur lors de la mise a jour ${type}:`, error);
        } finally {
            trigger.removeAttribute('aria-busy');
            refreshFlashMessages();
        }
    }

    async deleteProduct(product) {
        const response = await fetch(this.buildDeleteUrl(product.resource, product.id), {
            method: 'DELETE',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': this.deleteCsrfTokenValue,
            },
        });

        if (response.status === 404) {
            console.warn(`Product grid item not found: ${product.resource}/${product.id}`);
            return;
        }

        if (!response.ok) {
            throw new Error(`Unable to delete product grid item ${product.resource}/${product.id}: ${response.status}`);
        }

        product.card.closest('li')?.remove();
    }

    async queueProductForRename(product) {
        const response = await fetch(this.buildRenameUrl(product.resource, product.id), {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': this.renameCsrfTokenValue,
            },
        });

        if (response.status === 404) {
            console.warn(`Product grid item not found: ${product.resource}/${product.id}`);
            return;
        }

        if (!response.ok) {
            const error = await response.json().catch(() => ({}));

            throw new Error(error.error || `Unable to queue product grid item for rename ${product.resource}/${product.id}: ${response.status}`);
        }

        product.card.closest('li')?.remove();
    }

    currentResourceKey() {
        return this.filtersTarget.querySelector(`[data-filter-label="${this.resourceParamNameValue}"]`)?.value || this.resourceKeyValue;
    }

    buildDetailUrl(resource, card) {
        return this.detailUrlTemplateValue
            .replace('__PART__', encodeURIComponent(resource || this.resourceKeyValue))
            .replace('__RESOURCE__', encodeURIComponent(resource || this.resourceKeyValue))
            .replace('__SLUG__', encodeURIComponent(card.slug || card.id))
            .replace('__ID__', encodeURIComponent(card.id));
    }

    buildDeleteUrl(resource, id) {
        return this.deleteUrlTemplateValue
            .replace('__PART__', encodeURIComponent(resource || this.resourceKeyValue))
            .replace('__RESOURCE__', encodeURIComponent(resource || this.resourceKeyValue))
            .replace('__ID__', encodeURIComponent(id));
    }

    buildRenameUrl(resource, id) {
        return this.renameUrlTemplateValue
            .replace('__PART__', encodeURIComponent(resource || this.resourceKeyValue))
            .replace('__RESOURCE__', encodeURIComponent(resource || this.resourceKeyValue))
            .replace('__ID__', encodeURIComponent(id));
    }

    confirmMessage(message, count) {
        return (message || 'Confirmer cette action pour __COUNT__ element(s) ?').replace('__COUNT__', String(count));
    }

    renderTurboStream(html) {
        if (window.Turbo?.renderStreamMessage) {
            window.Turbo.renderStreamMessage(html);
            return;
        }

        const documentFragment = document.createRange().createContextualFragment(html);
        const template = documentFragment.querySelector('template');
        const target = document.getElementById('modal-root');

        if (template && target) {
            target.replaceChildren(template.content.cloneNode(true));
        }
    }


    //? =================================
    //! ======== Utility Methods ========
    //? =================================
    escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

}
