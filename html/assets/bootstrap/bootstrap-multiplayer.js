/**
 * Multiplayer page Stimulus configuration
 * Loads multiplayer-specific + global controllers
 */
import { Application } from '@hotwired/stimulus';

// Import multiplayer controllers
import RoomController from '../controllers/multiplayer/room_controller.js';
import CountdownController from '../controllers/multiplayer/countdown_controller.js';

// Import global controllers
import UsernameEditController from '../controllers/global/username_edit_controller.js';
import WikiAutocompleteController from '../controllers/global/wiki_autocomplete_controller.js';

// Start Stimulus application
export const app = Application.start();

// Register multiplayer controllers
app.register('multiplayer--room', RoomController);
app.register('multiplayer--countdown', CountdownController);

// Register global controllers
app.register('username-edit', UsernameEditController);
app.register('wiki-autocomplete', WikiAutocompleteController);

// Make app globally available
window.Stimulus = app;

console.log('✅ Multiplayer Stimulus app started with controllers:', app.controllers);
