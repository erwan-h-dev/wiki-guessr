import { startStimulusApp } from '@symfony/stimulus-bridge';

// Lance l'application Stimulus
export const app = startStimulusApp(require.context(
    '@symfony/stimulus-bridge/lazy-controller-loader!./controllers',
    true,
    /\.[jt]sx?$/
));
