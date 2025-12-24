import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'results', 'hiddenInput'];
    static values = {
        url: String,
        minChars: { type: Number, default: 2 },
        debounce: { type: Number, default: 300 }
    };

    connect() {
        this.debounceTimeout = null;
        this.selectedIndex = -1;
    }

    disconnect() {
        if (this.debounceTimeout) {
            clearTimeout(this.debounceTimeout);
        }
        this.hideResults();
    }

    search(event) {
        const query = this.inputTarget.value.trim();

        if (query.length < this.minCharsValue) {
            this.hideResults();
            return;
        }

        if (this.debounceTimeout) {
            clearTimeout(this.debounceTimeout);
        }

        this.debounceTimeout = setTimeout(() => {
            this.performSearch(query);
        }, this.debounceValue);
    }

    async performSearch(query) {
        try {
            const url = `${this.urlValue}?q=${encodeURIComponent(query)}`;
            const response = await fetch(url);
            const data = await response.json();

            if (data.results && data.results.length > 0) {
                this.showResults(data.results);
            } else {
                this.hideResults();
            }
        } catch (error) {
            console.error('Search error:', error);
            this.hideResults();
        }
    }

    showResults(results) {
        this.resultsTarget.innerHTML = '';
        this.selectedIndex = -1;

        results.forEach((result, index) => {
            const item = document.createElement('div');
            item.className = 'autocomplete-item';
            item.textContent = result;
            item.dataset.index = index;
            item.dataset.action = 'click->wiki-autocomplete#select mouseenter->wiki-autocomplete#highlight';
            this.resultsTarget.appendChild(item);
        });

        this.resultsTarget.style.display = 'block';
    }

    hideResults() {
        this.resultsTarget.style.display = 'none';
        this.resultsTarget.innerHTML = '';
        this.selectedIndex = -1;
    }

    select(event) {
        const value = event.target.textContent;
        this.inputTarget.value = value;
        if (this.hasHiddenInputTarget) {
            this.hiddenInputTarget.value = value;
        }
        this.hideResults();
        this.inputTarget.focus();
    }

    highlight(event) {
        const items = this.resultsTarget.querySelectorAll('.autocomplete-item');
        items.forEach(item => item.classList.remove('active'));
        event.target.classList.add('active');
        this.selectedIndex = parseInt(event.target.dataset.index);
    }

    navigate(event) {
        const items = this.resultsTarget.querySelectorAll('.autocomplete-item');

        if (items.length === 0) return;

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            this.selectedIndex = Math.min(this.selectedIndex + 1, items.length - 1);
            this.updateHighlight(items);
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            this.selectedIndex = Math.max(this.selectedIndex - 1, -1);
            this.updateHighlight(items);
        } else if (event.key === 'Enter') {
            event.preventDefault();
            if (this.selectedIndex >= 0 && items[this.selectedIndex]) {
                const value = items[this.selectedIndex].textContent;
                this.inputTarget.value = value;
                if (this.hasHiddenInputTarget) {
                    this.hiddenInputTarget.value = value;
                }
                this.hideResults();
            }
        } else if (event.key === 'Escape') {
            this.hideResults();
        }
    }

    updateHighlight(items) {
        items.forEach((item, index) => {
            if (index === this.selectedIndex) {
                item.classList.add('active');
                item.scrollIntoView({ block: 'nearest' });
            } else {
                item.classList.remove('active');
            }
        });
    }

    blur(event) {
        // Delay hiding to allow click events to fire
        setTimeout(() => {
            if (!this.element.contains(document.activeElement)) {
                this.hideResults();
            }
        }, 200);
    }
}
