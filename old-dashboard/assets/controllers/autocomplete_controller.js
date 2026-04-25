import { Controller } from '@hotwired/stimulus';

/*
* The following line makes this controller "lazy": it won't be downloaded until needed
* See https://symfony.com/bundles/StimulusBundle/current/index.html#lazy-stimulus-controllers
*/

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['input','container','autocomplete','option'];
    static values = {
        loading: {type: Boolean, default: false},
        isExpended: {type: Boolean, default: false},
        urlProvider: {type: String, default: null},
        propertyName: {type: String, default: null},
        collectionData: {type: Array, default: []}
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


        this.injectAutocomplete();
    }

    // Add custom controller actions here
    // fooBar() { this.fooTarget.classList.toggle(this.bazClass) }

    disconnect() {
        // Called anytime its element is disconnected from the DOM
        // (on page change, when it's removed from or moved in the DOM, etc.)

        // Here you should remove all event listeners added in "connect()" 
        // this.fooTarget.removeEventListener('click', this._fooBar)
    }


    autocompleteTargetConnected(){
        this.getCollection();
    }

    /**
     * @description inject le container des options dans le container
     */
    injectAutocomplete(){
        const autocomplete = document.createElement('ul');
        autocomplete.classList.add('autocomplete__container');
        autocomplete.setAttribute('data-autocomplete-target', 'autocomplete');
        autocomplete.setAttribute('data-action', 'click@window->autocomplete#clickOut');

        this.containerTarget.append(autocomplete);
        
    }

    /**
     * @description create li for each item in collections
     * @param {Array} data
     */
    injectOption(data){
        this.removeAutocompleteChoices();

        
        if(data.length == 0){
            const noResultItem = document.createElement('li');
            noResultItem.classList.add('autocomplete__option');
            noResultItem.setAttribute('data-autocomplete-target', 'option');
            noResultItem.textContent =  'Aucune donnée trouvé';
            this.autocompleteTarget.append(noResultItem);

        } else{
            data.forEach(element => {
                const autocompleteItem = document.createElement('li');
                autocompleteItem.classList.add('autocomplete__option');
                autocompleteItem.setAttribute('data-autocomplete-target', 'option');
                autocompleteItem.setAttribute('data-action', 'click->autocomplete#onOptionSelect');
                
                // autocompleteItem.textContent = element.name
                autocompleteItem.textContent = element[this.propertyNameValue];
                
                this.autocompleteTarget.append(autocompleteItem);
            });
            
        }

        
    }

    removeAutocompleteChoices(){
        this.optionTargets.forEach(option =>{

            option.remove();
        });
    }

    onOptionSelect(event){
        const choice = event.target.textContent;
        this.inputTarget.value = choice;
        this.removeAutocompleteChoices();
    }

    searchOnChange (event = null, data = null){
        // console.log(event , event.target.value, data)
        const query  = event ? event.target.value : data;
        // console.log(query.length)
        const options = this.collectionDataValue;
        if(query.length === 0){
                
            this.injectOption(options);
        }else{
            const searchedOption = options.filter((option)=>{
            
                return option[this.propertyNameValue].toLowerCase().startsWith(query.toLowerCase());

                
            });
            // console.log(searchedOption)
            this.injectOption(searchedOption);

        }
    }
    //todo add a value Open/close to see the list

    displayOnFocus(event){

        if(!this.isExpendedValue)
            
            // this.injectOption(this.collectionDataValue)
            this.searchOnChange (event);
        
    }
    removeOnFocusout(){
        this.removeAutocompleteChoices();
    }

    clickOut(event){
        // console.log(event.target, ! (event.target == this.containerTarget ||  event.target == this.inputTarget ))
        if(! (event.target == this.containerTarget ||  event.target == this.inputTarget )){
            this.removeAutocompleteChoices();
        }
    }

    async getCollection(){
        // console.log(window.location.origin )
        const urlToFetch = window.location.origin + this.urlProviderValue;
        // console.log(urlToFetch )
        this.loadingValue = true;
        try {
            const response = await fetch(urlToFetch);
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            const data = await response.json();
            this.collectionDataValue = data.member; // <-- ici on ne garde que les entités
        } catch (error) {
            console.error('Fetch error: ', error);
        } finally {
            this.loadingValue = false;
        }



    }


}
