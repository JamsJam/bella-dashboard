import { Controller } from '@hotwired/stimulus';

/*
* The following line makes this controller "lazy": it won't be downloaded until needed
* See https://symfony.com/bundles/StimulusBundle/current/index.html#lazy-stimulus-controllers
*/

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['layout','aside','overlay']
    static values = {
        isOpen: {type: Boolean, default: false}
    }

    isOpenValueChanged(current, old){

        if (current) {
            this.asideOn()
        }else{
            this.asideOff()

        }
    }


    asideOff() {
        this.layoutTarget.classList.replace("layout--open","layout--close");
        this.asideTarget.classList.replace("aside--open","aside--close");
        this.overlayTarget.classList.replace("overlay--open","overlay--close");
    }
    asideOn() {
        this.layoutTarget.classList.replace("layout--close","layout--open");
        this.asideTarget.classList.replace("aside--close","aside--open");
        this.overlayTarget.classList.replace("overlay--close","overlay--open");
    }

    //? eventlistner
    handleAsideToggle(){
        const isOpen = this.layoutTarget.classList.contains('layout--open');
        
        this.isOpenValue = isOpen ? false : true;
        console.log(isOpen, window.matchMedia('min-width : 1068px').matches)
    }
}
