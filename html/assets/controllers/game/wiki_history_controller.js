import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['frame'];
    static values = {
        gameId: Number
    };

    connect() {
        console.log('📚 Wiki history controller connected');

        // Initialiser l'historique
        this.history = [];
        this.currentIndex = 0;

        // Créer une référence stable à la méthode pour pouvoir la supprimer
        this.boundHandleLinkClick = this.handleLinkClick.bind(this);
        this.boundHandlePopState = this.handlePopState.bind(this);

        // Écouter les clics sur l'élément parent (qui n'est pas remplacé par Turbo)
        this.element.addEventListener('click', this.boundHandleLinkClick);

        // Écouter le bouton précédent du navigateur
        window.addEventListener('popstate', this.boundHandlePopState);

        // Charger la première page
        this.loadInitialPage();
    }

    disconnect() {
        // Nettoyer les event listeners
        this.element.removeEventListener('click', this.boundHandleLinkClick);
        window.removeEventListener('popstate', this.boundHandlePopState);
    }

    loadInitialPage() {
        const startPage = this.element.dataset.startPage;
        this.loadPage(startPage, true);
    }

    handleLinkClick(event) {
        // Vérifier si c'est un lien interne Wikipedia
        const link = event.target.closest('a[data-wiki-link]');

        if (link) {
            event.preventDefault();

            const href = link.getAttribute('href');
            const title = this.extractTitle(href);

            console.log('🔗 Clicked wiki link:', title);

            // Charger la nouvelle page
            this.loadPage(title, true);
        }
    }

    loadPage(title, addToHistory = true) {
        console.log('📄 Loading page:', title);
        // Utiliser l'API Turbo Frame pour charger le contenu
        this.frameTarget.src = `/game/${this.gameIdValue}/page/${title}`;

        // Gérer l'historique après le chargement
        if (addToHistory) {

            // Écouter la fin du chargement du frame
            this.frameTarget.addEventListener('turbo:frame-load', () => {

                this.addToHistory(title);
            }, { once: true });
        }
    }

    addToHistory(title) {
        // Supprimer tout ce qui est après l'index actuel
        this.history = this.history.slice(0, this.currentIndex + 1);

        // Ajouter la nouvelle page
        this.history.push({
            title: title,
            url: `/game/${this.gameIdValue}/page/${title}`
        });

        this.currentIndex++;

        console.log('📚 History:', this.history.map(h => h.title));
        console.log('📍 Current index:', this.currentIndex);

        // Ajouter à l'historique navigateur sans changer l'URL
        window.history.pushState(
            { wikiPage: title, index: this.currentIndex },
            '',
            window.location.pathname
        );

        // Scroll en haut
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    handlePopState(event) {

        if (event.state && event.state.index !== undefined) {
            const targetIndex = event.state.index;

            if (targetIndex >= 0 && targetIndex < this.history.length) {
                this.currentIndex = targetIndex;
                const page = this.history[targetIndex];

                // Recharger via Turbo Frame (ne pas ajouter à l'historique)
                this.loadPage(page.title, false);
            }
        }
    }

    extractTitle(href) {
        // Extraire le titre de l'URL
        // Ex: /game/123/page/France → France
        const match = href.match(/\/page\/([^?#]+)/);
        return match ? match[1] : '';
    }

    // Méthodes publiques pour naviguer programmatiquement
    canGoBack() {
        return this.currentIndex > 0;
    }

    canGoForward() {
        return this.currentIndex < this.history.length - 1;
    }

    goBack() {
        if (this.canGoBack()) {
            window.history.back();
        }
    }

    goForward() {
        if (this.canGoForward()) {
            window.history.forward();
        }
    }
}
