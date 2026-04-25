import { Controller } from "@hotwired/stimulus";

/*
 * The following line makes this controller "lazy": it won't be downloaded until needed
 * See https://symfony.com/bundles/StimulusBundle/current/index.html#lazy-stimulus-controllers
 */

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = [
        "fileInput",
        "fileInputContainer",
        "previewContainer",
        "previewRow",
    ];
    static Values = {
        clotheIndex: {type: Number, default:0}
    }

    initialize() {
        // Called once when the controller is first instantiated (per element)

        // Here you can initialize variables, create scoped callables for event
        // listeners, instantiate external libraries, etc.
        // this._fooBar = this.fooBar.bind(this)
        this.fileList = [];
        this.clotheIndexValue = document.querySelectorAll('.formpart-clothe__container').length - 1
    }

    connect() {
        // Called every time the controller is connected to the DOM
        // (on page load, when it's added to the DOM, moved in the DOM, etc.)

        // Here you can add event listeners on the element or target elements,
        // add or remove classes, attributes, dispatch custom events, etc.
        // this.fooTarget.addEventListener('click', this._fooBar)
        this.fileInputTarget.addEventListener("change", (e) =>
            this.onFileInputChange(e)
        );
    }

    // Add custom controller actions here
    // fooBar() { this.fooTarget.classList.toggle(this.bazClass) }

    disconnect() {
        // Called anytime its element is disconnected from the DOM
        // (on page change, when it's removed from or moved in the DOM, etc.)
        // Here you should remove all event listeners added in "connect()"
        // this.fooTarget.removeEventListener('click', this._fooBar)
    }

    //? ----- event handler
    onFileInputChange({ target: { files } }) {
        this.addTofileList(files);
    }
    onFileRemove({ params: { index } }) {
        this.removeTofileList(index);
    }

    //? -----

    addTofileList(files) {
        const newlist = [...this.fileList, ...Array.from(files)];

        this.updateFileList(newlist);
    }

    removeTofileList(fileIndex) {
        const newlist = this.fileList.filter((files, index) => {
            return index !== fileIndex;
        });
        this.updateFileList(newlist);
    }

    updateFileList(newlist) {
        this.fileList = newlist;
        this.previewContainerTarget.innerHTML = "";
        this.fileList.forEach((file, index) => {
            this.createPreviewRowFromPrototype(file, index);
        });
        this.renewFileInput();
    }

    createPreviewRowFromPrototype(file, index) {
        const prototype =
            this.previewContainerTarget.dataset.previewRowPrototype;

        this.getImageasURL(file, index, prototype);
    }

    getImageasURL(file, index, prototype) {
        const reader = new FileReader();
        reader.addEventListener("load", (fileEvent) => {
            const row = this.buildHtmlFromPrototype(prototype, {
                __SRC__: fileEvent.target.result, // 'result', not 'results'
                __INDEX__: index,
                __CLOTHEINDEX__: this.clotheIndexValue,
                __NAME__: file.name,
            });
            this.previewContainerTarget.append(row);
        });
        reader.readAsDataURL(file);
    }

    buildHtmlFromPrototype(prototype, replacements = {}) {
        let html = prototype;

        for (const [key, value] of Object.entries(replacements)) {
            const pattern = new RegExp(key, "g");
            html = html.replace(pattern, value);
        }

        const wrapper = document.createElement("div");
        wrapper.innerHTML = html.trim();
        return wrapper.firstChild;
    }

    renewFileInput() {
        const fileList = this.dataTransfereFactory();
        this.fileInputTarget.files = fileList;
    }

    dataTransfereFactory() {
        const dt = new DataTransfer();
        this.fileList.map((element) => {
            dt.items.add(element);
        });
        return dt.files;
    }
}
