<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Audio Statistik - Audio Statistik Sulawesi Utara')</title>
    <meta name="description" content="Layanan audio untuk publikasi dan berita resmi statistik Sulawesi Utara yang mendukung aksesibilitas pengguna dengan gangguan penglihatan.">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'inter': ['Inter', 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Custom styles for text hover sounds */
        .text-sound {
            transition: color 0.1s ease;
        }
        
        .text-sound:hover {
            color: #1d4ed8 !important;
        }
        
        /* Smooth transitions for all interactive elements */
        * {
            transition: color 0.15s ease, background-color 0.15s ease, border-color 0.15s ease;
        }

        .sticky-navbar-compensation {
            padding-top: 4rem; /* 64px = h-16 */
        }

        /* Smooth scroll behavior */
        html {
            scroll-behavior: smooth;
        }

        /* Enhanced sticky navbar dengan backdrop blur untuk modern look */
        header.sticky {
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            background-color: rgba(255, 255, 255, 0.95);
            border-bottom: 1px solid rgba(229, 231, 235, 0.8);
        }

        /* Animasi smooth untuk navbar saat scroll */
        header {
            transition: all 0.3s ease;
        }

        /* Z-index hierarchy untuk mencegah overlap */
        .z-navbar { z-index: 40; }
        .z-modal { z-index: 50; }
        .z-dropdown { z-index: 45; }

        /* Mobile menu smooth animation */
        #mobile-menu {
            transition: max-height 0.3s ease, opacity 0.3s ease;
            max-height: 0;
            opacity: 0;
            overflow: hidden;
        }

        #mobile-menu:not(.hidden) {
            max-height: 300px;
            opacity: 1;
        }

        /* Sound indicator classes */
        .text-sound:focus-within,
        .hover-sound:focus-within {
            outline: 2px solid #3B82F6;
            outline-offset: 2px;
        }

        /* Smooth transitions */
        .transition-colors {
            transition-property: color, background-color, border-color;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
            transition-duration: 150ms;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #a1a1a1;
        }

        /* Print styles */
        @media print {
            .no-print {
                display: none !important;
            }
        }

        #voice-search-modal {
            pointer-events: none;      /* default tidak menerima hover */
        }

        #voice-search-modal > .relative {
            pointer-events: auto;      /* aktif hanya untuk box */
        }

    </style>
    
    @stack('styles')
