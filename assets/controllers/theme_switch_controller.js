import { Controller } from '@hotwired/stimulus';

/*
* The following line makes this controller "lazy": it won't be downloaded until needed
* See https://symfony.com/bundles/StimulusBundle/current/index.html#lazy-stimulus-controllers
*/

/* stimulusFetch: 'lazy' */
export default class extends Controller {

    static targets = [
        'menu'
    ];

    initialize() {
        // Called once when the controller is first instantiated (per element)

        // Here you can initialize variables, create scoped callables for event
        // listeners, instantiate external libraries, etc.
        // this._fooBar = this.fooBar.bind(this)
    }

    connect() {
        // Called every time the controller is connected to the DOM
        // (on page load, when it's added to the DOM, moved in the DOM, etc.)

        // Here you can add event listeners on the element or target elements,
        // add or remove classes, attributes, dispatch custom events, etc.
        // this.fooTarget.addEventListener('click', this._fooBar)
    }

    // Add custom controller actions here
    // fooBar() { this.fooTarget.classList.toggle(this.bazClass) }

    disconnect() {
        // Called anytime its element is disconnected from the DOM
        // (on page change, when it's removed from or moved in the DOM, etc.)

        // Here you should remove all event listeners added in "connect()" 
        // this.fooTarget.removeEventListener('click', this._fooBar)
    }

    async switchTheme(){
        console.log('THEME');
        const menu = document.querySelector('.menu');
        const actualTheme = Array.from(this.menuTarget.classList).some((el => el.endsWith('--dark'))) ? 'dark' : 'light';
        const newTheme = actualTheme === 'dark' ? 'light' : 'dark';
        const success = await this.switchThemeConfig(newTheme);
        if (!success) {
            console.error('Erreur lors de la mise à jour du thème côté serveur');
            return;
        }
        const items = document.querySelectorAll(`[class *="--${actualTheme}"`);
        items.forEach(element => {
            const oldClass = Array.from(element.classList).find((el => el.endsWith(`--${actualTheme}`)));
            const newClass = oldClass.replace(`--${actualTheme}`,`--${newTheme}`);
            element.classList.replace(oldClass, newClass);
        });
    }

    async switchThemeConfig(theme){
        try{

            const response = await fetch('/config/change-theme',{
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    
                    // Ajouter CSRF token ici si nécessaire
                },
                body:JSON.stringify({theme})
            });
            if(response.ok){
    
                return true;
            }
        }catch (error) {
            console.error('Erreur fetch:', error);
            return false;
        }

    }


}
