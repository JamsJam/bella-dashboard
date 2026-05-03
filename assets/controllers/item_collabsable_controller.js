import { Controller } from '@hotwired/stimulus';

/*
* The following line makes this controller "lazy": it won't be downloaded until needed
* See https://symfony.com/bundles/StimulusBundle/current/index.html#lazy-stimulus-controllers
*/

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['collapsablebox']
    static values = {
        isCollapsed: {type: Boolean, default: true},
    }


    isCollapsedValueChanged(current, old){

        if (current) {
            this.collapse()
        }else{
            this.expend()
        }
    }

    collapse(){
        this.collapsableboxTarget.classList.add("collapsed");
    }

    expend(){
        this.collapsableboxTarget.classList.remove("collapsed");;
    }

    //? eventlistner
    handleCollapseToggle(){
        const isCollapse = this.collapsableboxTarget.classList.contains('collapsed');
        
        this.isCollapsedValue = isCollapse ? false : true;

    }

}
