import { Controller } from '@hotwired/stimulus';

/*
* The following line makes this controller "lazy": it won't be downloaded until needed
* See https://github.com/symfony/stimulus-bridge#lazy-controllers
*/
/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['flash'];
    static values = {
        countdown: {type:Number, default: 5000},
    };
    
    initialize(){}

    connect(){}

    flashTargetConnected(){
        setTimeout(() => {
            
            this.flashFadeDown();
        }, 2000);
        
        
        setTimeout(() => {
            this.flashContainerHide();
        }, this.countdownValue);
    }


    flashFadeDown(){
        this.flashTarget.classList.add('hide');
    }
    flashContainerHide(){
        this.element.style.display = 'none';
    }
}
