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

console.log('✓ Wiki Game loaded!');
