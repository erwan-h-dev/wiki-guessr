/**
 * Game page Stimulus configuration
 * Only loads game-specific controllers
 */
import { startStimulusApp } from '@symfony/stimulus-bridge';

export const app = startStimulusApp(require.context(
    '@symfony/stimulus-bridge/lazy-controller-loader!../controllers/game',
    true,
    /\.js$/
));
