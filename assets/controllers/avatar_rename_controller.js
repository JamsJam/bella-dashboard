import { Controller } from '@hotwired/stimulus';
import { refreshFlashMessages } from '../lib/refresh_flash_messages.js';

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
        'newFilterColor',
        'newFilterGroup',
        'generatedName',
        'renamesInput',
        'finishButton',
        'card',
    ];

    static values = {
        filterUrl: String,
        deleteCsrfToken: String,
    };

    connect() {
        this.currentAvatar = null;
        this.pendingConfirmationPayload = null;
        this.confirmationAcceptedHandler = this.onConfirmationAccepted.bind(this);
        window.addEventListener('avatar-rename:confirmation-accepted', this.confirmationAcceptedHandler);
        this.renames = [];
        this.cardTargets
            .filter((card) => card.dataset.avatarStatus === 'validated')
            .forEach((card) => this.renames.push({ avatarTempId: Number(card.dataset.avatarId) }));
        this.renamesInputTarget.value = JSON.stringify(this.renames);
        this.finishButtonTarget.hidden = this.renames.length === 0;
        this.closePanel();
        this.renderFilters(this.categoryTarget.value || 'body');
    }

    disconnect() {
        window.removeEventListener('avatar-rename:confirmation-accepted', this.confirmationAcceptedHandler);
    }

    selectAvatar(event) {
        const button = event.currentTarget;
        const card = button.closest('.avatar-rename__card');

        this.currentAvatar = {
            id: Number(button.dataset.avatarId),
            originalName: button.dataset.avatarName,
            preview: button.dataset.avatarPreview,
            deleteUrl: button.dataset.avatarDeleteUrl,
            validateUrl: button.dataset.avatarValidateUrl,
            validateCsrfToken: button.dataset.avatarValidateCsrfToken,
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
        };

        let validationResult;
        try {
            validationResult = await this.validateOnServer(payload, false);
            if (!validationResult.success) {
                if (validationResult.confirmationRequired) {
                    this.pendingConfirmationPayload = payload;
                }
                return;
            }
        } catch (error) {
            console.error('Unable to validate avatar name', error);
            refreshFlashMessages();

            return;
        }

        await this.applySuccessfulValidation(payload, validationResult);
    }

    async onConfirmationAccepted(event) {
        const payload = this.pendingConfirmationPayload;
        this.pendingConfirmationPayload = null;

        if (!payload) {
            return;
        }

        await this.applySuccessfulValidation(payload, event.detail || {});
    }

    async applySuccessfulValidation(payload, validationResult) {
        // Validation may have created new filter values in MySQL. Reload the
        // definitions so the next image can select them immediately.
        try {
            await this.renderFilters(payload.category);
        } catch (error) {
            console.error('Unable to reload avatar filters after validation', error);
        }

        this.renames = this.renames.filter((rename) => rename.avatarTempId !== payload.avatarTempId);
        this.renames.push({ avatarTempId: payload.avatarTempId });
        this.renamesInputTarget.value = JSON.stringify(this.renames);

        const card = this.cardTargets.find((item) => Number(item.dataset.avatarId) === payload.avatarTempId);
        if (card) {
            const cardButton = card.querySelector('.avatar-rename__card-button');
            const name = cardButton?.querySelector('span');
            if (name) {
                name.textContent = validationResult.name || payload.newName;
            }
            if (cardButton) {
                cardButton.dataset.avatarName = validationResult.name || payload.newName;
            }
            card.classList.remove('is-selected');
            card.classList.add('is-validated');
            card.dataset.avatarStatus = 'validated';
            this.validatedListTarget.appendChild(card);
        }

        this.finishButtonTarget.hidden = this.renames.length === 0;
    }

    async validateOnServer(payload, authorization) {
        const body = new FormData();
        body.set('_csrf_token', this.currentAvatar.validateCsrfToken);
        body.set('name', payload.newName);
        body.set('category', payload.category);
        body.set('filters', JSON.stringify(payload.filters));
        body.set('authorization', authorization ? '1' : '0');

        const response = await fetch(this.currentAvatar.validateUrl, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'text/vnd.turbo-stream.html, application/json',
            },
            body,
        });
        const contentType = response.headers.get('Content-Type') || '';
        if (contentType.includes('text/vnd.turbo-stream.html')) {
            const html = await response.text();
            window.Turbo?.renderStreamMessage(html);
            return { success: false, confirmationRequired: true };
        }

        const data = await response.json().catch(() => ({}));
        if (!response.ok || data.error) {
            throw new Error(data.error || `Validation failed with status ${response.status}`);
        }

        return data;
    }

    async deleteAvatar(event) {
        const button = event.currentTarget;
        const avatarTempId = Number(button.dataset.avatarId);
        const deleteUrl = button.dataset.avatarDeleteUrl;
        const card = button.closest('.avatar-rename__card');

        if (card?.dataset.avatarStatus === 'validated') {
            await this.cancelValidation(button);
            return;
        }

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

    async cancelValidation(button) {
        const url = button.dataset.avatarCancelValidationUrl;
        const csrfToken = button.dataset.avatarCancelValidationCsrfToken;
        if (!url || !csrfToken) {
            return;
        }

        const body = new FormData();
        body.set('_csrf_token', csrfToken);
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
            body,
        });
        const data = await response.json().catch(() => ({}));

        if (!response.ok || data.error) {
            return;
        }

        const avatarTempId = Number(button.dataset.avatarId);
        const card = button.closest('.avatar-rename__card');
        this.renames = this.renames.filter((rename) => rename.avatarTempId !== avatarTempId);
        this.renamesInputTarget.value = JSON.stringify(this.renames);

        if (card) {
            card.classList.remove('is-selected', 'is-validated');
            card.dataset.avatarStatus = data.status || 'uploaded';
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
            const customColor = this.newFilterColorTargets.find((input) => input.dataset.filterName === filterName);
            const customValue = customInput?.value.trim();

            if (select.value === this.constructor.newFilterValue && this.isColorFilter(select)) {
                filters[filterName] = {
                    name: customValue,
                    hexa: customColor?.value || null,
                };

                return;
            }

            filters[filterName] = select.value === this.constructor.newFilterValue ? customValue : select.value;
        });

        return filters;
    }

    buildNewName() {
        const filters = this.collectFilterLabels();
        const category = this.categoryTarget.value === 'face' ? 'visage' : this.categoryTarget.value;
        const parts = [
            category,
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

            if (['accessory', 'clothes'].includes(filterName) && select.value === '-none-') {
                filters[filterName] = '-none-';

                return;
            }

            filters[filterName] = select.value === this.constructor.newFilterValue ? customValue : selectedLabel;
        });

        return filters;
    }

    async renderFilters(part) {
        const filters = (await this.fetchFilters(part)).filter(
            (filter) => !['partie', 'collection', 'sort', 'direction'].includes(filter.id),
        );

        this.filtersContainerTarget.innerHTML = filters.map((filter) => {
            const isRequired = !['accessory', 'clothes'].includes(filter.id);

            return `
            <label class="avatar-rename__field">
                <span>${this.escapeHtml(filter.label)}${isRequired ? ' *' : ''}</span>
                <select
                    data-avatar-rename-target="filter"
                    data-filter-name="${this.escapeAttribute(filter.id)}"
                    data-is-color="${filter.isColor ? 'true' : 'false'}"
                    data-action="change->avatar-rename#onFilterChange"
                    ${isRequired ? 'required' : ''}
                >
                    ${part === 'body' && filter.id === 'clothes' ? `
                        <option value="-none-">Pas de vêtement</option>
                    ` : ''}
                    ${filter.options.filter((option) => this.isUsableFilterOption(option)).map((option) => `
                        <option value="${this.escapeAttribute(option.value)}">${this.escapeHtml(option.label)}</option>
                    `).join('')}
                    ${filter.allowCreate ? `
                        <option value="${this.constructor.newFilterValue}">Nouvelle ${this.escapeHtml(filter.label.toLowerCase())}</option>
                    ` : ''}
                </select>
                ${filter.allowCreate ? `
                    <div
                        hidden
                        class="avatar-rename__new-filter"
                        data-avatar-rename-target="newFilterGroup"
                        data-filter-name="${this.escapeAttribute(filter.id)}"
                    >
                        <input
                            type="text"
                            placeholder="Nom de la nouvelle ${this.escapeAttribute(filter.label.toLowerCase())}"
                            data-avatar-rename-target="newFilter"
                            data-filter-name="${this.escapeAttribute(filter.id)}"
                            data-action="input->avatar-rename#generateName"
                        >
                        ${this.isColorFilterDefinition(filter) ? `
                            <span class="avatar-rename__color-picker">
                                <input
                                    type="color"
                                    value="#000000"
                                    aria-label="Choisir la couleur"
                                    data-avatar-rename-target="newFilterColor"
                                    data-filter-name="${this.escapeAttribute(filter.id)}"
                                    data-action="input->avatar-rename#onColorChange"
                                >
                                <span class="avatar-rename__color-value">#000000</span>
                            </span>
                        ` : ''}
                    </div>
                ` : ''}
            </label>
        `;
        }).join('');

        this.filterTargets.forEach((select) => this.toggleNewFilterInput(select));
    }

    isColorFilter(select) {
        return select.dataset.isColor === 'true';
    }

    isColorFilterDefinition(filter) {
        return filter.isColor === true;
    }

    onColorChange(event) {
        const colorInput = event.currentTarget;
        const value = colorInput.closest('.avatar-rename__color-picker')?.querySelector('.avatar-rename__color-value');

        if (value) {
            value.textContent = colorInput.value.toUpperCase();
        }
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
            cache: 'no-store',
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
        const customColor = this.newFilterColorTargets.find((input) => input.dataset.filterName === select.dataset.filterName);
        const customGroup = this.newFilterGroupTargets.find((group) => group.dataset.filterName === select.dataset.filterName);

        if (!customInput) {
            return;
        }

        const isNewFilter = select.value === this.constructor.newFilterValue;

        if (customGroup) {
            customGroup.hidden = !isNewFilter;
        } else {
            customInput.hidden = !isNewFilter;
        }

        customInput.required = isNewFilter;

        if (customColor) {
            customColor.hidden = !isNewFilter;
            customColor.disabled = !isNewFilter;
        }

        if (!isNewFilter) {
            customInput.value = '';
        }
    }

    normalizeValue(value) {
        if (value && typeof value === 'object') {
            value = value.name;
        }

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
