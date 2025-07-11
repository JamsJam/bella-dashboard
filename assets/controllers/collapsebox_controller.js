import { Controller } from '@hotwired/stimulus';

/*
* The following line makes this controller "lazy": it won't be downloaded until needed
* See https://symfony.com/bundles/StimulusBundle/current/index.html#lazy-stimulus-controllers
*/

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['collapsebox', 'toggler', 'collapseContent']
    static values = {
        isCollapse:{type: Boolean, default:false},
        boxTrueHeight:{type: Number, default:0},
    }

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
        this.togglerTarget.addEventListener('click', ()=>this.onCollapse())
    }

    collapseboxTargetConnected(){
        this.boxTrueHeightValue = this.collapseboxTarget.scrollHeight;

    }
    // Add custom controller actions here
    // fooBar() { this.fooTarget.classList.toggle(this.bazClass) }

    disconnect() {
        // Called anytime its element is disconnected from the DOM
        // (on page change, when it's removed from or moved in the DOM, etc.)

        // Here you should remove all event listeners added in "connect()" 
        // this.fooTarget.removeEventListener('click', this._fooBar)
        this.togglerTarget.removeEventListener('click', this.onCollapse)
    }

    onCollapse(){
        this.toggleCollapse()
        this.isCollapseValue ? this.expendBox() :  this.collapseBox();
    }
    toggleCollapse(){
        this.isCollapseValue = !this.isCollapseValue;
    }

    expendBox(){
        this.collapseboxTarget.style.maxHeight = this.boxTrueHeightValue + 'px';
        this.collapseboxTarget.classList.replace('filter__items--close','filter__items--open')
    }
    collapseBox(){
        this.collapseboxTarget.style.maxHeight = 0;
        this.collapseboxTarget.classList.replace('filter__items--open','filter__items--close')
    }

}
