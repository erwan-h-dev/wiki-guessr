/*
 * Global app JavaScript
 *
 * Common entry point loaded on all pages
 * Includes Turbo, Stimulus, and global styles
 * Page-specific bundles (game.js, multiplayer.js) are loaded separately
 */

// Import Turbo for navigation
import '@hotwired/turbo';

// Import Stimulus (global controllers only)
import '../bootstrap/bootstrap-global.js';

// Import global styles
import '../styles/app.css';

console.log('📦 Global bundle loaded');
