import { Controller } from '@hotwired/stimulus';

/*
* The following line makes this controller "lazy": it won't be downloaded until needed
* See https://symfony.com/bundles/StimulusBundle/current/index.html#lazy-stimulus-controllers
*/

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['help','input']
    static values = {
        max : {type:Number , default:0}
    }
    initialize() {
        // Called once when the controller is first instantiated (per element)

        // Here you can initialize variables, create scoped callables for event
        // listeners, instantiate external libraries, etc.
    }
    
    connect() {

        this.inputTarget.addEventListener('input',(e)=>this.count(e))
    }


    disconnect() {

    }

    count({target:{value}}){

        this.helpTarget.textContent = value.length + ' / ' + this.maxValue + ' caracteres max'
    }
}
