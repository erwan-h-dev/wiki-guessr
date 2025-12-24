import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['display'];
    static values = {
        startTime: String  // Format ISO: "2025-11-28 14:30:00"
    };

    connect() {
        console.log('⏱️ Timer controller connected');
        this.startTimer();

        // Écouter l'événement de victoire
        document.addEventListener('game:victory', () => this.stopTimer());
    }

    disconnect() {
        console.log('⏱️ Timer controller disconnected');
        this.stopTimer();
        document.removeEventListener('game:victory', () => this.stopTimer());
    }

    startTimer() {
        // Parse le timestamp de départ
        this.startTimestamp = new Date(this.startTimeValue).getTime();

        // Lance l'interval
        this.interval = setInterval(() => {
            this.updateDisplay();
        }, 1000);

        // Première mise à jour immédiate
        this.updateDisplay();
    }

    stopTimer() {
        if (this.interval) {
            clearInterval(this.interval);
            this.interval = null;
        }
    }

    updateDisplay() {
        const now = new Date().getTime();
        const elapsed = Math.floor((now - this.startTimestamp) / 1000);

        const minutes = Math.floor(elapsed / 60).toString().padStart(2, '0');
        const seconds = (elapsed % 60).toString().padStart(2, '0');

        this.displayTarget.textContent = `${minutes}:${seconds}`;
    }
}
