import { Controller } from '@hotwired/stimulus';

/*
* The following line makes this controller "lazy": it won't be downloaded until needed
* See https://symfony.com/bundles/StimulusBundle/current/index.html#lazy-stimulus-controllers
*/

/* stimulusFetch: 'lazy' */
export default class extends Controller {

    static targets = ['visualizer','text_input','color_input'];
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

    colorChangeByInputColor(e){
        const color = e.target.value;
        this.updateVisualizer(color);
        this.updateInputColor(color);

    }
    colorChangeByColorPicker(e){
        const color = e.target.value;
        // this.updateVisualizer(color)
        this.updateInputText(color);

    }

    updateInputText(color){
        // console.log(color)
        const hexRegex = /#[0-9A-Fa-f]{6}/;
        if (!hexRegex.test(color)) {
            return;
        }
        this.text_inputTarget.value = color;
    }

    updateInputColor(color){
        // console.log(color)
        const hexRegex = /#[0-9A-Fa-f]{6}/;
        if (!hexRegex.test(color)) {
            return;
        }
        this.color_inputTarget.value = color;
    }
    updateVisualizer(color){
        const hexRegex = /#[0-9A-Fa-f]{6}/;
        if (!hexRegex.test(color)) {
            return;
        }
        this.visualizerTarget.style.backgroundColor = color;
    }
}
