import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        // Écouter les événements Turbo
        this.element.addEventListener('turbo:frame-load', this.onFrameLoad.bind(this));
    }

    disconnect() {
        // Nettoyer les event listeners
        this.element.removeEventListener('turbo:frame-load', this.onFrameLoad.bind(this));
    }

    onFrameLoad() {
        // Scroll en haut de la page
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}
