import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['tab', 'panel'];
    static values = { storageKey: String };

    connect() {
        const invalidPanel = this.panelTargets.find((panel) => panel.dataset.hasErrors === 'true');
        const storedPanelId = this.hasStorageKeyValue
            ? sessionStorage.getItem(this.storageKey)
            : null;
        const selectedTab = invalidPanel
            ? this.tabTargets.find((tab) => tab.getAttribute('aria-controls') === invalidPanel.id)
            : storedPanelId
                ? this.tabTargets.find((tab) => tab.getAttribute('aria-controls') === storedPanelId)
            : this.tabTargets.find((tab) => tab.getAttribute('aria-selected') === 'true');

        this.activate(selectedTab || this.tabTargets[0], false);
    }

    select(event) {
        this.activate(event.currentTarget);
    }

    keydown(event) {
        if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) {
            return;
        }

        event.preventDefault();
        const current = this.tabTargets.indexOf(event.currentTarget);
        const next = event.key === 'Home'
            ? 0
            : event.key === 'End'
                ? this.tabTargets.length - 1
                : (current + (event.key === 'ArrowRight' ? 1 : -1) + this.tabTargets.length) % this.tabTargets.length;
        this.activate(this.tabTargets[next]);
    }

    activate(activeTab, focus = true) {
        if (!activeTab) {
            return;
        }

        this.tabTargets.forEach((tab) => {
            const active = tab === activeTab;
            tab.setAttribute('aria-selected', String(active));
            tab.tabIndex = active ? 0 : -1;
        });
        this.panelTargets.forEach((panel) => {
            panel.hidden = panel.id !== activeTab.getAttribute('aria-controls');
        });

        if (this.hasStorageKeyValue) {
            sessionStorage.setItem(this.storageKey, activeTab.getAttribute('aria-controls'));
        }

        if (focus) {
            activeTab.focus();
        }
    }

    get storageKey() {
        return `config-tabs:${window.location.pathname}:${this.storageKeyValue}`;
    }
}
