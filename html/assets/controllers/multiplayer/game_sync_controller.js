import { Controller } from '@hotwired/stimulus';

/**
 * Coordinateur pour la synchronisation en temps réel du jeu multijoueur
 * Polling toutes les 2 secondes pour les mises à jour du serveur
 * Détecte les victoires propres et des adversaires
 * Dispatche des événements pour les autres contrôleurs
 */
export default class extends Controller {
    static values = {
        gameId: Number,
        participantId: Number,
        playerId: Number,
        syncUrl: String,
        lobbyUrl: String,
        resultsUrl: String,
    };

    static targets = ['personalVictory'];

    connect() {
        console.log('🎮 Game Sync Controller connected');

        this.pollInterval = null;
        this.previousState = null;
        this.ownVictoryShown = false;
        this.updateCounter = 0;

        // Start polling immediately
        this.startPolling();

        // Cleanup on disconnect
        this.boundCleanup = this.cleanup.bind(this);
        window.addEventListener('beforeunload', this.boundCleanup);
    }

    disconnect() {
        console.log('🎮 Game Sync Controller disconnected');
        this.cleanup();
        window.removeEventListener('beforeunload', this.boundCleanup);
    }

    cleanup() {
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
            this.pollInterval = null;
        }
    }

    startPolling() {
        // Initial poll immediately
        this.pollGameState();

        // Poll every 2 seconds
        this.pollInterval = setInterval(() => {
            this.pollGameState();
        }, 2000);
    }

    async pollGameState() {
        try {
            const response = await fetch(this.syncUrlValue);
            const data = await response.json();

            this.updateCounter++;

            // Detect own victory (only once)
            if (!this.ownVictoryShown) {
                const ownParticipant = data.participants.find(
                    p => p.playerId === this.playerIdValue
                );

                if (ownParticipant && ownParticipant.hasFinished) {
                    this.ownVictoryShown = true;
                    this.dispatch('own-victory', {
                        detail: { participant: ownParticipant, gameState: data }
                    });
                    // Show personal victory
                    this.showPersonalVictory(ownParticipant, data);
                }
            }

            // Detect opponent victories (compare with previous state)
            if (this.previousState) {
                this.previousState.participants.forEach(prevParticipant => {
                    const newParticipant = data.participants.find(
                        p => p.id === prevParticipant.id
                    );

                    if (newParticipant &&
                        !prevParticipant.hasFinished &&
                        newParticipant.hasFinished &&
                        newParticipant.playerId !== this.playerIdValue) {

                        this.dispatch('opponent-victory', {
                            detail: { participant: newParticipant, gameState: data }
                        });
                    }
                });
            }

            // Always dispatch state update for UI refresh
            this.dispatch('participants-update', {
                detail: { participants: data.participants, gameState: data }
            });

            // Check if game is finished and all participants have left
            if (data.state === 'finished') {
                this.handleGameFinished(data);
            }

            this.previousState = data;

        } catch (error) {
            console.error('❌ Poll error:', error);
        }
    }

    showPersonalVictory(participant, gameState) {
        if (this.hasPersonalVictoryTarget) {
            this.personalVictoryTarget.style.display = 'flex';

            // Pass data to final_scoreboard_controller
            this.dispatch('personal-victory-show', {
                detail: { participant, gameState }
            });
        }
    }

    handleGameFinished(gameState) {
        console.log('🎮 Game finished, redirecting to results in 3 seconds...');

        // Stop polling
        this.cleanup();

        // Redirect to results after a short delay
        setTimeout(() => {
            window.location.href = this.resultsUrlValue;
        }, 3000);
    }
}
