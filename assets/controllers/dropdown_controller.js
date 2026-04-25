import { Controller } from '@hotwired/stimulus';

/*
* The following line makes this controller "lazy": it won't be downloaded until needed
* See https://symfony.com/bundles/StimulusBundle/current/index.html#lazy-stimulus-controllers
*/

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['container', 'toggleler'];
    static values = {
        isOpen: {
            type:Boolean, default: false
        },
    };

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
        document.addEventListener('click',(e) => this.closeDropDown(e));
        this.togglelerTarget.addEventListener('click',(e)=>this.toggleDropDown(e));
    }

    // Add custom controller actions here
    // fooBar() { this.fooTarget.classList.toggle(this.bazClass) }

    disconnect() {
        // Called anytime its element is disconnected from the DOM
        // (on page change, when it's removed from or moved in the DOM, etc.)

        // Here you should remove all event listeners added in "connect()" 
        // this.fooTarget.removeEventListener('click', this._fooBar)
    }


    toggleDropDown(e){
        
        e.stopPropagation();
        if(!this.isOpenValue){

            const allDrop = document.querySelectorAll('ul.actionDropdown');
            allDrop.forEach(element=>element.classList.remove('open'));

            this.isOpenValue = true;
            this.containerTarget.classList.add('open');
        }else{
            this.closeDropDown(e);

        }
        // }
    }

    closeDropDown(e){
        // console.log(e.srcElement, this.containerTarget)
        // e.stopPropagation()
        if(!this.containerTarget.contains(e.target) && this.isOpenValue ){
            this.isOpenValue = false;
            this.containerTarget.classList.remove('open');
        } 
    }
}
