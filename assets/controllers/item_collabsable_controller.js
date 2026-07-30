import { Controller } from '@hotwired/stimulus';

/*
* The following line makes this controller "lazy": it won't be downloaded until needed
* See https://symfony.com/bundles/StimulusBundle/current/index.html#lazy-stimulus-controllers
*/

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['collapsablebox', 'header']

    static values = {
        isCollapsed: {type: Boolean, default: true},
    }

    isCollapsedValueChanged(current) {
        if (current) {
            this.collapse();
        } else {
            this.expand();
        }
    }

    collapse() {
        this.collapsableboxTarget.classList.add('collapsed');
        this.headerTarget.classList.remove('open');
        this.headerTarget.setAttribute('aria-expanded', 'false');
    }

    expand() {
        this.collapsableboxTarget.classList.remove('collapsed');
        this.headerTarget.classList.add('open');
        this.headerTarget.setAttribute('aria-expanded', 'true');
    }

    handleCollapseToggle() {
        this.isCollapsedValue = !this.isCollapsedValue;
    }
}
