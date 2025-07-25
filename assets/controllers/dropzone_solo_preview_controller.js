import { Controller } from '@hotwired/stimulus';

/*
* The following line makes this controller "lazy": it won't be downloaded until needed
* See https://symfony.com/bundles/StimulusBundle/current/index.html#lazy-stimulus-controllers
*/

/* stimulusFetch: 'lazy' */
export default class extends Controller {

    static targets = [
        "dropzoneContainer",
        "dropzoneInput",
        "dropzonePlaceholder",
        "preview"
    ]


    initialize() {
        // Called once when the controller is first instantiated (per element)
        this.fileUpload = [];
    }


    connect() {
        // Called every time the controller is connected to the DOM
        // (on page load, when it's added to the DOM, moved in the DOM, etc.)

        // Here you can add event listeners on the element or target elements,
        // add or remove classes, attributes, dispatch custom events, etc.
        // this.fooTarget.addEventListener('click', this._fooBar)
        this.dropzoneInputTarget.addEventListener('change',(e)=>this.onFileInputChange(e))
        this.dropzoneInputTarget.addEventListener('dragover',(e)=>this.onFileInputDragOver(e))
        this.dropzoneInputTarget.addEventListener('dragleave',(e)=>this.onFileInputDragLeave(e))
        this.dropzoneContainerTarget.addEventListener('mouseover',(e)=>this.onFileInputHover(e))
        this.dropzoneContainerTarget.addEventListener('mouseleave',(e)=>this.onFileInputLeave(e))
        // this.dropzoneInputTarget.addEventListener('drop',(e)=>this.onFileInputDrop(e))
    }

    // Add custom controller actions here
    // fooBar() { this.fooTarget.classList.toggle(this.bazClass) }

    disconnect() {
        // Called anytime its element is disconnected from the DOM
        // (on page change, when it's removed from or moved in the DOM, etc.)

        // Here you should remove all event listeners added in "connect()" 
        // this.fooTarget.removeEventListener('click', this._fooBar)
    }


    onFileInputChange(e){
        console.log('change')
        const file = e.target.files[0]
        if (file.type.startsWith('image/', 0)) {
            const reader = new FileReader();
            reader.addEventListener('load', (fileEvent) => {
                const urlImage =  fileEvent.target.result;
                
                if (!this.hasPreviewTarget) {
                    //todo create preview container + img
                    this.createpPreviewContainer()
                }
                //todo change img src with dataUrl
                this.loadPreview(urlImage)

            });
            reader.readAsDataURL(file);
        }
    }

    onFileInputDragOver(e){
        e.preventDefault()
        this.dropzoneContainerTarget.classList.add('dropzone__container--drophover')
        this.dropzonePlaceholderTarget.innerText = "Déposer votre image ici"
        // console.log('Dragover',e)
        
    }
    onFileInputDragLeave(e){
        // e.preventDefault()
        this.dropzoneContainerTarget.classList.remove('dropzone__container--drophover')
        this.dropzonePlaceholderTarget.innerText = "Déposer votre image ici ou parcourir"
        // console.log('Dragover',e)
    }

    onFileInputHover(e){
        e.preventDefault()
        this.dropzoneContainerTarget.classList.add('dropzone__container--drophover')
        this.dropzonePlaceholderTarget.innerText = "Parcourir vos fichiers"
        // console.log('Dragover',e)
    }
    onFileInputLeave(e){
        e.preventDefault()
        this.dropzoneContainerTarget.classList.remove('dropzone__container--drophover')
        this.dropzonePlaceholderTarget.innerText = "Déposer votre image ici ou parcourir"
        // console.log('Dragover',e)
    }
    onFileInputDrop(e){
        console.log('drop',e)
        e.preventDefault()
        // console.log('onFileInputDrop',e)
    }

    createpPreviewContainer(){
        const preview = document.createElement('div');
        preview.classList.add('preview__container')
        preview.setAttribute('data-dropzone-solo-preview-target','preview')
        preview.innerHTML = "<img src=''>"
        this.element.append(preview)
    }

    loadPreview(url){
        this.previewTarget.children[0].setAttribute('src',url)
    }
}
