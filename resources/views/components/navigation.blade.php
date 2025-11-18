<nav class="bg-white shadow-sm sticky top-0 z-40" role="navigation" aria-label="Navigasi utama">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <!-- Logo -->
            <div class="flex items-center">
                <a href="{{ route('home') }}" class="flex items-center space-x-3 hover-sound">
                    <div
                        class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center shadow-md">
                        <i class="fas fa-volume-up text-white text-lg" aria-hidden="true"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900 text-sound">Audio Statistik</h1>
                        <p class="text-xs text-gray-500 text-sound">BPS Sulawesi Utara</p>
                    </div>
                </a>
            </div>

            <!-- Desktop Navigation -->
            <div class="hidden md:flex items-center space-x-6">
                <!-- Main Navigation Links -->
                <a href="{{ route('home') }}"
                    class="text-gray-700 hover:text-blue-600 font-medium transition-colors hover-sound text-sound {{ request()->routeIs('home') ? 'text-blue-600' : '' }}"
                    @if (request()->routeIs('home')) aria-current="page" @endif>
                    Beranda
                </a>
                <a href="{{ route('documents.publications') }}"
                    class="text-gray-700 hover:text-blue-600 font-medium transition-colors hover-sound text-sound {{ request()->routeIs('documents.publications') ? 'text-blue-600' : '' }}"
                    @if (request()->routeIs('documents.publications')) aria-current="page" @endif>
                    Publikasi
                </a>
                <a href="{{ route('documents.brs') }}"
                    class="text-gray-700 hover:text-blue-600 font-medium transition-colors hover-sound text-sound {{ request()->routeIs('documents.brs') ? 'text-blue-600' : '' }}"
                    @if (request()->routeIs('documents.brs')) aria-current="page" @endif>
                    BRS
                </a>
                @auth
                    @if (!auth()->user()->isAdmin())
                        <button type="button" data-voice-search id="voice-search-btn"
                            class="voice-search-btn text-gray-700 hover:text-blue-600 font-medium transition-colors hover-sound text-sound flex items-center"
                            aria-label="Start voice search" title="Voice Search (Ctrl atau katakan 'Hai Audio Statistik')"
                            onclick="openVoiceSearchModal()">
                            <i class="fas fa-microphone mr-2" aria-hidden="true"></i>
                            <span class="text-sound">Voice</span>
                        </button>
                    @endif
                @endauth
                @auth
                    @if (auth()->user()->isAdmin())
                        {{-- Progress Notifications untuk Admin --}}
                        @include('components.progress-notifications')

                        {{-- Admin Dropdown --}}
                        <div class="relative cursor-pointer" x-data="{ open: false }">
                            <button @click="open = !open" @click.away="open = false"
                                class="flex items-center cursor-pointer  text-gray-700 hover:text-blue-600 font-medium transition-colors hover-sound text-sound focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 rounded-md px-3 py-2 {{ request()->routeIs('admin.*') ? 'text-blue-600 bg-blue-50' : '' }}">
                                <i class="fas fa-user-shield mr-2" aria-hidden="true"></i>
                                <span class="text-sound">Admin</span>
                                <i class="fas fa-chevron-down ml-1 text-xs transition-transform duration-200"
                                    :class="{ 'rotate-180': open }" aria-hidden="true"></i>
                            </button>

                            {{-- Dropdown Menu --}}
                            <div x-show="open" x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="transform opacity-0 scale-95"
                                x-transition:enter-end="transform opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-75"
                                x-transition:leave-start="transform opacity-100 scale-100"
                                x-transition:leave-end="transform opacity-0 scale-95"
                                class="absolute right-0 mt-2 w-64 rounded-lg shadow-lg bg-white ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 z-50"
                                style="display: none;">
                                <div class="py-1" role="menu" aria-orientation="vertical">
                                    {{-- Admin Menu Items --}}
                                    <a href="{{ route('admin.dashboard') }}"
                                        class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors hover-sound {{ request()->routeIs('admin.dashboard') ? 'bg-blue-50 text-blue-600' : '' }}"
                                        role="menuitem">
                                        <i class="fas fa-tachometer-alt mr-3 w-5 h-5" aria-hidden="true"></i>
                                        <div>
                                            <div class="font-medium text-sound">Dashboard</div>
                                            <div class="text-xs text-gray-500">Overview sistem</div>
                                        </div>
                                    </a>

                                    <a href="{{ route('admin.documents.index') }}"
                                        class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors hover-sound {{ request()->routeIs('admin.documents.*') ? 'bg-blue-50 text-blue-600' : '' }}"
                                        role="menuitem">
                                        <i class="fas fa-file-alt mr-3 w-5 h-5" aria-hidden="true"></i>
                                        <div>
                                            <div class="font-medium text-sound">Document Processing</div>
                                            <div class="text-xs text-gray-500">Kelola dokumen</div>
                                        </div>
                                    </a>
                                    
                                    <a href="{{ route('admin.recycle-bin') }}"
                                        class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors hover-sound {{ request()->routeIs('admin.recycle-bin.*') ? 'bg-blue-50 text-blue-600' : '' }}"
                                        role="menuitem">
                                        <i class="fa-solid fa-trash mr-3 w-5 h-5" aria-hidden="true"></i>
                                        <div>
                                            <div class="font-medium text-sound">Recycle Bin</div>
                                            <div class="text-xs text-gray-500">Kelola sampah</div>
                                        </div>
                                    </a>

                                    <a href="{{ route('admin.api-monitor') }}"
                                        class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-colors hover-sound {{ request()->routeIs('admin.api-monitor') ? 'bg-blue-50 text-blue-600' : '' }}"
                                        role="menuitem">
                                        <i class="fas fa-chart-line mr-3 w-5 h-5" aria-hidden="true"></i>
                                        <div>
                                            <div class="font-medium text-sound">API Monitor</div>
                                            <div class="text-xs text-gray-500">Monitor API & Queue</div>
                                        </div>
                                    </a>
                                </div>

                                {{-- Logout Section --}}
                                <div class="py-1">
                                    <form action="{{ route('logout') }}" method="POST" class="block">
                                        @csrf
                                        <button type="submit"
                                            class="flex items-center w-full px-4 py-3 text-sm text-gray-700 hover:bg-red-50 hover:text-red-600 transition-colors hover-sound text-left"
                                            role="menuitem">
                                            <i class="fas fa-sign-out-alt mr-3 w-5 h-5" aria-hidden="true"></i>
                                            <div>
                                                <div class="font-medium text-sound">Logout</div>
                                                <div class="text-xs text-gray-500">Keluar dari sistem</div>
                                            </div>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Regular User Menu (hanya tampil jika bukan admin) --}}
                    @if (!auth()->user()->isAdmin())
                        <div class="flex items-center space-x-4">
                            <span class="text-sm text-gray-600">
                                Halo, <span class="font-medium text-sound">{{ auth()->user()->name }}</span>
                            </span>
                            <form action="{{ route('logout') }}" method="POST" class="inline">
                                @csrf
                                <button type="submit"
                                    class="text-gray-700 hover:text-blue-600 font-medium transition-colors hover-sound text-sound">
                                    <i class="fas fa-sign-out-alt mr-1" aria-hidden="true"></i>
                                    <span class="text-sound">Keluar</span>
                                </button>
                            </form>
                        </div>
                    @endif
                @else
                    {{-- Guest User - Show Login --}}
                    <a href="{{ route('login') }}"
                        class="inline-flex items-center px-4 py-2 border border-blue-600 text-blue-600 hover:bg-blue-600 hover:text-white font-medium rounded-lg transition-colors hover-sound">
                        <i class="fas fa-sign-in-alt mr-2" aria-hidden="true"></i>
                        <span class="text-sound">Login Admin</span>
                    </a>
                @endauth
            </div>

            <!-- Mobile menu button -->
            <button type="button"
                class="md:hidden inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 hover-sound focus:outline-none focus:ring-2 focus:ring-blue-500"
                aria-controls="mobile-menu" aria-expanded="false" id="mobile-menu-button">
                <span class="sr-only">Buka menu utama</span>
                <i class="fas fa-bars" aria-hidden="true"></i>
            </button>
        </div>
    </div>

    <!-- Mobile menu -->
    <div class="md:hidden hidden" id="mobile-menu">
        <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3 bg-gray-50 border-t border-gray-200">
            {{-- Main Navigation Links --}}
            <a href="{{ route('home') }}"
                class="block px-3 py-2 text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-white rounded-md hover-sound text-sound {{ request()->routeIs('home') ? 'text-blue-600 bg-blue-50' : '' }}">
                <i class="fas fa-home mr-2" aria-hidden="true"></i>Beranda
            </a>
            <a href="{{ route('documents.publications') }}"
                class="block px-3 py-2 text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-white rounded-md hover-sound text-sound {{ request()->routeIs('documents.publications') ? 'text-blue-600 bg-blue-50' : '' }}">
                <i class="fas fa-book mr-2" aria-hidden="true"></i>Publikasi
            </a>
            <a href="{{ route('documents.brs') }}"
                class="block px-3 py-2 text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-white rounded-md hover-sound text-sound {{ request()->routeIs('documents.brs') ? 'text-blue-600 bg-blue-50' : '' }}">
                <i class="fas fa-newspaper mr-2" aria-hidden="true"></i>BRS
            </a>
            @auth
                @if (!auth()->user()->isAdmin())
                    <button type="button" data-voice-search id="voice-search-mobile"
                        class="voice-search-btn block w-full text-left px-3 py-2 text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-white rounded-md hover-sound text-sound"
                        onclick="openVoiceSearchModal()">
                        <i class="fas fa-microphone mr-2" aria-hidden="true"></i>Voice Search
                    </button>
                @endif
            @endauth
            @auth
                @if (auth()->user()->isAdmin())
                    {{-- Admin Mobile Menu --}}
                    <div class="border-t border-gray-200 pt-3 mt-3">
                        <div class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <i class="fas fa-user-shield mr-1" aria-hidden="true"></i>Admin Menu
                        </div>
                        <a href="{{ route('admin.dashboard') }}"
                            class="block px-3 py-2 text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-white rounded-md hover-sound text-sound {{ request()->routeIs('admin.dashboard') ? 'text-blue-600 bg-blue-50' : '' }}">
                            <i class="fas fa-tachometer-alt mr-2" aria-hidden="true"></i>Dashboard
                        </a>
                        <a href="{{ route('admin.documents.index') }}"
                            class="block px-3 py-2 text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-white rounded-md hover-sound text-sound {{ request()->routeIs('admin.documents.*') ? 'text-blue-600 bg-blue-50' : '' }}">
                            <i class="fas fa-file-alt mr-2" aria-hidden="true"></i>Document Processing
                        </a>
                        <a href="{{ route('admin.api-monitor') }}"
                            class="block px-3 py-2 text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-white rounded-md hover-sound text-sound {{ request()->routeIs('admin.api-monitor') ? 'text-blue-600 bg-blue-50' : '' }}">
                            <i class="fas fa-chart-line mr-2" aria-hidden="true"></i>API Monitor
                        </a>
                    </div>
                @else
                    {{-- Regular User Info --}}
                    <div class="border-t border-gray-200 pt-3 mt-3">
                        <div class="px-3 py-2 text-sm text-gray-600">
                            Halo, <span class="font-medium text-sound">{{ auth()->user()->name }}</span>
                        </div>
                    </div>
                @endif

                {{-- Logout --}}
                <div class="border-t border-gray-200 pt-3 mt-3">
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit"
                            class="block w-full text-left px-3 py-2 text-base font-medium text-gray-700 hover:text-red-600 hover:bg-red-50 rounded-md hover-sound text-sound">
                            <i class="fas fa-sign-out-alt mr-2" aria-hidden="true"></i>
                            @if (auth()->user()->isAdmin())
                                Logout
                            @else
                                Keluar
                            @endif
                        </button>
                    </form>
                </div>
            @else
                {{-- Guest Login --}}
                <div class="border-t border-gray-200 pt-3 mt-3">
                    <a href="{{ route('login') }}"
                        class="block px-3 py-2 text-base font-medium text-blue-600 hover:text-blue-700 hover:bg-blue-50 rounded-md hover-sound text-sound">
                        <i class="fas fa-sign-in-alt mr-2" aria-hidden="true"></i>Login Admin
                    </a>
                </div>
            @endauth
        </div>
    </div>
