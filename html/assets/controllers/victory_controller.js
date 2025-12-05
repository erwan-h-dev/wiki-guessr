import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['modal'];

    connect() {
        console.log('🎉 Victory controller connected');

        // Afficher la modal avec une petite animation
        setTimeout(() => {
            this.showModal();
        }, 100);
    }

    showModal() {
        if (this.hasModalTarget) {
            this.modalTarget.classList.add('show');

            // Lancer l'animation confetti
            this.launchConfetti();

            // Empêcher le scroll du body
            document.body.style.overflow = 'hidden';
        }
    }

    close() {
        if (this.hasModalTarget) {
            this.modalTarget.classList.remove('show');

            // Restaurer le scroll
            document.body.style.overflow = '';
        }
    }

    launchConfetti() {
        // Simple animation confetti avec des émojis
        const confettiCount = 50;
        const confettiContainer = document.createElement('div');
        confettiContainer.className = 'confetti-container';
        document.body.appendChild(confettiContainer);

        const emojis = ['🎉', '✨', '🎊', '⭐', '💫', '🌟'];

        for (let i = 0; i < confettiCount; i++) {
            const confetti = document.createElement('div');
            confetti.className = 'confetti-piece';
            confetti.textContent = emojis[Math.floor(Math.random() * emojis.length)];
            confetti.style.left = Math.random() * 100 + '%';
            confetti.style.animationDelay = Math.random() * 3 + 's';
            confetti.style.animationDuration = (Math.random() * 3 + 2) + 's';
            confettiContainer.appendChild(confetti);
        }

        // Nettoyer après l'animation
        setTimeout(() => {
            confettiContainer.remove();
        }, 6000);
    }

    // Méthode pour fermer en cliquant sur le backdrop
    handleBackdropClick(event) {
        if (event.target === this.modalTarget) {
            this.close();
        }
    }
}
