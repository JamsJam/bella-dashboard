import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = {
        mode: String,
    }

    initialize() {
        this.applyTheme(this.getDefaultColorTheme());
        document.querySelector("#theme-button").classList.add(this.modeValue)
    }

    getDefaultColorTheme() {
        return localStorage.getItem('theme') ?? 'dark';
    }

    setDefaultColorTheme(mode) {
        localStorage.setItem('theme', mode);
        this.applyTheme(mode);
    }

    applyTheme(mode) {
        document.documentElement.dataset.theme = mode;
        this.modeValue = mode;
    }

    toggle() {
        const nextMode = this.modeValue === 'dark' ? 'light' : 'dark';
        this.setDefaultColorTheme(nextMode);
    }
    
    modeValueChanged(current, old){
        document.querySelector("#theme-button").classList.replace(old, current)

    }
}