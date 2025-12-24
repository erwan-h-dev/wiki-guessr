import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['popover'];
    static values = {
        gameId: Number,
        delay: { type: Number, default: 500 }
    };

    connect() {
        this.popover = null;
        this.hoverTimeout = null;
        this.hideTimeout = null;
        this.currentLink = null;
        this.cache = new Map();

        // Create popover element
        this.createPopover();

        // Listen for mouseover on wiki links
        this.element.addEventListener('mouseover', this.handleMouseOver.bind(this));
        this.element.addEventListener('mouseout', this.handleMouseOut.bind(this));
    }

    disconnect() {
        this.element.removeEventListener('mouseover', this.handleMouseOver.bind(this));
        this.element.removeEventListener('mouseout', this.handleMouseOut.bind(this));

        if (this.popover) {
            this.popover.remove();
        }

        this.clearTimeouts();
    }

    createPopover() {
        this.popover = document.createElement('div');
        this.popover.className = 'wiki-popover';
        this.popover.innerHTML = `
            <div class="wiki-popover-content">
                <div class="wiki-popover-loading">Chargement...</div>
            </div>
        `;
        document.body.appendChild(this.popover);

        // Add event listeners to keep popover visible when hovering over it
        this.popover.addEventListener('mouseenter', () => {
            this.clearHideTimeout();
        });

        this.popover.addEventListener('mouseleave', () => {
            this.hidePopover();
        });
    }

    handleMouseOver(event) {
        const link = event.target.closest('a[data-wiki-link="true"]');

        if (!link) {
            return;
        }

        this.currentLink = link;
        this.clearTimeouts();

        // Extract page title from the link
        const title = this.extractTitleFromLink(link);

        if (!title) {
            return;
        }

        // Show popover after delay
        this.hoverTimeout = setTimeout(() => {
            this.showPopover(link, title);
        }, this.delayValue);
    }

    handleMouseOut(event) {
        const link = event.target.closest('a[data-wiki-link="true"]');

        if (!link) {
            return;
        }

        this.clearHoverTimeout();

        // Hide popover after a short delay
        this.hideTimeout = setTimeout(() => {
            this.hidePopover();
        }, 200);
    }

    extractTitleFromLink(link) {
        const href = link.getAttribute('href');
        if (!href) return null;

        // Extract title from URL like /game/{id}/page/{title}
        const match = href.match(/\/game\/\d+\/page\/(.+)$/);
        return match ? decodeURIComponent(match[1]) : null;
    }

    async showPopover(link, title) {
        // Position popover
        this.positionPopover(link);

        // Show loading state
        this.popover.classList.add('visible');
        this.popover.querySelector('.wiki-popover-content').innerHTML = `
            <div class="wiki-popover-loading">Chargement...</div>
        `;

        // Check cache
        if (this.cache.has(title)) {
            this.displayContent(this.cache.get(title));
            return;
        }

        // Fetch content
        try {
            const response = await fetch(`/game/${this.gameIdValue}/extract/${encodeURIComponent(title)}`);
            const data = await response.json();

            if (data.success) {
                this.cache.set(title, data);
                this.displayContent(data);
            } else {
                this.displayError();
            }
        } catch (error) {
            console.error('Error fetching popover content:', error);
            this.displayError();
        }
    }

    displayContent(data) {
        const content = `
            ${data.thumbnail ? `<img src="${data.thumbnail}" alt="${data.title}" class="wiki-popover-thumbnail">` : ''}
            <div class="wiki-popover-body">
                <h3 class="wiki-popover-title">${this.escapeHtml(data.title)}</h3>
                <p class="wiki-popover-extract">${this.escapeHtml(data.extract)}</p>
            </div>
        `;

        this.popover.querySelector('.wiki-popover-content').innerHTML = content;
    }

    displayError() {
        this.popover.querySelector('.wiki-popover-content').innerHTML = `
            <div class="wiki-popover-error">Impossible de charger l'aperçu</div>
        `;
    }

    positionPopover(link) {
        const rect = link.getBoundingClientRect();
        const popoverWidth = 400;
        const popoverHeight = 250; // Approximate
        const margin = 10;

        let left = rect.left + (rect.width / 2) - (popoverWidth / 2);
        let top = rect.bottom + margin;

        // Adjust if popover goes off screen horizontally
        if (left < margin) {
            left = margin;
        } else if (left + popoverWidth > window.innerWidth - margin) {
            left = window.innerWidth - popoverWidth - margin;
        }

        // Adjust if popover goes off screen vertically (show above instead)
        if (top + popoverHeight > window.innerHeight - margin) {
            top = rect.top - popoverHeight - margin;
        }

        this.popover.style.left = `${left + window.scrollX}px`;
        this.popover.style.top = `${top + window.scrollY}px`;
    }

    hidePopover() {
        if (this.popover) {
            this.popover.classList.remove('visible');
        }
        this.currentLink = null;
    }

    clearTimeouts() {
        this.clearHoverTimeout();
        this.clearHideTimeout();
    }

    clearHoverTimeout() {
        if (this.hoverTimeout) {
            clearTimeout(this.hoverTimeout);
            this.hoverTimeout = null;
        }
    }

    clearHideTimeout() {
        if (this.hideTimeout) {
            clearTimeout(this.hideTimeout);
            this.hideTimeout = null;
        }
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}
