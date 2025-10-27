<!-- Spotify UI Audio Player Component -->
<div id="bottom-audio-player" class="fixed bottom-0 left-0 right-0 bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 shadow-lg z-50 hidden transition-all duration-300 ease-in-out">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between py-3 space-x-4">
            
            {{-- Track Info Section --}}
            <div class="flex items-center space-x-3 flex-shrink-0 min-w-0 max-w-xs lg:max-w-md">
                <img src="" alt="Cover" class="track-cover w-12 h-12 rounded-lg object-cover flex-shrink-0 hidden">
                <div class="min-w-0 flex-1">
                    <h4 class="track-title text-sm font-medium text-gray-900 dark:text-white truncate">
                        No audio loaded
                    </h4>
                    <p class="track-subtitle text-xs text-gray-500 dark:text-gray-400 truncate">
                        Select a document to play
                    </p>
                </div>
            </div>

            {{-- Main Controls Section --}}
            <div class="flex items-center justify-center space-x-2 flex-1 max-w-2xl">
                
                {{-- Transport Controls --}}
                <div class="flex items-center space-x-2">
                    <button class="backward-btn p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors" 
                            title="Backward 10s (←)" aria-label="Backward 10 seconds">
                        <i class="fas fa-backward text-gray-600 dark:text-gray-400"></i>
                    </button>
                    
                    <button class="play-pause-btn p-3 rounded-full bg-blue-600 hover:bg-blue-700 text-white transition-colors" 
                            title="Play/Pause (Space)" aria-label="Play or pause audio">
                        <i class="fas fa-play"></i>
                    </button>
                    
                    <button class="forward-btn p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors" 
                            title="Forward 10s (→)" aria-label="Forward 10 seconds">
                        <i class="fas fa-forward text-gray-600 dark:text-gray-400"></i>
                    </button>
                </div>

                {{-- Progress Section --}}
                <div class="flex items-center space-x-3 flex-1 max-w-lg">
                    <span class="current-time text-xs text-gray-500 dark:text-gray-400 font-mono min-w-[40px]">
                        00:00
                    </span>
                    
                    <div class="progress-container relative flex-1 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden cursor-pointer group">
                        <div class="progress-bar h-full bg-blue-600 transition-all duration-100 ease-out" style="width: 0%"></div>
                        <div class="absolute inset-0 hover:bg-blue-600 hover:bg-opacity-10 transition-colors"></div>
                    </div>
                    
                    <span class="duration text-xs text-gray-500 dark:text-gray-400 font-mono min-w-[40px]">
                        00:00
                    </span>
                </div>
            </div>

            {{-- Additional Controls Section --}}
            <div class="flex items-center space-x-2 flex-shrink-0">
                
                {{-- Format Indicator --}}
                <div class="hidden sm:flex items-center space-x-1">
                    <span class="format-indicator text-xs font-semibold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900 px-2 py-1 rounded">
                        MP3
                    </span>
                </div>

                {{-- Speed Control --}}
                <button class="speed-btn hidden md:block px-2 py-1 text-xs font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors"
                        title="Playback speed" aria-label="Change playback speed">
                    <span class="playback-rate">1x</span>
                </button>

                {{-- Volume Control --}}
                <div class="hidden lg:flex items-center space-x-2">
                    <button class="volume-btn p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                            title="Volume (↑/↓)" aria-label="Toggle mute">
                        <i class="fas fa-volume-up text-gray-600 dark:text-gray-400 text-sm"></i>
                    </button>
                    
                    <div class="volume-container relative w-20 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden cursor-pointer">
                        <div class="volume-bar h-full bg-gray-600 dark:bg-gray-400 transition-all duration-100" style="width: 100%"></div>
                    </div>
                </div>

                {{-- Format Switch Buttons (Mobile Hidden) --}}
                {{-- <div class="hidden xl:flex items-center space-x-1">
                    <button class="format-mp3-btn px-2 py-1 text-xs font-medium text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
                            title="Switch to MP3" aria-label="Switch to MP3 format">
                        MP3
                    </button>
                    <span class="text-gray-400">|</span>
                    <button class="format-flac-btn px-2 py-1 text-xs font-medium text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
                            title="Switch to FLAC" aria-label="Switch to FLAC format">
                        FLAC
                    </button>
                </div> --}}

                {{-- Stop/Close Button --}}
                <button class="stop-btn p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors ml-2"
                        title="Stop and close player" aria-label="Stop audio and close player">
                    <i class="fas fa-times text-gray-600 dark:text-gray-400"></i>
                </button>
            </div>

            {{-- Loading Indicator --}}
            <div class="loading-indicator absolute inset-0 bg-white dark:bg-gray-900 bg-opacity-90 flex items-center justify-center hidden">
                <div class="flex items-center space-x-2">
                    <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-blue-600"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Loading audio...</span>
                </div>
            </div>
        </div>

        {{-- Mobile Controls (Expandable) --}}
        <div class="lg:hidden border-t border-gray-200 dark:border-gray-700 pt-2 pb-1">
            <div class="flex items-center justify-center space-x-6">
                
                {{-- Mobile Volume --}}
                <div class="flex items-center space-x-2">
                    <button class="volume-btn p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                        <i class="fas fa-volume-up text-gray-600 dark:text-gray-400 text-sm"></i>
                    </button>
                    <div class="volume-container relative w-16 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden cursor-pointer">
                        <div class="volume-bar h-full bg-gray-600 dark:bg-gray-400 transition-all duration-100" style="width: 100%"></div>
                    </div>
                </div>

                {{-- Mobile Speed --}}
                <button class="speed-btn px-2 py-1 text-xs font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors border border-gray-300 dark:border-gray-600 rounded"
                        aria-label="Change playback speed">
                    <span class="playback-rate">1x</span>
                </button>

                {{-- Mobile Format Switch --}}
                {{-- <div class="flex items-center space-x-1 text-xs">
                    <button class="format-mp3-btn px-2 py-1 font-medium text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors border border-gray-300 dark:border-gray-600 rounded">
                        MP3
                    </button>
                    <button class="format-flac-btn px-2 py-1 font-medium text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors border border-gray-300 dark:border-gray-600 rounded">
                        FLAC
                    </button>
                </div> --}}
            </div>
        </div>
    </div>
