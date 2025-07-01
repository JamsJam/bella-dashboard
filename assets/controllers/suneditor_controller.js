import { Controller } from '@hotwired/stimulus';
import suneditor from 'suneditor'
import plugins from 'suneditor/src/plugins'
import lang from 'suneditor/src/lang'
import fr from 'suneditor/src/lang/fr'



/*
* The following line makes this controller "lazy": it won't be downloaded until needed
* See https://symfony.com/bundles/StimulusBundle/current/index.html#lazy-stimulus-controllers
*/

/* stimulusFetch: 'lazy' */
export default class extends Controller {

    static targets = [
        'sample'
    ]

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
    }

    // Add custom controller actions here
    // fooBar() { this.fooTarget.classList.toggle(this.bazClass) }

    disconnect() {
        // Called anytime its element is disconnected from the DOM
        // (on page change, when it's removed from or moved in the DOM, etc.)

        // Here you should remove all event listeners added in "connect()" 
        // this.fooTarget.removeEventListener('click', this._fooBar)
    }

    sampleTargetConnected() {
        this.initEditor()
    }

    initEditor() {

        const editor = suneditor.create(this.sampleTarget.getAttribute('id'), {
            "mode": "classic",
            "plugins": plugins,
            "rtl": false,
            "height": "120px",
            "katex": "window.katex",
            "charCounter": true,
            "charCounterType": "char",
            "charCounterLabel": "characteres",
            "maxCharCount": "1000",
            "className": "wysiwygEditor",
            "font": [
                'Montserra'
            ],
            "fontSize": [
                    8, 9, 10, 11, 12, 14, 16, 18, 20, 22, 24, 26, 28, 36, 48, 72
                ],
            "fontSizeUnit": 'px',
            "formats": [
                'p', 
                'h1', 
                'h2', 
                'h3', 
                'h4', 
                'h5', 
                'h6',
                'div',
                'blockquote', 
                'pre'
            ],
            "videoFileInput": false,
            "tabDisable": false,
            "paragraphStyles": [
                "spaced",
                {
                    "name": "Box",
                    "class": "__se__customClass"
                }
            ],
            "textStyles": [
                "translucent",
                {
                    "name": "Emphasis",
                    "style": "-webkit-text-emphasis: filled;",
                    "tag": "span"
                }
            ],
            "buttonList": [
                [
                    "undo",
                    "redo",
                    "font",
                    "fontSize",
                    "formatBlock",
                    "paragraphStyle",
                    "blockquote",
                    "bold",
                    "underline",
                    "italic",
                    "strike",
                    // "subscript",
                    // "superscript",
                    // "fontColor",
                    "hiliteColor",
                    "textStyle",
                    "removeFormat",
                    "outdent",
                    "indent",
                    "align",
                    "horizontalRule",
                    "list",
                    "lineHeight",
                    "table",
                    "link",
                    "image",
                    "video",
                    // "audio",
                    // "math",
                    // "imageGallery",
                    "fullScreen",
                    "showBlocks",
                    // "codeView",
                    "preview",
                    // "print",
                    // "save",
                    // "template"
                ]
            ],
            "lang": lang.fr,
            // "lang(In nodejs)": "fr"
        });
        editor.onChange = function (contents, core) { editor.save() }
    }
}
