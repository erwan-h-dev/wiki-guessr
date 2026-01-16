import { Controller } from '@hotwired/stimulus';

/**
 * Gère les animations de victoire d'un adversaire
 * Flash blanc fullscreen (300ms) + confettis localisés
 */
export default class extends Controller {
    connect() {
        console.log('✨ Victory Flash Controller connected');

        // Listen for opponent victory events from game_sync_controller
        this.element.parentElement?.addEventListener('opponent-victory', (e) => {
            this.showVictoryFlash(e.detail.participant);
        });
    }

    showVictoryFlash(participant) {
        // Create flash overlay
        const flash = document.createElement('div');
        flash.className = 'victory-flash';

        const content = document.createElement('div');
        content.className = 'flash-content';
        content.innerHTML = `
            <div class="flash-player-name">${participant.userName}</div>
            <div class="flash-message">a terminé la partie!</div>
        `;
        flash.appendChild(content);

        this.element.appendChild(flash);

        // Trigger animation
        requestAnimationFrame(() => {
            flash.classList.add('active');
        });

        // Remove flash and show confetti
        setTimeout(() => {
            flash.classList.remove('active');
            setTimeout(() => {
                flash.remove();
                // Show confetti
                this.showConfetti(participant);
            }, 300);
        }, 300);
    }

    showConfetti(participant) {
        const emojis = ['🎉', '✨', '🌟', '⭐', '🎊', '🏆', '👏', '🎯'];
        const container = this.element;

        // Find the participant card to localize confetti
        const participantCard = document.querySelector(
            `[data-participant-id="${participant.id}"]`
        );

        let posX = window.innerWidth / 2;
        let posY = window.innerHeight / 2;

        if (participantCard) {
            const rect = participantCard.getBoundingClientRect();
            posX = rect.left + rect.width / 2;
            posY = rect.top + rect.height / 2;
        }

        // Create 15 confetti elements
        for (let i = 0; i < 15; i++) {
            const confetti = document.createElement('div');
            confetti.className = 'confetti';
            confetti.textContent = emojis[Math.floor(Math.random() * emojis.length)];

            // Random position around the center point
            const angle = (Math.PI * 2 * i) / 15;
            const startX = posX + Math.cos(angle) * 20;
            const startY = posY + Math.sin(angle) * 20;

            confetti.style.left = startX + 'px';
            confetti.style.top = startY + 'px';

            container.appendChild(confetti);

            // Remove after animation
            setTimeout(() => confetti.remove(), 3000);
        }
    }
}
