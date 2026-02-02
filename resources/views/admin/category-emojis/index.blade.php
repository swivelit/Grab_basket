<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Category Emoji Manager - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .admin-header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            padding: 1rem 0;
            margin-bottom: 2rem;
        }
        
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .category-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            margin-bottom: 1rem;
        }
        
        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 48px rgba(0, 0, 0, 0.15);
        }
        
        .emoji-input {
            font-size: 2rem;
            text-align: center;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.8);
            transition: all 0.3s ease;
        }
        
        .emoji-input:focus {
            border-color: #667eea;
            box-shadow: 0 0 20px rgba(102, 126, 234, 0.3);
            background: white;
        }
        
        .emoji-suggestions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }
        
        .emoji-suggestion {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 5px 10px;
            font-size: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .emoji-suggestion:hover {
            transform: scale(1.1);
            background: linear-gradient(135deg, #764ba2, #667eea);
        }
        
        .save-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            border-radius: 10px;
            color: white;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .save-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        
        .bulk-save-btn {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            border-radius: 10px;
            color: white;
            padding: 15px 30px;
            font-weight: 700;
            font-size: 1.1rem;
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
            box-shadow: 0 8px 32px rgba(40, 167, 69, 0.3);
            transition: all 0.3s ease;
        }
        
        .bulk-save-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 12px 48px rgba(40, 167, 69, 0.4);
        }
        
        .category-name {
            font-weight: 700;
            color: #2c3e50;
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
        }
        
        .category-id {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        
        .preview-emoji {
            font-size: 3rem;
            text-align: center;
            margin: 1rem 0;
            padding: 20px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 15px;
            border: 2px dashed #dee2e6;
        }
        
        .success-message {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 15px 25px;
            border-radius: 10px;
            box-shadow: 0 8px 32px rgba(40, 167, 69, 0.3);
            display: none;
        }
        
        .search-box {
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 15px;
            padding: 12px 20px;
            margin-bottom: 2rem;
            backdrop-filter: blur(10px);
        }
        
        .search-box:focus {
            border-color: #667eea;
            box-shadow: 0 0 20px rgba(102, 126, 234, 0.3);
            background: white;
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="admin-container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="text-white mb-0">
                        <i class="bi bi-emoji-smile"></i>
                        Category Emoji Manager
                    </h1>
                    <p class="text-white-50 mb-0">Manage emojis for Shop by Category section</p>
                </div>
                <a href="{{ route('home') }}" class="btn btn-outline-light">
                    <i class="bi bi-arrow-left"></i> Back to Site
                </a>
            </div>
        </div>
    </div>

    <div class="admin-container">
        <div class="row">
            <div class="col-12">
                <input type="text" class="form-control search-box" id="searchCategories" 
                       placeholder="🔍 Search categories..." autocomplete="off">
            </div>
        </div>

        <div class="row" id="categoriesContainer">
            @foreach($categories as $category)
                <div class="col-lg-4 col-md-6 category-item" data-name="{{ strtolower($category->name) }}">
                    <div class="category-card">
                        <div class="card-body p-4">
                            <div class="category-name">{{ $category->name }}</div>
                            <div class="category-id">ID: {{ $category->id }}</div>
                            
                            <div class="preview-emoji" id="preview-{{ $category->id }}">
                                {{ $category->emoji ?: '🛍️' }}
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Emoji:</label>
                                <input type="text" 
                                       class="form-control emoji-input" 
                                       data-category-id="{{ $category->id }}"
                                       data-category-name="{{ $category->name }}"
                                       value="{{ $category->emoji }}"
                                       placeholder="Enter emoji..."
                                       maxlength="10">
                            </div>
                            
                            <div class="emoji-suggestions" id="suggestions-{{ $category->id }}">
                                <!-- Suggestions will be loaded here -->
                            </div>
                            
                            <div class="d-grid gap-2 mt-3">
                                <button class="btn save-btn" onclick="saveEmoji({{ $category->id }})">
                                    <i class="bi bi-check-circle"></i> Save
                                </button>
                                <button class="btn btn-outline-secondary" onclick="getSuggestions({{ $category->id }}, '{{ $category->name }}')">
                                    <i class="bi bi-lightbulb"></i> Get Suggestions
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <button class="bulk-save-btn" onclick="bulkSave()" title="Save All Changes">
        <i class="bi bi-cloud-upload"></i> Save All
    </button>

    <div class="success-message" id="successMessage">
        <i class="bi bi-check-circle"></i>
        <span id="messageText"></span>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // CSRF token setup
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // Real-time preview update
        document.addEventListener('DOMContentLoaded', function() {
            const emojiInputs = document.querySelectorAll('.emoji-input');
            
            emojiInputs.forEach(input => {
                input.addEventListener('input', function() {
                    const categoryId = this.dataset.categoryId;
                    const preview = document.getElementById(`preview-${categoryId}`);
                    preview.textContent = this.value || '🛍️';
                });
            });

            // Search functionality
            const searchBox = document.getElementById('searchCategories');
            searchBox.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const categoryItems = document.querySelectorAll('.category-item');
                
                categoryItems.forEach(item => {
                    const categoryName = item.dataset.name;
                    if (categoryName.includes(searchTerm)) {
                        item.style.display = 'block';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        });

        // Save individual emoji
        async function saveEmoji(categoryId) {
            const input = document.querySelector(`[data-category-id="${categoryId}"]`);
            const emoji = input.value;

            if (!emoji.trim()) {
                alert('Please enter an emoji');
                return;
            }

            console.log('Saving emoji:', emoji, 'for category:', categoryId); // Debug log

            try {
                const response = await fetch(`/admin/category-emojis/${categoryId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ emoji: emoji })
                });

                console.log('Response status:', response.status); // Debug log
                const data = await response.json();
                console.log('Response data:', data); // Debug log
                
                if (data.success) {
                    showMessage('Emoji saved successfully! ✨');
                    input.style.background = 'linear-gradient(135deg, #d4edda, #c3e6cb)';
                    setTimeout(() => {
                        input.style.background = '';
                    }, 2000);
                } else {
                    throw new Error(data.message || 'Failed to save emoji');
                }
            } catch (error) {
                console.error('Error saving emoji:', error);
                alert('Error saving emoji: ' + error.message);
            }
        }

        // Get emoji suggestions
        async function getSuggestions(categoryId, categoryName) {
            try {
                console.log('Getting suggestions for:', categoryName); // Debug log
                
                const response = await fetch('/admin/category-emojis/suggestions', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ category_name: categoryName })
                });

                console.log('Suggestions response status:', response.status); // Debug log
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();
                console.log('Suggestions data:', data); // Debug log
                
                const suggestionsContainer = document.getElementById(`suggestions-${categoryId}`);
                
                suggestionsContainer.innerHTML = '';
                
                if (data.suggestions && data.suggestions.length > 0) {
                    data.suggestions.forEach(emoji => {
                        const button = document.createElement('button');
                        button.className = 'emoji-suggestion';
                        button.textContent = emoji;
                        button.onclick = () => selectSuggestion(categoryId, emoji);
                        button.title = `Click to use ${emoji}`;
                        suggestionsContainer.appendChild(button);
                    });
                } else {
                    suggestionsContainer.innerHTML = '<p class="text-muted">No suggestions available</p>';
                }
            } catch (error) {
                console.error('Error getting suggestions:', error);
                alert('Error getting suggestions: ' + error.message);
            }
        }

        // Select suggestion
        function selectSuggestion(categoryId, emoji) {
            const input = document.querySelector(`[data-category-id="${categoryId}"]`);
            const preview = document.getElementById(`preview-${categoryId}`);
            
            input.value = emoji;
            preview.textContent = emoji;
            
            // Highlight the input
            input.style.background = 'linear-gradient(135deg, #fff3cd, #ffeeba)';
            setTimeout(() => {
                input.style.background = '';
            }, 1000);
        }

        // Bulk save all emojis
        async function bulkSave() {
            const inputs = document.querySelectorAll('.emoji-input');
            const emojis = [];

            inputs.forEach(input => {
                if (input.value.trim()) {
                    emojis.push({
                        id: parseInt(input.dataset.categoryId),
                        emoji: input.value.trim()
                    });
                }
            });

            if (emojis.length === 0) {
                alert('No emojis to save');
                return;
            }

            console.log('Bulk saving emojis:', emojis); // Debug log

            try {
                const response = await fetch('/admin/category-emojis/bulk-update', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ emojis: emojis })
                });

                console.log('Bulk response status:', response.status); // Debug log
                const data = await response.json();
                console.log('Bulk response data:', data); // Debug log
                
                if (data.success) {
                    showMessage(`Successfully saved ${emojis.length} emojis! 🎉`);
                    
                    // Visual feedback
                    inputs.forEach(input => {
                        if (input.value.trim()) {
                            input.style.background = 'linear-gradient(135deg, #d4edda, #c3e6cb)';
                        }
                    });
                    
                    setTimeout(() => {
                        inputs.forEach(input => {
                            input.style.background = '';
                        });
                    }, 3000);
                } else {
                    throw new Error(data.message || 'Failed to save emojis');
                }
            } catch (error) {
                console.error('Error bulk saving:', error);
                alert('Error saving emojis: ' + error.message);
            }
        }

        // Show success message
        function showMessage(message) {
            const messageElement = document.getElementById('successMessage');
            const messageText = document.getElementById('messageText');
            
            messageText.textContent = message;
            messageElement.style.display = 'block';
            
            setTimeout(() => {
                messageElement.style.display = 'none';
            }, 4000);
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl+S for bulk save
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                bulkSave();
            }
        });

        // Auto-load suggestions for categories without emojis
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('.emoji-input');
            
            inputs.forEach(input => {
                if (!input.value.trim()) {
                    const categoryId = input.dataset.categoryId;
                    const categoryName = input.dataset.categoryName;
                    getSuggestions(categoryId, categoryName);
                }
            });
        });
    </script>
</body>
</html>