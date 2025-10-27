<!-- Progress Notification Bell -->
<div class="relative">
    <button id="progress-notification-btn" 
            class="relative p-2 text-gray-600 hover:text-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 rounded-md transition-colors hover-sound"
            aria-label="Lihat progress processing">
        <i class="fas fa-bell text-lg" aria-hidden="true"></i>
        <!-- Notification Badge -->
        <span id="progress-badge" 
              class="absolute -top-1 -right-1 h-5 w-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center hidden">
            0
        </span>
        <!-- Processing Indicator -->
        <span id="processing-indicator" 
              class="absolute -top-1 -right-1 h-3 w-3 bg-blue-500 rounded-full animate-pulse hidden"></span>
    </button>

    <!-- Dropdown Panel -->
    <div id="progress-dropdown" 
         class="absolute right-0 mt-2 w-96 bg-white rounded-lg shadow-lg border border-gray-200 z-50 hidden">
        
        <!-- Header -->
        <div class="px-4 py-3 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-900 text-sound">Progress Processing</h3>
                <button id="close-progress-dropdown" 
                        class="text-gray-400 hover:text-gray-600 hover-sound">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
            </div>
            <div id="progress-stats" class="text-xs text-gray-500 mt-1 text-sound">
                <span id="stats-processing">0 sedang diproses</span> • 
                <span id="stats-pending">0 menunggu</span>
            </div>
        </div>

        <!-- Progress Items -->
        <div id="progress-items" class="max-h-80 overflow-y-auto">
            <!-- Progress items will be inserted here -->
        </div>

        <!-- Footer -->
        <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
            <div class="flex justify-between items-center">
                <button id="clear-completed" 
                        class="text-xs text-gray-600 hover:text-gray-800 hover-sound">
                    <i class="fas fa-check mr-1" aria-hidden="true"></i>
                    <span class="text-sound">Hapus yang selesai</span>
                </button>
                <a href="{{ route('admin.documents.index') }}" 
                   class="text-xs text-blue-600 hover:text-blue-800 hover-sound">
                    <span class="text-sound">Lihat semua dokumen →</span>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const progressBtn = document.getElementById('progress-notification-btn');
    const progressDropdown = document.getElementById('progress-dropdown');
    const closeBtn = document.getElementById('close-progress-dropdown');
    const progressBadge = document.getElementById('progress-badge');
    const processingIndicator = document.getElementById('processing-indicator');
    const progressItems = document.getElementById('progress-items');
    const statsProcessing = document.getElementById('stats-processing');
    const statsPending = document.getElementById('stats-pending');
    
    let updateInterval;
    let isOpen = false;

    // Toggle dropdown
    progressBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        isOpen = !isOpen;
        
        if (isOpen) {
            progressDropdown.classList.remove('hidden');
            startProgressUpdates();
        } else {
            progressDropdown.classList.add('hidden');
            stopProgressUpdates();
        }
    });

    // Close dropdown
    closeBtn.addEventListener('click', function() {
        progressDropdown.classList.add('hidden');
        isOpen = false;
        stopProgressUpdates();
    });

    // Close on outside click
    document.addEventListener('click', function(e) {
        if (!progressBtn.contains(e.target) && !progressDropdown.contains(e.target)) {
            progressDropdown.classList.add('hidden');
            isOpen = false;
            stopProgressUpdates();
        }
    });

    function startProgressUpdates() {
        updateProgress(); // Initial update
        updateInterval = setInterval(updateProgress, 2000); // Update every 2 seconds
    }

    function stopProgressUpdates() {
        if (updateInterval) {
            clearInterval(updateInterval);
            updateInterval = null;
        }
    }

    async function updateProgress() {
        try {
            const response = await fetch('{{ route("admin.progress.all") }}');
            const data = await response.json();
            
            updateProgressDisplay(data);
            updateNotificationBadge(data);
            
        } catch (error) {
            // console.error('Failed to fetch progress:', error);
        }
    }

    function updateProgressDisplay(data) {
        const items = data.progress_items || [];
        
        // Update stats
        const processing = items.filter(item => item.status === 'processing' || (item.percentage > 0 && item.percentage < 100)).length;
        const pending = items.filter(item => item.status === 'pending' || item.percentage === 0).length;
        
        statsProcessing.textContent = `${processing} sedang diproses`;
        statsPending.textContent = `${pending} menunggu`;

        // Clear and rebuild items
        progressItems.innerHTML = '';

        if (items.length === 0) {
            progressItems.innerHTML = `
                <div class="px-4 py-8 text-center text-gray-500">
                    <i class="fas fa-check-circle text-3xl mb-2 text-green-500" aria-hidden="true"></i>
                    <p class="text-sm text-sound">Tidak ada dokumen yang sedang diproses</p>
                </div>
            `;
            return;
        }

        items.forEach(item => {
            const itemEl = createProgressItem(item);
            progressItems.appendChild(itemEl);
        });
    }

    function createProgressItem(item) {
        const div = document.createElement('div');
        div.className = 'px-4 py-3 border-b border-gray-100 hover:bg-gray-50';
        div.dataset.documentId = item.document_id;

        const percentage = Math.max(0, Math.min(100, item.percentage || 0));
        const isCompleted = percentage >= 100 || item.status === 'completed';
        const isFailed = percentage < 0 || item.status === 'failed';
        
        let statusIcon = 'fa-clock text-yellow-500';
        let statusText = 'Menunggu';
        
        if (isCompleted) {
            statusIcon = 'fa-check-circle text-green-500';
            statusText = 'Selesai';
        } else if (isFailed) {
            statusIcon = 'fa-exclamation-circle text-red-500';
            statusText = 'Gagal';
        } else if (percentage > 0) {
            statusIcon = 'fa-spinner fa-spin text-blue-500';
            statusText = 'Diproses';
        }

        div.innerHTML = `
            <div class="flex items-start space-x-3">
                <div class="flex-shrink-0 mt-1">
                    <i class="fas ${statusIcon}" aria-hidden="true"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate text-sound">
                        ${item.document_title || 'Dokumen'}
                    </p>
                    <p class="text-xs text-gray-500 mb-2 text-sound">
                        ${item.message || statusText}
                    </p>
                    
                    ${!isFailed ? `
                        <div class="w-full bg-gray-200 rounded-full h-1.5">
                            <div class="bg-blue-600 h-1.5 rounded-full transition-all duration-300" 
                                 style="width: ${Math.max(0, percentage)}%"></div>
                        </div>
                        <div class="flex justify-between items-center mt-1">
                            <span class="text-xs text-gray-500 text-sound">${Math.round(percentage)}%</span>
                            ${item.estimated_remaining_formatted ? `
                                <span class="text-xs text-gray-500 text-sound">
                                    ~${item.estimated_remaining_formatted}
                                </span>
                            ` : ''}
                        </div>
                    ` : `
                        <div class="text-xs text-red-600 bg-red-50 px-2 py-1 rounded text-sound">
                            ${item.message || 'Proses gagal'}
                        </div>
                    `}
                </div>
                
                <div class="flex-shrink-0">
                    <button class="text-gray-400 hover:text-gray-600 hover-sound" 
                            onclick="removeProgressItem(${item.document_id})"
                            title="Hapus dari daftar">
                        <i class="fas fa-times text-xs" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        `;

        return div;
    }

    function updateNotificationBadge(data) {
        const items = data.progress_items || [];
        const activeItems = items.filter(item => 
            item.status === 'processing' || item.status === 'pending' || 
            (item.percentage >= 0 && item.percentage < 100)
        );

        if (activeItems.length > 0) {
            progressBadge.textContent = activeItems.length;
            progressBadge.classList.remove('hidden');
            processingIndicator.classList.remove('hidden');
        } else {
            progressBadge.classList.add('hidden');
            processingIndicator.classList.add('hidden');
        }
    }

    // Global function to remove progress item
    window.removeProgressItem = function(documentId) {
        const itemEl = document.querySelector(`[data-document-id="${documentId}"]`);
        if (itemEl) {
            itemEl.remove();
        }
    };

    // Clear completed items
    document.getElementById('clear-completed').addEventListener('click', function() {
        const completedItems = progressItems.querySelectorAll('[data-document-id]');
        completedItems.forEach(item => {
            const statusIcon = item.querySelector('.fa-check-circle');
            if (statusIcon) {
                item.remove();
            }
        });
    });

    // Auto-update badge even when dropdown is closed
    setInterval(async function() {
        if (!isOpen) {
            try {
                const response = await fetch('{{ route("admin.progress.all") }}');
                const data = await response.json();
                updateNotificationBadge(data);
            } catch (error) {
                // console.error('Failed to fetch progress for badge:', error);
            }
        }
    }, 5000); // Check every 5 seconds
});
</script>