</div>

{{-- Audio Element (Hidden) --}}
<audio id="main-audio" preload="metadata" class="hidden">
    Your browser does not support the audio element.
</audio>

{{-- Screen Reader Announcements --}}
<div aria-live="polite" aria-atomic="true" class="sr-only"></div>

{{-- Keyboard Shortcuts Help (Hidden by default) --}}
<div id="keyboard-shortcuts-help" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white dark:bg-gray-900 rounded-lg p-6 max-w-md w-full">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Keyboard Shortcuts</h3>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Play/Pause</span>
                    <kbd class="bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded text-xs">Space</kbd>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Seek backward</span>
                    <kbd class="bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded text-xs">←</kbd>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Seek forward</span>
                    <kbd class="bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded text-xs">→</kbd>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Volume up</span>
                    <kbd class="bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded text-xs">↑</kbd>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Volume down</span>
                    <kbd class="bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded text-xs">↓</kbd>
                </div>
            </div>
            <button onclick="document.getElementById('keyboard-shortcuts-help').classList.add('hidden')" 
                    class="mt-4 w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 transition-colors">
                Close
            </button>
        </div>
    </div>
</div>

<style>
    /* Additional CSS for enhanced audio player */
    #bottom-audio-player.visible {
        transform: translateY(0);
    }

    #bottom-audio-player.hidden {
        transform: translateY(100%);
    }

    .progress-container:hover .progress-bar {
        box-shadow: 0 0 0 1px rgba(59, 130, 246, 0.5);
    }

    .volume-container:hover .volume-bar {
        box-shadow: 0 0 0 1px rgba(107, 114, 128, 0.5);
    }

    /* Accessibility improvements */
    .sr-only {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
    }

    /* Focus styles for keyboard navigation */
    button:focus,
    .progress-container:focus,
    .volume-container:focus {
        outline: 2px solid #3B82F6;
        outline-offset: 2px;
    }

    /* Loading animation */
    .loading-indicator .animate-spin {
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    /* Format indicator active state */
    .format-indicator.active {
        background-color: #3B82F6;
        color: white;
    }

    /* Responsive adjustments */
    @media (max-width: 640px) {
        #bottom-audio-player .flex {
            flex-wrap: wrap;
        }
        
        .track-info {
            order: 1;
            width: 100%;
            margin-bottom: 8px;
        }
        
        .main-controls {
            order: 2;
            width: 100%;
        }
    }

    /* Dark mode adjustments */
    @media (prefers-color-scheme: dark) {
        #bottom-audio-player {
            border-top-color: #374151;
        }
    }

    /* High contrast mode support */
    @media (prefers-contrast: high) {
        .progress-bar {
            border: 1px solid;
        }
        
        .volume-bar {
            border: 1px solid;
        }
        
        button {
            border: 1px solid;
        }
    }

    /* Reduced motion support */
    @media (prefers-reduced-motion: reduce) {
        #bottom-audio-player,
        .progress-bar,
        .volume-bar,
        button {
            transition: none;
        }
        
        .loading-indicator .animate-spin {
            animation: none;
        }
    }
</style>

<script>
    // Show keyboard shortcuts help
    function showKeyboardShortcuts() {
        document.getElementById('keyboard-shortcuts-help').classList.remove('hidden');
    }

    // Add keyboard shortcut to show help
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === '?') {
            e.preventDefault();
            showKeyboardShortcuts();
        }
    });
</script>