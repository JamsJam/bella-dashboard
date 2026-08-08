import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'button', 'showIcon', 'hideIcon'];

    connect() {
        this.sync(false);
    }

    toggle() {
        this.sync(this.inputTarget.type === 'password');
        this.inputTarget.focus({ preventScroll: true });
    }

    conceal() {
        this.sync(false);
    }

    preventCopy(event) {
        if (this.inputTarget.type !== 'text') {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
    }

    sync(visible) {
        this.inputTarget.type = visible ? 'text' : 'password';
        this.buttonTarget.setAttribute('aria-pressed', String(visible));
        this.buttonTarget.setAttribute(
            'aria-label',
            visible ? 'Masquer le mot de passe' : 'Afficher le mot de passe',
        );
        this.showIconTarget.hidden = visible;
        this.hideIconTarget.hidden = !visible;
    }
}
