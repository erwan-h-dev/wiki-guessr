import { Controller } from '@hotwired/stimulus';

/**
 * Handles opening the username change modal on multiplayer pages
 * Triggered by clicking the player's username
 */
export default class extends Controller {
    static targets = ['modal', 'input'];

    connect() {
        console.log('👤 Username toggle controller connected');
    }

    openModal() {
        if (this.hasModalTarget) {
            // Clear previous error
            const errorEl = this.modalTarget.querySelector('[data-username-modal-target="errorMessage"]');
            if (errorEl) {
                errorEl.style.display = 'none';
            }

            // Reset input
            if (this.hasInputTarget) {
                this.inputTarget.value = this.inputTarget.placeholder || '';
                this.inputTarget.focus();
            }

            // Show modal
            this.modalTarget.showModal();

            console.log('👤 Username modal opened');
        }
    }

    closeModal() {
        if (this.hasModalTarget) {
            this.modalTarget.close();
        }
    }
}
