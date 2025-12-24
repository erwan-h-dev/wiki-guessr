import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['overlay', 'text'];

    connect() {
        this.countdownInterval = null;
        this.doStartUrl = null;

        // Listen for countdown start event from room controller
        this.boundHandleCountdownStart = this.handleCountdownStart.bind(this);
        document.addEventListener(
            'multiplayer--room:countdown-start',
            this.boundHandleCountdownStart
        );
        console.log('⏱️ Countdown controller connected');
    }

    disconnect() {
        this.cleanup();
        document.removeEventListener(
            'multiplayer--room:countdown-start',
            this.boundHandleCountdownStart
        );
    }

    cleanup() {
        if (this.countdownInterval) {
            clearInterval(this.countdownInterval);
            this.countdownInterval = null;
        }
    }

    handleCountdownStart(event) {
        const { endsAt, doStartUrl } = event.detail;
        this.doStartUrl = doStartUrl;
        this.showCountdown(endsAt);
    }

    showCountdown(endsAt) {
        if (!this.hasOverlayTarget || !this.hasTextTarget) return;

        this.overlayTarget.style.display = 'flex';

        const now = Math.floor(Date.now() / 1000);
        const timeLeft = Math.max(0, endsAt - now);
        let remaining = timeLeft;

        const updateCountdown = () => {
            if (remaining > 0) {
                this.textTarget.textContent = remaining;
                remaining--;
            } else {
                this.textTarget.textContent = 'GO!';
                this.cleanup();

                // Call do-start endpoint after a brief delay
                setTimeout(() => {
                    this.doStart();
                }, 500);
            }
        };

        updateCountdown();
        this.countdownInterval = setInterval(updateCountdown, 1000);
    }

    async doStart() {
        if (!this.doStartUrl) return;

        try {
            const response = await fetch(this.doStartUrl, { method: 'POST' });
            const data = await response.json();

            if (data.success) {
                this.redirectToGame();
            }
        } catch (error) {
            console.error('Error starting game:', error);
        }
    }

    redirectToGame() {
        // TODO: Implement proper game redirect
        console.log('Redirecting to game...');
    }
}
