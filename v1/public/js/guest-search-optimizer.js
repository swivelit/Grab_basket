/**
 * Enhanced Guest Search JavaScript
 * Provides autocomplete, instant search, and optimized UX for guest users
 */

class GuestSearchOptimizer {
    constructor() {
        this.searchInput = document.getElementById('search-input');
        this.searchForm = document.getElementById('search-form');
        this.suggestionsContainer = document.getElementById('search-suggestions');
        this.searchResults = document.getElementById('search-results');
        this.loadingIndicator = document.getElementById('search-loading');
        
        this.searchTimeout = null;
        this.currentRequest = null;
        this.cache = new Map();
        
        this.init();
    }
    
    init() {
        if (!this.searchInput) return;
        
        // Create suggestions container if it doesn't exist
        if (!this.suggestionsContainer) {
            this.createSuggestionsContainer();
        }
        
        // Bind events
        this.searchInput.addEventListener('input', this.handleSearchInput.bind(this));
        this.searchInput.addEventListener('focus', this.handleSearchFocus.bind(this));
        this.searchInput.addEventListener('blur', this.handleSearchBlur.bind(this));
        this.searchInput.addEventListener('keydown', this.handleKeyDown.bind(this));
        
        // Form submission
        if (this.searchForm) {
            this.searchForm.addEventListener('submit', this.handleFormSubmit.bind(this));
        }
        
        // Initialize search from URL parameters
        this.initializeFromURL();
    }
    
    createSuggestionsContainer() {
        const container = document.createElement('div');
        container.id = 'search-suggestions';
        container.className = 'search-suggestions';
        container.style.cssText = `
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1000;
            max-height: 300px;
            overflow-y: auto;
            display: none;
        `;
        
        // Insert after search input
        this.searchInput.parentNode.style.position = 'relative';
        this.searchInput.parentNode.appendChild(container);
        this.suggestionsContainer = container;
    }
    
    handleSearchInput(event) {
        const query = event.target.value.trim();
        
        // Clear previous timeout
        if (this.searchTimeout) {
            clearTimeout(this.searchTimeout);
        }
        
        // Cancel previous request
        if (this.currentRequest) {
            this.currentRequest.abort();
        }
        
        if (query.length < 2) {
            this.hideSuggestions();
            return;
        }
        
        // Debounce search requests
        this.searchTimeout = setTimeout(() => {
            this.fetchSuggestions(query);
        }, 300);
    }
    
    async fetchSuggestions(query) {
        // Check cache first
        if (this.cache.has(query)) {
            this.displaySuggestions(this.cache.get(query), query);
            return;
        }
        
        try {
            // Show loading state
            this.showLoadingState();
            
            // Create abort controller for request cancellation
            const controller = new AbortController();
            this.currentRequest = controller;
            
            const response = await fetch(`/api/search/suggestions?q=${encodeURIComponent(query)}`, {
                signal: controller.signal,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            
            const suggestions = await response.json();
            
            // Cache results
            this.cache.set(query, suggestions);
            
            // Display suggestions
            this.displaySuggestions(suggestions, query);
            
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('Search suggestions error:', error);
                this.hideSuggestions();
            }
        } finally {
            this.hideLoadingState();
            this.currentRequest = null;
        }
    }
    
    displaySuggestions(suggestions, query) {
        if (!suggestions || suggestions.length === 0) {
            this.hideSuggestions();
            return;
        }
        
        const html = suggestions.map((suggestion, index) => `
            <div class="suggestion-item" data-index="${index}" data-value="${suggestion}">
                <i class="bi bi-search text-muted me-2"></i>
                ${this.highlightMatch(suggestion, query)}
            </div>
        `).join('');
        
        this.suggestionsContainer.innerHTML = html;
        this.showSuggestions();
        
        // Bind click events
        this.suggestionsContainer.querySelectorAll('.suggestion-item').forEach(item => {
            item.addEventListener('click', () => this.selectSuggestion(item.dataset.value));
        });
    }
    
    highlightMatch(text, query) {
        const regex = new RegExp(`(${query})`, 'gi');
        return text.replace(regex, '<strong>$1</strong>');
    }
    
    showSuggestions() {
        this.suggestionsContainer.style.display = 'block';
    }
    
    hideSuggestions() {
        if (this.suggestionsContainer) {
            this.suggestionsContainer.style.display = 'none';
        }
    }
    
    selectSuggestion(value) {
        this.searchInput.value = value;
        this.hideSuggestions();
        this.performSearch();
    }
    
    handleSearchFocus() {
        const query = this.searchInput.value.trim();
        if (query.length >= 2 && this.cache.has(query)) {
            this.displaySuggestions(this.cache.get(query), query);
        }
    }
    
    handleSearchBlur() {
        // Delay hiding to allow for clicks on suggestions
        setTimeout(() => {
            this.hideSuggestions();
        }, 200);
    }
    
    handleKeyDown(event) {
        const suggestions = this.suggestionsContainer.querySelectorAll('.suggestion-item');
        const activeSuggestion = this.suggestionsContainer.querySelector('.suggestion-item.active');
        
        if (suggestions.length === 0) return;
        
        switch (event.key) {
            case 'ArrowDown':
                event.preventDefault();
                this.navigateSuggestions(suggestions, activeSuggestion, 1);
                break;
            case 'ArrowUp':
                event.preventDefault();
                this.navigateSuggestions(suggestions, activeSuggestion, -1);
                break;
            case 'Enter':
                if (activeSuggestion) {
                    event.preventDefault();
                    this.selectSuggestion(activeSuggestion.dataset.value);
                }
                break;
            case 'Escape':
                this.hideSuggestions();
                break;
        }
    }
    
    navigateSuggestions(suggestions, activeSuggestion, direction) {
        // Remove current active state
        if (activeSuggestion) {
            activeSuggestion.classList.remove('active');
        }
        
        let nextIndex = 0;
        if (activeSuggestion) {
            const currentIndex = parseInt(activeSuggestion.dataset.index);
            nextIndex = currentIndex + direction;
        } else if (direction === -1) {
            nextIndex = suggestions.length - 1;
        }
        
        // Handle bounds
        if (nextIndex < 0) nextIndex = suggestions.length - 1;
        if (nextIndex >= suggestions.length) nextIndex = 0;
        
        // Set new active suggestion
        suggestions[nextIndex].classList.add('active');
    }
    
    handleFormSubmit(event) {
        event.preventDefault();
        this.performSearch();
    }
    
    performSearch() {
        const query = this.searchInput.value.trim();
        if (query.length === 0) {
            return;
        }
        
        // Add loading state to form
        this.showSearchLoading();
        
        // Get current filters
        const formData = new FormData(this.searchForm);
        const params = new URLSearchParams();
        
        // Add search query
        params.append('q', query);
        
        // Add filters
        for (const [key, value] of formData.entries()) {
            if (key !== 'q' && value) {
                params.append(key, value);
            }
        }
        
        // Navigate to search results
        window.location.href = `/products?${params.toString()}`;
    }
    
    showLoadingState() {
        if (this.loadingIndicator) {
            this.loadingIndicator.style.display = 'block';
        }
    }
    
    hideLoadingState() {
        if (this.loadingIndicator) {
            this.loadingIndicator.style.display = 'none';
        }
    }
    
    showSearchLoading() {
        const submitButton = this.searchForm.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.innerHTML = '<i class="bi bi-search"></i> Searching...';
            submitButton.disabled = true;
        }
    }
    
