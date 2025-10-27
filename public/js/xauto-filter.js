// Auto Filter Script untuk Search, Publikasi, dan BRS
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search');
    const queryInput = document.getElementById('query');
    const typeSelect = document.getElementById('type');
    const yearSelect = document.getElementById('year');
    const indicatorSelect = document.getElementById('indicator');
    const filterForm = document.querySelector('form[method="GET"]');
    
    let debounceTimer;
    let isSubmitting = false;
    let currentPage = 1;

    // Function untuk update URL tanpa reload
    function updateURLWithoutReload(url) {
        const newURL = new URL(url, window.location.origin);
        window.history.pushState({ path: newURL.href }, '', newURL.href);
    }

    // Function untuk fetch dan update grid content
    async function fetchAndUpdateGrid(url) {
        try {
            showLoadingIndicator();
            
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            
            const html = await response.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            
            // Update grid content
            updateGridContent(doc);
            
            // Update results count
            updateResultsCount(doc);
            
            // Update pagination
            updatePagination(doc);
            
            // Update URL
            updateURLWithoutReload(url);
            
            hideLoadingIndicator();
            
            console.log('✅ Grid updated successfully');
            
        } catch (error) {
            console.error('❌ Failed to update grid:', error);
            hideLoadingIndicator();
            
            // Fallback to full page reload
            window.location.href = url;
        }
    }

    // Function untuk update grid content
    function updateGridContent(doc) {
        const currentGrid = document.querySelector('.grid.grid-cols-1');
        const newGrid = doc.querySelector('.grid.grid-cols-1');
        
        if (currentGrid && newGrid) {
            // Preserve existing audio players state
            const playingAudio = document.querySelector('audio:not([paused])');
            
            currentGrid.innerHTML = newGrid.innerHTML;
            
            // Re-initialize hover effects and play buttons
            initializeDocumentCards();
            
            // Preserve audio if it was playing
            if (playingAudio && !playingAudio.paused) {
                console.log('🎵 Preserving audio playback during grid update');
            }
            
            announceToScreenReader('Hasil pencarian diperbarui');
        } else {
            // Handle no results case
            const noResultsSection = doc.querySelector('.text-center.py-16, .text-center.py-12');
            if (noResultsSection && currentGrid) {
                currentGrid.parentElement.innerHTML = noResultsSection.outerHTML;
            }
        }
    }

    // Function untuk update results count
    function updateResultsCount(doc) {
        const currentCount = document.querySelector('.mb-6 p.text-gray-600');
        const newCount = doc.querySelector('.mb-6 p.text-gray-600');
        
        if (currentCount && newCount) {
            currentCount.textContent = newCount.textContent;
        }
    }

    // Function untuk update pagination
    function updatePagination(doc) {
        const currentPagination = document.querySelector('.flex.justify-center');
        const newPagination = doc.querySelector('.flex.justify-center');
        
        if (currentPagination && newPagination) {
            currentPagination.innerHTML = newPagination.innerHTML;
            initializePaginationLinks();
        } else if (currentPagination && !newPagination) {
            // Remove pagination if no longer needed
            currentPagination.remove();
        }
    }

    // Function untuk initialize document cards
    function initializeDocumentCards() {
        // Re-attach event listeners for play buttons
        document.querySelectorAll('[onclick*="playDocumentAudio"]').forEach(button => {
            const onclickAttr = button.getAttribute('onclick');
            const match = onclickAttr.match(/playDocumentAudio\((.*?)\)/);
            
            if (match) {
                button.removeAttribute('onclick');
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    try {
                        const documentData = JSON.parse(match[1]);
                        if (window.playDocumentAudio) {
                            window.playDocumentAudio(documentData);
                        }
                    } catch (error) {
                        console.error('❌ Failed to parse document data:', error);
                    }
                });
            }
        });

        // Re-attach hover effects
        document.querySelectorAll('.hover-sound').forEach(element => {
            element.addEventListener('mouseenter', function() {
                if (window.playTextHoverSound) {
                    window.playTextHoverSound();
                }
            });
        });
    }

    // Function untuk initialize pagination links
    function initializePaginationLinks() {
        document.querySelectorAll('.flex.justify-center a[href]').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const url = this.href;
                fetchAndUpdateGrid(url);
            });
        });
    }

    // Debounce function
    function debounce(func, wait) {
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(debounceTimer);
                func(...args);
            };
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(later, wait);
        };
    }

    // Function untuk auto submit dengan AJAX
    function autoSubmitForm() {
        if (isSubmitting) return;
        
        isSubmitting = true;
        
        // Build URL with current form data
        const formData = new FormData(filterForm);
        const params = new URLSearchParams();
        
        for (let [key, value] of formData.entries()) {
            if (value.trim() !== '') {
                params.append(key, value);
            }
        }
        
        // Add page=1 for new searches
        params.set('page', '1');
        currentPage = 1;
        
        const currentURL = new URL(window.location);
        const newURL = `${currentURL.pathname}?${params.toString()}`;
        
        // Fetch and update grid
        fetchAndUpdateGrid(newURL);
        
        // Reset flag after delay
        setTimeout(() => {
            isSubmitting = false;
        }, 1000);
    }

    // Show loading indicator
    function showLoadingIndicator() {
        hideLoadingIndicator(); // Remove existing first
        
        const indicator = document.createElement('div');
        indicator.id = 'filter-loading';
        indicator.className = 'fixed top-20 right-4 bg-blue-600 text-white px-4 py-2 rounded-lg shadow-lg z-50 flex items-center space-x-2';
        indicator.innerHTML = `
            <i class="fas fa-spinner fa-spin" aria-hidden="true"></i>
            <span class="text-sound">Memperbarui hasil...</span>
        `;
        
        document.body.appendChild(indicator);
    }

    // Hide loading indicator
    function hideLoadingIndicator() {
        const existing = document.getElementById('filter-loading');
        if (existing) {
            existing.remove();
        }
    }

    // Debounced auto submit function
    const debouncedAutoSubmit = debounce(autoSubmitForm, 800);

    // Event listeners untuk search input
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            debouncedAutoSubmit();
            announceToScreenReader('Filter pencarian diperbarui');
        });
        
        searchInput.addEventListener('paste', function() {
            setTimeout(debouncedAutoSubmit, 100);
        });
    }

    // Event listeners untuk query input (di halaman search)
    if (queryInput) {
        queryInput.addEventListener('input', function() {
            debouncedAutoSubmit();
            announceToScreenReader('Kata kunci pencarian diperbarui');
        });
        
        queryInput.addEventListener('paste', function() {
            setTimeout(debouncedAutoSubmit, 100);
        });
    }

    // Event listeners untuk dropdown selects
    if (typeSelect) {
        typeSelect.addEventListener('change', function() {
            const selectedText = this.options[this.selectedIndex].text;
            autoSubmitForm();
            announceToScreenReader(`Filter jenis dokumen diubah ke: ${selectedText}`);
        });
    }

    if (yearSelect) {
        yearSelect.addEventListener('change', function() {
            const selectedValue = this.value;
            autoSubmitForm();
            announceToScreenReader(`Filter tahun diubah ke: ${selectedValue || 'Semua tahun'}`);
        });
    }

    if (indicatorSelect) {
        indicatorSelect.addEventListener('change', function() {
            const selectedText = this.options[this.selectedIndex].text;
            autoSubmitForm();
            announceToScreenReader(`Filter indikator diubah ke: ${selectedText}`);
        });
    }

    // Prevent form submission jika sudah auto-submitting
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault(); // Always prevent default form submission
            
            if (!isSubmitting) {
                autoSubmitForm();
            }
            
            return false;
        });
    }

    // Clear filters functionality
    function addClearFiltersButton() {
        const hasActiveFilters = (searchInput && searchInput.value) ||
                                 (queryInput && queryInput.value) ||
                                 (typeSelect && typeSelect.value) ||
                                 (yearSelect && yearSelect.value) ||
                                 (indicatorSelect && indicatorSelect.value);

        if (hasActiveFilters && filterForm) {
            const existingClearBtn = document.getElementById('clear-filters-btn');
            if (existingClearBtn) return;

            const clearBtn = document.createElement('button');
            clearBtn.id = 'clear-filters-btn';
            clearBtn.type = 'button';
            clearBtn.className = 'ml-2 px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white text-sm font-medium rounded-lg transition-colors hover-sound';
            clearBtn.innerHTML = '<i class="fas fa-times mr-2" aria-hidden="true"></i><span class="text-sound">Reset Filter</span>';
            
            clearBtn.addEventListener('click', function() {
                // Clear all filter values
                if (searchInput) searchInput.value = '';
                if (queryInput) queryInput.value = '';
                if (typeSelect) typeSelect.selectedIndex = 0;
                if (yearSelect) yearSelect.selectedIndex = 0;
                if (indicatorSelect) indicatorSelect.selectedIndex = 0;
                
                // Submit form
                autoSubmitForm();
                announceToScreenReader('Semua filter telah direset');
            });

            const submitButton = filterForm.querySelector('button[type="submit"]');
            if (submitButton && submitButton.parentNode) {
                submitButton.parentNode.appendChild(clearBtn);
            }
        }
    }

    // Real-time search suggestions
    if (searchInput || queryInput) {
        const input = searchInput || queryInput;
        let suggestionsContainer;

        input.addEventListener('focus', function() {
            if (!suggestionsContainer) {
                suggestionsContainer = document.createElement('div');
                suggestionsContainer.className = 'absolute top-full left-0 right-0 bg-white border border-gray-300 rounded-b-lg shadow-lg z-10 hidden';
                
                const inputContainer = this.closest('.relative') || this.parentNode;
                if (!inputContainer.classList.contains('relative')) {
                    inputContainer.style.position = 'relative';
                }
                inputContainer.appendChild(suggestionsContainer);
            }
        });

        input.addEventListener('input', function() {
            const query = this.value.trim();
            
            if (query.length >= 2 && suggestionsContainer) {
                fetch(`/api/search-suggestions?q=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(suggestions => {
                        if (suggestions.length > 0) {
                            suggestionsContainer.innerHTML = suggestions.map(suggestion => 
                                `<div class="px-4 py-2 hover:bg-gray-100 cursor-pointer text-sm suggestion-item text-sound" data-suggestion="${suggestion}">
                                    <i class="fas fa-search text-gray-400 mr-2" aria-hidden="true"></i>
                                    ${suggestion}
                                </div>`
                            ).join('');
                            
                            suggestionsContainer.classList.remove('hidden');
                            
                            // Add click handlers
                            suggestionsContainer.querySelectorAll('.suggestion-item').forEach(item => {
                                item.addEventListener('click', function() {
                                    input.value = this.dataset.suggestion;
                                    suggestionsContainer.classList.add('hidden');
                                    debouncedAutoSubmit();
                                });
                            });
                        } else {
                            suggestionsContainer.classList.add('hidden');
                        }
                    })
                    .catch(() => {
                        suggestionsContainer.classList.add('hidden');
                    });
            } else if (suggestionsContainer) {
                suggestionsContainer.classList.add('hidden');
            }
        });

        // Hide suggestions when clicking outside
        document.addEventListener('click', function(e) {
            if (suggestionsContainer && !input.contains(e.target) && !suggestionsContainer.contains(e.target)) {
                suggestionsContainer.classList.add('hidden');
            }
        });
    }

    // Keyboard shortcuts untuk filter
    document.addEventListener('keydown', function(e) {
        // Skip jika user sedang mengetik di input
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') {
            return;
        }

        // Ctrl + F = focus ke search input
        if (e.ctrlKey && e.key === 'f') {
            e.preventDefault();
            const focusInput = searchInput || queryInput;
            if (focusInput) {
                focusInput.focus();
                announceToScreenReader('Fokus ke kolom pencarian');
            }
        }

        // Escape = clear all filters
        if (e.key === 'Escape') {
            if (searchInput && searchInput.value) {
                searchInput.value = '';
                debouncedAutoSubmit();
                announceToScreenReader('Pencarian dikosongkan');
            } else if (queryInput && queryInput.value) {
                queryInput.value = '';
                debouncedAutoSubmit();
                announceToScreenReader('Kata kunci dikosongkan');
            }
        }
    });

    // Browser back/forward button handling
    window.addEventListener('popstate', function(e) {
        if (e.state && e.state.path) {
            fetchAndUpdateGrid(e.state.path);
        } else {
            // Fallback: reload page for back button
            window.location.reload();
        }
    });

    // Helper function untuk screen reader announcements
    function announceToScreenReader(message) {
        const announcement = document.createElement('div');
        announcement.setAttribute('aria-live', 'polite');
        announcement.setAttribute('aria-atomic', 'true');
        announcement.className = 'sr-only';
        announcement.textContent = message;
        document.body.appendChild(announcement);
        
        setTimeout(() => {
            if (document.body.contains(announcement)) {
                document.body.removeChild(announcement);
            }
        }, 1000);
    }

    // Initialize pada page load
    initializeDocumentCards();
    initializePaginationLinks();
    addClearFiltersButton();

    // Show filter status dalam URL
    function showFilterStatus() {
        const url = new URL(window.location);
        const params = new URLSearchParams(url.search);
        const activeFilters = [];

        if (params.get('search')) activeFilters.push(`Pencarian: "${params.get('search')}"`);
        if (params.get('query')) activeFilters.push(`Kata kunci: "${params.get('query')}"`);
        if (params.get('type')) {
            const typeText = params.get('type') === 'publication' ? 'Publikasi' : 'BRS';
            activeFilters.push(`Jenis: ${typeText}`);
        }
        if (params.get('year')) activeFilters.push(`Tahun: ${params.get('year')}`);
        if (params.get('indicator')) {
            const indicatorElement = document.querySelector(`option[value="${params.get('indicator')}"]`);
            if (indicatorElement) {
                activeFilters.push(`Indikator: ${indicatorElement.textContent}`);
            }
        }

        if (activeFilters.length > 0) {
            announceToScreenReader(`Filter aktif: ${activeFilters.join(', ')}`);
        }
    }

    // Show status on page load
    showFilterStatus();

    console.log('🔍 Enhanced Auto Filter System initialized - No page reload mode');
});