</head>
<body class="bg-gray-50 font-inter">
    <!-- Skip to main content for accessibility -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-blue-600 text-white px-4 py-2 rounded-md z-50">
        Lewati ke konten utama
    </a>

    <!-- Navigation -->
    @include('components.navigation')

    <!-- Main Content -->
    <main id="main-content" class="min-h-screen pb-32 pt-0" role="main">
        <div class="hidden">Akun ini : <span id="role-user">{{ auth()->user()->role }}</span></div>
        @yield('content')
    </main>

    <!-- Bottom Audio Player (Spotify-style) -->
    <div id="bottom-audio-player" class="fixed bottom-0 left-0 right-0 bg-gray-900 text-white border-t border-gray-700 hidden z-40">
        <div class="flex items-center h-20 px-4">
            <!-- Current Document Info -->
            <div class="flex items-center space-x-3 flex-1 min-w-0">
                <img id="current-doc-cover" src="" alt="" class="w-14 h-14 rounded-lg object-cover bg-gray-800">
                <div class="min-w-0">
                    <h4 id="current-doc-title" class="text-sm font-medium text-white truncate text-sound"></h4>
                    <p id="current-doc-indicator" class="text-xs text-gray-400 truncate text-sound"></p>
                </div>
            </div>

            <!-- Audio Controls -->
            <div class="flex flex-col items-center flex-1 max-w-md mx-4">
                <div class="flex items-center space-x-4 mb-2">
                    <button id="shuffle-btn" class="text-gray-400 hover:text-white transition-colors hover-sound" title="Acak">
                        <i class="fas fa-random"></i>
                    </button>
                    <button id="prev-btn" class="text-gray-400 hover:text-white transition-colors hover-sound" title="Sebelumnya">
                        <i class="fas fa-step-backward"></i>
                    </button>
                    <button id="play-pause-main-btn" class="w-10 h-10 bg-white hover:bg-gray-200 text-black rounded-full flex items-center justify-center transition-colors hover-sound" title="Putar/Jeda">
                        <i class="fas fa-play text-sm"></i>
                    </button>
                    <button id="next-btn" class="text-gray-400 hover:text-white transition-colors hover-sound" title="Selanjutnya">
                        <i class="fas fa-step-forward"></i>
                    </button>
                    <button id="repeat-btn" class="text-gray-400 hover:text-white transition-colors hover-sound" title="Ulangi">
                        <i class="fas fa-redo"></i>
                    </button>
                </div>
                
                <!-- Progress Bar -->
                <div class="flex items-center space-x-2 w-full">
                    <span id="current-time-main" class="text-xs text-gray-400 text-sound">00:00</span>
                    <div id="progress-container-main" class="flex-1 h-1 bg-gray-600 rounded-full cursor-pointer">
                        <div id="progress-bar-main" class="h-1 bg-white rounded-full transition-all duration-100" style="width: 0%"></div>
                    </div>
                    <span id="total-time-main" class="text-xs text-gray-400 text-sound">00:00</span>
                </div>
            </div>

            <!-- Right Controls -->
            <div class="flex items-center space-x-3 flex-1 justify-end">
                <div class="flex items-center space-x-2">
                    <button id="format-mp3" class="px-3 py-1 text-xs bg-gray-700 hover:bg-gray-600 rounded-md transition-colors hover-sound text-sound">MP3</button>
                    <button id="format-flac" class="px-3 py-1 text-xs text-gray-400 hover:text-white transition-colors hover-sound text-sound">FLAC</button>
                </div>
                
                <div class="flex items-center space-x-2">
                    <button id="speed-btn" class="text-gray-400 hover:text-white transition-colors hover-sound" title="Kecepatan">
                        <i class="fas fa-tachometer-alt"></i>
                        <span class="text-xs ml-1 text-sound">1x</span>
                    </button>
                    <button id="download-btn-main" class="text-gray-400 hover:text-white transition-colors hover-sound" title="Unduh">
                        <i class="fas fa-download"></i>
                    </button>
                    <button id="popup-btn" class="text-gray-400 hover:text-white transition-colors hover-sound" title="Buka Detail">
                        <i class="fas fa-external-link-alt"></i>
                    </button>
                    <button id="close-player-btn" class="text-gray-400 hover:text-white transition-colors hover-sound" title="Tutup">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Hidden Audio Element -->
        <audio id="main-audio-element" preload="metadata" style="display: none;"></audio>
    </div>

    <!-- Right Sidebar (Spotify-style) -->
    <div id="right-sidebar" class="fixed top-16 right-0 w-80 h-full bg-gray-100 border-l border-gray-300 transform translate-x-full transition-transform duration-300 z-30 overflow-y-auto">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-900 text-sound">Detail Dokumen</h3>
                <button id="close-sidebar-btn" class="text-gray-400 hover:text-gray-600 hover-sound">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div id="sidebar-content">
                <div class="mb-6">
                    <img id="sidebar-doc-cover" src="" alt="" class="w-full aspect-[3/4] object-cover rounded-lg bg-gray-200 mb-4">
                    <h4 id="sidebar-doc-title" class="font-semibold text-gray-900 mb-2 text-sound"></h4>
                    <p id="sidebar-doc-indicator" class="text-sm text-gray-600 mb-2 text-sound"></p>
                    <p id="sidebar-doc-date" class="text-sm text-gray-500 mb-4 text-sound"></p>
                </div>
                
                <div class="mb-6">
                    <h5 class="font-medium text-gray-900 mb-2 text-sound">Deskripsi</h5>
                    <p id="sidebar-doc-description" class="text-sm text-gray-600 leading-relaxed text-sound"></p>
                </div>
                
                <div class="mb-6">
                    <h5 class="font-medium text-gray-900 mb-2 text-sound">Informasi Audio</h5>
                    <div class="space-y-2 text-sm text-gray-600">
                        <div class="flex justify-between">
                            <span class="text-sound">Durasi:</span>
                            <span id="sidebar-audio-duration" class="text-sound">-</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sound">Format:</span>
                            <span id="sidebar-audio-format" class="text-sound">MP3</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sound">Ukuran:</span>
                            <span id="sidebar-file-size" class="text-sound">-</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Voice Search Modal -->
    <div id="voice-search-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center">
        <!-- background overlay -->
        <div class="absolute inset-0 bg-black bg-opacity-50"></div>

        <!-- modal box -->
        <div class="relative bg-white rounded-lg p-6 max-w-md w-full mx-4 pointer-events-auto">
            <div class="text-center">
                <div class="animate-pulse text-blue-600 mb-4">
                    <i class="fas fa-microphone text-4xl"></i>
                </div>
                <p class="text-lg font-semibold mb-2">Mendengarkan...</p>
                <p class="text-gray-600 mb-4">Silakan katakan kata kunci pencarian</p>
                <button id="stop-listening"
                        class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg">
                    Berhenti
                </button>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white" role="contentinfo">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                            <i class="fas fa-volume-up text-white text-sm" aria-hidden="true"></i>
                        </div>
                        <span class="font-bold text-sound">Audio Statistik</span>
                    </div>
                    <p class="text-gray-300 text-sm text-sound">
                        Layanan audio untuk publikasi dan berita resmi statistik BPS Sulawesi Utara 
                        yang mendukung aksesibilitas untuk pengguna dengan gangguan penglihatan.
                    </p>
                </div>
                
                <div>
                    <h3 class="font-semibold mb-3 text-sound">Tautan</h3>
                    <ul class="space-y-2 text-sm">
                        <li><a href="{{ route('home') }}" class="text-gray-300 hover:text-white transition-colors hover-sound text-sound">Beranda</a></li>
                        <li><a href="{{ route('documents.publications') }}" class="text-gray-300 hover:text-white transition-colors hover-sound text-sound">Publikasi</a></li>
                        <li><a href="{{ route('documents.brs') }}" class="text-gray-300 hover:text-white transition-colors hover-sound text-sound">BRS</a></li>
                        <li><a href="https://sulut.bps.go.id" target="_blank" class="text-gray-300 hover:text-white transition-colors hover-sound text-sound">BPS Sulut</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="font-semibold mb-3 text-sound">Aksesibilitas</h3>
                    <ul class="space-y-2 text-sm text-gray-300">
                        <li class="text-sound"><i class="fas fa-check mr-2 text-green-400" aria-hidden="true"></i>Text-to-Speech Otomatis</li>
                        <li class="text-sound"><i class="fas fa-check mr-2 text-green-400" aria-hidden="true"></i>Navigasi Keyboard</li>
                        <li class="text-sound"><i class="fas fa-check mr-2 text-green-400" aria-hidden="true"></i>Screen Reader Friendly</li>
                        <li class="text-sound"><i class="fas fa-check mr-2 text-green-400" aria-hidden="true"></i>Pencarian Suara</li>
                        <li class="text-sound"><i class="fas fa-check mr-2 text-green-400" aria-hidden="true"></i>Suara Hover untuk Teks</li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-700 mt-8 pt-6 text-center text-sm text-gray-400">
                <p class="text-sound">&copy; {{ date('Y') }} BPS Provinsi Sulawesi Utara. Semua hak dilindungi.</p>
            </div>
        </div>
    </footer>

    <!-- Scroll to Top Button -->
    <button id="scroll-to-top" 
            class="fixed bottom-6 right-6 bg-blue-600 hover:bg-blue-700 text-white p-3 rounded-full shadow-lg transition-all duration-300 transform hover:scale-110 hidden hover-sound z-50"
            aria-label="Kembali ke atas">
        <i class="fas fa-arrow-up" aria-hidden="true"></i>
    </button>

    <!-- Loading Overlay -->
    <div id="loading-overlay" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg p-6 flex items-center space-x-3">
            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
            <span class="text-gray-700 text-sound">Memuat...</span>
        </div>
    </div>

    <!-- Toast Notifications -->
    <div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2">
        <!-- Toast notifications will be inserted here by JavaScript -->
    </div>

 <!-- Scripts -->
    @stack('scripts')

    <!-- Core app.js - Always load -->
    <script src="{{ asset('js/app1.js') }}" defer></script>

    <!-- Enhanced Voice Search - Load pada semua halaman kecuali login/register -->
    @unless(request()->routeIs('login') || request()->routeIs('register') || request()->routeIs('brs.') || request()->routeIs('publications.'))
        <script src="{{ asset('js/enhanced-voice-search.js') }}" defer></script>
    @endunless

    <!-- Page-specific initialization -->
    <script>
        // Initialize page-specific features that require Blade variables
        document.addEventListener('DOMContentLoaded', function() {
            // Set search URL untuk voice search
            document.body.dataset.searchUrl = '{{ route("search") }}';
            
            @if(request()->routeIs('home'))
            // Welcome message
            if (!sessionStorage.getItem('welcomed')) {
                setTimeout(() => {
                    const welcomeMessage = new SpeechSynthesisUtterance(
                        'Selamat datang di Audio Statistik, portal audio untuk publikasi dan berita resmi statistik BPS Sulawesi Utara. ' +
                        'Gunakan tombol Ctrl untuk pencarian suara, atau katakan "Hai Audio Statistik".'
                    );
                    welcomeMessage.lang = 'id-ID';
                    welcomeMessage.rate = 0.9;
                    window.speechSynthesis.speak(welcomeMessage);
                    sessionStorage.setItem('welcomed', 'true');
                }, 1000);
            }
            @endif

            @unless(request()->routeIs('login') || request()->routeIs('register'))
            // Initialize voice search dengan compatibility layer
            if (window.AudioSystem && window.AudioSystem.initializeVoiceSearch) {
                // console.log('🌉 Using legacy AudioSystem voice search');
                window.AudioSystem.initializeVoiceSearch('{{ route("search") }}');
            } else {
                // console.log('🎤 Using enhanced voice search system');
                // Enhanced voice search akan auto-initialize
            }
            @endunless
        });

        // Enhanced error handling dan monitoring
        let voiceFeaturesStatus = {
            coordinator: true,
            search: false,
            welcome: false,
            navigation: false
        };

        // Debug functions
        // window.checkVoiceFeatures = function() {
        //     console.group('🎤 Voice Features Status');
        //     console.log('Voice Search:', voiceFeaturesStatus.search ? '✅' : '❌');
        //     console.log('Welcome Message:', voiceFeaturesStatus.welcome ? '✅' : '❌');
        //     console.log('Voice Navigation:', voiceFeaturesStatus.navigation ? '✅' : '❌');
            
        //     // Check legacy AudioSystem
        //     if (window.AudioSystem) {
        //         console.log('Legacy AudioSystem:', '✅ Available');
        //     }
            
        //     // Check enhanced voice search
        //     if (window.AudioStatistik?.Voice?.Search) {
        //         console.log('Enhanced Voice Search:', '✅ Available');
        //     }
            
        //     // Check recognition objects
        //     if (window.commandRecognition) {
        //         console.log('Wake Recognition:', window.commandRecognition.state || 'Available');
        //     }
        //     if (window.voiceRecognition) {
        //         console.log('Search Recognition:', window.voiceRecognition.state || 'Available');
        //     }
            
        //     console.groupEnd();
        // };

        // Manual voice search activation
        window.activateVoiceSearch = function() {
            // console.log('🎤 Manually activating voice search...');
            
            if (window.startVoiceSearch) {
                window.startVoiceSearch();
            } else if (window.AudioSystem?.openVoiceSearchModal) {
                window.AudioSystem.openVoiceSearchModal();
            } else {
                // console.warn('⚠️ Voice search not available');
            }
        };

        // Reset voice systems
        window.resetVoiceSystem = function() {
            // console.log('🔄 Resetting voice system...');
            
            // Stop all voice activities
            if (window.stopVoiceSearch) {
                window.stopVoiceSearch();
            }
            
            // Clear session storage
            sessionStorage.removeItem('welcomed');
            
            // Restart after delay
            setTimeout(() => {
                location.reload();
            }, 1000);
        };

        // Test voice search function
        window.testVoiceSearch = function() {
            // console.log('🧪 Testing voice search...');
            
            if ('webkitSpeechRecognition' in window) {
                // console.log('✅ Speech recognition supported');
                
                if (window.startVoiceSearch) {
                    // console.log('✅ Voice search functions available');
                    window.startVoiceSearch();
                } else {
                    // console.warn('❌ Voice search functions not found');
                }
            } else {
                // console.warn('❌ Speech recognition not supported');
            }
        };

        // Auto-check status after page load
        // setTimeout(() => {
        //     // console.log('🔍 Auto-checking voice features status...');
        //     window.checkVoiceFeatures();
        // }, 3000);
    </script>
    <script>
    function clearAllFilters() {
        window.location.href = "{{ route('documents.index') }}";
    }
    </script>
</body>
</html>