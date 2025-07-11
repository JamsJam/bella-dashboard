import { Controller } from '@hotwired/stimulus';

/*
* The following line makes this controller "lazy": it won't be downloaded until needed
* See https://symfony.com/bundles/StimulusBundle/current/index.html#lazy-stimulus-controllers
*/

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['input', 'dropzone', 'resume', 'hairTable', 'bodyTable', 'partTable', 'unkownTable'];
    static values = {
        bufferSize: { type: Number, default: 5 },
        max_parallele_fetch: { type: Number, default: 5 },
        url: { type: String, default: '' },
        token: { type: String, default: '' },
        // requestDetails: { type: Array, default: [] },
        partTranslations: {type: Object, default: {
            nose: 'Nez',
            noze: 'Nez',
            eye: 'Yeux',
            eyes: 'Yeux',
            eyebrows: 'Sourcils',
            eyesbrows: 'Sourcils',
            mouth: 'Bouche',
            face: 'Visage'
        }
        }
    };

    //? ------------------------------------------------------------------
    //? Lifecycle
    //? ------------------------------------------------------------------
    initialize() {
        // Called once when the controller is first instantiated (per element)
        this.fileUpload = [];
        this.pool = [];
        this.queue = [];
        this.requestDetails = [];
    }
    connect() {
        // Called every time the controller is connected to the DOM

        this.inputTarget.addEventListener('change', (e) => this.addFileByChange(e));
        this.inputTarget.addEventListener('dragover', (e) => this.handleFileDragover(e));
        this.dropzoneTarget.addEventListener('dragover', (e) => this.handleFileDragover(e));
    }
    disconnect() {
        // Called anytime its element is disconnected from the DOM
    }

    //? ------------------------------------------------------------------
    //? Event Handlers
    //? ------------------------------------------------------------------

    addFileByChange(e) {
        const files = Array.from(e.target.files);

        files.forEach(file => {
            const isAlreadyStored = this.fileUpload.some(
                f => f.name === file.name && f.size === file.size && f.lastModified === file.lastModified
            );

            if (!isAlreadyStored) {
                this.fileUpload.push(file);
            }
        });

        this.inputTarget.value = '';
        this.reloadResumeTables();
    }

    addFileByDrop(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'copy';
    }

    handleFileDragover(e) {
        e.preventDefault();
    }

    removeFileOnClick(e){
        const filename = e.target.dataset.name;
        const newArray = this.fileUpload.filter((file)=>file.name !== filename);
        this.fileUpload = newArray;
        this.reloadResumeTables();
    } 

    removeAllFile(){
        const newArray = this.fileUpload.filter((file)=>false);
        this.fileUpload = newArray;
        this.reloadResumeTables();
    }
    //? ------------------------------------------------------------------
    //? fetch
    //? ------------------------------------------------------------------
    //start the request
    async start(){
        this.queue = [...this.fileUpload];; // charger la liste d'attente
        while (this.queue.length > 0) { // tant que la fil d'attente n'est pas vide
            
            const batch = this.queue.length > this.bufferSizeValue ?  this.queue.splice(0,this.bufferSizeValue) : this.queue.splice(0,this.queue.length);// je recupere dans pool le nombre de file a envoyer selon le buffersize
            const token = this.tokenValue;
        
            await Promise.all(batch.map(async (file)=>{
                console.log(file);
                const fileData = new FormData();
                fileData.append('fileData', file);
                fileData.append('token', token);
                const response =  await fetch(this.urlValue,{
                    method: 'POST',
                    body:fileData
                });
                if(!response.ok){
                    this.requestDetails['error'].push(file.name);
                }else{
                    this.requestDetails['success'].push(file.name);
                }
                    
                    

            }));
                
        }
        this.removeAllFile();

    }
    //? ------------------------------------------------------------------
    //? Helpers
    //? ------------------------------------------------------------------

    reloadResumeTables() {
        this.hasHairTableTarget && this.hairTableTarget.remove();
        this.hasBodyTableTarget && this.bodyTableTarget.remove();
        this.hasPartTableTarget && this.partTableTarget.remove();
        this.hasUnkownTableTarget && this.unkownTableTarget.remove();
        this.fileUpload.sort((a, b) =>{a.name.localeCompare(b.name);}).forEach(file => {this.resolveTableResumeByName(file);});
    }
    resolveTableResumeByName(file) {

        if(!this.isValidAvatarTag(file.name)){
            this.showUnkownTable(file);
        }else{
            

            const fileNameKey = file.name.split('__')[0];
                
            if (fileNameKey === 'hair') {
                this.showHairTable(file);
            } else if (fileNameKey === 'body') {
                this.showBodyTable(file);
            } else if (['nose', 'eye', 'eyebrows', 'mouth', 'face'].includes(fileNameKey)) {
                this.showPartTable(file);
            }else{
                this.showUnkownTable(file);
            }
        }
    }

    
    showHairTable(file) {
        if (!this.hasHairTableTarget) {
            const hairTable = document.createElement('div');
            hairTable.className = 'resume__table--hair';
            hairTable.setAttribute('data-avatar-dd-target', 'hairTable');

            // headers
            const headers = ['Element', 'Couleur', 'Forme', 'Side', 'Action'];
            headers.forEach(text => {
                const h = document.createElement('p');
                h.classList.add('resume__header');
                h.textContent = text;
                hairTable.appendChild(h);
            });

            this.resumeTarget.appendChild(hairTable);
        }

        const fileInfo = file.name.split('__').slice(0, 4);

        fileInfo.forEach((text, index) => {
            const div = document.createElement('p');
            if (index === 0) {
                div.textContent = 'Cheveux';
            } else if (index === 3) {

                div.textContent = text.split('.')[0];
            } else {

                div.textContent = text;
            }
            this.hairTableTarget.appendChild(div);
        });

        const deleteBtn = this.createDeleteButton(file.name);
        this.hairTableTarget.appendChild(deleteBtn);
    }
    showBodyTable(file) {
        if (!this.hasBodyTableTarget) {
            const bodyTable = document.createElement('div');
            bodyTable.className = 'resume__table--body';
            bodyTable.setAttribute('data-avatar-dd-target', 'bodyTable');

            const headers = ['Element', 'Couleur', 'Morphotype', 'Morphologie', 'Vetement', 'Action'];
            headers.forEach(text => {
                const h = document.createElement('p');
                h.classList.add('resume__header');
                h.textContent = text;
                bodyTable.appendChild(h);
            });
            this.resumeTarget.appendChild(bodyTable);
        }

        const fileInfo = file.name.split('__').slice(0, 5);
        fileInfo.forEach((text, index) => {
            const div = document.createElement('p');
            if (index === 0) {
                div.textContent = 'Corps';

            } else if (index === 4) {

                div.textContent = text.split('.')[0];
            } else {
                div.textContent = text;
            }
            this.bodyTableTarget.appendChild(div);
        });

        const deleteBtn = this.createDeleteButton(file.name);
        this.bodyTableTarget.appendChild(deleteBtn);
    }
    showPartTable(file) {
        if (!this.hasPartTableTarget) {
            const partTable = document.createElement('div');
            partTable.className = 'resume__table--part';
            partTable.setAttribute('data-avatar-dd-target', 'partTable');

            const headers = ['Element', 'Couleur', 'Style', 'Action'];
            headers.forEach(text => {
                const h = document.createElement('p');
                h.classList.add('resume__header');
                h.textContent = text;
                partTable.appendChild(h);
            });
            this.resumeTarget.appendChild(partTable);
        }

        
        const fileInfo = file.name.split('__').slice(0, 3); // key + color + style (if any)

        fileInfo.forEach((text, index) => {
            const div = document.createElement('p');
            if (index === 0) {
                
                div.textContent = this.partTranslationsValue[text];
            } else if (index === 2) {

                div.textContent = text.split('.')[0];
            } else {
                div.textContent = text;
            }
            this.partTableTarget.appendChild(div);
        });

        this.partTableTarget.appendChild(this.createDeleteButton(file.name));
    }
    showUnkownTable(file) {
        if (!this.hasUnkownTableTarget) {
            const unkownTable = document.createElement('div');
            unkownTable.className = 'resume__table--unkown';
            unkownTable.setAttribute('data-avatar-dd-target', 'unkownTable');

            const title = ['Fichier non traité'];
            title.forEach(text => {
                const h = document.createElement('p');
                h.classList.add('resume__header');
                h.textContent = text;
                unkownTable.appendChild(h);
            });
            this.resumeTarget.appendChild(unkownTable);
        }
        const cell = ['Nom du fichier', file.name ];
        cell.forEach(text => {
            const filecell = document.createElement('p');
            // cell.classList.add('resume__header')
            filecell.textContent = text;
            this.unkownTableTarget.appendChild(filecell);

        });
        

        const filename = file.name;
        const newArray = this.fileUpload.filter((file)=>file.name !== filename);
        this.fileUpload = newArray;

        // this.reloadResumeTables();
    }
    createDeleteButton(filename) {
        const deleteBtn = document.createElement('button');

        deleteBtn.textContent = 'Supprimer';
        deleteBtn.classList.add('resume__button');

        deleteBtn.dataset.name = filename;
        deleteBtn.dataset.action = 'click->avatar-dd#removeFileOnClick';

        return deleteBtn;
    }

    isValidAvatarTag(tag) {
        if (typeof tag !== 'string') return false;

        // Découper sur le séparateur imposé
        const parts  = tag.trim().split('__');
        const elem   = parts[0];        // "hair", "eye", ...
        const params = parts.slice(1);  // reste des blocs


        const spec = {
            hair:      3,   // color, shape, side
            eye:       2,   // color, shape
            eyebrows:  2,   // color, shape
            mouth:     2,   // color, shape
            nose:      2,   // skincolor, shape
            face:      2,   // skincolor, shape
            body:      4    // skincolor, morphotype, morphologie, vêtement
        };

        // 1) Élément reconnu ?
        if (!spec.hasOwnProperty(elem)) return false;

        // 2) Nombre de blocs correct ?
        if (params.length !== spec[elem]) return false;

        // 3) Expression générique pour un bloc « valeur » :
        //    - lettres minuscules uniquement
        //    - tirets simples ou doubles pour remplacer les espaces, mais jamais au début/fin
        const blockRe = /^[a-z]+(?:-+[a-z]+)*$/;

        // 4) Vérifier chaque paramètre
        for (let i = 0; i < params.length; i++) {
            let p = i = params.length - 1 ? params[i].slice('.')[0] : params[i];
            // Cas particulier : body … vetement peut être "-none-"
            if (elem === 'body' && i === 3 && p === '-none-') continue;

            if (!blockRe.test(p)) return false;
        }

        return true; // tout est conforme
    }
    
}
