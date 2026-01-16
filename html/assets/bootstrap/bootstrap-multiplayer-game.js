import { Application } from '@hotwired/stimulus';

// Import all multiplayer controllers
import GameSyncController from '../controllers/multiplayer/game_sync_controller.js';
import ParticipantsOverlayController from '../controllers/multiplayer/participants_overlay_controller.js';
import VictoryFlashController from '../controllers/multiplayer/victory_flash_controller.js';
import FinalScoreboardController from '../controllers/multiplayer/final_scoreboard_controller.js';

// Import existing controllers needed for multiplayer
import WikiHistoryController from '../controllers/game/wiki_history_controller.js';
import TimerController from '../controllers/game/timer_controller.js';

export const application = Application.start();

// Register multiplayer-specific controllers
application.register('multiplayer--game-sync', GameSyncController);
application.register('multiplayer--participants-overlay', ParticipantsOverlayController);
application.register('multiplayer--victory-flash', VictoryFlashController);
application.register('multiplayer--final-scoreboard', FinalScoreboardController);

// Register existing controllers that are needed
application.register('wiki-history', WikiHistoryController);
application.register('timer', TimerController);

// Log that the application is ready
console.log('🎮 Multiplayer Game App initialized');
