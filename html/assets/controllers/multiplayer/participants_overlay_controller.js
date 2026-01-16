import { Controller } from '@hotwired/stimulus';

/**
 * Gère l'affichage et la mise à jour de la liste des participants
 * Trie les participants: terminés (par position) puis en cours (par pages desc)
 * Interpole les timers localement entre les mises à jour du serveur
 */
export default class extends Controller {
    static targets = ['list'];

    static values = {
        gameStartedAt: Number,
    };

    connect() {
        console.log('👥 Participants Overlay Controller connected');

        this.participants = [];
        this.timerIntervals = [];

        // Listen for participants update events from game_sync_controller
        this.element.addEventListener('participants-update', (e) => {
            this.updateParticipants(e.detail.participants);
        });
    }

    disconnect() {
        // Clear all local timer intervals
        this.timerIntervals.forEach(interval => clearInterval(interval));
        this.timerIntervals = [];
    }

    updateParticipants(participants) {
        this.participants = participants;
        this.renderParticipants();
    }

    renderParticipants() {
        const container = this.listTarget;
        container.innerHTML = '';

        if (this.participants.length === 0) {
            container.innerHTML = '<div class="no-participants">Aucun participant</div>';
            return;
        }

        // Sort participants
        const sorted = this.sortParticipants(this.participants);

        // Render each participant
        sorted.forEach((participant, index) => {
            const card = this.createParticipantCard(participant, index);
            container.appendChild(card);

            // Set up local timer interpolation if not finished
            if (!participant.hasFinished && this.gameStartedAtValue) {
                this.setupLocalTimer(card, participant);
            }
        });
    }

    sortParticipants(participants) {
        return [...participants].sort((a, b) => {
            // Finished participants first, sorted by finish position
            if (a.hasFinished && !b.hasFinished) return -1;
            if (!a.hasFinished && b.hasFinished) return 1;

            if (a.hasFinished && b.hasFinished) {
                return (a.finishPosition || Infinity) - (b.finishPosition || Infinity);
            }

            // In progress: sort by page count descending
            return b.pageCount - a.pageCount;
        });
    }

    createParticipantCard(participant, index) {
        const card = document.createElement('div');
        card.className = 'participant-card';
        card.setAttribute('data-participant-id', participant.id);

        if (participant.playerId === parseInt(document.querySelector('[data-game-sync-player-id-value]')?.dataset.gameSyncPlayerIdValue)) {
            card.classList.add('current-player');
        }

        if (participant.hasFinished) {
            card.classList.add('finished');
        }

        // Check if inactive (no activity in last 30 seconds)
        const lastActivity = participant.lastActivity;
        const now = Math.floor(Date.now() / 1000);
        if (now - lastActivity > 30) {
            card.classList.add('inactive');
        }

        // Build position badge if finished
        let badgeHTML = '';
        if (participant.hasFinished && participant.finishPosition) {
            const position = participant.finishPosition;
            let badgeClass = '';
            let badgeIcon = '';

            if (position === 1) {
                badgeClass = 'first';
                badgeIcon = '🥇';
            } else if (position === 2) {
                badgeClass = 'second';
                badgeIcon = '🥈';
            } else if (position === 3) {
                badgeClass = 'third';
                badgeIcon = '🥉';
            } else {
                badgeIcon = `${position}`;
            }

            badgeHTML = `<span class="participant-position-badge ${badgeClass}">${badgeIcon}</span>`;
        }

        // Build timer display
        const timerClass = participant.hasFinished ? 'participant-timer finished' : 'participant-timer';
        const timerDisplay = this.formatTime(participant.durationSeconds);

        // Build HTML
        card.innerHTML = `
            <div class="participant-header">
                <div class="participant-name">
                    ${badgeHTML}
                    <span>${participant.userName}</span>
                </div>
                <div class="${timerClass}">${timerDisplay}</div>
            </div>

            <div class="participant-stats">
                <div class="participant-stat">
                    <span class="participant-stat-label">Pages:</span>
                    <span class="participant-stat-value">${participant.pageCount}</span>
                </div>
                <div class="participant-stat">
                    <span class="participant-stat-label">Efficacité:</span>
                    <span class="participant-stat-value">${this.calculateEfficiency(participant)}%</span>
                </div>
            </div>

            ${participant.currentPage ? `
                <div class="participant-page">
                    <strong>Actuellement:</strong>
                    ${participant.currentPage}
                </div>
            ` : ''}
        `;

        return card;
    }

    setupLocalTimer(cardElement, participant) {
        // Update timer display locally every second for better UX
        const timerEl = cardElement.querySelector('.participant-timer');
        if (!timerEl) return;

        const updateTimer = () => {
            const now = Math.floor(Date.now() / 1000);
            const elapsed = now - this.gameStartedAtValue;
            const formatted = this.formatTime(elapsed);
            timerEl.textContent = formatted;
        };

        const intervalId = setInterval(updateTimer, 1000);
        this.timerIntervals.push(intervalId);
    }

    formatTime(seconds) {
        const minutes = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return `${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }

    calculateEfficiency(participant) {
        // Efficiency = (pages to reach target / actual pages taken) * 100
        // For now, return a simple calculation based on page count
        // In a real scenario, you'd need the optimal path length
        if (participant.pageCount === 0) return 0;

        // Assuming average efficient path is around 5-6 pages
        const targetPages = 5;
        const efficiency = Math.round((targetPages / participant.pageCount) * 100);
        return Math.min(100, efficiency);
    }
}
