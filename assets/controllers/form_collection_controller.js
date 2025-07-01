import { Controller } from '@hotwired/stimulus';

/*
* The following line makes this controller "lazy": it won't be downloaded until needed
* See https://symfony.com/bundles/StimulusBundle/current/index.html#lazy-stimulus-controllers
*/

/* stimulusFetch: 'lazy' */
export default class extends Controller {


    static targets = [
        'collectionContainer'

    ];


    static values = {
        index    : Number,
        prototype: String,
    };

    addCollectionElement(event)
    {
        const item = document.createElement('li');
        item.innerHTML = this.prototypeValue.replace(/__name__/g, this.indexValue);
        this.collectionContainerTarget.appendChild(item);
        this.indexValue++;
        this.addTagFormDeleteLink(item);
    }

    addTagFormDeleteLink(item) {
        const removeFormButton = document.createElement('button');
        removeFormButton.innerText = 'Supprimer';
        removeFormButton.classList.add('button__action', 'action--delete') ;

        item.append(removeFormButton);

        removeFormButton.addEventListener('click', (e) => {
            e.preventDefault();
            // remove the li for the tag form
            item.remove();
        });


    }


}

