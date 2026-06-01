export function refreshFlashMessages() {
    window.dispatchEvent(new CustomEvent('flash-reload'));
}
