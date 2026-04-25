import { Controller } from '@hotwired/stimulus';

/*
* The following line makes this controller "lazy": it won't be downloaded until needed
* See https://symfony.com/bundles/StimulusBundle/current/index.html#lazy-stimulus-controllers
*/

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['container', 'trueInput', 'falseInput', 'tag','tagContainer']
    static values = {
        tagList: {type:Array, default:[]},
        // closeIcon: {type: String, default:''},
        withAutocomplete: {type: Boolean, default:false},
        autocompleteLink: {type: String, default:''},
        falseInputLabel: {type: String, default:''},
        
        
    }

    initialize() {
        

    }

    connect() {
        
        

        this.createUI()
    }



    disconnect() {
        
        

    }

    createUI(){
        const falseInput = this.createFalseInputRow();
        const tagContainer = this.createTagContainer();

        this.containerTarget.prepend(tagContainer)
        this.containerTarget.prepend(falseInput)

        this.falseInputTarget.addEventListener('keydown',(e)=>this.onFalseInputKeyDown(e));
    }

    //? ------ event handler

    onFalseInputKeyDown(e){
        if((e.key !== 'Enter' && e.key !== ',' && e.key !== ';')){
            return
        }
        e.preventDefault();
        const entry = e.target.value.toUpperCase()



        this.falseInputValueProcessing(entry)

        this.falseInputTarget.value = ''
    }

    removeTag({params:{value}}){

        this.tagListValue = this.tagListValue.filter((tag) => tag !== value)
    }

    //? ------ utils

    falseInputValueProcessing(value){
        //! value vide or null
        if(!value || value.length < 0){
            return
        }
        //! value en double
        if(this.tagListValue.findIndex((tag)=> tag === value) !== -1){
            return
        }

        this.tagListValue = [...this.tagListValue, value];
    }

    updateTrueField(list){
        this.trueInputTarget.value = JSON.stringify(list)
    }


    //? ------ Value Change Observer

    tagListValueChanged(value) {
        if (!this.hasTagContainerTarget || !value) {
            return
        }
        this.tagContainerTarget.innerHTML = ''
        value.forEach(tag => {
            this.createTag(tag);
        });
        this.updateTrueField(value);
    }

    autocompleteLinkValueChange(value){}



    //? ------ creating UI
    
    createFalseInputRow(){
        const false_row_prototype = this.trueInputTarget.dataset.falseRowPrototype;
        const index = this.extractIndex(this.trueInputTarget.getAttribute('name'))
        const false_row = this.buildHtmlFromPrototype(false_row_prototype,{
            __LABEL__ : this.falseInputLabelValue,
            __INDEX__ : index,
        });
        if(this.withAutocompleteValue){
            false_row.setAttribute('data-controller','autocomplete');
            false_row.setAttribute('data-autocomplete-target','container');
            false_row.setAttribute('data-autocomplete-url-provider-value',this.autocompleteLinkValue);
            false_row.setAttribute('data-autocomplete-property-name-value','name');
            false_row.classList.add('autocomplete');
            false_row.lastElementChild.setAttribute('data-autocomplete-target', "input")
            false_row.lastElementChild.setAttribute('data-action','focus->autocomplete#displayOnFocus input->autocomplete#searchOnChange')
        }
        return false_row;
    }

    extractIndex(str) {
        const match = str.match(/\[clothes\]\[(\d+)\]\[size\]/);
        if (match) {
            return Number(match[1]);
        }
        return null;
    }


    createTagContainer(){
        const tagContainer = document.createElement('div');
        tagContainer.setAttribute('data-tag-collection-target','tagContainer');
        tagContainer.classList.add('tagContainer');
        
        return tagContainer
    }

    createTag(value){

        const tag_prototype = this.trueInputTarget.dataset.tagPrototype;

        const tag = this.buildHtmlFromPrototype(tag_prototype, 
            {
                __VALUE__ : value,
                // __ICONE__ : "X",
            })
        this.tagContainerTarget.append(tag)
    }

    buildHtmlFromPrototype(prototype, replacements = {}) {
        let html = prototype;

        for (const [key, value] of Object.entries(replacements)) {
            const pattern = new RegExp(key, 'g');
            html = html.replace(pattern, value);
        }

        const wrapper = document.createElement('div');
        wrapper.innerHTML = html.trim();
        return wrapper.firstChild;
    }




}
