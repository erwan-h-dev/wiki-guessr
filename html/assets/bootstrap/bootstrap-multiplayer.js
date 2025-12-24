/**
 * Multiplayer page Stimulus configuration
 * Only loads multiplayer-specific controllers
 */
import { startStimulusApp } from '@symfony/stimulus-bridge';

// Load both multiplayer AND global controllers (like username_modal)
export const app = startStimulusApp(require.context(
    '@symfony/stimulus-bridge/lazy-controller-loader!../controllers/multiplayer',
    true,
    /multiplayer|global/
));

