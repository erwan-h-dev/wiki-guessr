import { Controller } from '@hotwired/stimulus';

/**
 * Contrôleur universel pour gérer le changement de pseudo
 * Fusionne les fonctionnalités de username_toggle et username_modal
 * Utilisable dans tout le contexte de l'application
 */
export default class extends Controller {
    static targets = ['modal', 'input', 'error', 'submitBtn', 'overlay'];

    connect() {
        console.log('👤 Username edit controller connected');
        // Permet d'appeler ce contrôleur globalement
        window.showUsernameModal = () => this.show();

        // Écoute un événement personnalisé pour afficher la modale
        this.element.addEventListener('show-username-modal', (e) => {
            const username = e.detail?.username;
            this.show(username);
        });

        // Affiche la modale au chargement si nécessaire
        const shouldShow = this.element.dataset.usernameEditShowOnLoad === 'true';
        if (shouldShow) {
            setTimeout(() => this.show(), 100);
        }
    }

    /**
     * Affiche la modale de changement de pseudo
     * @param {string} currentUsername - Le pseudo actuel à afficher dans le champ
     */
    show(currentUsername = '') {

        if (this.hasModalTarget) {
            // Réinitialiser les erreurs
            if (this.hasErrorTarget) {
                this.errorTarget.style.display = 'none';
            }

            // Remplir l'input avec le pseudo actuel ou le placeholder
            if (this.hasInputTarget) {
                this.inputTarget.value = currentUsername || this.inputTarget.placeholder || '';
                this.inputTarget.focus();
            }

            // Afficher la modale
            this.showModal();
        }
    }

    /**
     * Affiche la modale en modifiant l'overlay
     */
    showModal() {
        if (this.hasOverlayTarget) {
            this.overlayTarget.style.display = 'flex';
        } else if (this.hasModalTarget) {
            // Fallback si overlay n'existe pas
            this.modalTarget.style.display = 'flex';
        }
    }

    /**
     * Cache la modale
     */
    hide() {
        this.hideModal();
    }

    hideModal() {
        if (this.hasOverlayTarget) {
            this.overlayTarget.style.display = 'none';
        } else if (this.hasModalTarget) {
            this.modalTarget.style.display = 'none';
        }

        if (this.hasInputTarget) {
            this.inputTarget.value = '';
        }

        if (this.hasErrorTarget) {
            this.errorTarget.style.display = 'none';
        }
    }

    /**
     * Soumet le formulaire de changement de pseudo
     */
    async submit(event) {
        event.preventDefault();

        const username = this.inputTarget.value.trim();

        // Validation côté client
        if (!username || username.length < 2 || username.length > 50) {
            this.showError('Le pseudo doit contenir entre 2 et 50 caractères');
            return;
        }

        // Désactiver le bouton submit
        if (this.hasSubmitBtnTarget) {
            this.submitBtnTarget.disabled = true;
            this.submitBtnTarget.textContent = 'Enregistrement...';
        }

        try {
            // Déterminer l'URL correcte basée sur les attributs data
            const form = event.target;
            const actionUrl = form.action || form.dataset.updateUrl || '/player/set-username';

            const response = await fetch(actionUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ username: username })
            });

            const data = await response.json();

            if (data.success) {
                // Fermer la modale
                this.hideModal();
                // Recharger la page après un court délai pour afficher le nouveau pseudo
                setTimeout(() => window.location.reload(), 300);
            } else {
                this.showError(data.error || 'Une erreur est survenue');
                if (this.hasSubmitBtnTarget) {
                    this.submitBtnTarget.disabled = false;
                    this.submitBtnTarget.textContent = 'Confirmer';
                }
            }
        } catch (err) {
            console.error('❌ Erreur lors de la sauvegarde du pseudo:', err);
            this.showError('Erreur lors de l\'enregistrement');
            if (this.hasSubmitBtnTarget) {
                this.submitBtnTarget.disabled = false;
                this.submitBtnTarget.textContent = 'Confirmer';
            }
        }
    }

    /**
     * Affiche un message d'erreur
     */
    showError(message) {
        if (this.hasErrorTarget) {
            this.errorTarget.textContent = '⚠️ ' + message;
            this.errorTarget.style.display = 'block';
        } else {
            alert(message);
        }

        if (this.hasInputTarget) {
            this.inputTarget.focus();
        }
    }

    disconnect() {
        console.log('👤 Username edit controller disconnected');
    }
}