</nav>

{{-- JavaScript untuk mobile menu dan dropdown --}}
@push('scripts')
    <script>
        // Mobile menu toggle
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuButton = document.getElementById('mobile-menu-button');
            const mobileMenu = document.getElementById('mobile-menu');

            if (mobileMenuButton && mobileMenu) {
                mobileMenuButton.addEventListener('click', function() {
                    const isHidden = mobileMenu.classList.contains('hidden');

                    if (isHidden) {
                        mobileMenu.classList.remove('hidden');
                        mobileMenuButton.setAttribute('aria-expanded', 'true');
                    } else {
                        mobileMenu.classList.add('hidden');
                        mobileMenuButton.setAttribute('aria-expanded', 'false');
                    }
                });
            }
        });

        // Voice Search Modal Functions
        function openVoiceSearchModal() {
            const modal = document.getElementById('voice-search-modal');
            if (modal) {
                modal.classList.remove('hidden');

                // Trigger voice search dari unified app jika tersedia
                if (window.unifiedApp && window.unifiedApp.modules.voiceSearch) {
                    // console.log('🎤 Opening voice search via unified app');
                    window.unifiedApp.modules.voiceSearch.startListening();
                } else if (window.AudioSystem && window.AudioSystem.initializeVoiceSearch) {
                    // console.log('🌉 Opening voice search via compatibility bridge');
                    // Fallback ke compatibility bridge
                    if (typeof window.AudioSystem.startListening === 'function') {
                        window.AudioSystem.startListening();
                    }
                } else {
                    // console.log('⚠️ Voice search system not ready yet');
                }
            }
        }

        function closeVoiceSearchModal() {
            const modal = document.getElementById('voice-search-modal');
            if (modal) {
                modal.classList.add('hidden');

                // Stop voice search
                if (window.unifiedApp && window.unifiedApp.modules.voiceSearch) {
                    window.unifiedApp.modules.voiceSearch.stopListening();
                } else if (window.AudioSystem && typeof window.AudioSystem.stopListening === 'function') {
                    window.AudioSystem.stopListening();
                }
            }
        }

        // Event listeners tambahan
        document.addEventListener('DOMContentLoaded', function() {
            // Handle modal close by clicking outside
            const modal = document.getElementById('voice-search-modal');
            if (modal) {
                modal.addEventListener('click', function(event) {
                    if (event.target === modal) {
                        closeVoiceSearchModal();
                    }
                });
            }

            // Handle stop button di modal
            const stopBtn = document.getElementById('stop-listening');
            if (stopBtn) {
                stopBtn.addEventListener('click', closeVoiceSearchModal);
            }

            // Handle Ctrl key untuk buka modal
            document.addEventListener('keydown', function(event) {
                const path = window.location.pathname;

                // Only run on non-auth/admin pages


                if (
                    path.includes('login') ||
                    path.includes('register') ||
                    path.includes('admin')
                ) {
                    return;
                }

                // Ignore if typing in an input, textarea, or contenteditable
                const active = document.activeElement;
                const isTyping =
                    active &&
                    (active.tagName == 'input' ||
                        active.tagName == 'textarea' ||
                        active.isContentEditable);


                if (isTyping) return;

                // Trigger when SPACE pressed (no modifiers)
                if (
                    event.code === 'Space' &&
                    !event.ctrlKey &&
                    !event.altKey &&
                    !event.metaKey &&
                    !event.shiftKey
                ) {

                    event.preventDefault(); // Prevent page scroll
                    openVoiceSearchModal();
                }
            });


            // Handle ESC key untuk tutup modal
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    closeVoiceSearchModal();
                }
            });

            // Update voice button states when voice search is active
            function updateVoiceButtonStates(isActive) {
                const buttons = document.querySelectorAll('.voice-search-btn');
                buttons.forEach(button => {
                    if (isActive) {
                        button.classList.add('listening', 'text-red-600');
                        button.classList.remove('text-gray-700');
                    } else {
                        button.classList.remove('listening', 'text-red-600');
                        button.classList.add('text-gray-700');
                    }
                });
            }

            // Listen for voice search state changes
            document.addEventListener('voiceSearchStateChange', function(event) {
                updateVoiceButtonStates(event.detail.isListening);
            });
        });
    </script>
@endpush
