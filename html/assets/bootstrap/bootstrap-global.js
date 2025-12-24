/**
 * Global Stimulus configuration
 * Loads universal controllers for all pages
 */
import { startStimulusApp } from '@symfony/stimulus-bridge';

export const app = startStimulusApp(require.context(
    '@symfony/stimulus-bridge/lazy-controller-loader!../controllers/global',
    true,
    /\.js$/
));

window.Stimulus = app;

console.log('🎮 global bundle loaded');
