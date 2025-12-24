/*
 * Game page JavaScript
 *
 * Entry point for game/gameplay pages
 * Includes game-specific controllers and styles
 */

// Import Turbo & Stimulus (game controllers only)
import '@hotwired/turbo';
import '../bootstrap/bootstrap-game.js';

// Import game-specific styles
import '../styles/app.css';
import '../styles/game.css';
import '../styles/wiki.css';
import '../styles/popover.css';

// 🎯 Block Ctrl+F during gameplay
document.addEventListener('DOMContentLoaded', () => {
    const isGamePage = document.getElementById('wiki-content');

    if (isGamePage) {
        document.addEventListener('keydown', (event) => {
            if ((event.ctrlKey || event.metaKey) && (event.key.toLowerCase() === 'f' || event.key.toLowerCase() === 'g')) {
                event.preventDefault();
                event.stopPropagation();
                return false;
            }
        });
    }
});

console.log('🎮 Game bundle loaded');
