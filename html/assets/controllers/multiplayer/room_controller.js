import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [
        'readyBtn',
        'startBtn',
        'participantsList',
        'kickedModal'
    ];

    static values = {
        gameId: Number,
        playerId: Number,
        isCreator: Boolean,
        syncUrl: String,
        readyUrl: String,
        updateUsernameUrl: String,
        kickUrl: String,
        startUrl: String,
        doStartUrl: String,
        lobbyUrl: String
    };

    connect() {
        this.pollInterval = null;
        this.gameStartedAt = null;
        this.currentGameState = null;
        this.currentPlayerReady = false;

        // Start polling
        this.startPolling();

        // Cleanup on disconnect
        this.boundCleanup = this.cleanup.bind(this);
        window.addEventListener('beforeunload', this.boundCleanup);

        console.log('🕹️ Multiplayer Room Controller connected');
    }

    disconnect() {
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
        // Initial poll
        this.pollRoomState();

        // Poll every 2 seconds
        this.pollInterval = setInterval(() => {
            this.pollRoomState();
        }, 2000);
    }

    async pollRoomState() {
        try {
            const response = await fetch(this.syncUrlValue);
            const data = await response.json();

            this.currentGameState = data;

            // Find current player status
            const currentPlayerData = data.participants.find(
                p => p.playerId === this.playerIdValue
            );

            // Check if kicked
            if (!currentPlayerData) {
                this.cleanup();
                this.showKickedModal();
                return;
            }

            // Update ready state
            this.currentPlayerReady = currentPlayerData.isReady;
            this.updateReadyButtonUI();

            // Update participants list
            this.updateParticipantsList(data.participants);

            // Handle state transitions
            if (data.state === 'countdown' && !this.gameStartedAt) {
                this.dispatchCountdownEvent(data.countdownEndsAt);
            } else if (data.state === 'in_progress') {
                this.gameStartedAt = data.gameStartedAt;
                this.redirectToGame();
            }
        } catch (error) {
            console.error('Poll error:', error);
        }
    }

    updateParticipantsList(participants) {
        const allParticipantIds = participants.map(p => p.id);
        const container = this.participantsListTarget;

        // Update or add participants
        participants.forEach(participant => {
            const existingEl = container.querySelector(
                `[data-participant-id="${participant.id}"]`
            );

            if (existingEl) {
                // Update existing
                const statusEl = existingEl.querySelector('[data-status]');
                const usernameEl = existingEl.querySelector('[data-username]');

                if (statusEl) {
                    statusEl.textContent = participant.isReady ? '✓ Prêt' : '⏳ En attente';
                }
                if (usernameEl) {
                    usernameEl.textContent = participant.userName;
                }
            } else {
                // Add new participant
                this.addParticipantToDOM(participant);
            }
        });

        // Remove participants no longer in the list
        container.querySelectorAll('[data-participant-id]').forEach(el => {
            const pid = parseInt(el.getAttribute('data-participant-id'));
            if (!allParticipantIds.includes(pid)) {
                el.remove();
            }
        });

        // Update start button state
        this.updateStartButtonState();
    }

    addParticipantToDOM(participant) {
        const container = this.participantsListTarget;

        let buttonHTML = '';
        if (participant.playerId === this.playerIdValue) {
            buttonHTML = this.getEditButtonHTML();
        } else if (this.isCreatorValue) {
            buttonHTML = this.getKickButtonHTML(participant.id, participant.userName);
        }

        const participantHTML = `
            <div data-participant-id="${participant.id}" style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; background: var(--white); border: 1px solid var(--gray-200); border-radius: var(--radius-sm);">
                <div style="flex: 1;">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span data-username style="font-weight: 600;">${participant.userName}</span>
                        ${buttonHTML}
                    </div>
                    <div data-status style="font-size: 12px; color: var(--gray-600);">
                        ${participant.isReady ? '✓ Prêt' : '⏳ En attente'}
                    </div>
                </div>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', participantHTML);
    }

    getEditButtonHTML() {
        return `
            <button type="button" data-action="click->username-edit#show" style="background: none; border: none; cursor: pointer; padding: 0.25rem; display: flex; align-items: center; color: var(--gray-600); transition: color 0.2s ease;" title="Modifier le pseudo">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
            </button>
        `;
    }

    getKickButtonHTML(participantId, userName) {
        return `
            <button type="button" data-action="click->multiplayer--room#kickPlayer" data-participant-id="${participantId}" data-username="${userName}" style="background: none; border: none; cursor: pointer; padding: 0.25rem; display: flex; align-items: center; color: var(--gray-600); transition: color 0.2s ease;" title="Retirer ce joueur">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="3 6 5 6 21 6"/>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                    <line x1="10" y1="11" x2="10" y2="17"/>
                    <line x1="14" y1="11" x2="14" y2="17"/>
                </svg>
            </button>
        `;
    }

    updateReadyButtonUI() {
        if (!this.hasReadyBtnTarget) return;

        if (this.currentPlayerReady) {
            this.readyBtnTarget.style.background = 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)';
            this.readyBtnTarget.textContent = '✕ Je ne suis pas prêt';
        } else {
            this.readyBtnTarget.style.background = 'linear-gradient(135deg, var(--success) 0%, #059669 100%)';
            this.readyBtnTarget.textContent = '✓ Je suis prêt';
        }
    }

    updateStartButtonState() {
        if (!this.hasStartBtnTarget) return;

        const allReady = this.checkAllPlayersReady();
        const hasChallengeSelected = this.currentGameState && this.currentGameState.challenge !== null;
        const canStart = allReady && hasChallengeSelected;

        if (canStart) {
            this.startBtnTarget.disabled = false;
            this.startBtnTarget.style.opacity = '1';
            this.startBtnTarget.style.cursor = 'pointer';
            this.startBtnTarget.title = 'Démarrer la partie';
        } else {
            this.startBtnTarget.disabled = true;
            this.startBtnTarget.style.opacity = '0.5';
            this.startBtnTarget.style.cursor = 'not-allowed';
            this.startBtnTarget.title = !hasChallengeSelected
                ? 'Un challenge doit être sélectionné'
                : 'Tous les joueurs doivent être prêts';
        }
    }

    checkAllPlayersReady() {
        const participants = this.participantsListTarget.querySelectorAll('[data-participant-id]');

        if (participants.length === 0) {
            return false;
        }

        for (const participant of participants) {
            const statusEl = participant.querySelector('[data-status]');
            if (statusEl && !statusEl.textContent.includes('Prêt')) {
                return false;
            }
        }

        return true;
    }

    async toggleReady() {
        const newReadyState = !this.currentPlayerReady;

        try {
            const response = await fetch(this.readyUrlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `ready=${newReadyState ? '1' : '0'}`
            });

            const data = await response.json();

            if (data.success) {
                this.currentPlayerReady = newReadyState;
                this.updateReadyButtonUI();
                // Immediate poll
                setTimeout(() => this.pollRoomState(), 500);
            }
        } catch (error) {
            console.error('Error toggling ready:', error);
            alert('Erreur lors de la mise à jour du statut');
        }
    }

    async kickPlayer(event) {
        const button = event.currentTarget;
        const participantId = button.dataset.participantId;

        try {
            const url = this.kickUrlValue.replace('PARTICIPANT_ID', participantId);
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            });

            const data = await response.json();

            if (data.success) {
                // Remove from DOM immediately
                const el = this.participantsListTarget.querySelector(
                    `[data-participant-id="${participantId}"]`
                );
                if (el) {
                    el.remove();
                }
                // Update via polling
                setTimeout(() => this.pollRoomState(), 300);
            } else {
                alert('Erreur: ' + (data.error || 'Impossible de retirer le joueur'));
            }
        } catch (error) {
            console.error('Error kicking player:', error);
            alert('Erreur lors du retrait du joueur');
        }
    }

    async startCountdown() {
        if (!this.hasStartBtnTarget) return;

        this.startBtnTarget.disabled = true;
        this.startBtnTarget.textContent = 'Démarrage...';

        try {
            const response = await fetch(this.startUrlValue, { method: 'POST' });
            const data = await response.json();

            if (data.success) {
                this.dispatchCountdownEvent(data.countdownEndsAt);
            } else {
                alert('Erreur: ' + (data.error || 'Cannot start game'));
                this.startBtnTarget.disabled = false;
                this.startBtnTarget.textContent = '🚀 Démarrer la partie';
            }
        } catch (error) {
            console.error('Error starting countdown:', error);
            alert('Erreur lors du démarrage');
            this.startBtnTarget.disabled = false;
            this.startBtnTarget.textContent = '🚀 Démarrer la partie';
        }
    }

    dispatchCountdownEvent(endsAt) {
        this.dispatch('countdown-start', {
            detail: { endsAt, doStartUrl: this.doStartUrlValue }
        });
    }

    redirectToGame() {
        // TODO: Implement proper game redirect
        // For now, just log
    }

    showKickedModal() {
        if (this.hasKickedModalTarget) {
            this.kickedModalTarget.style.display = 'flex';
        }
    }

    returnToLobby() {
        window.location.href = this.lobbyUrlValue;
    }
}
