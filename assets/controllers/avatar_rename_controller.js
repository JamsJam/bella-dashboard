import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static newFilterValue = '__new__';

    static targets = [
        'pendingList',
        'validatedList',
        'panel',
        'preview',
        'currentName',
        'category',
        'filtersContainer',
        'filter',
        'newFilter',
        'generatedName',
        'renamesInput',
        'finishButton',
        'card',
    ];

    static values = {
        filterUrl: String,
        checkNameUrl: String,
        deleteCsrfToken: String,
    };

    connect() {
        this.currentAvatar = null;
        this.renames = [];
        this.closePanel();
        this.renderFilters(this.categoryTarget.value || 'body');
    }

    selectAvatar(event) {
        const button = event.currentTarget;
        const card = button.closest('.avatar-rename__card');

        this.currentAvatar = {
            id: Number(button.dataset.avatarId),
            originalName: button.dataset.avatarName,
            preview: button.dataset.avatarPreview,
            deleteUrl: button.dataset.avatarDeleteUrl,
        };

        this.cardTargets.forEach((item) => item.classList.remove('is-selected'));
        card?.classList.add('is-selected');

        this.previewTarget.src = this.currentAvatar.preview || '';
        this.currentNameTarget.textContent = this.currentAvatar.originalName;
        this.openPanel();
        this.generateName();
    }

    openPanel() {
        this.panelTarget.classList.add('is-open');
        this.panelTarget.setAttribute('aria-hidden', 'false');
    }

    closePanel() {
        this.currentAvatar = null;
        this.cardTargets.forEach((item) => item.classList.remove('is-selected'));
        this.panelTarget.classList.remove('is-open');
        this.panelTarget.setAttribute('aria-hidden', 'true');
        this.previewTarget.removeAttribute('src');
        this.currentNameTarget.textContent = '';
        this.generatedNameTarget.textContent = '';
    }

    async onCategoryChange() {
        await this.renderFilters(this.categoryTarget.value || 'body');
        this.generateName();
    }

    onFilterChange(event) {
        this.toggleNewFilterInput(event.currentTarget);
        this.generateName();
    }

    generateName() {
        if (!this.currentAvatar) {
            return;
        }

        this.generatedNameTarget.textContent = this.buildNewName();
    }

    async validateCurrent() {
        if (!this.currentAvatar) {
            return;
        }

        const payload = {
            avatarTempId: this.currentAvatar.id,
            newName: this.buildNewName(),
            category: this.normalizeValue(this.categoryTarget.value || 'body'),
            filters: this.collectFilters(),
            replaceExisting: false,
        };

        let availability = { available: true };

        try {
            availability = await this.checkNameAvailability(payload);
        } catch (error) {
            console.error('Unable to check avatar name availability', error);

            return;
        }

        if (!availability.available) {
            const shouldReplace = await this.confirmReplaceExisting(availability, payload.newName);

            if (!shouldReplace) {
                return;
            }

            payload.replaceExisting = true;
        }

        this.renames = this.renames.filter((rename) => rename.avatarTempId !== payload.avatarTempId);
        this.renames.push(payload);
        this.renamesInputTarget.value = JSON.stringify(this.renames);

        const card = this.cardTargets.find((item) => Number(item.dataset.avatarId) === payload.avatarTempId);
        if (card) {
            card.classList.remove('is-selected');
            card.classList.add('is-validated');
            card.dataset.avatarStatus = 'validated';
            this.validatedListTarget.appendChild(card);
        }

        this.finishButtonTarget.hidden = this.renames.length === 0;
    }

    async checkNameAvailability(payload) {
        if (!this.hasCheckNameUrlValue || this.checkNameUrlValue === '') {
            return { available: true };
        }

        const url = new URL(this.checkNameUrlValue, window.location.origin);
        url.searchParams.set('name', payload.newName);
        url.searchParams.set('category', payload.category);
        url.searchParams.set('filters', JSON.stringify(payload.filters));

        const response = await fetch(url.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
        });
        const data = await response.json().catch(() => ({}));

        if (!response.ok || data.error) {
            throw new Error(data.error || `Name check failed with status ${response.status}`);
        }

        return data;
    }

    confirmReplaceExisting(availability, newName) {
        return new Promise((resolve) => {
            const overlay = document.createElement('div');
            overlay.className = 'avatar-rename__confirm-backdrop';
            overlay.innerHTML = `
                <dialog class="avatar-rename__confirm" open aria-modal="true">
                    <header>
                        <h2>Nom deja utilise</h2>
                    </header>
                    <div class="avatar-rename__confirm-body">
                        <p>${this.escapeHtml(availability.message || `Un element existe deja avec le nom ${newName}.`)}</p>
                        ${availability.previewUrl ? `
                            <img src="${this.escapeAttribute(availability.previewUrl)}" alt="Apercu de l image existante">
                        ` : ''}
                    </div>
                    <footer class="avatar-rename__confirm-actions">
                        <button type="button" data-action="cancel">Annuler</button>
                        <button type="button" data-action="replace">Remplacer</button>
                    </footer>
                </dialog>
            `;

            const close = (value) => {
                overlay.remove();
                resolve(value);
            };

            overlay.querySelector('[data-action="cancel"]')?.addEventListener('click', () => close(false));
            overlay.querySelector('[data-action="replace"]')?.addEventListener('click', () => close(true));
            overlay.addEventListener('click', (event) => {
                if (event.target === overlay) {
                    close(false);
                }
            });

            document.body.appendChild(overlay);
        });
    }

    async deleteAvatar(event) {
        const button = event.currentTarget;
        const avatarTempId = Number(button.dataset.avatarId);
        const deleteUrl = button.dataset.avatarDeleteUrl;
        const card = button.closest('.avatar-rename__card');

        if (!avatarTempId || !deleteUrl) {
            return;
        }

        const avatarName = card?.querySelector('.avatar-rename__card-button span')?.textContent?.trim() || 'cette image';
        if (!window.confirm(`Supprimer definitivement ${avatarName} ?`)) {
            return;
        }

        button.disabled = true;

        try {
            const response = await fetch(deleteUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.deleteCsrfTokenValue,
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
            });
            const data = await response.json().catch(() => ({}));

            if (!response.ok || data.error) {
                throw new Error(data.error || `Delete failed with status ${response.status}`);
            }

            this.renames = this.renames.filter((rename) => rename.avatarTempId !== avatarTempId);
            this.renamesInputTarget.value = JSON.stringify(this.renames);

            if (this.currentAvatar?.id === avatarTempId) {
                this.closePanel();
            }

            card?.remove();
            this.finishButtonTarget.hidden = this.renames.length === 0;
            this.renderEmptyStateIfNeeded();
        } catch (error) {
            console.error('Unable to delete avatar temporary image', error);
            button.disabled = false;
        }
    }

    cancelRename(event) {
        const button = event.currentTarget;
        const avatarTempId = Number(button.dataset.avatarId);
        const card = button.closest('.avatar-rename__card');

        this.renames = this.renames.filter((rename) => rename.avatarTempId !== avatarTempId);
        this.renamesInputTarget.value = JSON.stringify(this.renames);

        if (card) {
            card.classList.remove('is-selected', 'is-validated');
            card.dataset.avatarStatus = 'uploaded';
            this.pendingListTarget.appendChild(card);
        }

        if (this.currentAvatar?.id === avatarTempId) {
            this.closePanel();
        }

        this.finishButtonTarget.hidden = this.renames.length === 0;
    }

    collectFilters() {
        const filters = {};

        this.filterTargets.forEach((select) => {
            const filterName = select.dataset.filterName;
            const customInput = this.newFilterTargets.find((input) => input.dataset.filterName === filterName);
            const customValue = customInput?.value.trim();

            filters[filterName] = select.value === this.constructor.newFilterValue ? customValue : select.value;
        });

        return filters;
    }

    buildNewName() {
        const filters = this.collectFilterLabels();
        const parts = [
            this.categoryTarget.value,
            ...Object.values(filters),
        ];

        return `${parts.map((part) => this.normalizeValue(part)).join('__')}.png`;
    }

    collectFilterLabels() {
        const filters = {};

        this.filterTargets.forEach((select) => {
            const filterName = select.dataset.filterName;
            const customInput = this.newFilterTargets.find((input) => input.dataset.filterName === filterName);
            const customValue = customInput?.value.trim();
            const selectedLabel = select.selectedOptions[0]?.textContent || select.value;

            filters[filterName] = select.value === this.constructor.newFilterValue ? customValue : selectedLabel;
        });

        return filters;
    }

    async renderFilters(part) {
        const filters = (await this.fetchFilters(part)).filter((filter) => !['partie', 'collection'].includes(filter.id));

        this.filtersContainerTarget.innerHTML = filters.map((filter) => `
            <label class="avatar-rename__field">
                <span>${this.escapeHtml(filter.label)}</span>
                <select
                    data-avatar-rename-target="filter"
                    data-filter-name="${this.escapeAttribute(filter.id)}"
                    data-action="change->avatar-rename#onFilterChange"
                >
                    ${filter.options.filter((option) => this.isUsableFilterOption(option)).map((option) => `
                        <option value="${this.escapeAttribute(option.value)}">${this.escapeHtml(option.label)}</option>
                    `).join('')}
                    ${filter.allowCreate ? `
                        <option value="${this.constructor.newFilterValue}">Nouvelle ${this.escapeHtml(filter.label.toLowerCase())}</option>
                    ` : ''}
                </select>
                ${filter.allowCreate ? `
                    <input
                        hidden
                        type="text"
                        placeholder="Nom de la nouvelle ${this.escapeAttribute(filter.label.toLowerCase())}"
                        data-avatar-rename-target="newFilter"
                        data-filter-name="${this.escapeAttribute(filter.id)}"
                        data-action="input->avatar-rename#generateName"
                    >
                ` : ''}
            </label>
        `).join('');
    }

    renderEmptyStateIfNeeded() {
        if (this.pendingListTarget.children.length > 0) {
            return;
        }

        const empty = document.createElement('li');
        empty.className = 'avatar-rename__empty';
        empty.textContent = 'Aucune image en attente.';
        this.pendingListTarget.appendChild(empty);
    }

    isUsableFilterOption(option) {
        const value = String(option.value ?? '').trim();
        const label = String(option.label ?? '').trim().toLowerCase();

        return value !== '' && value !== '0' && label !== 'tous';
    }

    async fetchFilters(part) {
        const url = new URL(this.filterUrlValue, window.location.origin);
        url.searchParams.set('part', part || 'body');

        const response = await fetch(url.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            throw new Error(`Unable to fetch avatar filters: ${response.status}`);
        }

        const data = await response.json();

        return Array.from(data.filters || []);
    }

    toggleNewFilterInput(select) {
        const customInput = this.newFilterTargets.find((input) => input.dataset.filterName === select.dataset.filterName);

        if (!customInput) {
            return;
        }

        const isNewFilter = select.value === this.constructor.newFilterValue;

        customInput.hidden = !isNewFilter;
        customInput.required = isNewFilter;

        if (!isNewFilter) {
            customInput.value = '';
        }
    }

    normalizeValue(value) {
        return String(value || '-none-')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .trim()
            .toLowerCase()
            .replace(/\s+/g, '--')
            .replace(/[^a-z0-9_-]/g, '')
            .replace(/_+/g, '_')
            .replace(/^_+|_+$/g, '') || '-none-';
    }

    escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    escapeAttribute(value) {
        return this.escapeHtml(value);
    }
}