    initializeFromURL() {
        const urlParams = new URLSearchParams(window.location.search);
        const query = urlParams.get('q');
        if (query && this.searchInput) {
            this.searchInput.value = query;
        }
    }
}

// Enhanced product filtering for guests
class GuestProductFilter {
    constructor() {
        this.filterForm = document.getElementById('filter-form');
        this.priceRange = document.getElementById('price-range');
        this.discountRange = document.getElementById('discount-range');
        this.sortSelect = document.getElementById('sort-select');
        
        this.init();
    }
    
    init() {
        // Price range filtering
        if (this.priceRange) {
            this.priceRange.addEventListener('input', this.handlePriceChange.bind(this));
        }
        
        // Discount filtering
        if (this.discountRange) {
            this.discountRange.addEventListener('input', this.handleDiscountChange.bind(this));
        }
        
        // Sort selection
        if (this.sortSelect) {
            this.sortSelect.addEventListener('change', this.handleSortChange.bind(this));
        }
        
        // Filter checkboxes
        document.querySelectorAll('.filter-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', this.handleFilterChange.bind(this));
        });
    }
    
    handlePriceChange(event) {
        const value = event.target.value;
        const display = document.getElementById('price-display');
        if (display) {
            display.textContent = `₹${value}`;
        }
        
        this.debounceFilter();
    }
    
    handleDiscountChange(event) {
        const value = event.target.value;
        const display = document.getElementById('discount-display');
        if (display) {
            display.textContent = `${value}%`;
        }
        
        this.debounceFilter();
    }
    
    handleSortChange() {
        this.applyFilters();
    }
    
    handleFilterChange() {
        this.debounceFilter();
    }
    
    debounceFilter() {
        if (this.filterTimeout) {
            clearTimeout(this.filterTimeout);
        }
        
        this.filterTimeout = setTimeout(() => {
            this.applyFilters();
        }, 500);
    }
    
    applyFilters() {
        const formData = new FormData(this.filterForm);
        const params = new URLSearchParams(window.location.search);
        
        // Update parameters with filter values
        for (const [key, value] of formData.entries()) {
            if (value) {
                params.set(key, value);
            } else {
                params.delete(key);
            }
        }
        
        // Preserve search query
        const currentQuery = new URLSearchParams(window.location.search).get('q');
        if (currentQuery) {
            params.set('q', currentQuery);
        }
        
        // Update URL and refresh results
        window.location.search = params.toString();
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize search optimizer
    new GuestSearchOptimizer();
    
    // Initialize product filter
    new GuestProductFilter();
    
    // Add CSS for suggestions
    const style = document.createElement('style');
    style.textContent = `
        .search-suggestions .suggestion-item {
            padding: 12px 16px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
            transition: background-color 0.2s;
        }
        .search-suggestions .suggestion-item:hover,
        .search-suggestions .suggestion-item.active {
            background-color: #f8f9fa;
        }
        .search-suggestions .suggestion-item:last-child {
            border-bottom: none;
        }
        .search-loading {
            padding: 12px 16px;
            text-align: center;
            color: #6c757d;
        }
        .search-input-container {
            position: relative;
        }
        .search-input-container .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
    `;
    document.head.appendChild(style);
});