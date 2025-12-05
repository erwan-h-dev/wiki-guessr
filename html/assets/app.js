/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)

// Importe Turbo (pas besoin de CDN !)
import '@hotwired/turbo';

// Importe Stimulus
import './bootstrap.js';

// Importe les styles
import './styles/app.css';
import './styles/game.css';
import './styles/wiki.css';
import './styles/popover.css';

// 🎯 Bloquer Ctrl+F
document.addEventListener('DOMContentLoaded', () => {

    // Vérifier si on est sur une page de jeu
    const isGamePage = document.getElementById('wiki-content')

    if (isGamePage) {
        document.addEventListener('keydown', (event) => {
            // Détecter Ctrl+F ou Cmd+F
            if ((event.ctrlKey || event.metaKey) && (event.key.toLowerCase() === 'f' || event.key.toLowerCase() === 'g')) {
                event.preventDefault();
                event.stopPropagation();
                console.log('🔍 Recherche bloquée pendant le jeu !');
                return false;
            }
        });
    }
});
