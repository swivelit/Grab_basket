/**
 * Zepto/Blinkit Style Instant Search
 * Provides real-time search with autocomplete, instant suggestions, and fast product loading
 */

class ZeptoStyleSearch {
    constructor() {
        this.searchInput = null;
        this.searchForm = null;
        this.suggestionsContainer = null;
        this.instantResultsContainer = null;
        
        this.searchTimeout = null;
        this.currentRequest = null;
        this.cache = new Map();
        
        this.init();
    }
    
    init() {
        // Find search inputs (multiple possible IDs)
        const searchSelectors = [
            '#search-input',
            'input[name="q"]',
            '.search-input',
            '.search-bar-modern input'
        ];
        
        for (const selector of searchSelectors) {
            this.searchInput = document.querySelector(selector);
            if (this.searchInput) break;
        }
        
        if (!this.searchInput) {
            console.log('Search input not found');
            return;
        }
        
        this.searchForm = this.searchInput.closest('form');
        this.createSuggestionsUI();
        this.bindEvents();
        
        console.log('Zepto-style search initialized');
    }
    
    createSuggestionsUI() {
        // Create suggestions dropdown
        this.suggestionsContainer = document.createElement('div');
        this.suggestionsContainer.id = 'zepto-search-suggestions';
        this.suggestionsContainer.className = 'zepto-suggestions';
        this.suggestionsContainer.style.cssText = `
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: none;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.12);
            z-index: 1000;
            max-height: 400px;
            overflow-y: auto;
            display: none;
            margin-top: 4px;
        `;
        
        // Make search input container relative
        const container = this.searchInput.parentElement;
        if (container) {
            container.style.position = 'relative';
            container.appendChild(this.suggestionsContainer);
        }
        
        // Create instant results overlay (for mobile)
        this.instantResultsContainer = document.createElement('div');
        this.instantResultsContainer.id = 'zepto-instant-results';
        this.instantResultsContainer.className = 'zepto-instant-results';
        this.instantResultsContainer.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: white;
            z-index: 2000;
            display: none;
            overflow-y: auto;
        `;
        
        document.body.appendChild(this.instantResultsContainer);
    }
    
    bindEvents() {
        // Search input events
        this.searchInput.addEventListener('input', this.handleSearchInput.bind(this));
        this.searchInput.addEventListener('focus', this.handleSearchFocus.bind(this));
        this.searchInput.addEventListener('blur', this.handleSearchBlur.bind(this));
        this.searchInput.addEventListener('keydown', this.handleKeyDown.bind(this));
        
        // Form submission
        if (this.searchForm) {
            this.searchForm.addEventListener('submit', this.handleFormSubmit.bind(this));
        }
        
        // Click outside to close
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.zepto-suggestions') && 
                !e.target.closest('.search-bar-modern') &&
                e.target !== this.searchInput) {
                this.hideSuggestions();
            }
        });
    }
    
    handleSearchInput(event) {
        const query = event.target.value.trim();
        
        // Clear previous timeout
        clearTimeout(this.searchTimeout);
        
        // Cancel previous request
        if (this.currentRequest) {
            this.currentRequest.abort();
        }
        
        if (query.length < 2) {
            this.hideSuggestions();
            return;
        }
        
        // Debounce search requests (faster than regular search)
        this.searchTimeout = setTimeout(() => {
            this.fetchInstantResults(query);
        }, 200); // 200ms debounce for instant feel
    }
    
    async fetchInstantResults(query) {
        try {
            // Check cache first
            const cacheKey = `instant_${query.toLowerCase()}`;
            if (this.cache.has(cacheKey)) {
                this.displayInstantResults(this.cache.get(cacheKey), query);
                return;
            }
            
            // Show loading state
            this.showLoadingState();
            
            // Create AbortController for request cancellation
            const controller = new AbortController();
            this.currentRequest = controller;
            
            const response = await fetch(`/api/search/instant?q=${encodeURIComponent(query)}`, {
                signal: controller.signal,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            
            const data = await response.json();
            
            // Cache the results
            this.cache.set(cacheKey, data);
            
            // Display results
            this.displayInstantResults(data, query);
            
        } catch (error) {
            if (error.name === 'AbortError') {
                return; // Request was cancelled
            }
            
            console.error('Instant search error:', error);
            this.showErrorState();
        } finally {
            this.currentRequest = null;
        }
    }
    
    displayInstantResults(data, query) {
        if (!data.products && !data.categories) {
            this.hideSuggestions();
            return;
        }
        
        let html = '';
        
        // Popular suggestions
        if (data.suggestions && data.suggestions.length > 0) {
            html += `
                <div class="suggestion-section">
                    <div class="suggestion-header">
                        <i class="bi bi-clock-history"></i> Popular searches
                    </div>`;
            
            data.suggestions.forEach(suggestion => {
                html += `
                    <div class="suggestion-item" onclick="window.zeptoSearch.selectSuggestion('${suggestion}')">
                        <i class="bi bi-search"></i>
                        <span>${this.highlightMatch(suggestion, query)}</span>
                    </div>`;
            });
            
            html += '</div>';
        }
        
        // Categories
        if (data.categories && data.categories.length > 0) {
            html += `
                <div class="suggestion-section">
                    <div class="suggestion-header">
                        <i class="bi bi-grid-3x3-gap"></i> Categories
                    </div>`;
            
            data.categories.forEach(category => {
                html += `
                    <div class="suggestion-item" onclick="window.zeptoSearch.navigateToProduct('${category.url}', event)">
                        <span class="category-emoji">${category.emoji}</span>
                        <span>${this.highlightMatch(category.name, query)}</span>
                    </div>`;
            });
            
            html += '</div>';
        }
        
        // Products
        if (data.products && data.products.length > 0) {
            html += `
                <div class="suggestion-section">
                    <div class="suggestion-header">
                        <i class="bi bi-box"></i> Products
                    </div>`;
            
            data.products.forEach(product => {
                const discountBadge = product.discount > 0 ? 
                    `<span class="discount-badge">-${Math.round(product.discount)}%</span>` : '';
                
                const stockStatus = product.in_stock ? 
                    '<span class="stock-status in-stock">In Stock</span>' : 
                    '<span class="stock-status out-stock">Out of Stock</span>';
                
                html += `
                    <div class="product-suggestion" onclick="window.zeptoSearch.navigateToProduct('${product.url}', event)">
                        <div class="product-image">
                            <img src="${product.image}" alt="${product.name}" 
                                 onerror="this.src='/images/placeholder.png'">
                            ${discountBadge}
                        </div>
                        <div class="product-info">
                            <div class="product-name">${this.highlightMatch(product.name, query)}</div>
                            <div class="product-price">₹${product.price}</div>
                            ${stockStatus}
                        </div>
                    </div>`;
            });
            
            html += '</div>';
        }
        
        // Show all results link
        html += `
            <div class="suggestion-section">
                <div class="suggestion-item view-all" onclick="window.zeptoSearch.viewAllResults('${query}')">
                    <i class="bi bi-arrow-right"></i>
                    <span>View all results for "${query}"</span>
                </div>
            </div>`;
        
        this.suggestionsContainer.innerHTML = html;
        this.showSuggestions();
    }
    
    highlightMatch(text, query) {
        if (!query) return text;
        
        const regex = new RegExp(`(${query})`, 'gi');
        return text.replace(regex, '<mark>$1</mark>');
    }
    
    showLoadingState() {
        this.suggestionsContainer.innerHTML = `
            <div class="suggestion-loading">
                <div class="loading-spinner"></div>
                <span>Searching...</span>
            </div>`;
        this.showSuggestions();
    }
    
    showErrorState() {
        this.suggestionsContainer.innerHTML = `
            <div class="suggestion-error">
                <i class="bi bi-exclamation-triangle"></i>
                <span>Search temporarily unavailable</span>
            </div>`;
        this.showSuggestions();
    }
    
    showSuggestions() {
        this.suggestionsContainer.style.display = 'block';
    }
    
    hideSuggestions() {
        this.suggestionsContainer.style.display = 'none';
    }
    
    selectSuggestion(suggestion) {
        this.searchInput.value = suggestion;
        this.hideSuggestions();
        this.searchInput.focus();
    }
    
    viewAllResults(query) {
        // Hide suggestions first
        this.hideSuggestions();
        
        // Add loading state
        document.body.style.cursor = 'wait';
        
        if (this.searchForm) {
            this.searchInput.value = query;
            setTimeout(() => {
                this.searchForm.submit();
            }, 100);
        } else {
            setTimeout(() => {
                window.location.href = `/products?q=${encodeURIComponent(query)}`;
            }, 100);
        }
    }
    
    handleSearchFocus() {
        if (this.searchInput.value.trim().length >= 2) {
            this.fetchInstantResults(this.searchInput.value.trim());
        }
    }
    
    handleSearchBlur() {
        // Delay hiding to allow clicks on suggestions
        setTimeout(() => {
            this.hideSuggestions();
        }, 200);
    }
    
    handleKeyDown(event) {
        const suggestions = this.suggestionsContainer.querySelectorAll('.suggestion-item, .product-suggestion');
        const activeSuggestion = this.suggestionsContainer.querySelector('.active');
        
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
                    activeSuggestion.click();
                }
                break;
                
            case 'Escape':
                this.hideSuggestions();
                break;
        }
    }
    
    navigateSuggestions(suggestions, activeSuggestion, direction) {
        if (!suggestions.length) return;
        
        // Remove current active state
        if (activeSuggestion) {
            activeSuggestion.classList.remove('active');
        }
        
        let nextIndex = 0;
        if (activeSuggestion) {
            const currentIndex = Array.from(suggestions).indexOf(activeSuggestion);
            nextIndex = currentIndex + direction;
        } else if (direction === -1) {
            nextIndex = suggestions.length - 1;
        }
        
        // Handle bounds
        if (nextIndex < 0) nextIndex = suggestions.length - 1;
        if (nextIndex >= suggestions.length) nextIndex = 0;
        
        // Set new active suggestion
        suggestions[nextIndex].classList.add('active');
        suggestions[nextIndex].scrollIntoView({ block: 'nearest' });
    }
    
    handleFormSubmit(event) {
        event.preventDefault();
        this.viewAllResults(this.searchInput.value.trim());
    }
    
    // Smooth navigation method to prevent jumping
    navigateToProduct(url, event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        
        // Hide suggestions first
        this.hideSuggestions();
        
        // Add loading state
        document.body.style.cursor = 'wait';
        
        // Navigate after a small delay to prevent jumping
        setTimeout(() => {
            window.location.href = url;
        }, 100);
    }
}

// Add Zepto-style CSS
const style = document.createElement('style');
style.textContent = `
    .zepto-suggestions {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
    }
    
    .suggestion-section {
        padding: 8px 0;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .suggestion-section:last-child {
        border-bottom: none;
    }
    
    .suggestion-header {
        padding: 8px 16px;
        font-size: 0.85rem;
        font-weight: 600;
        color: #666;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .suggestion-item {
        padding: 12px 16px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 12px;
        transition: background-color 0.2s;
        font-size: 0.9rem;
    }
    
    .suggestion-item:hover,
    .suggestion-item.active {
        background-color: #f8f9fa;
    }
    
    .suggestion-item i {
        color: #28a745;
        font-size: 0.9rem;
    }
    
    .suggestion-item mark {
        background: #fff3cd;
        padding: 2px 4px;
        border-radius: 3px;
        font-weight: 600;
    }
    
    .product-suggestion {
        padding: 12px 16px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 12px;
        transition: background-color 0.2s, transform 0.1s;
        border-radius: 8px;
        margin: 4px 8px;
        user-select: none;
        -webkit-tap-highlight-color: transparent;
    }
    
    .product-suggestion:hover,
    .product-suggestion.active {
        background-color: #f8f9fa;
        transform: translateY(-1px);
    }
    
    .product-suggestion:active {
        transform: translateY(0);
    }
    
    .product-image {
        position: relative;
        width: 50px;
        height: 50px;
        border-radius: 8px;
        overflow: hidden;
        flex-shrink: 0;
    }
    
    .product-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .discount-badge {
        position: absolute;
        top: 2px;
        right: 2px;
        background: #dc3545;
        color: white;
        font-size: 0.7rem;
        padding: 1px 4px;
        border-radius: 3px;
        font-weight: 600;
    }
    
    .product-info {
        flex: 1;
        min-width: 0;
    }
    
    .product-name {
        font-weight: 600;
        font-size: 0.9rem;
        margin-bottom: 4px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    .product-price {
        color: #28a745;
        font-weight: 700;
        font-size: 0.9rem;
    }
    
    .stock-status {
        font-size: 0.75rem;
        padding: 2px 6px;
        border-radius: 10px;
        font-weight: 600;
        margin-top: 2px;
        display: inline-block;
    }
    
    .stock-status.in-stock {
        background: #d4edda;
        color: #155724;
    }
    
    .stock-status.out-stock {
        background: #f8d7da;
        color: #721c24;
    }
    
    .category-emoji {
        font-size: 1.2rem;
    }
    
    .view-all {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white !important;
        margin: 8px;
        border-radius: 8px;
        font-weight: 600;
    }
    
    .view-all:hover {
        background: linear-gradient(135deg, #20c997, #28a745);
    }
    
    .view-all i {
        color: white !important;
    }
    
    .suggestion-loading {
        padding: 20px;
        text-align: center;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        color: #666;
    }
    
    .loading-spinner {
        width: 20px;
        height: 20px;
        border: 2px solid #f3f3f3;
        border-top: 2px solid #28a745;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .suggestion-error {
        padding: 20px;
        text-align: center;
        color: #dc3545;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    
    /* Mobile responsive */
    @media (max-width: 768px) {
        .zepto-suggestions {
            max-height: 300px;
        }
        
        .product-suggestion {
            padding: 10px 12px;
        }
        
        .product-image {
            width: 45px;
            height: 45px;
        }
        
        .suggestion-item {
            padding: 10px 12px;
        }
    }
`;

document.head.appendChild(style);

// Initialize when DOM is loaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        window.zeptoSearch = new ZeptoStyleSearch();
    });
} else {
    window.zeptoSearch = new ZeptoStyleSearch();
}