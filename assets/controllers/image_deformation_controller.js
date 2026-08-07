import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['file', 'selection', 'submit', 'status', 'download'];
    static values = { uploadUrl: String };

    disconnect() {
        if (this.pollTimer) {
            window.clearTimeout(this.pollTimer);
        }
    }

    selectFile() {
        const file = this.fileTarget.files[0];
        this.selectionTarget.hidden = !file;
        this.selectionTarget.textContent = file ? `Fichier sélectionné : ${file.name}` : '';
    }

    async submit(event) {
        event.preventDefault();
        if (!this.fileTarget.files.length) {
            this.statusTarget.textContent = 'Sélectionnez d’abord une image PNG.';
            return;
        }

        if (this.pollTimer) {
            window.clearTimeout(this.pollTimer);
        }
        this.pollStartedAt = Date.now();
        this.submitTarget.disabled = true;
        this.downloadTarget.hidden = true;
        this.statusTarget.textContent = 'Envoi de l’image…';

        try {
            const response = await fetch(this.uploadUrlValue, {
                method: 'POST',
                body: new FormData(event.currentTarget),
                headers: { Accept: 'application/json' },
            });
            const payload = await response.json();
            if (!response.ok) {
                throw new Error(payload.error || 'Impossible d’envoyer l’image.');
            }

            this.statusTarget.textContent = 'Transformation en attente…';
            await this.poll(payload.statusUrl);
        } catch (error) {
            this.statusTarget.textContent = error.message;
            this.submitTarget.disabled = false;
        }
    }

    async poll(statusUrl) {
        try {
            if (Date.now() - this.pollStartedAt > 60_000) {
                throw new Error('Le traitement tarde anormalement. Vérifiez que le worker image_deformation est démarré.');
            }

            const response = await fetch(statusUrl, { headers: { Accept: 'application/json' } });
            const payload = await response.json();
            if (!response.ok) {
                throw new Error(payload.error || 'Impossible de récupérer le statut.');
            }

            if (payload.status === 'completed') {
                this.statusTarget.textContent = 'Transformation terminée.';
                this.downloadTarget.href = payload.downloadUrl;
                this.downloadTarget.hidden = false;
                this.submitTarget.disabled = false;
                return;
            }
            if (payload.status === 'failed') {
                throw new Error(payload.error || 'La transformation a échoué.');
            }

            this.statusTarget.textContent = payload.status === 'processing'
                ? 'Transformation en cours…'
                : 'Transformation en attente…';
            this.pollTimer = window.setTimeout(() => this.poll(statusUrl), 1000);
        } catch (error) {
            this.statusTarget.textContent = error.message;
            this.submitTarget.disabled = false;
        }
    }
}
