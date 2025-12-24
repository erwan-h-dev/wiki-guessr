import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['form', 'input', 'submitBtn', 'errorMessage'];

    connect() {
        console.log('👤 Username modal controller connected');
        this.hideModal();
        // Attach form submit handler
        if (this.hasFormTarget) {
            this.formTarget.addEventListener('submit', (e) => this.handleSubmit(e));
        }
        window.showUsernameModal = () => this.showModal();
        // Show modal if subscriber indicated it should be shown
        const shouldShow = this.element.dataset.usernameModalShowOnLoad === 'true';
        if (shouldShow) {
            this.showModal();
        }
    }

    showModal() {
        console.log('👤 Showing username modal');
        this.element.style.display = 'flex';

        // Auto-focus input
        if (this.hasInputTarget) {
            setTimeout(() => this.inputTarget.focus(), 100);
        }
    }

    hideModal() {
        console.log('👤 Hiding username modal');
        this.element.style.display = 'none';
    }

    disconnect() {
        console.log('👤 Username modal controller disconnected');
    }

    async handleSubmit(event) {
        event.preventDefault();

        const username = this.inputTarget.value.trim();

        // Client-side validation
        if (!username || username.length < 2 || username.length > 50) {
            this.showError('Le nom doit contenir entre 2 et 50 caractères');
            return;
        }

        this.submitBtn.disabled = true;
        this.submitBtn.textContent = 'Enregistrement...';

        try {
            const response = await fetch(this.formTarget.action || '/player/set-username', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    username: username
                })
            });

            const data = await response.json();

            if (data.success) {
                console.log('✅ Username saved successfully');
                // Reload page to apply username and remove modal
                window.location.reload();
            } else {
                this.showError(data.error || 'Une erreur est survenue');
                this.submitBtn.disabled = false;
                this.submitBtn.textContent = 'Confirmer';
            }
        } catch (err) {
            console.error('❌ Error saving username:', err);
            this.showError('Erreur lors de l\'enregistrement');
            this.submitBtn.disabled = false;
            this.submitBtn.textContent = 'Confirmer';
        }
    }

    showError(message) {
        if (this.hasErrorMessageTarget) {
            this.errorMessageTarget.textContent = '⚠️ ' + message;
            this.errorMessageTarget.style.display = 'block';
        } else {
            alert(message);
        }

        this.inputTarget.focus();
    }

    get submitBtn() {
        return this.hasSubmitBtnTarget ? this.submitBtnTarget : this.formTarget.querySelector('button[type="submit"]');
    }
}
