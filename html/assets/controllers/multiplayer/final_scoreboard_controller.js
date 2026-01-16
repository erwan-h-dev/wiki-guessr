import { Controller } from '@hotwired/stimulus';

/**
 * Affiche le scoreboard personnel avec les stats finales du joueur
 * Écouté après sa victoire via l'événement personal-victory-show du game_sync_controller
 */
export default class extends Controller {
    connect() {
        console.log('🏆 Final Scoreboard Controller connected');

        // Listen for personal victory event from game_sync_controller
        this.element.parentElement?.addEventListener('personal-victory-show', (e) => {
            this.showPersonalScoreboard(e.detail.participant, e.detail.gameState);
        });
    }

    showPersonalScoreboard(participant, gameState) {
        this.element.innerHTML = `
            <div class="victory-title">Vous avez terminé! 🎉</div>

            <div class="victory-stats">
                <div class="victory-stat">
                    <span class="victory-stat-label">Temps:</span>
                    <span class="victory-stat-value">${this.formatTime(participant.durationSeconds)}</span>
                </div>

                <div class="victory-stat">
                    <span class="victory-stat-label">Pages visitées:</span>
                    <span class="victory-stat-value">${participant.pageCount}</span>
                </div>

                <div class="victory-stat">
                    <span class="victory-stat-label">Position:</span>
                    <span class="victory-stat-value">#${participant.finishPosition || '-'}</span>
                </div>

                <div class="victory-stat">
                    <span class="victory-stat-label">Efficacité:</span>
                    <span class="victory-stat-value">${this.calculateEfficiency(participant)}%</span>
                </div>
            </div>

            <button class="view-ranking-btn" data-action="click->multiplayer--final-scoreboard#viewRanking">
                Voir le classement complet
            </button>
        `;
    }

    viewRanking() {
        // Scroll to full results page or navigate
        // This could be handled by a separate navigation
        const resultsUrl = document.querySelector('[data-game-sync-results-url-value]')?.dataset.gameSyncResultsUrlValue;
        if (resultsUrl) {
            window.location.href = resultsUrl;
        }
    }

    formatTime(seconds) {
        const minutes = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return `${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }

    calculateEfficiency(participant) {
        // Efficiency = (optimal pages / actual pages taken) * 100
        // Assuming optimal path is around 5 pages on average
        if (participant.pageCount === 0) return 0;

        const optimalPages = 5;
        const efficiency = Math.round((optimalPages / participant.pageCount) * 100);
        return Math.min(100, efficiency);
    }
}
