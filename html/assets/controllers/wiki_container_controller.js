import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        console.log('📖 Wiki container connected');

        // Écouter les événements Turbo
        this.element.addEventListener('turbo:frame-load', this.onFrameLoad.bind(this));
        this.element.addEventListener('turbo:before-fetch-request', this.onBeforeFetch.bind(this));
    }

    disconnect() {
        console.log('📖 Wiki container disconnected');

        // Nettoyer les event listeners
        this.element.removeEventListener('turbo:frame-load', this.onFrameLoad.bind(this));
        this.element.removeEventListener('turbo:before-fetch-request', this.onBeforeFetch.bind(this));
    }

    onFrameLoad(event) {
        console.log('✓ Wiki page loaded');

        // Scroll en haut de la page
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    onBeforeFetch(event) {
        console.log('⏳ Loading wiki page...');

        // Ajouter un loader (optionnel)
        // this.element.classList.add('loading');
    }
}
