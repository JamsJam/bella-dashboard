import { Controller } from '@hotwired/stimulus';

/*
* The following line makes this controller "lazy": it won't be downloaded until needed
* See https://symfony.com/bundles/StimulusBundle/current/index.html#lazy-stimulus-controllers
*/

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['burgerBtn', 'mobileNav', 'closeBtn'];
    static values = {
        isOpen: Boolean,
        widthSize: Number,
    };

    initialize() {
        this.getInnerWidth();
    }

    connect() {


        window.addEventListener('resize',()=>{
            this.getInnerWidth();

            if (this.widthSizeValue > 769) {
                this.closeMobileNav();
                this.isOpenValue = false;
            }

        });

    }

    // Add custom controller actions here
    // fooBar() { this.fooTarget.classList.toggle(this.bazClass) }

    disconnect() {
        // Called anytime its element is disconnected from the DOM
        // (on page change, when it's removed from or moved in the DOM, etc.)

        // Here you should remove all event listeners added in "connect()" 
        // this.fooTarget.removeEventListener('click', this._fooBar)
    }

    onToggleNav(){
        if (this.widthSizeValue > 769) {
            return;
        }else{
            this.toggleMobileNav();
        }
    }

    closeMobileNav(){

        this.mobileNavTarget.classList.remove('open');
        
    }
    
    toggleMobileNav(){

        this.mobileNavTarget.classList.toggle('open');
        this.isOpenValue = this.mobileNavTarget.classList.contains('open');

    }

    getInnerWidth(){
        this.widthSizeValue =  window.innerWidth;
    }

}
