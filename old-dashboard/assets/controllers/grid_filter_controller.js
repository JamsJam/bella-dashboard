import { Controller } from '@hotwired/stimulus';

/*
* The following line makes this controller "lazy": it won't be downloaded until needed
* See https://symfony.com/bundles/StimulusBundle/current/index.html#lazy-stimulus-controllers
*/

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['filterOption', 'card', 'resultFrame'];
    static values = {
        deleteToken : {type: String, default: ''},
        bodypart:{type: String, default: ''},
        deleteUrl: {type: String, default: ''},
        selectMode : {type: Boolean, default: false},
        selectedList : {type: Array, default: []},
        filtersList : {type: Object, default: {}},

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
        this.cardTargets.forEach(cardTarget => {
            // console.log(cardTarget)
            cardTarget.addEventListener('click',(e)=>this.selectCard(e));
        });

        this.filterOptionTargets.forEach(option=>{
            option.addEventListener('change', (e)=>this.onFilterChange(e));
        });

        this.element.addEventListener('turbo:frame-load', () => {
            this.cardTargets.forEach(card => {
                // card.removeEventListener('click',selectCard , false);
                card.addEventListener('click', (e) => this.selectCard(e));
            });

        });
    }
    
    // Add custom controller actions here
    // fooBar() { this.fooTarget.classList.toggle(this.bazClass) }
    
    disconnect() {
        // Called anytime its element is disconnected from the DOM
        // (on page change, when it's removed from or moved in the DOM, etc.)
        
        // Here you should remove all event listeners added in "connect()" 
        // this.fooTarget.removeEventListener('click', this._fooBar)

    }

    cardTargetConnected(){

    }

    selectCard(e){
        const card = e.srcElement;
        const input = this.getCardInput(card);

        !this.selectModeValue && this.turnSelectModeOn();
        card.classList.contains('card--unselected') ? card.classList.replace('card--unselected','card--selected') : card.classList.replace('card--selected','card--unselected');
        input.checked = input.checked ? false : true;

        this.addOrRemoveToSelectedCardList(input);

    }
    unselectedCardList(){

        this.selectedListValue = [];
        this.removeCancelActionButton();
        this.removeDeleteActionButton();

    }

    onFilterChange(e){
        const name = e.target.name;
        const value = e.target.value;

        const currentValues = this.filtersListValue[name] || [];

        
        const updatedValues = currentValues.includes(value)
            ? currentValues.filter(v => v !== value)  // toggle off
            : [...currentValues, value];              // toggle on

        this.filtersListValue = {
            ...this.filtersListValue,
            [name]: updatedValues
        };

        this.reloadResult();
    }

    //? =================== fetcher

    async deleteSelectedCard(){
        if(!confirm('voulez-vous vraiment supprimer ces parties d\'avatar ?')){
            return;
        }
        const itemsToDelete = [...this.selectedListValue];
        const data = new FormData();
        const dataArray = {
            ids : JSON.stringify(itemsToDelete),
            token : this.deleteTokenValue,
            bodyPart: this.bodypartValue
        };

        for (const property in dataArray) {
            data.append(property, dataArray[property]);
        }

        try{

            const response = await fetch(
                this.deleteUrlValue,{
                    method: 'POST',
                    body: data
                }
            );
            if (!response.ok) {
                alert('Une erreur s\'est produite pendant l\'opération. code : ' + response.status);
            }
            alert('Les elements ont bien été effacé');
    
            this.turnSelectModeOff();
            this.reloadResult();
        }catch (error) {
            console.error('Erreur réseau :', error);
            alert('Une erreur réseau s’est produite.');
        }
    }

    //? =================== frame logic

    async reloadResult(){
        const finalFilters = {...this.filtersListValue};
        console.log(finalFilters);
        let filterRequest = '';
        for (const [key, filterGroupe] of Object.entries(finalFilters)) {
            filterGroupe.forEach((filter)=>{
                filterRequest += '&'+key+'[]='+filter;
            });
        }
        
        console.log(document.location.origin + document.location.pathname + '?type=' + this.bodypartValue + filterRequest);
        Turbo.visit(
            document.location.origin + document.location.pathname + '?type=' + this.bodypartValue + filterRequest,{
                action: 'replace', 
                frame: this.resultFrameTarget.getAttribute('id')
            }
                
        );

    }

    //? =================== herlper


    addOrRemoveToSelectedCardList(input){
        console.log(this.selectedListValue.length, input.checked);
        if (input.checked) {
            
            console.log(
                'Value : ',this.selectedListValue,
                'longueur: ',this.selectedListValue.length,
                'value: ',input.value);
            if (this.selectedListValue.length == 0 || (this.selectedListValue.length > 0 && !this.selectedListValue.includes(input.value))) {
                this.selectedListValue = [...this.selectedListValue, input.value];
            } else {
                
            }
            this.selectedListValue.length >= 1  && this.createDeleteActionButton();
        } else {
            this.selectedListValue = this.selectedListValue.filter((value)=> value != input.value);
            this.selectedListValue.length < 1 && this.removeDeleteActionButton();
        }
        console.log(
            'Value : ',this.selectedListValue,
            'longueur: ',this.selectedListValue.length,
            'value: ',input.value);
    }
    

    createCancelActionButton(){
        if(document.querySelector('#cancel-button')){
            return;
        } 
        const action_container = document.querySelector('#avatar-actions .avatar__navigation');
        const cancel_action = document.createElement('button');
        cancel_action.setAttribute('id','cancel-button');
        cancel_action.classList.add('button__action','button__action--danger');
        cancel_action.innerText = 'Annuler la selection';
        // cancel_action.dataset.action = 'click->grid-filter#turnSelectModeOff'
        action_container.append(cancel_action);
        cancel_action.addEventListener('click',()=>this.turnSelectModeOff());
        // cancel_action.addEventListener('click',this.turnSelectModeOff())
    }
    removeCancelActionButton(){
        const cancelButton = document.querySelector('#cancel-button');
        if (cancelButton) {
            cancelButton.remove();
        }
    }


    createDeleteActionButton(){
        if(document.querySelector('#delete-button')){
            return;
        } 
        
        const action_container = document.querySelector('#avatar-actions .avatar__navigation');
        const delete_action = document.createElement('button');
        delete_action.setAttribute('id','delete-button');
        delete_action.classList.add('button__action','button__action--danger');
        delete_action.innerText = 'Supprimer';
        // delete_action.dataset.action = 'click->grid-filter#deleteSelectedCard'
        action_container.append(delete_action);
        delete_action.addEventListener('click',()=>this.deleteSelectedCard());

    }
    removeDeleteActionButton(){
        if(document.querySelector('#delete-button')){
            
            document.querySelector('#delete-button').remove();
        }
    }


    turnSelectModeOn(){
        console.log('turnSelectModeOn',this.selectModeValue);
        this.selectModeValue = true;
        console.log('turnSelectModeOn',this.selectModeValue);
        this.cardTargets.forEach((card)=>{
            const input = this.getCardInput(card);
            
            input.classList.add('card__selectInput--select-mode-on');
            console.log(input);
        });
        this.createCancelActionButton();
    }
    turnSelectModeOff(){
        this.selectModeValue = false;
        this.cardTargets.forEach((card)=>{
            card.classList.replace('card--selected','card--unselected');
            const input = this.getCardInput(card);
            input.classList.remove('card__selectInput--select-mode-on');
            input.checked = false;
        });
        this.unselectedCardList();
    }

    getCardInput(card){
        // console.log(card)
        return card.children[0];
    }


}
