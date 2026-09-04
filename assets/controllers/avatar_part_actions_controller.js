import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = {
        deleteUrl: String,
        deleteCsrfToken: String,
        renameUrl: String,
        renameCsrfToken: String,
        indexUrl: String,
        renameIndexUrl: String,
    };

    async rename(event) {
        event.preventDefault();

        try {
            const data = await this.sendRequest(this.renameUrlValue, 'POST', this.renameCsrfTokenValue);

            if (!data.success) {
                throw new Error(data.error || 'Impossible de mettre cette piece en renommage.');
            }

            window.location.href = this.renameIndexUrlValue;
        } catch (error) {
            console.error('Erreur lors de la mise en renommage:', error);
        }
    }

    async delete(event) {
        event.preventDefault();

        if (!window.confirm('Supprimer cette piece d avatar ?')) {
            return;
        }

        try {
            const data = await this.sendRequest(this.deleteUrlValue, 'DELETE', this.deleteCsrfTokenValue);

            if (!data.success) {
                throw new Error(data.error || 'Impossible de supprimer cette piece.');
            }

            window.location.href = this.indexUrlValue;
        } catch (error) {
            console.error('Erreur lors de la suppression:', error);
        }
    }

    async sendRequest(url, method, csrfToken) {
        const response = await fetch(url, {
            method,
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            throw new Error(data.error || `HTTP error! status: ${response.status}`);
        }

        return data;
    }
}
