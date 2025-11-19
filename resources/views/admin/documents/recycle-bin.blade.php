@extends('layouts.app')

@section('title', 'Kelola Dokumen - Admin')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 text-hover">Recycle Bin</h1>
            <p class="text-gray-600 mt-2 text-hover">Di sini daftar dokumen yang sudah dihapus sebelumnya, admin dapat mengembalikan file atau menghapus file dari server.</p>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <div id="alert-container"></div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <form method="GET" action="{{ route('admin.documents.index') }}" class="flex flex-wrap gap-2">
            <div>
                <label for="type" class="block text-sm font-medium text-gray-700 mb-1 text-hover">Jenis</label>
                <select name="type" id="type"
                    class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 hover-sound">
                    <option value="">Semua</option>
                    <option value="publication" {{ request('type') === 'publication' ? 'selected' : '' }}>Publikasi
                    </option>
                    <option value="brs" {{ request('type') === 'brs' ? 'selected' : '' }}>BRS</option>
                </select>
            </div>

            <div class="flex items-end space-x-2">
                <button type="submit"
                    class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-md transition-colors hover-sound">
                    <i class="fas fa-filter mr-2" aria-hidden="true"></i>
                    <span class="text-hover">Filter</span>
                </button>
                <button id="restore-batch" class="button-checked hidden px-4 py-2 ml-auto bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md transition-colors hover-sound">
                    <i class="fas fa-redo mr-2"></i>Restore Terpilih</button>
                <button id="force-delete-batch" class="button-checked hidden px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-md transition-colors hover-sound">
                    <i class="fa-solid fa-trash mr-2"></i> Hapus Terpilih</button>
            </div>
        </form>
    </div>

    <!-- Documents Table -->
    @include('admin.documents.partial-from-recycle-bin')
    
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Wait for a short delay to ensure all scripts are loaded
        setTimeout(() => {
            initializeAdminPlayButtons();
        }, 150);

        function initializeAdminPlayButtons() {
            const adminPlayButtons = document.querySelectorAll('.admin-play-btn');
            // console.log(`✅ Initializing ${adminPlayButtons.length} admin play buttons`);

            if (adminPlayButtons.length === 0) {
                // console.warn('⚠️ No admin play buttons found');
                return;
            }

            adminPlayButtons.forEach((button, index) => {
                // Remove any existing event listeners to prevent duplicates
                const newButton = button.cloneNode(true);
                button.parentNode.replaceChild(newButton, button);

                newButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    try {
                        const documentDataStr = this.dataset.document;
                        if (!documentDataStr) {
                            // console.error(`❌ No document data found in admin table button ${index}`);
                            alert('Data dokumen tidak ditemukan. Silakan refresh halaman.');
                            return;
                        }

                        // console.log(`📄 Raw document data for button ${index}:`, documentDataStr.substring(0, 100) + '...');

                        let documentData;
                        try {
                            documentData = JSON.parse(documentDataStr);
                        } catch (parseError) {
                            // console.error(`❌ Failed to parse document JSON for button ${index}:`, parseError);
                            // console.log('🔍 Problematic JSON:', documentDataStr);
                            alert('Format data dokumen tidak valid. Silakan refresh halaman.');
                            return;
                        }

                        // console.log('🎵 Playing document from admin table:', documentData.title);

                        // Validate document data structure
                        if (!documentData.id || !documentData.title) {
                            // console.error('❌ Invalid document data structure:', documentData);
                            alert('Data dokumen tidak lengkap. Silakan refresh halaman.');
                            return;
                        }

                        // Ensure we have the required indicator object structure
                        if (documentData.indicator_id && !documentData.indicator) {
                            // Find indicator name from the page context if available
                            const indicatorCell = this.closest('tr')?.querySelector(
                                'td:nth-child(3)');
                            const indicatorName = indicatorCell ? indicatorCell.textContent
                                .trim() : 'Unknown';

                            documentData.indicator = {
                                id: documentData.indicator_id,
                                name: indicatorName
                            };
                        }

                        // Check if global play function is available
                        if (typeof window.playDocumentAudio === 'function') {
                            try {
                                window.playDocumentAudio(documentData);
                                // console.log('✅ Successfully called global playDocumentAudio');
                            } catch (playError) {
                                // console.error('❌ Error calling playDocumentAudio:', playError);
                                alert('Gagal memutar audio. Silakan coba lagi.');
                            }
                        } else {
                            // console.error('❌ Global playDocumentAudio function not available');
                            // console.log('🔍 Available window functions:', Object.keys(window).filter(key => key.includes('play')));

                            // Try to create a simple fallback audio player
                            tryFallbackAudioPlay(documentData);
                        }

                    } catch (error) {
                        // console.error('❌ Critical error in admin play button handler:', error);
                        // console.error('🔍 Error stack:', error.stack);
                        alert(
                            'Terjadi kesalahan saat memutar audio. Silakan refresh halaman dan coba lagi.'
                        );
                    }
                });

                // Add visual feedback for button interaction
                newButton.addEventListener('mouseenter', function() {
                    if (!this.disabled) {
                        this.style.transform = 'scale(1.05)';
                        this.style.transition = 'transform 0.2s ease';
                    }
                });

                newButton.addEventListener('mouseleave', function() {
                    this.style.transform = 'scale(1)';
                });

                // Add keyboard support
                newButton.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        this.click();
                    }
                });
            });

            // console.log(`✅ Successfully initialized ${adminPlayButtons.length} admin play buttons`);
        }

        // Fallback audio player for when global system is not available
        function tryFallbackAudioPlay(docData) {
            // console.log('🔄 Attempting fallback audio play for:', docData.title);

            try {
                const audioUrl = `/documents/${docData.id}/audio/mp3/stream`;
                const audio = new Audio(audioUrl);

                audio.addEventListener('loadstart', () => {
                    // console.log('📡 Fallback audio loading started');
                    showSimpleNotification('Memuat audio...', 'info');
                });

                audio.addEventListener('canplay', () => {
                    // console.log('✅ Fallback audio ready to play');
                });

                audio.addEventListener('error', (e) => {
                    // console.error('❌ Fallback audio error:', e);
                    showSimpleNotification('Gagal memuat audio. Periksa koneksi internet Anda.',
                        'error');
                });

                audio.play()
                    .then(() => {
                        // console.log('▶️ Fallback audio playing:', docData.title);
                        showSimpleNotification(`Memutar: ${docData.title}`, 'success');
                    })
                    .catch(err => {
                        // console.error('❌ Fallback audio play failed:', err);
                        alert('Gagal memutar audio. Silakan coba lagi atau refresh halaman.');
                    });

            } catch (error) {
                // console.error('❌ Fallback audio creation failed:', error);
                alert('Sistem audio tidak tersedia. Silakan refresh halaman.');
            }
        }

        // Simple notification system
        function showSimpleNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 px-4 py-2 rounded-lg text-white ${
            type === 'success' ? 'bg-green-600' : 
            type === 'error' ? 'bg-red-600' : 
            'bg-blue-600'
        }`;
            notification.textContent = message;

            document.body.appendChild(notification);

            // Auto remove after 3 seconds
            setTimeout(() => {
                if (document.body.contains(notification)) {
                    notification.style.opacity = '0';
                    notification.style.transition = 'opacity 0.3s ease';
                    setTimeout(() => {
                        if (document.body.contains(notification)) {
                            document.body.removeChild(notification);
                        }
                    }, 300);
                }
            }, 3000);
        }

        // Initialize text hover sounds for admin interface
        function initializeTextHoverSounds() {
            const textElements = document.querySelectorAll('.text-hover, .text-sound');
            textElements.forEach(element => {
                element.addEventListener('mouseenter', function() {
                    if (window.playTextHoverSound && typeof window.playTextHoverSound ===
                        'function') {
                        window.playTextHoverSound();
                    } else {
                        // Fallback hover sound
                        playLocalTextHoverSound();
                    }
                });
            });

            // console.log(`✅ Initialized text hover sounds for ${textElements.length} elements`);
        }

        // Local text hover sound function (fallback)
        function playLocalTextHoverSound() {
            try {
                const audioContext = new(window.AudioContext || window.webkitAudioContext)();
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();

                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);

                oscillator.frequency.setValueAtTime(1000, audioContext.currentTime);
                gainNode.gain.setValueAtTime(0, audioContext.currentTime);
                gainNode.gain.linearRampToValueAtTime(0.03, audioContext.currentTime + 0.01);
                gainNode.gain.exponentialRampToValueAtTime(0.001, audioContext.currentTime + 0.12);

                oscillator.start(audioContext.currentTime);
                oscillator.stop(audioContext.currentTime + 0.12);
            } catch (e) {
                // Silently fail if Web Audio API is not supported
            }
        }

        // Initialize text hover sounds
        initializeTextHoverSounds();

        // Additional initialization for other admin features
        initializeAdminFeatures();

        function initializeAdminFeatures() {
            // Enhanced keyboard navigation for admin interface
            document.addEventListener('keydown', function(e) {
                // Space bar to play/pause when audio is active
                if (e.code === 'Space' && !e.target.matches('input, textarea, select')) {
                    e.preventDefault();
                    const playPauseBtn = document.getElementById('play-pause-main-btn');
                    if (playPauseBtn && !playPauseBtn.disabled) {
                        playPauseBtn.click();
                    }
                }

                // Escape to close audio player
                if (e.code === 'Escape') {
                    const closeBtn = document.getElementById('close-player-btn');
                    if (closeBtn) {
                        closeBtn.click();
                    }
                }

                // Enter to activate focused play button
                if (e.code === 'Enter' && e.target.classList.contains('admin-play-btn')) {
                    e.preventDefault();
                    e.target.click();
                }
            });

            // Add ARIA labels for better accessibility
            document.querySelectorAll('.admin-play-btn').forEach(button => {
                if (!button.getAttribute('aria-label')) {
                    button.setAttribute('aria-label', 'Putar audio dokumen');
                }
            });

            // console.log('✅ Admin features initialized');
        }

        // Global error handler for unhandled audio errors
        window.addEventListener('error', function(e) {
            if (e.message && e.message.includes('getElementById')) {
                // console.error('🚨 getElementById error detected:', e.message);
                // console.error('🔍 Error context:', e.filename, e.lineno, e.colno);

                // Show user-friendly message for audio-related errors (only once)
                if (e.message.includes('playDocumentAudio') && !window.audioErrorNotified) {
                    window.audioErrorNotified = true;
                    setTimeout(() => {
                        showSimpleNotification(
                            'Terjadi masalah dengan sistem audio. Silakan refresh halaman.',
                            'error');
                    }, 100);
                }
            }
        });

        // Periodic health check for audio system
        let healthCheckInterval = setInterval(() => {
            if (typeof window.playDocumentAudio === 'function') {
                // console.log('✅ Audio system health check passed');
                clearInterval(healthCheckInterval); // Stop checking once it's ready
            } else {
                // console.warn('⚠️ Audio system not ready yet...');
            }
        }, 2000);

        // Clear health check after 30 seconds to avoid infinite checking
        setTimeout(() => {
            if (healthCheckInterval) {
                clearInterval(healthCheckInterval);
                // console.log('🔄 Audio system health check timeout');
            }
        }, 30000);

        // Make functions globally available for dynamic content updates
        window.initializeAdminPlayButtons = initializeAdminPlayButtons;
        window.initializeTextHoverSounds = initializeTextHoverSounds;
        window.showSimpleNotification = showSimpleNotification;


    });


    // Backup initialization function that can be called manually
    window.reinitializeAdminAudio = function() {
        // console.log('🔄 Reinitializing admin audio system...');
        setTimeout(() => {
            if (document.querySelector('.admin-play-btn')) {
                const event = new Event('DOMContentLoaded');
                document.dispatchEvent(event);
            }
        }, 500);
    };



    // Debug function to check admin button status
    // window.debugAdminButtons = function() {
    //     const buttons = document.querySelectorAll('.admin-play-btn');
    //     // console.log('🔍 Debug: Found', buttons.length, 'admin play buttons');

    //     buttons.forEach((btn, i) => {
    //         console.log(`Button ${i}:`, {
    //             hasDataset: !!btn.dataset.document,
    //             dataLength: btn.dataset.document ? btn.dataset.document.length : 0,
    //             isVisible: btn.offsetParent !== null,
    //             hasEventListener: btn.onclick !== null,
    //             ariaLabel: btn.getAttribute('aria-label')
    //         });

    //         if (btn.dataset.document) {
    //             try {
    //                 const data = JSON.parse(btn.dataset.document);
    //                 console.log(`Button ${i} data preview:`, {
    //                     id: data.id,
    //                     title: data.title?.substring(0, 30) + '...',
    //                     indicator: data.indicator?.name || 'No indicator'
    //                 });
    //             } catch (e) {
    //                 // console.log(`Button ${i} has invalid JSON data`);
    //             }
    //         }
    //     });

    //     // console.log('Global playDocumentAudio available:', typeof window.playDocumentAudio);
    //     // console.log('Bottom player element:', !!document.getElementById('bottom-audio-player'));
    //     // console.log('Main audio element:', !!document.getElementById('main-audio-element'));
    //     // console.log('Audio error notifications:', window.audioErrorNotified ? 'Shown' : 'None');
    // };

    // Performance monitoring
    window.monitorAdminAudioPerformance = function() {
        const startTime = performance.now();

        // Test button initialization speed
        const buttons = document.querySelectorAll('.admin-play-btn');
        // console.log('🔍 Performance Monitor:');
        // console.log('- Buttons found:', buttons.length);
        // console.log('- Initialization time:', performance.now() - startTime, 'ms');
        // console.log('- Memory usage:', navigator.deviceMemory ? navigator.deviceMemory + 'GB' : 'Unknown');
        // console.log('- Connection:', navigator.connection ? navigator.connection.effectiveType : 'Unknown');

        // Test audio system availability
        const audioSystemReady = typeof window.playDocumentAudio === 'function';
        // console.log('- Audio system ready:', audioSystemReady ? 'Yes' : 'No');

        if (audioSystemReady) {
            console.log('- Audio elements present:', {
                bottomPlayer: !!document.getElementById('bottom-audio-player'),
                mainAudio: !!document.getElementById('main-audio-element'),
                playButton: !!document.getElementById('play-pause-main-btn')
            });
        }
    };
</script>
@endpush
@endsection