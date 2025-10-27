/**
 * =============================================================================
 * UNIFIED APP.JS - Complete Web Application Script
 * =============================================================================
 * 
 * This file contains all client-side functionality for the web application:
 * - Sticky Navbar Management
 * - Universal Hover Text System
 * - Voice Search Integration
 * - Welcome Message System
 * - Audio Player Management (Complete)
 * - Cross-page Integration
 * - Accessibility Features
 * - Error Handling & Recovery
 * 
 * Author: [Your Name]
 * Version: 1.0.0
 * =============================================================================
 */

(function() {
    'use strict';
    
    console.log('🚀 Loading Unified Web Application Script...');

    // =============================================================================
    // GLOBAL CONFIGURATION & CONSTANTS
    // =============================================================================
    
    const CONFIG = {
        audio: {
            defaultFormat: 'mp3',
            maxRetries: 3,
            loadTimeout: 15000,
            progressSaveInterval: 5000,
            healthCheckInterval: 10000
        },
        ui: {
            toastDuration: 3000,
            loadingMinDuration: 500,
            stickyNavOffset: 100,
            searchDebounceTime: 300
        },
        accessibility: {
            announceDelay: 100,
            focusTimeout: 150
        }
    };

    // =============================================================================
    // MAIN APPLICATION MANAGER
    // =============================================================================
    
    class UnifiedWebApplication {
        constructor() {
            this.modules = {};
            this.initialized = false;
            this.debug = window.location.hostname === 'localhost';
            
            this.init();
        }

        async init() {
            try {
                console.log('🎯 Initializing Unified Web Application');
                
                // Initialize all modules
                await this.initializeModules();
                
                // Setup global event handlers
                this.setupGlobalEvents();
                
                // Mark as initialized
                this.initialized = true;
                
                console.log('✅ Unified Web Application initialized successfully');
                
                // Perform post-initialization tasks
                this.postInit();
                
            } catch (error) {
                console.error('❌ Failed to initialize web application:', error);
                this.handleInitializationError(error);
            }
        }

        async initializeModules() {
            // Initialize in order of dependency
            this.modules.toast = new ToastManager();
            this.modules.stickyNav = new StickyNavManager();
            this.modules.soundEffects = new SoundEffectsManager();
            this.modules.hoverText = new UniversalHoverTextManager();
            // this.modules.voiceSearch = new VoiceSearchManager();
            // this.modules.welcomeMessage = new WelcomeMessageManager();
            this.modules.audioPlayer = new UnifiedAudioManager();
            this.modules.audioVoiceNavigation = new AudioPlayerVoiceNavigationManager();
            // this.modules.voiceNavigation = new UniversalVoiceNavigationManager();
            this.modules.accessibility = new AccessibilityManager();
            this.modules.healthMonitor = new HealthMonitorManager();

            // Initialize each module
            for (const [name, module] of Object.entries(this.modules)) {
                try {
                    await module.init();
                    console.log(`✅ ${name} module initialized`);
                } catch (error) {
                    console.error(`❌ Failed to initialize ${name} module:`, error);
                }
            }
        }

        setupGlobalEvents() {
            // Global error handler
            window.addEventListener('error', (e) => this.handleGlobalError(e));
            
            // Page visibility changes
            document.addEventListener('visibilitychange', () => this.handleVisibilityChange());
            
            // Before unload cleanup
            window.addEventListener('beforeunload', () => this.cleanup());
            
            // Resize handling
            window.addEventListener('resize', () => this.handleResize());

            // Audio voice navigation global events
            this.setupAudioVoiceNavigationEvents();
            
            // Unified error handling
            window.addEventListener('unhandledrejection', (event) => {
                console.error('Unhandled promise rejection:', event.reason);
                if (this.modules.toast) {
                    this.modules.toast.error('Terjadi kesalahan sistem');
                }
            });
        }

        setupAudioVoiceNavigationEvents() {
            // Listen for audio player events
            document.addEventListener('audioPlayerReady', (event) => {
                if (this.modules.audioVoiceNavigation && event.detail.documentData) {
                    // Auto-trigger voice navigation guidance
                    setTimeout(() => {
                        this.modules.audioVoiceNavigation.triggerAudioGuidancePrompt(event.detail.documentData);
                    }, 500);
                }
            });
            
            // Listen for audio player state changes
            document.addEventListener('audioPlayerStateChange', (event) => {
                if (this.modules.audioVoiceNavigation) {
                    const { state, documentData } = event.detail;
                    
                    if (state === 'stopped' || state === 'ended') {
                        // Deactivate voice navigation when audio stops
                        this.modules.audioVoiceNavigation.deactivateVoiceNavigation();
                    }
                }
            });
            
            // Custom keyboard shortcuts for audio voice navigation
            document.addEventListener('keydown', (event) => {
                // Alt + V to toggle audio voice navigation
                if (event.altKey && event.code === 'KeyV' && !event.ctrlKey && !event.shiftKey) {
                    event.preventDefault();
                    
                    if (this.modules.audioVoiceNavigation) {
                        if (this.modules.audioVoiceNavigation.isActive) {
                            this.modules.audioVoiceNavigation.deactivateVoiceNavigation();
                            if (this.modules.toast) {
                                this.modules.toast.info('Voice navigation dinonaktifkan');
                            }
                        } else {
                            this.modules.audioVoiceNavigation.activateVoiceNavigation();
                            if (this.modules.toast) {
                                this.modules.toast.info('Voice navigation diaktifkan');
                            }
                        }
                    }
                }
                
                // Alt + H untuk show audio guidance
                if (event.altKey && event.code === 'KeyH' && !event.ctrlKey && !event.shiftKey) {
                    event.preventDefault();
                    
                    if (this.modules.audioVoiceNavigation && this.modules.audioVoiceNavigation.isActive) {
                        this.modules.audioVoiceNavigation.showAudioGuidance();
                    }
                }
            });
        }
        postInit() {
            // Hide loading screens
            this.hideLoadingScreens();
            
            // Setup periodic health checks
            this.modules.healthMonitor?.startHealthChecks();
            
            // Initialize page-specific features
            this.initializePageSpecificFeatures();
        }

        // Module access methods
        getModule(name) {
            return this.modules[name];
        }

        // Global error handling
        handleGlobalError(error) {
            console.error('🚨 Global error detected:', error);
            this.modules.accessibility?.announceError('Terjadi kesalahan sistem');
        }

        handleInitializationError(error) {
            // Fallback initialization
            console.warn('🔄 Attempting fallback initialization...');
            this.initializeFallbackMode();
        }

        initializeFallbackMode() {
            // Basic functionality without advanced features
            console.log('🆘 Running in fallback mode');
            
            // Basic audio playback
            this.setupBasicAudioPlayback();
            
            // Basic UI interactions
            this.setupBasicUIInteractions();
        }

        cleanup() {
            for (const module of Object.values(this.modules)) {
                if (module.cleanup) {
                    module.cleanup();
                }
            }
        }

        handleVisibilityChange() {
            if (document.hidden) {
                // Page hidden - pause non-essential operations
                this.modules.audioPlayer?.handlePageHidden();
            } else {
                // Page visible - resume operations
                this.modules.audioPlayer?.handlePageVisible();
            }
        }

        handleResize() {
            // Notify modules of resize
            for (const module of Object.values(this.modules)) {
                if (module.handleResize) {
                    module.handleResize();
                }
            }
        }

        hideLoadingScreens() {
            const loadingElements = document.querySelectorAll('.loading-screen, .loading-overlay');
            loadingElements.forEach(el => {
                el.style.opacity = '0';
                setTimeout(() => el.style.display = 'none', 300);
            });
        }

        initializePageSpecificFeatures() {
            const path = window.location.pathname;
            
            if (path.includes('/admin')) {
                this.initializeAdminFeatures();
            } else if (path.includes('/publikasi') || path.includes('/brs')) {
                this.initializePublicationFeatures();
            } else if (path === '/') {
                this.initializeHomeFeatures();
            }
        }

        initializeAdminFeatures() {
            console.log('🔧 Initializing admin-specific features');
            // Admin-specific audio button handling
            this.modules.audioPlayer?.setupAdminIntegration();
        }

        initializePublicationFeatures() {
            console.log('📚 Initializing publication-specific features');
            // Publication-specific audio button handling
            this.modules.audioPlayer?.setupPublicationIntegration();
        }

        initializeHomeFeatures() {
            console.log('🏠 Initializing home page features');
            // Home page specific features
        }

        setupBasicAudioPlayback() {
            document.addEventListener('click', (e) => {
                const playBtn = e.target.closest('.play-audio-btn, .admin-play-btn, .play-document-btn');
                if (playBtn) {
                    this.basicAudioPlayback(playBtn);
                }
            });
        }

        basicAudioPlayback(button) {
            const documentId = button.dataset.documentId;
            if (documentId) {
                const audioUrl = `/audio/stream/${documentId}/mp3`;
                const audio = new Audio(audioUrl);
                audio.play().catch(console.error);
            }
        }

        setupBasicUIInteractions() {
            // Basic hover effects
            document.querySelectorAll('[data-hover-text]').forEach(el => {
                el.addEventListener('mouseenter', (e) => {
                    const text = e.target.dataset.hoverText;
                    if (text) console.log('Hover:', text);
                });
            });
        }
    }

    // =============================================================================
    // STICKY NAVIGATION MANAGER
    // =============================================================================
    
    class StickyNavManager {
        constructor() {
            this.navbar = null;
            this.isSticky = false;
            this.originalTop = 0;
            this.scrollHandler = null;
        }

        async init() {
            this.navbar = document.querySelector('.navbar, .main-nav, .navigation');
            if (!this.navbar) return;

            this.originalTop = this.navbar.offsetTop;
            this.scrollHandler = this.handleScroll.bind(this);
            
            window.addEventListener('scroll', this.scrollHandler);
            
            // Initial check
            this.handleScroll();
        }

        handleScroll() {
            const scrollY = window.pageYOffset || document.documentElement.scrollTop;
            
            if (scrollY > this.originalTop + CONFIG.ui.stickyNavOffset) {
                if (!this.isSticky) {
                    this.makeSticky();
                }
            } else {
                if (this.isSticky) {
                    this.removeSticky();
                }
            }
        }

        makeSticky() {
            this.navbar.classList.add('sticky', 'navbar-sticky');
            this.isSticky = true;
            
            // Add padding to body to prevent jump
            document.body.style.paddingTop = this.navbar.offsetHeight + 'px';
        }

        removeSticky() {
            this.navbar.classList.remove('sticky', 'navbar-sticky');
            this.isSticky = false;
            
            // Remove padding
            document.body.style.paddingTop = '0';
        }

        cleanup() {
            if (this.scrollHandler) {
                window.removeEventListener('scroll', this.scrollHandler);
            }
        }
    }

    // =============================================================================
    // UNIVERSAL HOVER TEXT MANAGER
    // =============================================================================
    
    class UniversalHoverTextManager {
        constructor() {
            this.tooltip = null;
            this.currentTarget = null;
            this.showTimeout = null;
            this.hideTimeout = null;
        }

        async init() {
            this.createTooltipElement();
            this.setupEventListeners();
        }

        createTooltipElement() {
            this.tooltip = document.createElement('div');
            this.tooltip.className = 'universal-tooltip';
            this.tooltip.style.cssText = `
                position: absolute;
                background: rgba(0, 0, 0, 0.8);
                color: white;
                padding: 8px 12px;
                border-radius: 4px;
                font-size: 14px;
                pointer-events: none;
                z-index: 10000;
                opacity: 0;
                transition: opacity 0.2s ease;
                max-width: 200px;
                word-wrap: break-word;
            `;
            document.body.appendChild(this.tooltip);
        }

        setupEventListeners() {
            document.addEventListener('mouseenter', (e) => this.handleMouseEnter(e), true);
            document.addEventListener('mouseleave', (e) => this.handleMouseLeave(e), true);
            document.addEventListener('mousemove', (e) => this.handleMouseMove(e));
        }

        handleMouseEnter(e) {
            if (!e || !e.target) return; // Safety check
            
            const target = e.target;
            const hoverText = this.getHoverText(target);
            
            if (hoverText && (!this.currentTarget || hoverText !== this.currentTarget.hoverText)) {
                this.clearTimeouts();
                
                this.showTimeout = setTimeout(() => {
                    this.showTooltip(target, hoverText);
                }, 500);
            }
        }

        handleMouseLeave(e) {
            if (!e || !e.target) return; // Safety check
            
            if (this.currentTarget && (e.target === this.currentTarget.element || 
                this.currentTarget.element.contains(e.target))) {
                this.clearTimeouts();
                
                this.hideTimeout = setTimeout(() => {
                    this.hideTooltip();
                }, 100);
            }
        }

        handleMouseMove(e) {
            if (this.tooltip.style.opacity === '1') {
                this.positionTooltip(e.clientX, e.clientY);
            }
        }

        getHoverText(element) {
            if (!element) return null;
            
            return element.dataset?.hoverText || 
                element.getAttribute?.('title') || 
                element.getAttribute?.('aria-label') ||
                null;
        }

        showTooltip(element, text) {
            this.currentTarget = { element, hoverText: text };
            this.tooltip.textContent = text;
            this.tooltip.style.opacity = '1';
            
            // Remove title to prevent default tooltip
            if (element.hasAttribute('title')) {
                element.dataset.originalTitle = element.getAttribute('title');
                element.removeAttribute('title');
            }
        }

        hideTooltip() {
            this.tooltip.style.opacity = '0';
            
            // Restore title
            if (this.currentTarget?.element.dataset.originalTitle) {
                this.currentTarget.element.setAttribute('title', this.currentTarget.element.dataset.originalTitle);
                delete this.currentTarget.element.dataset.originalTitle;
            }
            
            this.currentTarget = null;
        }

        positionTooltip(x, y) {
            const rect = this.tooltip.getBoundingClientRect();
            const viewportWidth = window.innerWidth;
            const viewportHeight = window.innerHeight;
            
            let left = x + 10;
            let top = y - rect.height - 10;
            
            // Adjust if tooltip goes off-screen
            if (left + rect.width > viewportWidth) {
                left = x - rect.width - 10;
            }
            if (top < 0) {
                top = y + 10;
            }
            
            this.tooltip.style.left = left + 'px';
            this.tooltip.style.top = top + 'px';
        }

        clearTimeouts() {
            if (this.showTimeout) {
                clearTimeout(this.showTimeout);
                this.showTimeout = null;
            }
            if (this.hideTimeout) {
                clearTimeout(this.hideTimeout);
                this.hideTimeout = null;
            }
        }

        cleanup() {
            this.clearTimeouts();
            if (this.tooltip) {
                this.tooltip.remove();
            }
        }
    }

    // =============================================================================
    // VOICE SEARCH MANAGER
    // =============================================================================
    
    class VoiceSearchManager {
        constructor() {
            // Basic properties
            this.recognition = null;
            this.wakeRecognition = null;
            this.searchRecognition = null;
            this.isListening = false;
            this.isWakeListening = false;
            this.voiceButton = null;
            this.searchInput = null;
            this.searchUrl = '/search';
            this.isInitialized = false;
            
            // Wake word detection
            this.wakeWords = ['hai audio statistik', 'hey audio statistik', 'audio statistik'];
            
            // 🔧 FIX: Move commands definition AFTER methods are defined
            // Don't use .bind() in constructor, use arrow functions or bind in init()
            this.commands = {};
        }

        async init(searchRoute = '/search') {
            if (this.isInitialized) {
                console.log('🔄 Voice Search Manager already initialized');
                return;
            }

            if (!this.isVoiceSearchSupported()) {
                console.log('ℹ️ Voice search not supported in this browser');
                return;
            }

            this.searchUrl = searchRoute;
            this.isInitialized = true;
            
            console.log('🎤 Initializing Voice Search Manager');
            
            // 🔧 FIX: Define commands HERE after methods exist
            this.setupCommands();
            this.setupWakeWordRecognition();
            this.setupSearchRecognition();
            this.setupVoiceButton();
            this.setupKeyboardListeners();
            this.startWakeWordListening();
            this.createCompatibilityBridge();
        }

        // 🔧 FIX: Define commands after all methods are available
        setupCommands() {
            this.commands = {
                'bantuan': () => this.showHelp(),
                'help': () => this.showHelp(),
                'search': () => this.startSearch(),
                'cari': () => this.startSearch()
            };
        }

        isVoiceSearchSupported() {
            return 'webkitSpeechRecognition' in window || 'SpeechRecognition' in window;
        }

        setupWakeWordRecognition() {
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            this.wakeRecognition = new SpeechRecognition();
            
            this.wakeRecognition.continuous = true;
            this.wakeRecognition.interimResults = false;
            this.wakeRecognition.lang = 'id-ID';
            
            this.wakeRecognition.onresult = (event) => {
                const transcript = event.results[event.results.length - 1][0].transcript.toLowerCase().trim();
                console.log('🎯 Wake word detected:', transcript);
                
                if (this.wakeWords.some(word => transcript.includes(word))) {
                    this.handleWakeWordDetected();
                } else if (this.commands[transcript]) {
                    this.commands[transcript]();
                }
            };

            this.wakeRecognition.onerror = (event) => {
                console.warn('⚠️ Wake word recognition error:', event.error);
                if (event.error !== 'no-speech') {
                    setTimeout(() => this.startWakeWordListening(), 1000);
                }
            };
        }

        setupSearchRecognition() {
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            this.searchRecognition = new SpeechRecognition();
            
            this.searchRecognition.continuous = false;
            this.searchRecognition.interimResults = true;
            this.searchRecognition.lang = 'id-ID';
            
            this.searchRecognition.onresult = (event) => {
                const transcript = event.results[event.results.length - 1][0].transcript;
                console.log('🔍 Search transcript:', transcript);
                
                if (this.searchInput) {
                    this.searchInput.value = transcript;
                }
                
                if (event.results[event.results.length - 1].isFinal) {
                    this.performSearch(transcript);
                }
            };

            this.searchRecognition.onend = () => {
                this.isListening = false;
                this.updateVoiceButtonState(false);
                this.startWakeWordListening();
            };

            this.searchRecognition.onerror = (event) => {
                console.warn('⚠️ Search recognition error:', event.error);
                this.isListening = false;
                this.updateVoiceButtonState(false);
                this.startWakeWordListening();
            };
        }

        setupVoiceButton() {
            this.voiceButton = document.querySelector('[data-voice-search]') || 
                            document.querySelector('.voice-search-btn') ||
                            document.getElementById('voice-search-btn');
            
            if (this.voiceButton) {
                this.voiceButton.addEventListener('click', () => this.toggleVoiceSearch());
            }
            
            this.searchInput = document.querySelector('input[name="search"]') ||
                            document.querySelector('#search-input') ||
                            document.querySelector('.search-input');
        }

        setupKeyboardListeners() {
            document.addEventListener('keydown', (event) => {
                if (!window.location.pathname.includes('login') && !window.location.pathname.includes('register')) {
                    if (event.ctrlKey && !event.altKey && !event.shiftKey && !event.metaKey) {
                        event.preventDefault();
                        this.toggleVoiceSearch();
                    }
                }
            });
        }

        startWakeWordListening() {
            if (!this.wakeRecognition || this.isWakeListening) return;
            
            try {
                this.isWakeListening = true;
                this.wakeRecognition.start();
                console.log('👂 Wake word listening started');
            } catch (error) {
                console.warn('⚠️ Could not start wake word listening:', error);
                this.isWakeListening = false;
            }
        }

        stopWakeWordListening() {
            if (this.wakeRecognition && this.isWakeListening) {
                this.wakeRecognition.stop();
                this.isWakeListening = false;
                console.log('🛑 Wake word listening stopped');
            }
        }

        handleWakeWordDetected() {
            console.log('🎯 Wake word detected - starting voice search');
            this.stopWakeWordListening();
            this.startVoiceSearch();
        }

        toggleVoiceSearch() {
            if (this.isListening) {
                this.stopVoiceSearch();
            } else {
                this.startVoiceSearch();
            }
        }

        startVoiceSearch() {
            if (!this.searchRecognition || this.isListening) return;
            
            this.stopWakeWordListening();
            this.isListening = true;
            this.updateVoiceButtonState(true);
            
            try {
                this.searchRecognition.start();
                console.log('🎤 Voice search started');
                this.announceToScreenReader('Voice search activated. Speak your search query.');
            } catch (error) {
                console.error('❌ Could not start voice search:', error);
                this.isListening = false;
                this.updateVoiceButtonState(false);
                this.startWakeWordListening();
            }
        }

        stopVoiceSearch() {
            if (this.searchRecognition && this.isListening) {
                this.searchRecognition.stop();
                this.isListening = false;
                this.updateVoiceButtonState(false);
                console.log('🛑 Voice search stopped');
                this.startWakeWordListening();
            }
        }

        performSearch(query) {
            if (!query.trim()) return;
            
            console.log('🔍 Performing search for:', query);
            
            const searchUrl = new URL(this.searchUrl, window.location.origin);
            searchUrl.searchParams.set('search', query.trim());
            
            window.location.href = searchUrl.toString();
        }

        // 🔧 FIX: Define method BEFORE it's used in commands
        showHelp() {
            const helpText = 'Perintah suara yang tersedia: ' +
                            'Katakan "Hai Audio Statistik" untuk mengaktifkan pencarian suara. ' +
                            'Tekan tombol Ctrl untuk pencarian suara manual. ' +
                            'Katakan "bantuan" untuk mendengar panduan ini.';
            
            const helpMessage = new SpeechSynthesisUtterance(helpText);
            helpMessage.lang = 'id-ID';
            helpMessage.rate = 0.9;
            window.speechSynthesis.speak(helpMessage);
        }

        // 🔧 FIX: Define method BEFORE it's used in commands
        startSearch() {
            this.startVoiceSearch();
        }

        updateVoiceButtonState(listening) {
            if (this.voiceButton) {
                if (listening) {
                    this.voiceButton.classList.add('listening', 'active', 'text-red-600');
                    this.voiceButton.classList.remove('text-gray-700');
                    this.voiceButton.setAttribute('aria-label', 'Stop voice search');
                } else {
                    this.voiceButton.classList.remove('listening', 'active', 'text-red-600');
                    this.voiceButton.classList.add('text-gray-700');
                    this.voiceButton.setAttribute('aria-label', 'Start voice search');
                }
            }
        }

        announceToScreenReader(message) {
            let announcement = document.getElementById('voice-announcement');
            if (!announcement) {
                announcement = document.createElement('div');
                announcement.id = 'voice-announcement';
                announcement.className = 'sr-only';
                announcement.setAttribute('aria-live', 'polite');
                announcement.setAttribute('aria-atomic', 'true');
                document.body.appendChild(announcement);
            }
            
            announcement.textContent = message;
            
            setTimeout(() => {
                announcement.textContent = '';
            }, 1000);
        }

        createCompatibilityBridge() {
            if (!window.AudioSystem) {
                window.AudioSystem = {};
            }
            
            window.AudioSystem.initializeVoiceSearch = (searchRoute) => {
                console.log('🌉 Using compatibility bridge for voice search');
                if (!this.isInitialized) {
                    this.init(searchRoute);
                }
                return this;
            };
            
            if (!window.voiceCommand) {
                window.voiceCommand = {
                    init: () => {
                        console.log('🌉 Using compatibility bridge for voice command');
                        if (!this.isInitialized) {
                            this.init();
                        }
                    }
                };
            }
        }

        initializeVoiceSearch(searchRoute) {
            return this.init(searchRoute);
        }

        cleanup() {
            try {
                if (this.wakeRecognition) this.wakeRecognition.stop();
                if (this.searchRecognition) this.searchRecognition.stop();
            } catch(e) {
                console.warn('Cleanup error:', e);
            }
            
            this.isListening = false;
            this.isWakeListening = false;
            this.isInitialized = false;
        }
    }

    // =============================================================================
    // WELCOME MESSAGE MANAGER
    // =============================================================================
    
    class WelcomeMessageManager {
        constructor() {
            this.hasShownWelcome = false;
            this.isInitialized = false;
        }

        async init() {
            if (this.isInitialized) {
                console.log('🔄 Welcome Message Manager already initialized');
                return;
            }

            this.isInitialized = true;
            console.log('🎉 Initializing Welcome Message Manager');

            // Check if we're on home page and should show welcome
            if (this.shouldShowWelcome()) {
                await this.showWelcomeMessage();
            }
        }

        shouldShowWelcome() {
            // Only show on home page
            const isHomePage = window.location.pathname === '/' || 
                            window.location.pathname === '/home';
            
            if (!isHomePage) return false;

            // Check sessionStorage (same as blade script) for consistency
            const welcomed = sessionStorage.getItem('welcomed');
            return !welcomed;
        }

        async showWelcomeMessage() {
            try {
                // Wait a bit to ensure other components are loaded
                await this.delay(1500);

                const welcomeText = 'Selamat datang di Audio Statistik, portal audio untuk publikasi dan berita resmi statistik BPS Sulawesi Utara. ' +
                                'Gunakan tombol Ctrl untuk pencarian suara, atau katakan "Hai Audio Statistik". ' +
                                'Katakan "bantuan" untuk mendengar panduan lengkap perintah suara.';

                // Use Speech Synthesis (same as blade script)
                const welcomeMessage = new SpeechSynthesisUtterance(welcomeText);
                welcomeMessage.lang = 'id-ID';
                welcomeMessage.rate = 0.9;
                
                // Add event listeners
                welcomeMessage.onstart = () => {
                    console.log('🔊 Welcome message started');
                };
                
                welcomeMessage.onend = () => {
                    console.log('✅ Welcome message completed');
                    // Mark as welcomed using sessionStorage for consistency
                    sessionStorage.setItem('welcomed', 'true');
                    this.hasShownWelcome = true;
                };

                welcomeMessage.onerror = (event) => {
                    console.error('❌ Welcome message error:', event);
                    sessionStorage.setItem('welcomed', 'true'); // Still mark as shown
                };

                // Speak the message
                window.speechSynthesis.speak(welcomeMessage);

            } catch (error) {
                console.error('❌ Error showing welcome message:', error);
                sessionStorage.setItem('welcomed', 'true'); // Mark as shown even on error
            }
        }

        delay(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        }

        // Method for manual trigger (if needed)
        forceShowWelcome() {
            sessionStorage.removeItem('welcomed');
            this.hasShownWelcome = false;
            this.showWelcomeMessage();
        }

        // Reset for testing
        resetWelcome() {
            sessionStorage.removeItem('welcomed');
            this.hasShownWelcome = false;
            this.isInitialized = false;
        }

        cleanup() {
            // Stop any ongoing speech
            if (window.speechSynthesis.speaking) {
                window.speechSynthesis.cancel();
            }
        }
    }

    // =============================================================================
    // UNIFIED AUDIO MANAGER
    // =============================================================================
    
    class UnifiedAudioManager {
        constructor() {
            // Core properties
            this.currentAudio = null;
            this.currentDocument = null;
            this.currentFormat = CONFIG.audio.defaultFormat;
            this.isPlaying = false;
            this.volume = 1.0;
            this.playbackRate = 1.0;
            this.progressInterval = null;
            this.retryCount = 0;
            
            // UI elements
            this.bottomPlayer = null;
            this.playPauseBtn = null;
            this.progressBar = null;
            this.currentTimeEl = null;
            this.totalTimeEl = null;
            
            // Event handlers (bound methods)
            this.boundEventHandlers = {};
            
            // Integration handlers
            this.integrationHandlers = new Map();

            // Seeking properties
            this.isSeeking = false;
            this.seekDebounceTimer = null;
            this.seekStartTime = null;
            this.targetSeekTime = null;
            this.isWaitingForSeek = false;
            
            // Performance properties
            this.lastSeekTime = 0;
            this.seekCooldown = 100;
        }

        async init() {
            console.log('🎵 Initializing Unified Audio Manager');
            
            await this.initializeAudioElement();
            await this.initializeUI();
            await this.setupEventListeners();
            await this.setupIntegrations();
            await this.setupKeyboardShortcuts();
            
            console.log('✅ Unified Audio Manager initialized');
        }

        async initializeAudioElement() {
            console.log('🎵 Initializing audio element with enhanced setup');
            
            // Remove existing main audio element if any
            const existingAudio = document.getElementById('main-audio-element');
            if (existingAudio) {
                console.log('🗑️ Removing existing main audio element');
                existingAudio.remove();
            }
            
            // Create fresh audio element
            this.currentAudio = document.createElement('audio');
            this.currentAudio.id = 'main-audio-element';
            this.currentAudio.preload = 'none';
            this.currentAudio.crossOrigin = 'anonymous';
            
            // Add to DOM but keep hidden
            this.currentAudio.style.display = 'none';
            document.body.appendChild(this.currentAudio);
            
            console.log('✅ Fresh main audio element created and added to DOM');
            
            // Setup audio event listeners
            this.setupAudioEventListeners();
            
            // Verify audio element
            const verification = document.getElementById('main-audio-element');
            if (!verification) {
                throw new Error('Failed to create main audio element');
            }
            
            console.log('✅ Audio element verification passed');
        }

        async initializeUI() {
            console.log('🎨 Initializing Audio Player UI with forced cleanup');
            
            // STEP 1: Force remove ALL existing audio players
            this.forceCleanupExistingPlayers();
            
            // STEP 2: Wait a bit for DOM cleanup
            await new Promise(resolve => setTimeout(resolve, 100));
            
            // STEP 3: Create fresh player
            await this.createBottomPlayer();
            
            // STEP 4: Cache UI elements
            this.cacheUIElements();
            
            // STEP 5: Verify UI integrity
            this.verifyUIIntegrity();
        }

        forceCleanupExistingPlayers() {
            console.log('🧹 Force cleaning existing audio players...');
            
            // Remove by ID (most specific)
            const existingPlayer = document.getElementById('bottom-audio-player');
            if (existingPlayer) {
                console.log('🗑️ Removing existing bottom-audio-player');
                existingPlayer.remove();
            }
            
            // Remove by class (backup)
            const playersByClass = document.querySelectorAll('.audio-player, .bottom-audio-player, .fixed.bottom-0');
            playersByClass.forEach((player, index) => {
                if (player.id !== 'bottom-audio-player' && 
                    (player.classList.contains('audio-player') || 
                    player.innerHTML.includes('play-pause-btn') ||
                    player.innerHTML.includes('current-doc-title'))) {
                    console.log(`🗑️ Removing duplicate audio player ${index}`);
                    player.remove();
                }
            });
            
            // Remove sidebar if exists
            const existingSidebar = document.getElementById('right-sidebar');
            if (existingSidebar) {
                console.log('🗑️ Removing existing sidebar');
                existingSidebar.remove();
            }
            
            // Clear any cached references
            this.bottomPlayer = null;
            this.playPauseBtn = null;
            this.progressBar = null;
            this.progressContainer = null;
            this.currentTimeEl = null;
            this.totalTimeEl = null;
        }

        async createBottomPlayer() {
            console.log('🏗️ Creating responsive Spotify-like audio player');
            
            const playerHTML = `
                <!-- Main Audio Player -->
                <div id="bottom-audio-player" class="fixed bottom-0 left-0 bg-gray-900 text-white shadow-2xl transform translate-y-full transition-transform duration-300 hidden z-40" 
                    style="right: 0; width: 100%;">
                    
                    <!-- Loading indicator -->
                    <div id="loading-indicator" class="absolute top-0 left-0 right-0 h-1 bg-blue-600 opacity-0 transition-opacity duration-300 hidden">
                        <div class="h-full bg-blue-400 animate-pulse"></div>
                    </div>
                    
                    <!-- Desktop Layout -->
                    <div class="hidden md:block">
                        <div class="px-6 py-4">
                            <!-- Top Row: Info + Controls + Volume -->
                            <div class="flex items-center justify-between mb-3">
                                <!-- Left: Document Info -->
                                <div class="flex items-center space-x-4 min-w-0 flex-1">
                                    <div class="relative flex-shrink-0">
                                        <img id="current-doc-cover" src="" alt="" class="w-14 h-14 object-cover rounded-lg shadow-md">
                                        <div id="cover-loading" class="absolute inset-0 bg-gray-700 rounded-lg animate-pulse hidden"></div>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <h4 id="current-doc-title" class="text-sm font-medium truncate hover:text-blue-300 cursor-pointer transition-colors leading-tight">-</h4>
                                        <p id="current-doc-indicator" class="text-xs text-gray-400 truncate mt-1">-</p>
                                    </div>
                                </div>

                                <!-- Center: Playback Controls -->
                                <div class="flex items-center space-x-6 flex-shrink-0">
                                    <button id="skip-backward-btn" class="text-gray-400 hover:text-white transition-colors p-2 rounded-full hover:bg-gray-800" 
                                            title="Mundur 10 detik" aria-label="Mundur 10 detik">
                                        <i class="fas fa-backward text-lg"></i>
                                    </button>
                                    
                                    <button id="play-pause-btn" class="bg-white hover:bg-gray-100 text-gray-900 rounded-full w-12 h-12 flex items-center justify-center transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-gray-900 transform hover:scale-105 active:scale-95">
                                        <i class="fas fa-play text-lg"></i>
                                        <span class="sr-only">Play/Pause</span>
                                    </button>
                                    
                                    <button id="skip-forward-btn" class="text-gray-400 hover:text-white transition-colors p-2 rounded-full hover:bg-gray-800" 
                                            title="Maju 10 detik" aria-label="Maju 10 detik">
                                        <i class="fas fa-forward text-lg"></i>
                                    </button>
                                </div>

                                <!-- Right: Volume + More Options -->
                                <div class="flex items-center space-x-4 min-w-0 flex-1 justify-end">
                                    <!-- Time Display -->
                                    <div class="text-xs text-gray-400 flex items-center space-x-2">
                                        <span id="current-time-desktop" class="min-w-[35px] text-right">00:00</span>
                                        <span>/</span>
                                        <span id="total-time-desktop" class="min-w-[35px]">00:00</span>
                                    </div>
                                    
                                    <!-- Volume Control -->
                                    <div class="flex items-center space-x-3">
                                        <button id="mute-btn" class="text-gray-400 hover:text-white transition-colors" title="Toggle Mute">
                                            <i id="volume-icon" class="fas fa-volume-up text-sm"></i>
                                        </button>
                                        <input id="volume-slider" type="range" min="0" max="100" value="100" 
                                            class="w-20 h-1 bg-gray-600 rounded-lg appearance-none cursor-pointer slider">
                                    </div>
                                    
                                    <!-- Speed Control -->
                                    <button id="speed-btn" class="text-xs px-2 py-1 text-gray-400 hover:text-white transition-colors rounded hover:bg-gray-800 min-w-[32px]" title="Playback Speed">
                                        <span id="speed-display">1x</span>
                                    </button>
                                    
                                    <!-- More Options -->
                                    <button id="more-options-btn" class="text-gray-400 hover:text-white transition-colors p-2 rounded-full hover:bg-gray-800" title="Detail Dokumen">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    
                                    <!-- Close Button -->
                                    <button id="close-player-btn" class="text-gray-400 hover:text-red-400 transition-colors p-2 rounded-full hover:bg-gray-800" title="Close Player">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Bottom Row: Progress Bar (Full Width) -->
                            <div class="w-full">
                                <div class="flex items-center space-x-3">
                                    <span id="current-time-main" class="text-xs text-gray-400 min-w-[35px] text-right">00:00</span>
                                    <div id="progress-container" class="flex-1 h-1 bg-gray-600 rounded-full cursor-pointer relative group">
                                        <div id="progress-bar" class="h-full bg-white rounded-full transition-all duration-150 relative" style="width: 0%">
                                            <div class="absolute right-0 top-1/2 transform translate-x-1/2 -translate-y-1/2 w-3 h-3 bg-white rounded-full opacity-0 group-hover:opacity-100 transition-opacity shadow-lg"></div>
                                        </div>
                                        <div class="absolute inset-0 rounded-full opacity-0 group-hover:opacity-100 bg-white bg-opacity-10 transition-opacity"></div>
                                    </div>
                                    <span id="total-time-main" class="text-xs text-gray-400 min-w-[35px]">00:00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Mobile Layout -->
                    <div class="block md:hidden">
                        <div class="px-4 py-3">
                            <!-- Top Row: Info + More Button -->
                            <div class="flex items-center justify-between mb-3">
                                <!-- Document Info -->
                                <div class="flex items-center space-x-3 min-w-0 flex-1">
                                    <img id="current-doc-cover-mobile" src="" alt="" class="w-10 h-10 object-cover rounded shadow-sm">
                                    <div class="min-w-0 flex-1">
                                        <h4 id="current-doc-title-mobile" class="text-sm font-medium truncate">-</h4>
                                        <p id="current-doc-indicator-mobile" class="text-xs text-gray-400 truncate">-</p>
                                    </div>
                                </div>
                                
                                <!-- More Options -->
                                <button id="more-options-btn-mobile" class="text-gray-400 hover:text-white p-2" title="Detail">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                            </div>
                            
                            <!-- Controls Row -->
                            <div class="flex items-center justify-center space-x-8 mb-3">
                                <button id="skip-backward-btn-mobile" class="text-gray-400 hover:text-white transition-colors">
                                    <i class="fas fa-backward text-xl"></i>
                                </button>
                                
                                <button id="play-pause-btn-mobile" class="bg-white text-gray-900 rounded-full w-14 h-14 flex items-center justify-center">
                                    <i class="fas fa-play text-xl"></i>
                                </button>
                                
                                <button id="skip-forward-btn-mobile" class="text-gray-400 hover:text-white transition-colors">
                                    <i class="fas fa-forward text-xl"></i>
                                </button>
                            </div>
                            
                            <!-- Progress Bar -->
                            <div class="w-full">
                                <div class="flex items-center space-x-2">
                                    <span id="current-time-mobile" class="text-xs text-gray-400 min-w-[30px] text-right">00:00</span>
                                    <div id="progress-container-mobile" class="flex-1 h-1 bg-gray-600 rounded-full cursor-pointer relative">
                                        <div id="progress-bar-mobile" class="h-full bg-white rounded-full" style="width: 0%"></div>
                                    </div>
                                    <span id="total-time-mobile" class="text-xs text-gray-400 min-w-[30px]">00:00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Create sidebar HTML separately to avoid nesting
            const sidebarHTML = `
                <!-- Sidebar - Positioned independently -->
                <div id="right-sidebar" class="fixed top-0 right-0 h-full bg-gray-800 text-white transform translate-x-full transition-transform duration-300 overflow-y-auto shadow-2xl z-50" 
                    style="width: 380px; border-left: 1px solid rgba(255,255,255,0.1);">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-semibold">Detail Dokumen</h3>
                            <button id="close-sidebar-btn" class="text-gray-400 hover:text-white p-2 rounded-full hover:bg-gray-700 transition-colors">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        
                        <div class="space-y-6">
                            <div class="relative">
                                <img id="sidebar-doc-cover" src="" alt="" class="w-full h-56 object-cover rounded-lg shadow-lg">
                                <div id="sidebar-cover-loading" class="absolute inset-0 bg-gray-700 rounded-lg animate-pulse hidden"></div>
                            </div>
                            
                            <div class="space-y-3">
                                <h4 id="sidebar-doc-title" class="text-xl font-medium leading-tight">-</h4>
                                <p id="sidebar-doc-indicator" class="text-sm text-blue-300 font-medium">-</p>
                                <p id="sidebar-doc-date" class="text-sm text-gray-400">-</p>
                            </div>
                            
                            <div>
                                <h5 class="text-sm font-medium mb-3 text-gray-300">Deskripsi</h5>
                                <div id="sidebar-doc-description" class="text-sm text-gray-300 leading-relaxed max-h-40 overflow-y-auto bg-gray-900 rounded-lg p-4">-</div>
                            </div>
                            
                            <div>
                                <h5 class="text-sm font-medium mb-3 text-gray-300">Informasi Audio</h5>
                                <div class="bg-gray-900 rounded-lg p-4 space-y-3">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-400">Durasi:</span>
                                        <span id="sidebar-audio-duration" class="text-sm text-white font-medium">-</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-400">Format:</span>
                                        <span id="sidebar-audio-format" class="text-sm text-white font-medium">MP3</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-400">Kualitas:</span>
                                        <span class="text-sm text-green-400 font-medium">High Quality</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-400">Ukuran:</span>
                                        <span class="text-sm text-white font-medium">~5MB</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="pt-4 border-t border-gray-700 space-y-3">
                                <button id="download-audio-btn" class="w-full bg-blue-600 hover:bg-blue-700 px-4 py-3 rounded-lg text-sm font-medium transition-colors flex items-center justify-center shadow-lg">
                                    <i class="fas fa-download mr-2"></i>
                                    Download Audio
                                </button>
                                
                                <button id="share-btn" class="w-full bg-gray-700 hover:bg-gray-600 px-4 py-3 rounded-lg text-sm font-medium transition-colors flex items-center justify-center">
                                    <i class="fas fa-share mr-2"></i>
                                    Bagikan Dokumen
                                </button>
                                
                                <button id="view-document-btn" class="w-full bg-green-600 hover:bg-green-700 px-4 py-3 rounded-lg text-sm font-medium transition-colors flex items-center justify-center group">
                                    <i class="fas fa-eye mr-2"></i>
                                    <span>Lihat Dokumen</span>
                                    <i class="fas fa-external-link-alt ml-2 text-xs opacity-60 group-hover:opacity-100"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Remove existing elements
            const existingPlayer = document.getElementById('bottom-audio-player');
            const existingSidebar = document.getElementById('right-sidebar');
            
            if (existingPlayer) existingPlayer.remove();
            if (existingSidebar) existingSidebar.remove();
            
            // Add new elements
            document.body.insertAdjacentHTML('beforeend', playerHTML);
            document.body.insertAdjacentHTML('beforeend', sidebarHTML);
            
            this.bottomPlayer = document.getElementById('bottom-audio-player');
            
            console.log('✅ Responsive audio player created successfully');
        }

        cacheUIElements() {
            console.log('💾 Caching responsive UI elements...');
            
            // Cache primary elements (desktop)
            this.playPauseBtn = document.getElementById('play-pause-btn');
            this.progressBar = document.getElementById('progress-bar');
            this.progressContainer = document.getElementById('progress-container');
            this.currentTimeEl = document.getElementById('current-time-main');
            this.totalTimeEl = document.getElementById('total-time-main');
            
            // Cache mobile elements
            this.playPauseBtnMobile = document.getElementById('play-pause-btn-mobile');
            this.progressBarMobile = document.getElementById('progress-bar-mobile');
            this.progressContainerMobile = document.getElementById('progress-container-mobile');
            
            // Verify core elements exist
            const coreElements = {
                'Play/Pause Button (Desktop)': this.playPauseBtn,
                'Play/Pause Button (Mobile)': this.playPauseBtnMobile,
                'Progress Bar (Desktop)': this.progressBar,
                'Progress Bar (Mobile)': this.progressBarMobile
            };
            
            let missingElements = [];
            Object.entries(coreElements).forEach(([name, element]) => {
                if (!element) {
                    missingElements.push(name);
                    console.error(`❌ Missing UI element: ${name}`);
                } else {
                    // console.log(`✅ Cached: ${name}`);
                }
            });
            
            // console.log(`✅ Cached ${Object.keys(coreElements).length - missingElements.length}/${Object.keys(coreElements).length} responsive UI elements`);
            
            return missingElements.length === 0;
        }

        verifyUIIntegrity() {
            console.log('🔍 Verifying UI integrity...');
            
            const playerCount = document.querySelectorAll('#bottom-audio-player').length;
            const sidebarCount = document.querySelectorAll('#right-sidebar').length;
            
            console.log(`📊 UI Integrity Check:
            - Bottom Players: ${playerCount}
            - Sidebars: ${sidebarCount}
            - Play Button Exists: ${!!this.playPauseBtn}
            - Progress Bar Exists: ${!!this.progressBar}`);
            
            if (playerCount > 1) {
                console.warn('⚠️ Multiple bottom players detected! Cleaning up...');
                this.forceCleanupExistingPlayers();
                return false;
            }
            
            return true;
        }

        setupAudioEventListeners() {
            if (!this.currentAudio) return;

            // Remove existing listeners
            this.removeAudioEventListeners();

            // Create bound handlers
            this.boundEventHandlers = {
                loadstart: () => this.handleLoadStart(),
                canplay: () => this.handleCanPlay(),
                timeupdate: () => this.handleTimeUpdate(),
                ended: () => this.handleEnded(),
                error: (e) => this.handleError(e),
                play: () => this.handlePlay(),
                pause: () => this.handlePause(),
                volumechange: () => this.handleVolumeChange()
            };

            // Add event listeners
            Object.entries(this.boundEventHandlers).forEach(([event, handler]) => {
                this.currentAudio.addEventListener(event, handler);
            });
        }

        removeAudioEventListeners() {
            if (!this.currentAudio || !this.boundEventHandlers) return;

            Object.entries(this.boundEventHandlers).forEach(([event, handler]) => {
                this.currentAudio.removeEventListener(event, handler);
            });
        }

        async setupEventListeners() {
            console.log('🎛️ Setting up responsive event listeners...');
            
            // Verify UI elements exist
            if (!this.cacheUIElements()) {
                console.error('❌ Cannot setup event listeners - missing UI elements');
                return false;
            }
            
            // Setup desktop listeners
            this.setupDesktopListeners();
            
            // Setup mobile listeners
            this.setupMobileListeners();
            
            // Setup shared listeners
            this.setupSharedListeners();
            
            // Verify listeners
            this.verifyEventListeners();
            
            console.log('✅ Responsive event listeners setup complete');
            return true;
        }

        setupDesktopListeners() {
            // Desktop play/pause
            const playPauseBtn = document.getElementById('play-pause-btn');
            if (playPauseBtn) {
                this.attachListener(playPauseBtn, 'click', () => this.togglePlayPause());
            }
            
            // Desktop progress
            const progressContainer = document.getElementById('progress-container');
            if (progressContainer) {
                this.attachListener(progressContainer, 'click', (e) => this.handleProgressClick(e));
            }
            
            // Desktop skip buttons
            this.attachListener(document.getElementById('skip-backward-btn'), 'click', () => this.skipBackward(10));
            this.attachListener(document.getElementById('skip-forward-btn'), 'click', () => this.skipForward(10));
            
            // Volume controls
            const volumeSlider = document.getElementById('volume-slider');
            const muteBtn = document.getElementById('mute-btn');
            
            if (volumeSlider) {
                this.attachListener(volumeSlider, 'input', (e) => {
                    console.log('🔊 Volume changed:', e.target.value);
                    this.setVolume(e.target.value / 100);
                });
            }
            
            if (muteBtn) {
                this.attachListener(muteBtn, 'click', () => {
                    console.log('🔇 Mute button clicked');
                    this.toggleMute();
                });
            }
            
            // Speed control
            this.attachListener(document.getElementById('speed-btn'), 'click', () => this.cyclePlaybackSpeed());
            
            // More options
            this.attachListener(document.getElementById('more-options-btn'), 'click', () => this.showSidebar());
            
            // Close button
            this.attachListener(document.getElementById('close-player-btn'), 'click', () => this.closePlayer());
        }

        setupMobileListeners() {
            // Mobile play/pause
            this.attachListener(document.getElementById('play-pause-btn-mobile'), 'click', () => this.togglePlayPause());
            
            // Mobile progress
            this.attachListener(document.getElementById('progress-container-mobile'), 'click', (e) => this.handleProgressClick(e));
            
            // Mobile skip buttons
            this.attachListener(document.getElementById('skip-backward-btn-mobile'), 'click', () => this.skipBackward(10));
            this.attachListener(document.getElementById('skip-forward-btn-mobile'), 'click', () => this.skipForward(10));
            
            // Mobile more options
            this.attachListener(document.getElementById('more-options-btn-mobile'), 'click', () => this.showSidebar());
        }

        setupSharedListeners() {
            // Sidebar listeners
            this.attachListener(document.getElementById('close-sidebar-btn'), 'click', () => this.hideSidebar());
            this.attachListener(document.getElementById('download-audio-btn'), 'click', () => this.downloadCurrentAudio());
            this.attachListener(document.getElementById('share-btn'), 'click', () => this.shareCurrentDocument());

            // View document button - use direct route navigation
            const viewDocBtn = document.getElementById('view-document-btn');
            if (viewDocBtn) {
                viewDocBtn.addEventListener('click', () => {
                    if (this.currentDocument) {
                        const docUrl = `/dokumen/${this.currentDocument.slug || this.currentDocument.id}`;
                        window.open(docUrl, '_blank');
                        console.log('✅ Opening document:', this.currentDocument.title);
                    }
                });
                viewDocBtn.setAttribute('data-listener-attached', 'true');
            }

            // Title clicks for sidebar
            this.attachListener(document.getElementById('current-doc-title'), 'click', () => this.showSidebar());
            this.attachListener(document.getElementById('current-doc-title-mobile'), 'click', () => this.showSidebar());
        }

        // Helper method to attach listeners with verification
        // attachListener(element, event, handler) {
        //     if (element && handler) {
        //         element.addEventListener(event, handler);
        //         element.setAttribute('data-listener-attached', 'true');
        //         console.log(`✅ Attached ${event} listener to ${element.id || element.className}`);
        //     }
        // }

        setupPlayPauseListener() {
            const playPauseBtn = document.getElementById('play-pause-btn');
            if (playPauseBtn) {
                // Remove existing listeners
                const newBtn = playPauseBtn.cloneNode(true);
                playPauseBtn.parentNode.replaceChild(newBtn, playPauseBtn);
                
                // Cache new reference
                this.playPauseBtn = newBtn;
                
                // Add new listener
                this.playPauseBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('🎵 Play/Pause button clicked (enhanced)');
                    this.togglePlayPause();
                });
                
                // Verify listener attachment
                this.playPauseBtn.setAttribute('data-listener-attached', 'true');
                console.log('✅ Play/Pause listener attached and verified');
            } else {
                console.error('❌ Play/Pause button not found for listener attachment');
            }
        }

        setupProgressListener() {
            const progressContainer = document.getElementById('progress-container');
            if (progressContainer) {
                // Remove existing listeners
                const newContainer = progressContainer.cloneNode(true);
                progressContainer.parentNode.replaceChild(newContainer, progressContainer);
                
                // Cache new reference  
                this.progressContainer = newContainer;
                this.progressBar = newContainer.querySelector('#progress-bar');
                
                // Add new listener
                this.progressContainer.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('📊 Progress bar clicked (enhanced)');
                    this.handleProgressClick(e);
                });
                
                // Verify listener attachment
                this.progressContainer.setAttribute('data-listener-attached', 'true');
                console.log('✅ Progress listener attached and verified');
            } else {
                console.error('❌ Progress container not found for listener attachment');
            }
        }

        setupVolumeListeners() {
            // Volume slider
            if (this.volumeSlider) {
                this.volumeSlider.addEventListener('input', (e) => {
                    console.log('🔊 Volume changed:', e.target.value);
                    this.setVolume(e.target.value / 100);
                });
                console.log('✅ Volume slider listener attached');
            }
            
            // Mute button
            if (this.muteBtn) {
                this.muteBtn.addEventListener('click', () => {
                    console.log('🔇 Mute button clicked');
                    this.toggleMute();
                });
                console.log('✅ Mute button listener attached');
            }
        }

        setupControlListeners() {
            // Skip buttons
            const skipBackBtn = document.getElementById('skip-backward-btn');
            const skipForwardBtn = document.getElementById('skip-forward-btn');
            
            if (skipBackBtn) {
                skipBackBtn.addEventListener('click', () => {
                    console.log('⏮️ Skip backward clicked');
                    this.skipBackward(10);
                });
            }
            
            if (skipForwardBtn) {
                skipForwardBtn.addEventListener('click', () => {
                    console.log('⏭️ Skip forward clicked');
                    this.skipForward(10);
                });
            }
            
            // Speed control
            if (this.speedBtn) {
                this.speedBtn.addEventListener('click', () => {
                    console.log('⚡ Speed button clicked');
                    this.cyclePlaybackSpeed();
                });
            }
            
            // More options (show sidebar)
            const moreOptionsBtn = document.getElementById('more-options-btn');
            if (moreOptionsBtn) {
                moreOptionsBtn.addEventListener('click', () => {
                    console.log('⚙️ More options clicked');
                    this.showSidebar();
                });
            }
            
            // Close button
            const closeBtn = document.getElementById('close-player-btn');
            if (closeBtn) {
                closeBtn.addEventListener('click', () => {
                    console.log('❌ Close button clicked');
                    this.closePlayer();
                });
            }
            
            // Title click (show sidebar)
            if (this.titleEl) {
                this.titleEl.addEventListener('click', () => {
                    console.log('📄 Title clicked');
                    this.showSidebar();
                });
            }
        }

        setupSidebarListeners() {
            // Download button
            const downloadBtn = document.getElementById('download-audio-btn');
            if (downloadBtn) {
                downloadBtn.addEventListener('click', () => {
                    console.log('💾 Download button clicked');
                    this.downloadCurrentAudio();
                });
            }
            
            // Share button
            const shareBtn = document.getElementById('share-btn');
            if (shareBtn) {
                shareBtn.addEventListener('click', () => {
                    console.log('🔗 Share button clicked');
                    this.shareCurrentDocument();
                });
            }
            
            // Sidebar close
            const closeSidebarBtn = document.getElementById('close-sidebar-btn');
            if (closeSidebarBtn) {
                closeSidebarBtn.addEventListener('click', () => {
                    console.log('❌ Sidebar close clicked');
                    this.hideSidebar();
                });
            }
        }

        removeAllEventListeners() {
            // Clone and replace elements to remove all listeners
            const elementsToClean = [
                'play-pause-btn',
                'progress-container',
                'volume-slider',
                'mute-btn',
                'speed-btn',
                'skip-backward-btn',
                'skip-forward-btn',
                'more-options-btn',
                'close-player-btn',
                'current-doc-title'
            ];
            
            elementsToClean.forEach(id => {
                const element = document.getElementById(id);
                if (element) {
                    const newElement = element.cloneNode(true);
                    element.parentNode.replaceChild(newElement, element);
                }
            });
            
            console.log('🧹 Cleaned existing event listeners');
        }

        verifyEventListeners() {
            console.log('🔍 Enhanced event listener verification...');
            
            const testResults = {
                playPause: this.testElementListener('play-pause-btn', 'click'),
                progress: this.testElementListener('progress-container', 'click'), 
                volume: this.testElementListener('volume-slider', 'input'),
                mute: this.testElementListener('mute-btn', 'click')
            };
            
            console.log('📊 Enhanced Event Listener Verification:', testResults);
            
            const workingListeners = Object.values(testResults).filter(Boolean).length;
            const totalListeners = Object.keys(testResults).length;
            
            console.log(`✅ ${workingListeners}/${totalListeners} core listeners verified (enhanced)`);
            
            return workingListeners >= 2; // At least play and progress should work
        }

        // testElementListener(elementId, eventType) {
        //     const element = document.getElementById(elementId);
        //     if (!element) {
        //         console.warn(`⚠️ Element ${elementId} not found for listener test`);
        //         return false;
        //     }
            
        //     // Check for data attribute we set during listener attachment
        //     const hasListener = element.hasAttribute('data-listener-attached');
            
        //     // Also check for actual event listeners (basic check)
        //     const hasOnClick = element.onclick !== null;
        //     const hasAttribute = element.getAttribute(`on${eventType}`) !== null;
            
        //     const result = hasListener || hasOnClick || hasAttribute;
        //     console.log(`🔍 Listener test for ${elementId}: ${result}`);
            
        //     return result;
        // }

        hasEventListener(element, eventType) {
            if (!element) return false;
            
            // Check if element has event listeners (this is a simplified check)
            return element.getAttribute(`on${eventType}`) !== null || element[`on${eventType}`] !== null;
        }

        async setupIntegrations() {
            // Setup page-specific integrations
            this.setupPublicationIntegration();
            this.setupAdminIntegration();
            this.setupGridIntegration();
            this.setupUniversalIntegration();
        }

        setupPublicationIntegration() {
            this.integrationHandlers.set('publication', {
                selector: '.play-audio-btn, .document-play-btn',
                handler: (button) => this.handlePublicationButtonClick(button)
            });
        }

        setupAdminIntegration() {
            this.integrationHandlers.set('admin', {
                selector: '.admin-play-btn, .document-play-button',
                handler: (button) => this.handleAdminButtonClick(button)
            });
        }

        setupGridIntegration() {
            this.integrationHandlers.set('grid', {
                selector: '.play-document-btn',
                handler: (button) => this.handleGridButtonClick(button)
            });
        }

        setupUniversalIntegration() {
            // Universal click handler for all audio buttons
            document.addEventListener('click', (e) => {
                const button = e.target.closest('.play-audio-btn, .admin-play-btn, .play-document-btn, .document-play-button');
                if (button && !button.hasAttribute('data-audio-handled')) {
                    button.setAttribute('data-audio-handled', 'true');
                    this.handleUniversalButtonClick(button);
                }
            });
        }

        async setupKeyboardShortcuts() {
            document.addEventListener('keydown', (e) => {
                // Only activate when player is visible and not typing
                const playerVisible = this.bottomPlayer && !this.bottomPlayer.classList.contains('hidden');
                const isTyping = e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.isContentEditable;
                
                if (!playerVisible || isTyping) return;

                switch(e.code) {
                    case 'Space':
                        e.preventDefault();
                        this.togglePlayPause();
                        break;
                    case 'ArrowLeft':
                        e.preventDefault();
                        this.skipBackward(10);
                        break;
                    case 'ArrowRight':
                        e.preventDefault();
                        this.skipForward(10);
                        break;
                    case 'KeyM':
                        e.preventDefault();
                        this.toggleMute();
                        break;
                    case 'Escape':
                        e.preventDefault();
                        this.closePlayer();
                        break;
                }
            });
        }

        // Audio Event Handlers
        handleLoadStart() {
            console.log('📥 Audio loading started');
            this.showLoadingState(true);
        }

        handleCanPlay() {
            console.log('✅ Audio can play');
            this.showLoadingState(false);
            this.updateDurationDisplay();
        }

        handleTimeUpdate() {
            this.updateProgressBar();
            this.updateTimeDisplays();
            
            // Auto-save progress periodically
            if (this.currentDocument && this.currentAudio.currentTime > 0) {
                this.saveProgressToBackend();
            }
        }

        handleEnded() {
            console.log('🏁 Audio playback ended');
            this.isPlaying = false;
            this.updatePlayPauseButton();
            this.announceToScreenReader('Audio selesai diputar');
        }

        handleError(e) {
            console.error('❌ Audio error:', e);
            this.showLoadingState(false);
            this.handleAudioError(e);
        }

        handlePlay() {
            this.isPlaying = true;
            this.updatePlayPauseButton();
        }

        handlePause() {
            this.isPlaying = false;
            this.updatePlayPauseButton();
        }

        handleVolumeChange() {
            this.updateVolumeDisplay();
        }

        // Button Click Handlers
        handlePublicationButtonClick(button) {
            const docData = this.extractDocumentDataFromButton(button, 'publication');
            if (docData) {
                this.playDocument(docData);
            }
        }

        handleAdminButtonClick(button) {
            const docData = this.extractDocumentDataFromButton(button, 'admin');
            if (docData) {
                this.playDocument(docData);
            }
        }

        handleGridButtonClick(button) {
            const docData = this.extractDocumentDataFromButton(button, 'grid');
            if (docData) {
                this.playDocument(docData);
            }
        }

        handleUniversalButtonClick(button) {
            console.log('🎵 Universal button click detected');
            console.log('🔍 Button element:', button);
            console.log('🔍 Button classes:', button.className);
            console.log('🔍 Button dataset:', button.dataset);
            
            // Determine context based on page URL and button location
            let context = 'publication'; // default
            
            if (button.closest('.admin-panel, .admin-content, .admin-table')) {
                context = 'admin';
            } else if (button.closest('.publication-grid, .document-grid, .grid')) {
                context = 'grid';
            } else if (window.location.pathname.includes('/admin')) {
                context = 'admin';
            } else if (window.location.pathname.includes('/publikasi')) {
                context = 'publication';
            } else if (window.location.pathname.includes('/brs')) {
                context = 'brs';
            }
            
            console.log('📍 Detected context:', context);
            
            let docData = null;
            
            try {
                // Try context-specific extraction first
                switch (context) {
                    case 'admin':
                        docData = this.extractDocumentDataFromButton(button, 'admin');
                        break;
                    case 'grid':
                    case 'publication':
                    case 'brs':
                        docData = this.extractDocumentDataFromButton(button, 'publication');
                        break;
                    default:
                        docData = this.extractDocumentDataFromButton(button, 'publication');
                }
                
                if (docData && docData.id) {
                    console.log('✅ Successfully extracted document data:', docData.title);
                    this.playDocument(docData);
                } else {
                    console.warn('⚠️ Could not extract document data from button');
                    console.log('🔍 Attempted extraction result:', docData);
                    
                    // Show user-friendly error
                    this.showErrorMessage('Tidak dapat memutar audio. Data dokumen tidak ditemukan.');
                    
                    // Try fallback method
                    this.tryFallbackPlayback(button);
                }
                
            } catch (error) {
                console.error('❌ Error in universal button click handler:', error);
                this.showErrorMessage('Terjadi kesalahan saat mencoba memutar audio.');
                
                // Try fallback method
                this.tryFallbackPlayback(button);
            }
        }

        // Fallback playback method
        tryFallbackPlayback(button) {
            console.log('🔄 Attempting fallback playback...');
            
            // Try to find any ID in the button or nearby elements
            const possibleIds = [
                button.dataset.documentId,
                button.dataset.id,
                button.getAttribute('data-document-id'),
                button.getAttribute('data-id'),
                button.closest('[data-document-id]')?.dataset.documentId,
                button.closest('[data-id]')?.dataset.id
            ].filter(Boolean);
            
            if (possibleIds.length > 0) {
                const id = possibleIds[0];
                console.log('🆔 Found fallback ID:', id);
                
                // Create minimal document data
                const fallbackData = {
                    id: id,
                    title: button.getAttribute('aria-label') || 
                        button.closest('.group')?.querySelector('.font-medium')?.textContent?.trim() || 
                        'Audio Document',
                    type: 'publication',
                    indicator: { name: 'Unknown' }
                };
                
                console.log('🎵 Attempting fallback playback with:', fallbackData);
                this.playDocument(fallbackData);
            } else {
                console.error('❌ No fallback options available');
                this.announceToScreenReader('Tidak dapat memutar audio dokumen');
            }
        }

        extractDocumentDataFromButton(button, context) {
            console.log('🔍 Extracting document data from button, context:', context);
            
            let docData = {};
            
            // Method 1: From data-document attribute (publications.blade.php menggunakan ini)
            const documentDataAttr = button.dataset.document || button.getAttribute('data-document');
            if (documentDataAttr) {
                try {
                    console.log('📄 Found data-document attribute');
                    const parsedData = JSON.parse(documentDataAttr);
                    
                    // Ensure we have required fields
                    if (parsedData.id && parsedData.title) {
                        console.log('✅ Successfully parsed document data from data-document:', parsedData.title);
                        
                        // Normalize the data structure
                        docData = {
                            id: parsedData.id,
                            title: parsedData.title,
                            slug: parsedData.slug,
                            year: parsedData.year || new Date().getFullYear(),
                            indicator: parsedData.indicator || { name: 'Unknown' },
                            audio_duration_formatted: parsedData.audio_duration_formatted || this.formatDuration(parsedData.audio_duration_seconds) || '00:00',
                            description: parsedData.description || parsedData.excerpt || '',
                            type: parsedData.type || 'publication',
                            // Copy all other fields
                            ...parsedData
                        };
                        
                        return docData;
                    }
                } catch (error) {
                    console.error('❌ Failed to parse data-document:', error);
                }
            }

            // Method 2: From individual data attributes
            const id = button.dataset.documentId || button.dataset.id || button.getAttribute('data-document-id');
            if (id) {
                console.log('📄 Found individual data attributes, ID:', id);
                
                docData = {
                    id: id,
                    title: button.dataset.title || button.getAttribute('data-title') || button.getAttribute('aria-label') || 'Unknown Document',
                    slug: button.dataset.slug || button.getAttribute('data-slug'),
                    indicator: { 
                        name: button.dataset.indicator || button.getAttribute('data-indicator') || 'Unknown'
                    },
                    year: button.dataset.year || button.getAttribute('data-year') || new Date().getFullYear(),
                    audio_duration_formatted: button.dataset.duration || button.getAttribute('data-duration') || '00:00',
                    type: button.dataset.type || button.getAttribute('data-type') || 'publication'
                };
                
                console.log('✅ Extracted from individual attributes:', docData.title);
                return docData;
            }

            // Method 3: From parent document card (untuk grid layout)
            const documentCard = button.closest('.document-card, .bg-white, .publication-item, .group');
            if (documentCard) {
                console.log('📄 Searching in parent document card');
                
                // Look for title in various selectors
                const titleEl = documentCard.querySelector('.document-title, .doc-title, h3, h4, .font-medium, .text-lg') ||
                            documentCard.querySelector('[data-title]') ||
                            documentCard.querySelector('a[href*="/dokumen/"]');
                
                // Look for indicator
                const indicatorEl = documentCard.querySelector('.indicator, .category, .type, .badge') ||
                                documentCard.querySelector('[data-indicator]');
                
                // Look for year
                const yearEl = documentCard.querySelector('.year, [data-year]') ||
                            documentCard.querySelector('.text-gray-500, .text-sm');
                
                // Try to get ID from various sources
                const cardId = documentCard.dataset.documentId || 
                            documentCard.dataset.id ||
                            documentCard.querySelector('[data-document-id]')?.dataset.documentId ||
                            documentCard.querySelector('[data-id]')?.dataset.id;
                
                if (titleEl || cardId) {
                    docData = {
                        id: cardId || this.extractIdFromElement(titleEl),
                        title: titleEl?.textContent?.trim() || titleEl?.dataset.title || 'Unknown Document',
                        indicator: { 
                            name: indicatorEl?.textContent?.trim() || indicatorEl?.dataset.indicator || 'Unknown'
                        },
                        year: this.extractYear(yearEl?.textContent) || new Date().getFullYear(),
                        type: 'publication'
                    };
                    
                    if (docData.id) {
                        console.log('✅ Extracted from document card:', docData.title);
                        return docData;
                    }
                }
            }

            // Method 4: From URL or href (untuk link-based buttons)
            const link = button.closest('a') || button.querySelector('a') || button.parentElement?.querySelector('a');
            if (link && link.href) {
                console.log('📄 Searching from link href:', link.href);
                
                const urlMatch = link.href.match(/\/dokumen\/(.+?)(?:\/|$|\?|#)/);
                if (urlMatch) {
                    const slug = urlMatch[1];
                    
                    docData = {
                        slug: slug,
                        title: link.textContent?.trim() || link.getAttribute('title') || 'Document',
                        id: this.getIdFromSlug(slug), // We'll implement this
                        type: 'publication'
                    };
                    
                    console.log('✅ Extracted from URL:', docData.title);
                    return docData;
                }
            }

            // Method 5: Try to find document data in nearby elements
            const nearbyElements = [
                button.parentElement,
                button.parentElement?.parentElement,
                button.closest('.grid > div'),
                button.closest('[data-document]')
            ].filter(Boolean);

            for (const element of nearbyElements) {
                const foundData = this.searchElementForDocumentData(element);
                if (foundData && foundData.id) {
                    console.log('✅ Found document data in nearby element:', foundData.title);
                    return foundData;
                }
            }

            console.warn('⚠️ Could not extract document data from any method');
            console.log('🔍 Button details:', {
                className: button.className,
                dataset: button.dataset,
                attributes: Array.from(button.attributes).map(attr => `${attr.name}="${attr.value}"`),
                innerHTML: button.innerHTML.substring(0, 100)
            });
            
            return null;
        }

        // Helper methods untuk data extraction:
        extractIdFromElement(element) {
            if (!element) return null;
            
            // Try data attributes
            const id = element.dataset.documentId || element.dataset.id;
            if (id) return id;
            
            // Try to extract from href
            if (element.href) {
                const match = element.href.match(/\/dokumen\/(\d+)/);
                if (match) return match[1];
            }
            
            return null;
        }

        extractYear(text) {
            if (!text) return null;
            
            const yearMatch = text.match(/\b(20\d{2})\b/);
            return yearMatch ? parseInt(yearMatch[1]) : null;
        }

        formatDuration(seconds) {
            if (!seconds || isNaN(seconds)) return null;
            
            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = Math.floor(seconds % 60);
            
            return `${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
        }

        searchElementForDocumentData(element) {
            if (!element) return null;
            
            // Check for data-document attribute
            const documentData = element.dataset.document || element.getAttribute('data-document');
            if (documentData) {
                try {
                    return JSON.parse(documentData);
                } catch (e) {
                    console.warn('Failed to parse document data from element');
                }
            }
            
            // Check for individual data attributes
            const id = element.dataset.documentId || element.dataset.id;
            const title = element.dataset.title || element.querySelector('.font-medium, h3, h4')?.textContent?.trim();
            
            if (id && title) {
                return {
                    id: id,
                    title: title,
                    type: 'publication'
                };
            }
            
            return null;
        }

        // Simple slug to ID mapping (you may need to implement this based on your needs)
        getIdFromSlug(slug) {
            // Option 1: Make an API call to get ID from slug
            // Option 2: Extract ID if slug contains ID
            // Option 3: Use slug as ID temporarily
            
            // For now, let's try to extract ID from slug if it's in format like "title-123"
            const idMatch = slug.match(/-(\d+)$/);
            if (idMatch) {
                return idMatch[1];
            }
            
            // Otherwise, we'll need to make an API call or use the slug
            console.log('📞 Making API call to resolve slug to ID:', slug);
            this.resolveDocumentFromSlug(slug).then(doc => {
                if (doc) {
                    // Re-trigger playback with resolved data
                    this.playDocument(doc);
                }
            });
            
            return null;
        }

        // Enhanced slug resolution with API call
        async resolveDocumentFromSlug(slug) {
            try {
                console.log('🔍 Resolving document from slug:', slug);
                
                // Try the API endpoint
                const response = await fetch(`/api/documents/by-slug/${slug}`);
                if (response.ok) {
                    const docData = await response.json();
                    console.log('✅ Resolved document from API:', docData.title);
                    return docData;
                }
                
                // Fallback: try to extract from current page context
                const pageContext = this.getDocumentFromPageContext(slug);
                if (pageContext) {
                    console.log('✅ Resolved document from page context:', pageContext.title);
                    return pageContext;
                }
                
            } catch (error) {
                console.error('❌ Failed to resolve document from slug:', error);
            }
            
            return null;
        }

        getDocumentFromPageContext(slug) {
            // Try to find document data in the current page
            const scripts = document.querySelectorAll('script');
            
            for (const script of scripts) {
                if (script.textContent.includes(slug)) {
                    // Try to extract document data from script content
                    const match = script.textContent.match(new RegExp(`"slug":\\s*"${slug}"[^}]+}`));
                    if (match) {
                        try {
                            // This is a simplified extraction, you may need to adjust
                            const jsonStr = '{' + match[0];
                            return JSON.parse(jsonStr);
                        } catch (e) {
                            console.warn('Failed to parse document from page context');
                        }
                    }
                }
            }
            
            return null;
        }

        // Core Playback Methods
        async playDocument(docData) {
            try {
                console.log('🎵 Starting playDocument for:', docData.title);
                
                // Stop all existing audio
                this.stopAllAudio();
                
                // Set current document
                this.currentDocument = docData;
                
                // Show and update UI
                this.showAudioPlayer();
                this.updatePlayerUI(docData);
                
                // Load and play audio
                await this.loadAndPlayAudio(docData);
                
                // Announce to screen reader
                this.announceToScreenReader(`Memutar dokumen: ${docData.title}`);
                
                console.log('✅ Document playback initiated successfully');
                
            } catch (error) {
                console.error('❌ playDocument error:', error);
                this.showErrorMessage('Gagal memutar dokumen audio');
                this.showLoadingState(false);
            }
        }

        async loadAndPlayAudio(docData) {
            if (!this.currentAudio) {
                throw new Error('Audio element not found');
            }
            
            // Construct audio URL using AudioController route
            const audioUrl = `/audio/stream/${docData.id}/${this.currentFormat}`;
            console.log('🔗 Loading audio from:', audioUrl);
            
            // Show loading state
            this.showLoadingState(true);
            
            // Set audio source
            this.currentAudio.src = audioUrl;
            this.currentAudio.load();
            
            // Wait for loading to complete
            return new Promise((resolve, reject) => {
                const timeout = setTimeout(() => {
                    reject(new Error('Audio loading timeout'));
                }, CONFIG.audio.loadTimeout);
                
                const onCanPlay = () => {
                    clearTimeout(timeout);
                    this.currentAudio.removeEventListener('canplay', onCanPlay);
                    this.currentAudio.removeEventListener('error', onError);
                    this.showLoadingState(false);
                    resolve();
                };
                
                const onError = (error) => {
                    clearTimeout(timeout);
                    this.currentAudio.removeEventListener('canplay', onCanPlay);
                    this.currentAudio.removeEventListener('error', onError);
                    this.showLoadingState(false);
                    reject(new Error('Audio loading failed'));
                };
                
                this.currentAudio.addEventListener('canplay', onCanPlay, { once: true });
                this.currentAudio.addEventListener('error', onError, { once: true });
            });
        }

        async togglePlayPause() {
            if (!this.currentAudio || !this.currentDocument) {
                console.warn('⚠️ No audio loaded for play/pause');
                this.announceToScreenReader('Tidak ada audio yang dimuat');
                return;
            }

            try {
                console.log(`🎵 Toggle play/pause - Current state: ${this.isPlaying ? 'Playing' : 'Paused'}`);
                
                if (this.isPlaying) {
                    // Pause audio
                    this.currentAudio.pause();
                    this.isPlaying = false;
                    console.log('⏸️ Audio paused successfully');
                    this.announceToScreenReader('Audio dijeda');
                } else {
                    // Play audio with enhanced error handling
                    await this.playAudioSafely();
                }
                
                this.updatePlayPauseButton();
                
            } catch (error) {
                console.error('❌ Play/pause error:', error);
                
                // Handle specific AbortError
                if (error.name === 'AbortError') {
                    console.log('🔄 Handling AbortError - retrying playback');
                    
                    // Wait a moment then retry
                    setTimeout(async () => {
                        try {
                            if (!this.isPlaying && this.currentAudio) {
                                await this.playAudioSafely();
                                this.updatePlayPauseButton();
                            }
                        } catch (retryError) {
                            console.error('❌ Retry failed:', retryError);
                            this.showErrorMessage('Gagal memutar audio setelah percobaan ulang');
                        }
                    }, 100);
                } else {
                    this.showErrorMessage('Gagal memutar/menjeda audio');
                }
            }
        }

        async playAudioSafely() {
            if (!this.currentAudio) {
                throw new Error('No audio element available');
            }
            
            // Check if audio is ready
            if (this.currentAudio.readyState < 2) {
                console.log('⏳ Waiting for audio to be ready...');
                await this.waitForAudioReady();
            }
            
            // Check if source is loaded
            if (!this.currentAudio.src || this.currentAudio.src === window.location.href) {
                console.log('🔄 Reloading audio source...');
                await this.loadAndPlayAudio(this.currentDocument);
                return;
            }
            
            // Play with promise handling
            const playPromise = this.currentAudio.play();
            
            if (playPromise !== undefined) {
                await playPromise;
                this.isPlaying = true;
                console.log('▶️ Audio playing successfully');
                this.announceToScreenReader('Audio diputar');
            }
        }

        async waitForAudioReady() {
            return new Promise((resolve, reject) => {
                const timeout = setTimeout(() => {
                    reject(new Error('Audio ready timeout'));
                }, 5000);
                
                if (this.currentAudio.readyState >= 2) {
                    clearTimeout(timeout);
                    resolve();
                    return;
                }
                
                const onCanPlay = () => {
                    clearTimeout(timeout);
                    this.currentAudio.removeEventListener('canplay', onCanPlay);
                    this.currentAudio.removeEventListener('error', onError);
                    resolve();
                };
                
                const onError = (error) => {
                    clearTimeout(timeout);
                    this.currentAudio.removeEventListener('canplay', onCanPlay);
                    this.currentAudio.removeEventListener('error', onError);
                    reject(error);
                };
                
                this.currentAudio.addEventListener('canplay', onCanPlay, { once: true });
                this.currentAudio.addEventListener('error', onError, { once: true });
            });
        }

        async switchFormat(format) {
            if (!this.currentDocument || format === this.currentFormat) {
                return;
            }
            
            const wasPlaying = this.isPlaying;
            const currentTime = this.currentAudio ? this.currentAudio.currentTime : 0;
            
            try {
                console.log(`🔄 Switching to ${format} format`);
                
                this.showLoadingState(true);
                this.currentFormat = format;
                
                // Update format button states immediately
                this.updateFormatButtons();
                
                // Pause current audio
                if (this.currentAudio && !this.currentAudio.paused) {
                    this.currentAudio.pause();
                }
                
                // Construct new URL
                const audioUrl = `/audio/stream/${this.currentDocument.id}/${format}`;
                console.log('🔗 New format URL:', audioUrl);
                
                // Set new source
                this.currentAudio.src = audioUrl;
                this.currentAudio.load();
                
                // Wait for new format to load with better timeout handling
                await new Promise((resolve, reject) => {
                    const timeout = setTimeout(() => {
                        reject(new Error('Format switch timeout after 30 seconds'));
                    }, 30000); // Increased timeout
                    
                    const onCanPlay = () => {
                        clearTimeout(timeout);
                        this.currentAudio.removeEventListener('canplay', onCanPlay);
                        this.currentAudio.removeEventListener('error', onError);
                        
                        // Restore position and playing state
                        if (currentTime > 0 && this.currentAudio.duration) {
                            this.currentAudio.currentTime = Math.min(currentTime, this.currentAudio.duration);
                        }
                        
                        if (wasPlaying) {
                            this.currentAudio.play().catch(console.error);
                        }
                        
                        resolve();
                    };
                    
                    const onError = (error) => {
                        clearTimeout(timeout);
                        this.currentAudio.removeEventListener('canplay', onCanPlay);
                        this.currentAudio.removeEventListener('error', onError);
                        reject(new Error(`Failed to load ${format} format: ${error.message || 'Unknown error'}`));
                    };
                    
                    this.currentAudio.addEventListener('canplay', onCanPlay, { once: true });
                    this.currentAudio.addEventListener('error', onError, { once: true });
                });
                
                this.showLoadingState(false);
                this.updateSidebarFormat();
                this.announceToScreenReader(`Format audio diubah ke ${format.toUpperCase()}`);
                
                console.log(`✅ Successfully switched to ${format} format`);
                
            } catch (error) {
                console.error('❌ Error switching format:', error);
                this.showLoadingState(false);
                
                // Revert to previous format
                this.currentFormat = format === 'mp3' ? 'flac' : 'mp3';
                this.updateFormatButtons();
                
                this.showErrorMessage(`Gagal mengganti ke format ${format.toUpperCase()}. ${error.message}`);
            }
        }

        stopAllAudio() {
            // Stop main audio
            if (this.currentAudio && !this.currentAudio.paused) {
                this.currentAudio.pause();
                this.currentAudio.currentTime = 0;
            }

            // Stop all inline audio players
            document.querySelectorAll('audio').forEach(audio => {
                if (!audio.paused) {
                    audio.pause();
                    audio.currentTime = 0;
                }
            });

            this.isPlaying = false;
            console.log('🛑 All audio stopped');
        }

        closePlayer() {
            this.stopAllAudio();
            if (this.bottomPlayer) {
                this.bottomPlayer.classList.add('hidden');
                this.bottomPlayer.classList.remove('translate-y-0');
                this.bottomPlayer.classList.add('translate-y-full');
            }
            this.currentDocument = null;
            this.announceToScreenReader('Pemutar audio ditutup');
        }

        // Additional Controls
        skipForward(seconds = 10) {
            if (this.currentAudio) {
                this.currentAudio.currentTime = Math.min(
                    this.currentAudio.currentTime + seconds,
                    this.currentAudio.duration || 0
                );
                this.announceToScreenReader(`Maju ${seconds} detik`);
            }
        }

        skipBackward(seconds = 10) {
            if (this.currentAudio) {
                this.currentAudio.currentTime = Math.max(
                    this.currentAudio.currentTime - seconds,
                    0
                );
                this.announceToScreenReader(`Mundur ${seconds} detik`);
            }
        }

        toggleMute() {
            if (this.currentAudio) {
                this.currentAudio.muted = !this.currentAudio.muted;
                this.announceToScreenReader(this.currentAudio.muted ? 'Audio dibisukan' : 'Audio tidak dibisukan');
            }
        }

        handleProgressClick(e) {
            if (!this.currentAudio || !this.currentAudio.duration) {
                console.warn('Cannot seek: audio not ready');
                return;
            }

            // Prevent rapid clicking
            const now = Date.now();
            if (now - this.lastSeekTime < this.seekCooldown) {
                console.log('Seek cooldown active, ignoring click');
                return;
            }

            const rect = e.currentTarget.getBoundingClientRect();
            const clickX = e.clientX - rect.left;
            const progress = Math.max(0, Math.min(1, clickX / rect.width));
            const seekTime = progress * this.currentAudio.duration;

            console.log(`🎯 Seeking to ${this.formatTime(seekTime)} (${Math.round(progress * 100)}%)`);
            
            this.performSeek(seekTime);
            this.lastSeekTime = now;
        }

        async performSeek(targetTime) {
            if (this.isSeeking) {
                console.log('Already seeking, queuing new seek time');
                this.targetSeekTime = targetTime;
                return;
            }

            try {
                this.isSeeking = true;
                this.isWaitingForSeek = true;
                this.seekStartTime = Date.now();
                
                // Show loading state immediately
                this.showSeekingState(true);
                
                // Update progress bar visually (optimistic update)
                this.updateProgressBarOptimistic(targetTime);
                
                // Perform the actual seek
                await this.seekToTime(targetTime);
                
            } catch (error) {
                console.error('Seek failed:', error);
                this.showErrorMessage('Gagal melompat ke posisi tersebut');
                
                // Restore original progress bar position
                this.updateProgressBar();
                
            } finally {
                this.isSeeking = false;
                this.isWaitingForSeek = false;
                this.showSeekingState(false);
                
                // Handle queued seek if any
                if (this.targetSeekTime !== null) {
                    const queuedTime = this.targetSeekTime;
                    this.targetSeekTime = null;
                    
                    // Slight delay to prevent overwhelming
                    setTimeout(() => {
                        this.performSeek(queuedTime);
                    }, 50);
                }
            }
        }

        // Core seeking logic with timeout
        async seekToTime(targetTime) {
            return new Promise((resolve, reject) => {
                // Set timeout for seek operation
                const seekTimeout = setTimeout(() => {
                    cleanup();
                    reject(new Error('Seek timeout - audio may not be fully loaded'));
                }, 5000);

                // Audio event handlers for seek completion
                const onSeeked = () => {
                    console.log('✅ Seek completed successfully');
                    cleanup();
                    resolve();
                };

                const onError = (error) => {
                    console.error('❌ Seek error:', error);
                    cleanup();
                    reject(error);
                };

                const onStalled = () => {
                    console.warn('⚠️ Audio stalled during seek');
                    // Don't reject immediately, give it more time
                };

                const cleanup = () => {
                    clearTimeout(seekTimeout);
                    this.currentAudio.removeEventListener('seeked', onSeeked);
                    this.currentAudio.removeEventListener('error', onError);
                    this.currentAudio.removeEventListener('stalled', onStalled);
                };

                // Attach event listeners
                this.currentAudio.addEventListener('seeked', onSeeked, { once: true });
                this.currentAudio.addEventListener('error', onError, { once: true });
                this.currentAudio.addEventListener('stalled', onStalled, { once: true });

                // Perform the seek
                try {
                    this.currentAudio.currentTime = targetTime;
                    
                    // Update time display immediately for better UX
                    this.updateTimeDisplays();
                    
                } catch (error) {
                    cleanup();
                    reject(error);
                }
            });
        }

        // Visual feedback during seeking
        showSeekingState(show) {
            // Update progress bar with seeking class
            const progressBars = ['progress-bar', 'progress-bar-mobile'];
            
            progressBars.forEach(id => {
                const bar = document.getElementById(id);
                if (bar) {
                    if (show) {
                        bar.classList.add('seeking');
                        bar.style.transition = 'none'; // Disable transition during seek
                    } else {
                        bar.classList.remove('seeking');
                        bar.style.transition = ''; // Restore transition
                    }
                }
            });

            // Show seeking indicator
            const seekingIndicators = document.querySelectorAll('.seeking-indicator');
            seekingIndicators.forEach(indicator => {
                if (show) {
                    indicator.classList.remove('hidden');
                    indicator.classList.add('animate-spin');
                } else {
                    indicator.classList.add('hidden');
                    indicator.classList.remove('animate-spin');
                }
            });

            // Update progress container with seeking state
            const progressContainers = ['progress-container', 'progress-container-mobile'];
            progressContainers.forEach(id => {
                const container = document.getElementById(id);
                if (container) {
                    if (show) {
                        container.classList.add('seeking');
                        container.style.cursor = 'wait';
                    } else {
                        container.classList.remove('seeking');
                        container.style.cursor = 'pointer';
                    }
                }
            });

            console.log(show ? '⏳ Showing seeking state' : '✅ Hiding seeking state');
        }

        // Optimistic progress bar update for immediate feedback
        updateProgressBarOptimistic(seekTime) {
            if (!this.currentAudio || !this.currentAudio.duration) return;

            const progress = (seekTime / this.currentAudio.duration) * 100;
            
            // Update both desktop and mobile progress bars
            const progressBars = ['progress-bar', 'progress-bar-mobile'];
            progressBars.forEach(id => {
                const bar = document.getElementById(id);
                if (bar) {
                    bar.style.width = `${Math.max(0, Math.min(100, progress))}%`;
                    bar.classList.add('seeking');
                }
            });

            // Update time display optimistically
            const timeElements = ['current-time-main', 'current-time-desktop', 'current-time-mobile'];
            timeElements.forEach(id => {
                this.updateElementSafely(id, this.formatTime(seekTime));
            });

            console.log(`🎯 Optimistic update: ${this.formatTime(seekTime)} (${Math.round(progress)}%)`);
        }

        // Enhanced progress bar with drag support
        setupEnhancedProgressBar() {
            const progressContainers = ['progress-container', 'progress-container-mobile'];
            
            progressContainers.forEach(containerId => {
                const container = document.getElementById(containerId);
                if (!container) return;

                // Remove existing click listener
                const newContainer = container.cloneNode(true);
                container.parentNode.replaceChild(newContainer, container);

                // Add enhanced event listeners
                this.addProgressBarListeners(newContainer);
            });
        }

        addProgressBarListeners(container) {
            let isDragging = false;
            let dragStartX = 0;
            let dragStartTime = 0;

            // Mouse events
            container.addEventListener('mousedown', (e) => {
                if (!this.currentAudio || !this.currentAudio.duration) return;
                
                isDragging = true;
                dragStartX = e.clientX;
                dragStartTime = this.currentAudio.currentTime;
                
                container.classList.add('dragging');
                document.body.style.userSelect = 'none';
                
                e.preventDefault();
            });

            document.addEventListener('mousemove', (e) => {
                if (!isDragging || !this.currentAudio || !this.currentAudio.duration) return;
                
                const rect = container.getBoundingClientRect();
                const progress = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
                const seekTime = progress * this.currentAudio.duration;
                
                // Update visual progress immediately
                this.updateProgressBarOptimistic(seekTime);
                
                e.preventDefault();
            });

            document.addEventListener('mouseup', (e) => {
                if (!isDragging) return;
                
                isDragging = false;
                container.classList.remove('dragging');
                document.body.style.userSelect = '';
                
                if (this.currentAudio && this.currentAudio.duration) {
                    const rect = container.getBoundingClientRect();
                    const progress = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
                    const seekTime = progress * this.currentAudio.duration;
                    
                    this.performSeek(seekTime);
                }
            });

            // Touch events for mobile
            container.addEventListener('touchstart', (e) => {
                if (!this.currentAudio || !this.currentAudio.duration) return;
                
                const touch = e.touches[0];
                isDragging = true;
                dragStartX = touch.clientX;
                
                container.classList.add('dragging');
                e.preventDefault();
            });

            container.addEventListener('touchmove', (e) => {
                if (!isDragging || !this.currentAudio || !this.currentAudio.duration) return;
                
                const touch = e.touches[0];
                const rect = container.getBoundingClientRect();
                const progress = Math.max(0, Math.min(1, (touch.clientX - rect.left) / rect.width));
                const seekTime = progress * this.currentAudio.duration;
                
                this.updateProgressBarOptimistic(seekTime);
                e.preventDefault();
            });

            container.addEventListener('touchend', (e) => {
                if (!isDragging) return;
                
                isDragging = false;
                container.classList.remove('dragging');
                
                if (this.currentAudio && this.currentAudio.duration && e.changedTouches.length > 0) {
                    const touch = e.changedTouches[0];
                    const rect = container.getBoundingClientRect();
                    const progress = Math.max(0, Math.min(1, (touch.clientX - rect.left) / rect.width));
                    const seekTime = progress * this.currentAudio.duration;
                    
                    this.performSeek(seekTime);
                }
            });

            // Click for non-drag users
            container.addEventListener('click', (e) => {
                if (isDragging) return; // Ignore clicks during drag
                
                this.handleProgressClick(e);
            });
        }

        // Preload optimization
        optimizeAudioPreloading() {
            if (!this.currentAudio) return;

            // Set preload to metadata initially
            this.currentAudio.preload = 'metadata';
            
            // Upgrade to auto preloading after user interaction
            const upgradePreload = () => {
                if (this.currentAudio) {
                    this.currentAudio.preload = 'auto';
                    console.log('📥 Upgraded audio preloading to auto');
                }
            };

            // Upgrade preload on first user interaction
            ['click', 'touchstart'].forEach(event => {
                document.addEventListener(event, upgradePreload, { once: true });
            });

            // Enable range requests for better seeking
            if (this.currentAudio.crossOrigin !== 'anonymous') {
                this.currentAudio.crossOrigin = 'anonymous';
            }
        }

        // Audio buffer monitoring
        monitorAudioBuffer() {
            if (!this.currentAudio) return;

            const checkBuffer = () => {
                if (this.currentAudio.buffered.length > 0) {
                    const bufferedEnd = this.currentAudio.buffered.end(this.currentAudio.buffered.length - 1);
                    const duration = this.currentAudio.duration || 0;
                    const bufferPercent = duration > 0 ? (bufferedEnd / duration) * 100 : 0;
                    
                    console.log(`📊 Audio buffered: ${bufferPercent.toFixed(1)}%`);
                    
                    // Update buffer indicator if exists
                    const bufferIndicator = document.getElementById('buffer-indicator');
                    if (bufferIndicator) {
                        bufferIndicator.style.width = `${bufferPercent}%`;
                    }
                }
            };

            this.currentAudio.addEventListener('progress', checkBuffer);
            
            // Initial check
            setTimeout(checkBuffer, 1000);
        }
        
        showSidebar() {
            const sidebar = document.getElementById('right-sidebar');
            const bottomPlayer = document.getElementById('bottom-audio-player');
            
            if (sidebar) {
                // Adjust bottom player margin to accommodate sidebar
                if (bottomPlayer && window.innerWidth >= 768) {
                    bottomPlayer.style.right = '380px';
                    bottomPlayer.style.width = 'calc(100% - 380px)';
                }
                
                sidebar.classList.remove('translate-x-full');
                sidebar.classList.add('translate-x-0');
                
                // Focus management
                const firstFocusable = sidebar.querySelector('button');
                if (firstFocusable) {
                    setTimeout(() => firstFocusable.focus(), 300);
                }
                
                this.announceToScreenReader('Detail dokumen dibuka');
                console.log('✅ Sidebar shown with proper spacing');
            }
        }

        hideSidebar() {
            const sidebar = document.getElementById('right-sidebar');
            const bottomPlayer = document.getElementById('bottom-audio-player');
            
            if (sidebar) {
                // Reset bottom player to full width
                if (bottomPlayer) {
                    bottomPlayer.style.right = '0';
                    bottomPlayer.style.width = '100%';
                }
                
                sidebar.classList.add('translate-x-full');
                sidebar.classList.remove('translate-x-0');
                
                this.announceToScreenReader('Detail dokumen ditutup');
                console.log('✅ Sidebar hidden and player restored to full width');
            }
        }

        shareCurrentDocument() {
            if (!this.currentDocument) return;
            
            const shareData = {
                title: this.currentDocument.title,
                text: `Dengarkan: ${this.currentDocument.title}`,
                url: window.location.origin + `/dokumen/${this.currentDocument.slug || this.currentDocument.id}`
            };
            
            if (navigator.share) {
                navigator.share(shareData).catch(console.error);
            } else {
                // Fallback: copy to clipboard
                navigator.clipboard.writeText(shareData.url).then(() => {
                    this.showToast('Link berhasil disalin ke clipboard', 'success');
                }).catch(() => {
                    this.showToast('Gagal menyalin link', 'error');
                });
            }
        }

        updateSidebarFormat() {
            const formatElement = document.getElementById('sidebar-audio-format');
            if (formatElement) {
                formatElement.textContent = this.currentFormat.toUpperCase();
            }
        }

        cyclePlaybackSpeed() {
            const speeds = [0.5, 0.75, 1.0, 1.25, 1.5, 2.0];
            const currentIndex = speeds.indexOf(this.playbackRate);
            const nextIndex = (currentIndex + 1) % speeds.length;
            
            this.setPlaybackRate(speeds[nextIndex]);
            
            const speedDisplay = document.getElementById('speed-display');
            if (speedDisplay) {
                speedDisplay.textContent = `${speeds[nextIndex]}x`;
            }
            
            this.announceToScreenReader(`Kecepatan playback: ${speeds[nextIndex]}x`);
        }

        setPlaybackRate(rate) {
            this.playbackRate = Math.max(0.25, Math.min(3, rate));
            if (this.currentAudio) {
                this.currentAudio.playbackRate = this.playbackRate;
            }
        }

        downloadCurrentAudio() {
            if (!this.currentDocument) return;
            
            const downloadUrl = `/documents/${this.currentDocument.id}/audio/${this.currentFormat}/download`;
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.download = `${this.currentDocument.title}.${this.currentFormat}`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            this.announceToScreenReader(`Mengunduh audio dalam format ${this.currentFormat.toUpperCase()}`);
        }

        showLoadingState(show) {
            const loadingIndicator = document.getElementById('loading-indicator');
            if (loadingIndicator) {
                if (show) {
                    loadingIndicator.classList.remove('hidden', 'opacity-0');
                    loadingIndicator.classList.add('opacity-100');
                } else {
                    loadingIndicator.classList.add('opacity-0');
                    setTimeout(() => {
                        loadingIndicator.classList.add('hidden');
                    }, 300);
                }
            }
        }

        // UI Update Methods
        showAudioPlayer() {
            if (this.bottomPlayer) {
                this.bottomPlayer.classList.remove('hidden', 'translate-y-full');
                this.bottomPlayer.classList.add('translate-y-0');
            }
        }

        updatePlayerUI(docData) {
            console.log('🎨 Updating responsive player UI for:', docData?.title);
            
            if (!docData) {
                console.warn('⚠️ No document data provided for UI update');
                return;
            }
            
            // Update desktop elements
            this.updateElementSafely('current-doc-title', docData.title);
            this.updateElementSafely('current-doc-indicator', docData.indicator?.name || 'Unknown');
            this.updateElementSafely('total-time-main', docData.audio_duration_formatted || '00:00');
            this.updateElementSafely('total-time-desktop', docData.audio_duration_formatted || '00:00');
            
            // Update mobile elements
            this.updateElementSafely('current-doc-title-mobile', docData.title);
            this.updateElementSafely('current-doc-indicator-mobile', docData.indicator?.name || 'Unknown');
            this.updateElementSafely('total-time-mobile', docData.audio_duration_formatted || '00:00');
            
            // Update cover images
            if (docData.id) {
                const coverUrl = `/documents/${docData.id}/cover`;
                
                const coverDesktop = document.getElementById('current-doc-cover');
                const coverMobile = document.getElementById('current-doc-cover-mobile');
                
                if (coverDesktop) this.updateImageSafely(coverDesktop, coverUrl, `Cover ${docData.title}`);
                if (coverMobile) this.updateImageSafely(coverMobile, coverUrl, `Cover ${docData.title}`);
            }
            
            // Update sidebar
            this.updateSidebarInfo(docData);
            
            console.log('✅ Responsive player UI updated successfully');
        }

        updateSidebarInfo(docData) {
            console.log('🎨 Updating sidebar info for:', docData?.title);
            
            if (!docData) {
                console.warn('⚠️ No document data provided for sidebar update');
                return;
            }
            
            const sidebarElements = {
                'sidebar-doc-title': docData.title || 'Unknown Document',
                'sidebar-doc-indicator': docData.indicator?.name || 'Unknown Category', 
                'sidebar-doc-description': docData.description || docData.excerpt || 'Tidak ada deskripsi tersedia.',
                'sidebar-doc-date': docData.year ? `Tahun ${docData.year}` : 'Tahun tidak diketahui',
                'sidebar-audio-duration': docData.audio_duration_formatted || docData.duration || '00:00',
                'sidebar-audio-format': this.currentFormat.toUpperCase()
            };

            Object.entries(sidebarElements).forEach(([id, text]) => {
                this.updateElementSafely(id, text);
            });

            // Update sidebar cover image
            const sidebarCover = document.getElementById('sidebar-doc-cover');
            if (sidebarCover && docData.id) {
                const coverUrl = `/documents/${docData.id}/cover`;
                this.updateImageSafely(sidebarCover, coverUrl, `Cover ${docData.title}`);
            }
            
            console.log('✅ Sidebar info updated successfully');
        }

        updateElementSafely(elementId, text) {
            const element = document.getElementById(elementId);
            if (element) {
                element.textContent = text;
                // console.log(`✅ Updated ${elementId}:`, text);
            } else {
                console.warn(`⚠️ Element not found: ${elementId}`);
            }
        }

        updateImageSafely(imgElement, src, alt) {
            if (imgElement) {
                // Show loading state
                const loadingEl = document.getElementById('cover-loading');
                if (loadingEl) loadingEl.classList.remove('hidden');
                
                imgElement.onload = () => {
                    if (loadingEl) loadingEl.classList.add('hidden');
                    console.log('✅ Cover image loaded');
                };
                
                imgElement.onerror = () => {
                    if (loadingEl) loadingEl.classList.add('hidden');
                    imgElement.src = '/images/default-document-cover.jpg';
                    console.warn('⚠️ Cover image failed to load, using default');
                };
                
                imgElement.src = src;
                imgElement.alt = alt;
            }
        }

        updatePlayPauseButton() {
            const buttons = [
                document.getElementById('play-pause-btn'),
                document.getElementById('play-pause-btn-mobile')
            ];
            
            buttons.forEach(button => {
                if (button) {
                    const icon = button.querySelector('i');
                    if (icon) {
                        if (this.isPlaying) {
                            icon.classList.remove('fa-play');
                            icon.classList.add('fa-pause');
                        } else {
                            icon.classList.remove('fa-pause');
                            icon.classList.add('fa-play');
                        }
                    }
                }
            });
        }

        updateFormatButtons() {
            const mp3Btn = document.getElementById('format-mp3');
            const flacBtn = document.getElementById('format-flac');
            
            if (mp3Btn && flacBtn) {
                if (this.currentFormat === 'mp3') {
                    mp3Btn.classList.add('bg-gray-700');
                    mp3Btn.classList.remove('text-gray-400');
                    flacBtn.classList.remove('bg-gray-700');
                    flacBtn.classList.add('text-gray-400');
                } else {
                    flacBtn.classList.add('bg-gray-700');
                    flacBtn.classList.remove('text-gray-400');
                    mp3Btn.classList.remove('bg-gray-700');
                    mp3Btn.classList.add('text-gray-400');
                }
            }
        }

        updateProgressBar() {
            if (!this.currentAudio) return;
            
            if (this.currentAudio.duration && this.currentAudio.duration > 0) {
                const progress = (this.currentAudio.currentTime / this.currentAudio.duration) * 100;
                const progressValue = `${Math.max(0, Math.min(100, progress))}%`;
                
                // Update desktop progress bar
                const progressBarDesktop = document.getElementById('progress-bar');
                if (progressBarDesktop) {
                    progressBarDesktop.style.width = progressValue;
                }
                
                // Update mobile progress bar
                const progressBarMobile = document.getElementById('progress-bar-mobile');
                if (progressBarMobile) {
                    progressBarMobile.style.width = progressValue;
                }
                
                // Update time displays
                this.updateTimeDisplays();
            }
        }

        updateTimeDisplays() {
            if (!this.currentAudio) return;
            
            const currentTime = this.formatTime(this.currentAudio.currentTime);
            const totalTime = this.currentAudio.duration ? this.formatTime(this.currentAudio.duration) : '00:00';
            
            // Update all time displays
            const timeElements = [
                'current-time-main',
                'current-time-desktop', 
                'current-time-mobile'
            ];
            
            const totalTimeElements = [
                'total-time-main',
                'total-time-desktop',
                'total-time-mobile'
            ];
            
            timeElements.forEach(id => {
                this.updateElementSafely(id, currentTime);
            });
            
            totalTimeElements.forEach(id => {
                this.updateElementSafely(id, totalTime);
            });
        }

        updateDurationDisplay() {
            if (!this.currentAudio || !this.totalTimeEl) return;
            
            if (this.currentAudio.duration) {
                this.totalTimeEl.textContent = this.formatTime(this.currentAudio.duration);
            }
        }

        updateVolumeDisplay() {
            // Update volume display if volume control exists
            const volumeSlider = document.getElementById('volume-slider');
            if (volumeSlider && this.currentAudio) {
                volumeSlider.value = this.currentAudio.volume * 100;
            }
        }

        showLoadingState(show) {
            const loadingIndicator = document.getElementById('loading-indicator');
            if (loadingIndicator) {
                if (show) {
                    loadingIndicator.classList.remove('hidden');
                } else {
                    loadingIndicator.classList.add('hidden');
                }
            }
        }

        // Error Handling
        handleAudioError(error) {
            console.error('💥 Audio error:', error);
            
            if (this.retryCount < CONFIG.audio.maxRetries) {
                this.retryCount++;
                console.log(`🔄 Retrying audio playback (${this.retryCount}/${CONFIG.audio.maxRetries})`);
                
                setTimeout(() => {
                    if (this.currentDocument) {
                        this.loadAndPlayAudio(this.currentDocument).catch(retryError => {
                            console.error('❌ Retry failed:', retryError);
                            this.handlePlaybackError(retryError);
                        });
                    }
                }, 2000);
            } else {
                this.handlePlaybackError(error);
            }
        }

        handlePlaybackError(error) {
            console.error('💥 Playback failed permanently:', error);
            this.showLoadingState(false);
            this.retryCount = 0;
            this.showErrorMessage('Gagal memutar audio. Silakan coba lagi.');
        }

        showErrorMessage(message) {
            console.error('🔔 Error:', message);
            this.announceToScreenReader(message);
            
            // Show toast notification if available
            if (window.showToast) {
                window.showToast(message, 'error');
            } else {
                // Fallback alert
                setTimeout(() => alert(message), 100);
            }
        }

        // Progress Management
        async saveProgressToBackend() {
            if (!this.currentDocument || !this.currentAudio || this.currentAudio.currentTime < 5) {
                return; // Don't save very short progress
            }

            try {
                const progressData = {
                    current_time: this.currentAudio.currentTime,
                    duration: this.currentAudio.duration || 0
                };

                // Save to backend
                await fetch(`/audio/progress/${this.currentDocument.id}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    },
                    body: JSON.stringify(progressData)
                });

                // Also save to localStorage as backup
                localStorage.setItem(`audio_progress_${this.currentDocument.id}`, JSON.stringify(progressData));
                
            } catch (error) {
                console.warn('⚠️ Failed to save progress:', error);
            }
        }

        async loadProgressFromBackend(documentId) {
            try {
                const response = await fetch(`/audio/progress/${documentId}`);
                if (response.ok) {
                    const data = await response.json();
                    return data.current_time || 0;
                }
            } catch (error) {
                console.warn('⚠️ Failed to load progress from backend:', error);
            }

            // Fallback to localStorage
            try {
                const stored = localStorage.getItem(`audio_progress_${documentId}`);
                if (stored) {
                    const data = JSON.parse(stored);
                    return data.current_time || 0;
                }
            } catch (error) {
                console.warn('⚠️ Failed to load progress from localStorage:', error);
            }

            return 0;
        }

        // Utility Methods
        formatTime(seconds) {
            if (isNaN(seconds) || seconds < 0) return '00:00';
            
            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = Math.floor(seconds % 60);
            
            return `${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
        }

        announceToScreenReader(message) {
            if (window.unifiedApp?.modules.accessibility) {
                window.unifiedApp.modules.accessibility.announce(message);
            } else {
                // Fallback screen reader announcement
                const announcement = document.createElement('div');
                announcement.setAttribute('aria-live', 'polite');
                announcement.setAttribute('aria-atomic', 'true');
                announcement.className = 'sr-only';
                announcement.textContent = message;
                
                document.body.appendChild(announcement);
                
                setTimeout(() => {
                    document.body.removeChild(announcement);
                }, 1000);
            }
        }

        // Page visibility handling
        handlePageHidden() {
            // Continue playing audio even when page is hidden
            console.log('📱 Page hidden - continuing audio playback');
        }

        handlePageVisible() {
            // Refresh UI when page becomes visible
            console.log('📱 Page visible - refreshing UI');
            this.updatePlayerUI(this.currentDocument);
        }

        // Public API Methods
        getCurrentDocument() {
            return this.currentDocument;
        }

        isPlayerActive() {
            return this.currentDocument !== null;
        }

        getPlaybackState() {
            return {
                isPlaying: this.isPlaying,
                currentTime: this.currentAudio ? this.currentAudio.currentTime : 0,
                duration: this.currentAudio ? this.currentAudio.duration : 0,
                volume: this.volume,
                playbackRate: this.playbackRate,
                currentFormat: this.currentFormat,
                currentDocument: this.currentDocument
            };
        }

        // Cleanup
        cleanup() {
            this.stopAllAudio();
            this.removeAudioEventListeners();
            
            if (this.progressInterval) {
                clearInterval(this.progressInterval);
            }
            
            // Save final progress
            this.saveProgressToBackend();
        }

        // =============================================================================
        // DIAGNOSTIC FUNCTIONS
        // =============================================================================

        diagnoseAudioPlayer() {
            console.log('🔍 AUDIO PLAYER DIAGNOSIS:');
            console.log('========================');
            
            // Check DOM elements
            const playerCount = document.querySelectorAll('#bottom-audio-player').length;
            const sidebarCount = document.querySelectorAll('#right-sidebar').length;
            
            console.log(`📊 DOM Elements:
            - Bottom Players: ${playerCount}
            - Sidebars: ${sidebarCount}
            - Audio Element: ${!!this.currentAudio}
            - Player Reference: ${!!this.bottomPlayer}`);
            
            // Check UI elements
            const uiElements = {
                playPauseBtn: !!this.playPauseBtn,
                progressBar: !!this.progressBar,
                progressContainer: !!this.progressContainer,
                currentTimeEl: !!this.currentTimeEl,
                totalTimeEl: !!this.totalTimeEl
            };
            
            console.log('🎛️ UI Elements:', uiElements);
            
            // Check current state
            console.log(`🎵 Current State:
            - Is Playing: ${this.isPlaying}
            - Current Document: ${this.currentDocument?.title || 'None'}
            - Current Format: ${this.currentFormat}
            - Audio Source: ${this.currentAudio?.src || 'None'}`);
            
            // Check event listeners
            const listenersWorking = this.verifyEventListeners();
            console.log(`🎛️ Event Listeners Working: ${listenersWorking}`);
            
            return {
                domElementsOk: playerCount === 1 && sidebarCount === 1,
                uiElementsOk: Object.values(uiElements).every(Boolean),
                listenersOk: listenersWorking,
                audioOk: !!this.currentAudio,
                playerOk: !!this.bottomPlayer
            };
        }
    }

    // =============================================================================
    // AUDIO PLAYER VOICE NAVIGATION MANAGER
    // =============================================================================
    // Menambahkan voice navigation untuk audio player dengan panduan instruksi

    class AudioPlayerVoiceNavigationManager {
        constructor() {
            this.isActive = false;
            this.recognition = null;
            this.isListening = false;
            this.currentSession = null;
            this.hasShownGuidance = false;
            this.audioManager = null;
            this.isSupported = 'webkitSpeechRecognition' in window;
            
            // Audio player commands
            this.commands = {
                // Guidance commands
                'ya': () => this.showAudioGuidance(),
                'iya': () => this.showAudioGuidance(),
                'tidak': () => this.skipGuidanceAndPlay(),
                'no': () => this.skipGuidanceAndPlay(),
                'lewati': () => this.skipGuidanceAndPlay(),
                'skip': () => this.skipGuidanceAndPlay(),
                
                // Playback control commands
                'play': () => this.executeAudioCommand('play'),
                'putar': () => this.executeAudioCommand('play'),
                'pause': () => this.executeAudioCommand('pause'),
                'jeda': () => this.executeAudioCommand('pause'),
                'stop': () => this.executeAudioCommand('stop'),
                'berhenti': () => this.executeAudioCommand('stop'),
                'hentikan': () => this.executeAudioCommand('stop'),
                
                // Navigation commands
                'maju': () => this.executeAudioCommand('forward'),
                'mundur': () => this.executeAudioCommand('backward'),
                'forward': () => this.executeAudioCommand('forward'),
                'backward': () => this.executeAudioCommand('backward'),
                'lompat': (text) => this.executeTimeJump(text),
                'loncat': (text) => this.executeTimeJump(text),
                'pindah': (text) => this.executeTimeJump(text),
                
                // Speed control commands
                'percepat': () => this.executeAudioCommand('speedUp'),
                'perlambat': () => this.executeAudioCommand('slowDown'),
                'normal': () => this.executeAudioCommand('normalSpeed'),
                'cepat': () => this.executeAudioCommand('speedUp'),
                'lambat': () => this.executeAudioCommand('slowDown'),
                
                // Volume control commands
                'volume naik': () => this.executeAudioCommand('volumeUp'),
                'volume turun': () => this.executeAudioCommand('volumeDown'),
                'senyap': () => this.executeAudioCommand('mute'),
                'bisukan': () => this.executeAudioCommand('mute'),
                'unmute': () => this.executeAudioCommand('unmute'),
                
                // Download and format commands
                'download': () => this.executeAudioCommand('download'),
                'unduh': () => this.executeAudioCommand('unduh'),
                'format mp3': () => this.executeAudioCommand('formatMp3'),
                'format flac': () => this.executeAudioCommand('formatFlac'),
                
                // Help and info commands
                'bantuan': () => this.showAudioGuidance(),
                'help': () => this.showAudioGuidance(),
                'panduan': () => this.showAudioGuidance(),
                'instruksi': () => this.showAudioGuidance(),
                'status': () => this.announceAudioStatus(),
                'info': () => this.announceAudioStatus(),
                
                // Exit commands
                'keluar': () => this.deactivateVoiceNavigation(),
                'selesai': () => this.deactivateVoiceNavigation(),
                'exit': () => this.deactivateVoiceNavigation()
            };
        }

        async init(audioManager) {
            if (!this.isSupported) {
                console.log('ℹ️ Voice navigation not supported in this browser');
                return;
            }

            this.audioManager = audioManager;
            console.log('🎤 Initializing Audio Player Voice Navigation');
            
            this.setupVoiceRecognition();
            this.setupAudioPlayerHooks();
            
            return this;
        }

        setupVoiceRecognition() {
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            this.recognition = new SpeechRecognition();
            
            this.recognition.continuous = true;
            this.recognition.interimResults = false;
            this.recognition.lang = 'id-ID';
            this.recognition.maxAlternatives = 1;
            
            this.recognition.onstart = () => {
                console.log('🎤 Audio player voice navigation started');
                this.isListening = true;
                this.updateVoiceIndicator(true);
            };

            this.recognition.onresult = (event) => {
                const transcript = event.results[event.results.length - 1][0].transcript.toLowerCase().trim();
                console.log('🎯 Audio command detected:', transcript);
                this.handleVoiceCommand(transcript);
            };

            this.recognition.onerror = (event) => {
                console.warn('⚠️ Audio voice navigation error:', event.error);
                if (event.error !== 'no-speech') {
                    this.scheduleRestart();
                }
            };

            this.recognition.onend = () => {
                console.log('🛑 Audio voice navigation stopped');
                this.isListening = false;
                this.updateVoiceIndicator(false);
                
                // Auto-restart if still active
                if (this.isActive) {
                    this.scheduleRestart();
                }
            };
        }

        setupAudioPlayerHooks() {
            // Hook into audio manager untuk mendeteksi kapan audio dimulai
            if (this.audioManager) {
                // Override playDocument method untuk add voice navigation
                const originalPlayDocument = this.audioManager.playDocument.bind(this.audioManager);
                
                this.audioManager.playDocument = async (documentData) => {
                    // Call original method
                    const result = await originalPlayDocument(documentData);
                    
                    // Trigger voice navigation setelah audio loaded
                    if (result) {
                        setTimeout(() => {
                            this.triggerAudioGuidancePrompt(documentData);
                        }, 1000);
                    }
                    
                    return result;
                };
            }
        }

        async triggerAudioGuidancePrompt(documentData) {
            if (this.hasShownGuidance) {
                // Jika sudah pernah show guidance, langsung play
                return;
            }

            this.currentSession = {
                documentData: documentData,
                startTime: Date.now()
            };

            // Activate voice navigation
            this.activateVoiceNavigation();

            // Ask user if they want guidance
            const promptText = `Audio dokumen "${documentData.title || 'dokumen'}" siap diputar. ` +
                            'Apakah Anda ingin mendengar panduan perintah suara untuk mengontrol audio player? ' +
                            'Katakan "ya" untuk panduan atau "tidak" untuk langsung memutar audio.';

            this.speak(promptText);
        }

        activateVoiceNavigation() {
            if (!this.isSupported || this.isActive) return;
            
            this.isActive = true;
            console.log('🎤 Activating audio player voice navigation');
            
            try {
                this.recognition.start();
            } catch (error) {
                console.warn('⚠️ Could not start audio voice navigation:', error);
                this.scheduleRestart();
            }
        }

        deactivateVoiceNavigation() {
            if (!this.isActive) return;
            
            this.isActive = false;
            console.log('🛑 Deactivating audio player voice navigation');
            
            if (this.recognition && this.isListening) {
                try {
                    this.recognition.stop();
                } catch (error) {
                    console.warn('⚠️ Error stopping audio voice navigation:', error);
                }
            }
            
            this.updateVoiceIndicator(false);
            this.currentSession = null;
        }

        handleVoiceCommand(transcript) {
            // Find matching command
            for (const [command, handler] of Object.entries(this.commands)) {
                if (transcript.includes(command)) {
                    try {
                        const additionalText = transcript.replace(command, '').trim();
                        handler(additionalText);
                        return;
                    } catch (error) {
                        console.error(`❌ Error executing audio command '${command}':`, error);
                        this.speak('Maaf, terjadi kesalahan saat memproses perintah.');
                    }
                    return;
                }
            }
            
            // Check for time-based commands
            this.handleTimeBasedCommands(transcript);
        }

        handleTimeBasedCommands(transcript) {
            // Handle "pindah ke menit X" atau "loncat ke X:XX"
            const timePatterns = [
                /(?:pindah|loncat|lompat)\s+(?:ke\s+)?(?:menit\s+)?(\d+)/i,
                /(?:pindah|loncat|lompat)\s+(?:ke\s+)?(\d+):(\d+)/i,
                /(?:maju|mundur)\s+(\d+)\s+(?:detik|menit)/i
            ];
            
            for (const pattern of timePatterns) {
                const match = transcript.match(pattern);
                if (match) {
                    this.executeTimeJump(transcript);
                    return;
                }
            }
            
            this.speak('Perintah tidak dikenali. Katakan "bantuan" untuk mendengar daftar perintah yang tersedia.');
        }

        showAudioGuidance() {
            this.hasShownGuidance = true;
            
            const guidanceText = 'Berikut adalah perintah suara yang tersedia untuk audio player: ' +
                                'Katakan "putar" atau "play" untuk memutar audio. ' +
                                'Katakan "jeda" atau "pause" untuk menjeda. ' +
                                'Katakan "stop" atau "berhenti" untuk menghentikan. ' +
                                'Katakan "maju" untuk maju 10 detik, atau "mundur" untuk mundur 10 detik. ' +
                                'Katakan "pindah ke menit" diikuti angka untuk loncat ke waktu tertentu. ' +
                                'Katakan "percepat" atau "perlambat" untuk mengubah kecepatan. ' +
                                'Katakan "volume naik" atau "volume turun" untuk mengatur volume. ' +
                                'Katakan "download" untuk mengunduh audio. ' +
                                'Katakan "bantuan" untuk mengulangi panduan ini. ' +
                                'Katakan "keluar" untuk mengakhiri navigasi suara. ' +
                                'Audio akan dimulai setelah panduan ini selesai.';

            this.speak(guidanceText, () => {
                // Auto-play setelah guidance selesai
                setTimeout(() => {
                    this.executeAudioCommand('play');
                }, 1000);
            });
        }

        skipGuidanceAndPlay() {
            this.hasShownGuidance = true;
            this.speak('Baik, audio akan langsung diputar.', () => {
                setTimeout(() => {
                    this.executeAudioCommand('play');
                }, 500);
            });
        }

        executeAudioCommand(command) {
            if (!this.audioManager || !this.audioManager.currentAudio) {
                this.speak('Tidak ada audio yang sedang dimuat.');
                return;
            }

            const audio = this.audioManager.currentAudio;
            let feedback = '';

            switch (command) {
                case 'play':
                    if (audio.paused) {
                        audio.play();
                        feedback = 'Audio diputar.';
                    } else {
                        feedback = 'Audio sudah diputar.';
                    }
                    break;

                case 'pause':
                    if (!audio.paused) {
                        audio.pause();
                        feedback = 'Audio dijeda.';
                    } else {
                        feedback = 'Audio sudah dijeda.';
                    }
                    break;

                case 'stop':
                    audio.pause();
                    audio.currentTime = 0;
                    feedback = 'Audio dihentikan.';
                    break;

                case 'forward':
                    audio.currentTime = Math.min(audio.currentTime + 10, audio.duration);
                    feedback = 'Maju 10 detik.';
                    break;

                case 'backward':
                    audio.currentTime = Math.max(audio.currentTime - 10, 0);
                    feedback = 'Mundur 10 detik.';
                    break;

                case 'speedUp':
                    const newFastRate = Math.min(audio.playbackRate + 0.25, 2);
                    audio.playbackRate = newFastRate;
                    feedback = `Kecepatan dinaikkan ke ${newFastRate}x.`;
                    break;

                case 'slowDown':
                    const newSlowRate = Math.max(audio.playbackRate - 0.25, 0.5);
                    audio.playbackRate = newSlowRate;
                    feedback = `Kecepatan diturunkan ke ${newSlowRate}x.`;
                    break;

                case 'normalSpeed':
                    audio.playbackRate = 1;
                    feedback = 'Kecepatan normal.';
                    break;

                case 'volumeUp':
                    audio.volume = Math.min(audio.volume + 0.1, 1);
                    feedback = `Volume dinaikkan ke ${Math.round(audio.volume * 100)}%.`;
                    break;

                case 'volumeDown':
                    audio.volume = Math.max(audio.volume - 0.1, 0);
                    feedback = `Volume diturunkan ke ${Math.round(audio.volume * 100)}%.`;
                    break;

                case 'mute':
                    audio.muted = true;
                    feedback = 'Audio dibisukan.';
                    break;

                case 'unmute':
                    audio.muted = false;
                    feedback = 'Audio tidak dibisukan.';
                    break;

                case 'download':
                    this.triggerDownload();
                    feedback = 'Mengunduh audio...';
                    break;

                case 'formatMp3':
                    if (this.audioManager.switchFormat) {
                        this.audioManager.switchFormat('mp3');
                        feedback = 'Beralih ke format MP3.';
                    }
                    break;

                case 'formatFlac':
                    if (this.audioManager.switchFormat) {
                        this.audioManager.switchFormat('flac');
                        feedback = 'Beralih ke format FLAC.';
                    }
                    break;

                default:
                    feedback = 'Perintah tidak dikenali.';
            }

            if (feedback) {
                this.speak(feedback);
            }

            // Update UI if audio manager has update methods
            if (this.audioManager.updateTimeDisplay) {
                this.audioManager.updateTimeDisplay();
            }
        }

        executeTimeJump(transcript) {
            if (!this.audioManager || !this.audioManager.currentAudio) {
                this.speak('Tidak ada audio yang sedang dimuat.');
                return;
            }

            const audio = this.audioManager.currentAudio;
            
            // Parse time from transcript
            const minuteMatch = transcript.match(/(?:pindah|loncat|lompat)\s+(?:ke\s+)?(?:menit\s+)?(\d+)/i);
            const timeMatch = transcript.match(/(?:pindah|loncat|lompat)\s+(?:ke\s+)?(\d+):(\d+)/i);
            const relativeMatch = transcript.match(/(?:maju|mundur)\s+(\d+)\s+(detik|menit)/i);

            if (timeMatch) {
                // Format MM:SS
                const minutes = parseInt(timeMatch[1]);
                const seconds = parseInt(timeMatch[2]);
                const totalSeconds = (minutes * 60) + seconds;
                
                if (totalSeconds <= audio.duration) {
                    audio.currentTime = totalSeconds;
                    this.speak(`Pindah ke ${minutes} menit ${seconds} detik.`);
                } else {
                    this.speak('Waktu yang diminta melebihi durasi audio.');
                }
            } else if (minuteMatch) {
                // Format MM (menit saja)
                const minutes = parseInt(minuteMatch[1]);
                const totalSeconds = minutes * 60;
                
                if (totalSeconds <= audio.duration) {
                    audio.currentTime = totalSeconds;
                    this.speak(`Pindah ke menit ${minutes}.`);
                } else {
                    this.speak('Waktu yang diminta melebihi durasi audio.');
                }
            } else if (relativeMatch) {
                // Relative movement
                const amount = parseInt(relativeMatch[1]);
                const unit = relativeMatch[2];
                const direction = transcript.includes('maju') ? 1 : -1;
                const seconds = unit === 'menit' ? amount * 60 : amount;
                
                const newTime = Math.max(0, Math.min(audio.currentTime + (direction * seconds), audio.duration));
                audio.currentTime = newTime;
                
                const actionText = direction === 1 ? 'maju' : 'mundur';
                this.speak(`${actionText} ${amount} ${unit}.`);
            }

            // Update display
            if (this.audioManager.updateTimeDisplay) {
                this.audioManager.updateTimeDisplay();
            }
        }

        announceAudioStatus() {
            if (!this.audioManager || !this.audioManager.currentAudio) {
                this.speak('Tidak ada audio yang sedang dimuat.');
                return;
            }

            const audio = this.audioManager.currentAudio;
            const currentMinutes = Math.floor(audio.currentTime / 60);
            const currentSeconds = Math.floor(audio.currentTime % 60);
            const totalMinutes = Math.floor(audio.duration / 60);
            const totalSeconds = Math.floor(audio.duration % 60);
            const playbackRate = audio.playbackRate;
            const volume = Math.round(audio.volume * 100);
            
            const statusText = `Status audio: ${audio.paused ? 'dijeda' : 'diputar'}. ` +
                            `Waktu saat ini ${currentMinutes} menit ${currentSeconds} detik dari total ${totalMinutes} menit ${totalSeconds} detik. ` +
                            `Kecepatan ${playbackRate}x. Volume ${volume}%.`;

            this.speak(statusText);
        }

        triggerDownload() {
            if (this.currentSession && this.currentSession.documentData) {
                // Try to trigger download via audio manager
                if (this.audioManager.downloadAudio) {
                    this.audioManager.downloadAudio();
                } else {
                    // Fallback: create download link
                    const documentId = this.currentSession.documentData.id;
                    const downloadUrl = `/documents/${documentId}/audio/mp3/download`;
                    const link = document.createElement('a');
                    link.href = downloadUrl;
                    link.download = `${this.currentSession.documentData.title || 'audio'}.mp3`;
                    link.click();
                }
            }
        }

        speak(text, onEnd = null) {
            const utterance = new SpeechSynthesisUtterance(text);
            utterance.lang = 'id-ID';
            utterance.rate = 0.9;
            
            if (onEnd) {
                utterance.onend = onEnd;
            }
            
            utterance.onerror = () => {
                console.warn('⚠️ Speech synthesis error');
                if (onEnd) onEnd();
            };
            
            window.speechSynthesis.speak(utterance);
        }

        updateVoiceIndicator(isActive) {
            // Update visual indicator in audio player
            const indicator = document.querySelector('#bottom-audio-player .voice-indicator');
            if (indicator) {
                if (isActive) {
                    indicator.classList.add('active', 'animate-pulse');
                    indicator.innerHTML = '<i class="fas fa-microphone text-red-500"></i>';
                } else {
                    indicator.classList.remove('active', 'animate-pulse');
                    indicator.innerHTML = '<i class="fas fa-microphone text-gray-400"></i>';
                }
            }
            
            // Update aria-live region
            const liveRegion = document.getElementById('aria-live-region');
            if (liveRegion && isActive) {
                liveRegion.textContent = 'Voice navigation aktif untuk audio player';
            }
        }

        scheduleRestart(delay = 1000) {
            if (this.isActive) {
                setTimeout(() => {
                    if (this.isActive && !this.isListening) {
                        try {
                            this.recognition.start();
                        } catch (error) {
                            console.warn('⚠️ Error restarting audio voice navigation:', error);
                        }
                    }
                }, delay);
            }
        }

        cleanup() {
            this.deactivateVoiceNavigation();
            this.currentSession = null;
            this.hasShownGuidance = false;
        }
    }

    // Export for integration with UnifiedWebApplication
    if (typeof module !== 'undefined' && module.exports) {
        module.exports = AudioPlayerVoiceNavigationManager;
    }
    // =============================================================================
    // ACCESSIBILITY MANAGER
    // =============================================================================
    
    class AccessibilityManager {
        constructor() {
            this.announcements = [];
            this.lastAnnouncement = '';
            this.announceTimeout = null;
        }

        async init() {
            console.log('♿ Initializing Accessibility Manager');
            this.setupKeyboardNavigation();
            this.setupScreenReaderSupport();
            this.setupFocusManagement();
        }

        setupKeyboardNavigation() {
            // Tab navigation improvements
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Tab') {
                    this.handleTabNavigation(e);
                }
            });
        }

        setupScreenReaderSupport() {
            // Create aria-live region for announcements
            this.createLiveRegion();
            
            // Announce page changes
            this.announcePageContent();
        }

        setupFocusManagement() {
            // Focus management for dynamic content
            this.setupFocusTrap();
            this.setupSkipLinks();
        }

        createLiveRegion() {
            const liveRegion = document.createElement('div');
            liveRegion.id = 'aria-live-region';
            liveRegion.setAttribute('aria-live', 'polite');
            liveRegion.setAttribute('aria-atomic', 'true');
            liveRegion.style.cssText = `
                position: absolute;
                left: -10000px;
                width: 1px;
                height: 1px;
                overflow: hidden;
            `;
            
            document.body.appendChild(liveRegion);
        }

        announce(message, priority = 'polite') {
            if (!message || message === this.lastAnnouncement) return;
            
            this.lastAnnouncement = message;
            
            // Clear existing timeout
            if (this.announceTimeout) {
                clearTimeout(this.announceTimeout);
            }
            
            // Delay announcement to prevent overwhelming screen readers
            this.announceTimeout = setTimeout(() => {
                const liveRegion = document.getElementById('aria-live-region');
                if (liveRegion) {
                    liveRegion.setAttribute('aria-live', priority);
                    liveRegion.textContent = message;
                    
                    // Clear after announcement
                    setTimeout(() => {
                        liveRegion.textContent = '';
                    }, 1000);
                }
            }, CONFIG.accessibility.announceDelay);
        }

        announceError(message) {
            this.announce(message, 'assertive');
        }

        handleTabNavigation(e) {
            // Improve tab navigation for audio player
            const focusableElements = this.getFocusableElements();
            const currentIndex = focusableElements.indexOf(document.activeElement);
            
            if (e.shiftKey && currentIndex === 0) {
                // Shift+Tab from first element
                e.preventDefault();
                focusableElements[focusableElements.length - 1].focus();
            } else if (!e.shiftKey && currentIndex === focusableElements.length - 1) {
                // Tab from last element
                e.preventDefault();
                focusableElements[0].focus();
            }
        }

        getFocusableElements() {
            const selector = 'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])';
            return Array.from(document.querySelectorAll(selector)).filter(el => {
                return !el.disabled && el.offsetParent !== null;
            });
        }

        setupFocusTrap() {
            // Focus trap for modals and audio player
            document.addEventListener('focusin', (e) => {
                const activeModal = document.querySelector('.modal:not(.hidden)');
                if (activeModal && !activeModal.contains(e.target)) {
                    const firstFocusable = activeModal.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
                    if (firstFocusable) {
                        firstFocusable.focus();
                    }
                }
            });
        }

        setupSkipLinks() {
            // Add skip to content link
            const skipLink = document.createElement('a');
            skipLink.href = '#main-content';
            skipLink.textContent = 'Skip to main content';
            skipLink.className = 'skip-link';
            skipLink.style.cssText = `
                position: absolute;
                top: -40px;
                left: 6px;
                background: #000;
                color: #fff;
                padding: 8px;
                text-decoration: none;
                border-radius: 4px;
                z-index: 10001;
                transition: top 0.3s;
            `;
            
            skipLink.addEventListener('focus', () => {
                skipLink.style.top = '6px';
            });
            
            skipLink.addEventListener('blur', () => {
                skipLink.style.top = '-40px';
            });
            
            document.body.insertBefore(skipLink, document.body.firstChild);
        }

        announcePageContent() {
            // Announce page title and main content
            const pageTitle = document.title;
            const mainHeading = document.querySelector('h1');
            
            if (mainHeading) {
                this.announce(`Halaman ${pageTitle}. ${mainHeading.textContent}`);
            } else {
                this.announce(`Halaman ${pageTitle}`);
            }
        }

        cleanup() {
            if (this.announceTimeout) {
                clearTimeout(this.announceTimeout);
            }
        }
    }

    // =============================================================================
    // HEALTH MONITOR MANAGER
    // =============================================================================
    
    class HealthMonitorManager {
        constructor() {
            this.healthChecks = [];
            this.healthInterval = null;
            this.healthCheckCount = 0;
            this.maxHealthChecks = 10;
            this.isMonitoring = false;
        }

        async init() {
            console.log('🏥 Initializing Health Monitor Manager');
            this.setupHealthChecks();
        }

        setupHealthChecks() {
            this.healthChecks = [
                () => this.checkAudioManagerHealth(),
                () => this.checkUIElementsHealth(),
                () => this.checkEventListenersHealth(),
                () => this.checkMemoryUsage(),
                () => this.checkPerformanceMetrics()
            ];
        }

        startHealthChecks() {
            if (this.isMonitoring) return;
            
            this.isMonitoring = true;
            this.healthInterval = setInterval(() => {
                this.runHealthCheck();
            }, CONFIG.audio.healthCheckInterval);
            
            console.log('🟢 Health monitoring started');
        }

        stopHealthChecks() {
            if (this.healthInterval) {
                clearInterval(this.healthInterval);
                this.healthInterval = null;
            }
            this.isMonitoring = false;
            console.log('🔴 Health monitoring stopped');
        }

        runHealthCheck() {
            this.healthCheckCount++;
            const results = this.healthChecks.map(check => check());
            const isHealthy = results.every(result => result.healthy);

            if (isHealthy) {
                if (this.healthCheckCount % 20 === 0) { // Log every 20 checks (1 minute)
                    console.log('✅ System health check passed');
                }
            } else {
                console.warn('⚠️ System health issues detected:', results.filter(r => !r.healthy));
                
                if (this.healthCheckCount >= this.maxHealthChecks) {
                    this.triggerRecovery(results);
                }
            }
        }

        checkAudioManagerHealth() {
            const audioManager = window.unifiedApp?.modules.audioPlayer;
            
            if (!audioManager) {
                return { healthy: false, issue: 'Audio manager not found' };
            }

            if (audioManager.currentAudio && audioManager.currentAudio.error) {
                return { healthy: false, issue: 'Audio element error', error: audioManager.currentAudio.error };
            }

            return { healthy: true };
        }

        checkUIElementsHealth() {
            const requiredElements = [
                'bottom-audio-player',
                'main-audio-element'
            ];

            for (const id of requiredElements) {
                if (!document.getElementById(id)) {
                    return { healthy: false, issue: `Missing UI element: ${id}` };
                }
            }

            return { healthy: true };
        }

        checkEventListenersHealth() {
            const audioButtons = document.querySelectorAll('.play-audio-btn, .admin-play-btn, .play-document-btn');
            const handledButtons = document.querySelectorAll('[data-audio-handled]');

            console.log(`🔍 Health check: ${audioButtons.length} audio buttons, ${handledButtons.length} handled`);

            // More lenient health check - allow some buttons to be unhandled
            if (audioButtons.length > 0 && handledButtons.length === 0) {
                return { 
                    healthy: false, 
                    issue: 'Audio buttons not properly initialized',
                    details: {
                        totalButtons: audioButtons.length,
                        handledButtons: handledButtons.length
                    }
                };
            }

            // If less than 50% are handled, consider it unhealthy
            if (audioButtons.length > 0 && (handledButtons.length / audioButtons.length) < 0.5) {
                return { 
                    healthy: false, 
                    issue: 'Many audio buttons not properly initialized',
                    details: {
                        totalButtons: audioButtons.length,
                        handledButtons: handledButtons.length,
                        percentage: Math.round((handledButtons.length / audioButtons.length) * 100)
                    }
                };
            }

            return { healthy: true };
        }

        checkMemoryUsage() {
            if ('memory' in performance) {
                const memInfo = performance.memory;
                const usedMB = memInfo.usedJSHeapSize / 1024 / 1024;
                const limitMB = memInfo.jsHeapSizeLimit / 1024 / 1024;
                
                if (usedMB > limitMB * 0.8) {
                    return { healthy: false, issue: 'High memory usage', usage: usedMB, limit: limitMB };
                }
            }

            return { healthy: true };
        }

        checkPerformanceMetrics() {
            const entries = performance.getEntriesByType('navigation');
            if (entries.length > 0) {
                const loadTime = entries[0].loadEventEnd - entries[0].loadEventStart;
                if (loadTime > 5000) { // 5 seconds
                    return { healthy: false, issue: 'Slow page load', loadTime };
                }
            }

            return { healthy: true };
        }

        triggerRecovery(healthResults) {
            console.warn('🚨 Triggering system recovery due to health issues');
            
            // Reset health check count
            this.healthCheckCount = 0;
            
            // Attempt targeted recovery based on issues
            healthResults.forEach(result => {
                if (!result.healthy) {
                    this.handleSpecificIssue(result);
                }
            });
        }

        handleSpecificIssue(result) {
            switch (result.issue) {
                case 'Audio manager not found':
                    this.recoverAudioManager();
                    break;
                case 'Audio buttons not properly initialized':
                    this.recoverAudioButtons();
                    break;
                default:
                    console.log('🔧 General recovery for:', result.issue);
            }
        }

        recoverAudioManager() {
            console.log('🔄 Attempting audio manager recovery');
            try {
                if (window.unifiedApp?.modules.audioPlayer) {
                    window.unifiedApp.modules.audioPlayer.cleanup();
                    window.unifiedApp.modules.audioPlayer = new UnifiedAudioManager();
                    window.unifiedApp.modules.audioPlayer.init();
                }
            } catch (error) {
                console.error('❌ Audio manager recovery failed:', error);
            }
        }

        recoverAudioButtons() {
            console.log('🔄 Attempting audio buttons recovery');
            try {
                // Remove existing handlers
                document.querySelectorAll('[data-audio-handled]').forEach(btn => {
                    btn.removeAttribute('data-audio-handled');
                });
                
                // Reinitialize buttons
                if (window.unifiedApp?.modules.audioPlayer) {
                    window.unifiedApp.modules.audioPlayer.setupUniversalIntegration();
                }
            } catch (error) {
                console.error('❌ Audio buttons recovery failed:', error);
            }
        }

        // Public API for manual health checks
        getSystemHealth() {
            const results = this.healthChecks.map(check => check());
            const isHealthy = results.every(result => result.healthy);
            
            return {
                healthy: isHealthy,
                checks: results,
                timestamp: new Date().toISOString()
            };
        }

        cleanup() {
            this.stopHealthChecks();
        }
    }

    // =============================================================================
    // SOUND EFFECTS MANAGER
    // =============================================================================
    
    class SoundEffectsManager {
        constructor() {
            this.sounds = new Map();
            this.enabled = true;
            this.volume = 0.3;
            this.soundsLoaded = false; // Add loading state
        }

        async init() {
            console.log('🔊 Initializing Sound Effects Manager');
        
            // Load preferences first
            this.loadPreferences();
            
            // Only load sounds if enabled and paths exist
            if (this.enabled) {
                await this.loadSoundEffects();
        }
        
        this.setupSoundTriggers();
        }

        async loadSoundEffects() {
            const soundEffects = {
                click: '/sounds/click.mp3',
                hover: '/sounds/hover.mp3',
                success: '/sounds/success.mp3',
                error: '/sounds/error.mp3',
                notification: '/sounds/notification.mp3'
            };

            const loadPromises = [];

            for (const [name, url] of Object.entries(soundEffects)) {
                loadPromises.push(
                    this.loadSingleSound(name, url)
                );
            }

            await Promise.allSettled(loadPromises);
            this.soundsLoaded = true;
            console.log('🔊 Sound effects loaded:', this.sounds.size, 'sounds');
        }

        async loadSingleSound(name, url) {
            try {
                // Check if file exists first
                const response = await fetch(url, { method: 'HEAD' });
                if (!response.ok) {
                    console.warn(`⚠️ Sound file not found: ${url}`);
                    return;
                }

                const audio = new Audio(url);
                audio.volume = this.volume;
                audio.preload = 'auto';
                
                // Wait for audio to load
                await new Promise((resolve, reject) => {
                    audio.addEventListener('canplaythrough', resolve, { once: true });
                    audio.addEventListener('error', reject, { once: true });
                    
                    // Timeout after 5 seconds
                    setTimeout(() => reject(new Error('Load timeout')), 5000);
                });
                
                this.sounds.set(name, audio);
                console.log(`✅ Loaded sound: ${name}`);
                
            } catch (error) {
                console.warn(`⚠️ Failed to load sound effect: ${name}`, error);
            }
        }

        setupSoundTriggers() {
            console.log('🔊 Setting up safe sound triggers...');
            
            // Safe click handler with multiple fallbacks
            const safeClickHandler = (e) => {
                try {
                    // Validate event and target
                    if (!e || !e.target) return;
                    
                    // Get the actual element (handle text nodes)
                    let targetElement = e.target;
                    if (targetElement.nodeType === 3) { // Text node
                        targetElement = targetElement.parentElement;
                    }
                    
                    if (!targetElement || targetElement.nodeType !== 1) return;
                    
                    // Method 1: Try closest() if available
                    if (typeof targetElement.closest === 'function') {
                        try {
                            const clickable = targetElement.closest('button, .btn, a, .clickable');
                            if (clickable) {
                                this.playSound('click');
                                return;
                            }
                        } catch (closestError) {
                            // Continue to fallback
                        }
                    }
                    
                    // Method 2: Manual traversal fallback
                    let current = targetElement;
                    let levels = 0;
                    
                    while (current && current.nodeType === 1 && levels < 5) {
                        const tagName = current.tagName.toLowerCase();
                        const classList = current.classList || [];
                        
                        // Check if it's clickable
                        if (tagName === 'button' || 
                            tagName === 'a' || 
                            classList.contains('btn') || 
                            classList.contains('clickable')) {
                            this.playSound('click');
                            return;
                        }
                        
                        current = current.parentElement;
                        levels++;
                    }
                    
                } catch (error) {
                    // Completely silent fallback - no console logs to avoid spam
                }
            };
            
            // Safe hover handler
            const safeHoverHandler = (() => {
                let hoverTimeout;
                
                return (e) => {
                    try {
                        if (!e || !e.target) return;
                        
                        let targetElement = e.target;
                        if (targetElement.nodeType === 3) {
                            targetElement = targetElement.parentElement;
                        }
                        
                        if (!targetElement || targetElement.nodeType !== 1) return;
                        
                        // Try closest first
                        if (typeof targetElement.closest === 'function') {
                            try {
                                const hoverElement = targetElement.closest('.hover-sound');
                                if (hoverElement) {
                                    if (hoverTimeout) clearTimeout(hoverTimeout);
                                    hoverTimeout = setTimeout(() => this.playSound('hover'), 100);
                                }
                            } catch (closestError) {
                                // Silent fallback
                            }
                        }
                    } catch (error) {
                        // Silent error handling
                    }
                };
            })();
            
            // Attach listeners with error boundaries
            try {
                document.addEventListener('click', safeClickHandler.bind(this));
                document.addEventListener('mouseenter', safeHoverHandler.bind(this), true);
                console.log('✅ Safe sound triggers attached successfully');
            } catch (error) {
                console.error('Failed to attach sound triggers:', error);
            }
        }

        playSound(name) {
            if (!this.enabled || !this.soundsLoaded) return;
        
            const sound = this.sounds.get(name);
            if (sound) {
                try {
                    sound.currentTime = 0;
                    sound.play().catch(error => {
                        // Only log non-autoplay errors
                        if (error.name !== 'NotAllowedError' && error.name !== 'NotSupportedError') {
                            console.warn(`⚠️ Failed to play sound: ${name}`, error);
                        }
                    });
                } catch (error) {
                    console.warn(`⚠️ Sound playback error: ${name}`, error);
                }
            }
        }

        setVolume(volume) {
            this.volume = Math.max(0, Math.min(1, volume));
            if (this.currentAudio) {
                this.currentAudio.volume = this.volume;
                this.updateVolumeIcon();
            }
        }

        updateVolumeIcon() {
            const volumeIcon = document.getElementById('volume-icon');
            if (volumeIcon) {
                if (this.currentAudio && this.currentAudio.muted) {
                    volumeIcon.className = 'fas fa-volume-mute text-sm';
                } else if (this.volume === 0) {
                    volumeIcon.className = 'fas fa-volume-off text-sm';
                } else if (this.volume < 0.5) {
                    volumeIcon.className = 'fas fa-volume-down text-sm';
                } else {
                    volumeIcon.className = 'fas fa-volume-up text-sm';
                }
            }
        }

        toggleMute() {
            if (this.currentAudio) {
                this.currentAudio.muted = !this.currentAudio.muted;
                this.updateVolumeIcon();
                this.announceToScreenReader(this.currentAudio.muted ? 'Audio dibisukan' : 'Audio tidak dibisukan');
            }
        }

        viewCurrentDocument() {
            if (!this.currentDocument) {
                console.warn('⚠️ No current document to view');
                this.showErrorMessage('Tidak ada dokumen yang sedang diputar');
                return;
            }
            
            try {
                // Use Laravel route pattern
                const docUrl = `/dokumen/${this.currentDocument.slug || this.currentDocument.id}`;
                
                // Open in new tab
                window.open(docUrl, '_blank');
                
                console.log('✅ Opening document via route:', {
                    title: this.currentDocument.title,
                    url: docUrl
                });
                
                // Optional: Track view event
                if (typeof gtag !== 'undefined') {
                    gtag('event', 'view_document', {
                        'document_title': this.currentDocument.title,
                        'document_id': this.currentDocument.id
                    });
                }
                
            } catch (error) {
                console.error('❌ Error opening document:', error);
                this.showErrorMessage('Gagal membuka dokumen');
            }
        }

        setEnabled(enabled) {
            this.enabled = enabled;
            localStorage.setItem('sound_effects_enabled', enabled);
        }

        isEnabled() {
            return this.enabled;
        }

        // Load preferences
        loadPreferences() {
            const enabled = localStorage.getItem('sound_effects_enabled');
            if (enabled !== null) {
                this.enabled = enabled === 'true';
            }

            const volume = localStorage.getItem('sound_effects_volume');
            if (volume !== null) {
                this.setVolume(parseFloat(volume));
            }
        }

        // Disable sound effects if not needed
        disable() {
            this.enabled = false;
            localStorage.setItem('sound_effects_enabled', 'false');
        }

        cleanup() {
            this.sounds.forEach(sound => {
                sound.pause();
                sound.src = '';
            });
            this.sounds.clear();
        }
    }

    // =============================================================================
    // TOAST NOTIFICATION SYSTEM
    // =============================================================================
    
    class ToastManager {
        constructor() {
            this.container = null;
            this.toasts = [];
        }

        async init() {
            console.log('🍞 Initializing Toast Manager');
            this.createToastContainer();
        }

        createToastContainer() {
            this.container = document.createElement('div');
            this.container.id = 'toast-container';
            this.container.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 10000;
                pointer-events: none;
            `;
            document.body.appendChild(this.container);
        }

        show(message, type = 'info', duration = CONFIG.ui.toastDuration) {
            const toast = this.createToast(message, type);
            this.container.appendChild(toast);
            this.toasts.push(toast);

            // Animate in
            setTimeout(() => {
                toast.style.transform = 'translateX(0)';
                toast.style.opacity = '1';
            }, 10);

            // Auto remove
            setTimeout(() => {
                this.removeToast(toast);
            }, duration);

            return toast;
        }

        createToast(message, type) {
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            
            const colors = {
                success: 'bg-green-500',
                error: 'bg-red-500',
                warning: 'bg-yellow-500',
                info: 'bg-blue-500'
            };

            toast.style.cssText = `
                transform: translateX(100%);
                opacity: 0;
                transition: all 0.3s ease;
                margin-bottom: 10px;
                padding: 12px 16px;
                border-radius: 6px;
                color: white;
                font-size: 14px;
                max-width: 300px;
                word-wrap: break-word;
                pointer-events: auto;
                cursor: pointer;
            `;

            toast.className += ` ${colors[type] || colors.info}`;
            toast.textContent = message;

            // Click to dismiss
            toast.addEventListener('click', () => {
                this.removeToast(toast);
            });

            return toast;
        }

        removeToast(toast) {
            toast.style.transform = 'translateX(100%)';
            toast.style.opacity = '0';
            
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
                
                const index = this.toasts.indexOf(toast);
                if (index > -1) {
                    this.toasts.splice(index, 1);
                }
            }, 300);
        }

        success(message) {
            return this.show(message, 'success');
        }

        error(message) {
            return this.show(message, 'error');
        }

        warning(message) {
            return this.show(message, 'warning');
        }

        info(message) {
            return this.show(message, 'info');
        }

        cleanup() {
            this.toasts.forEach(toast => this.removeToast(toast));
            if (this.container) {
                this.container.remove();
            }
        }
    }

    // =============================================================================
    // GLOBAL UTILITY FUNCTIONS
    // =============================================================================
    
    function playSound(soundName) {
        if (window.unifiedApp?.modules.soundEffects) {
            window.unifiedApp.modules.soundEffects.playSound(soundName);
        }
    }

    function showToast(message, type = 'info') {
        if (window.unifiedApp?.modules.toast) {
            return window.unifiedApp.modules.toast.show(message, type);
        } else {
            // Fallback to console
            console.log(`[${type.toUpperCase()}] ${message}`);
        }
    }

    function announceToScreenReader(message) {
        if (window.unifiedApp?.modules.accessibility) {
            window.unifiedApp.modules.accessibility.announce(message);
        }
    }

    // =============================================================================
    // GLOBAL API & BACKWARD COMPATIBILITY
    // =============================================================================
    
    // Create global functions for backward compatibility
    window.playDocumentAudio = function(docData) {
        if (window.unifiedApp?.modules.audioPlayer) {
            return window.unifiedApp.modules.audioPlayer.playDocument(docData);
        } else {
            console.warn('⚠️ Audio player not available');
        }
    };

    window.stopAudio = function() {
        if (window.unifiedApp?.modules.audioPlayer) {
            return window.unifiedApp.modules.audioPlayer.stopAllAudio();
        }
    };

    window.toggleAudioPlayPause = function() {
        if (window.unifiedApp?.modules.audioPlayer) {
            return window.unifiedApp.modules.audioPlayer.togglePlayPause();
        }
    };

    window.switchFormat = function(format) {
        if (window.unifiedApp?.modules.audioPlayer) {
            return window.unifiedApp.modules.audioPlayer.switchFormat(format);
        }
    };

    // Global utility functions
    window.playSound = playSound;
    window.showToast = showToast;
    window.announceToScreenReader = announceToScreenReader;

    // Debug functions for development
    window.debugAudio = function() {
        if (window.unifiedApp?.modules.audioPlayer) {
            console.log('🐛 Audio Player Debug Info:');
            console.log('- Current document:', window.unifiedApp.modules.audioPlayer.getCurrentDocument());
            console.log('- Playback state:', window.unifiedApp.modules.audioPlayer.getPlaybackState());
            console.log('- Audio element:', window.unifiedApp.modules.audioPlayer.currentAudio);
        }
    };

    window.getSystemHealth = function() {
        if (window.unifiedApp?.modules.healthMonitor) {
            return window.unifiedApp.modules.healthMonitor.getSystemHealth();
        }
    };

    window.reinitializeAudio = function() {
        if (window.unifiedApp?.modules.audioPlayer) {
            console.log('🔄 Reinitializing audio system...');
            window.unifiedApp.modules.audioPlayer.cleanup();
            window.unifiedApp.modules.audioPlayer = new UnifiedAudioManager();
            return window.unifiedApp.modules.audioPlayer.init();
        }
    };

    // =============================================================================
    // APPLICATION INITIALIZATION
    // =============================================================================
    
    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeApplication);
    } else {
        initializeApplication();
    }

    async function initializeApplication() {
        try {
            console.log('🚀 Starting Unified Web Application initialization...');
            
            // Create main application but don't initialize modules that might fail
            window.unifiedApp = new UnifiedWebApplication();
            
            // Initialize core modules first (those that are less likely to fail)
            window.unifiedApp.modules.toast = new ToastManager();
            await window.unifiedApp.modules.toast.init();
            
            // Initialize sound effects manager with better error handling
            window.unifiedApp.modules.soundEffects = new SoundEffectsManager();
            try {
                await window.unifiedApp.modules.soundEffects.init();
            } catch (soundError) {
                console.warn('⚠️ Sound effects disabled due to error:', soundError);
                window.unifiedApp.modules.soundEffects.disable();
            }
            
            console.log('✅ Unified Web Application loaded successfully!');
            
            // Show success toast after a delay
            setTimeout(() => {
                if (window.showToast) {
                    showToast('Aplikasi siap digunakan', 'success');
                }
            }, 1000);
            
        } catch (error) {
            console.error('❌ Failed to initialize application:', error);
            
            // Show error notification
            setTimeout(() => {
                alert('Gagal memuat beberapa fitur aplikasi. Halaman akan di-refresh.');
                window.location.reload();
            }, 1000);
        }
    }

    function initializeFallbackMode() {
        console.log('🆘 Initializing fallback mode...');
        
        // Basic audio playback fallback
        document.addEventListener('click', (e) => {
            const button = e.target.closest('.play-audio-btn, .admin-play-btn, .play-document-btn');
            if (button) {
                const documentId = button.dataset.documentId || button.dataset.id;
                if (documentId) {
                    const audio = new Audio(`/audio/stream/${documentId}/mp3`);
                    audio.play().catch(console.error);
                    console.log('🎵 Fallback audio playback for document:', documentId);
                }
            }
        });
        
        // Basic hover text fallback
        document.addEventListener('mouseenter', (e) => {
            const hoverText = e.target.dataset.hoverText || e.target.getAttribute('title');
            if (hoverText) {
                console.log('💬 Hover text:', hoverText);
            }
        }, true);
        
        console.log('✅ Fallback mode initialized');
    }

    // =============================================================================
    // ERROR HANDLING & LOGGING
    // =============================================================================
    
    // Global error handler
    window.addEventListener('error', (e) => {
        console.error('🚨 Global JavaScript Error:', {
            message: e.message,
            filename: e.filename,
            lineno: e.lineno,
            colno: e.colno,
            error: e.error
        });
        
        // Show user-friendly error message
        if (window.showToast) {
            showToast('Terjadi kesalahan. Silakan refresh halaman jika masalah berlanjut.', 'error');
        }
    });

    // Unhandled promise rejection handler
    window.addEventListener('unhandledrejection', (e) => {
        console.error('🚨 Unhandled Promise Rejection:', e.reason);
        
        // Prevent default browser behavior
        e.preventDefault();
        
        // Show user-friendly error message
        if (window.showToast) {
            showToast('Terjadi kesalahan sistem. Mencoba pemulihan otomatis...', 'warning');
        }
    });

    // Performance monitoring
    window.addEventListener('load', () => {
        // Log performance metrics
        setTimeout(() => {
            const perfData = performance.timing;
            const loadTime = perfData.loadEventEnd - perfData.navigationStart;
            
            console.log('📊 Performance Metrics:', {
                loadTime: loadTime + 'ms',
                domReady: (perfData.domContentLoadedEventEnd - perfData.navigationStart) + 'ms',
                firstPaint: performance.getEntriesByType('paint')[0]?.startTime + 'ms'
            });
            
            if (loadTime > 3000) {
                console.warn('⚠️ Slow page load detected:', loadTime + 'ms');
            }
        }, 1000);
    });

    console.log('📦 Unified Web Application Script Loaded - Ready for Initialization');

    // =============================================================================
    // COMPLETE CLEANUP & REINITIALIZATION FUNCTION
    // =============================================================================

    window.forceReinitializeAudioPlayer = async function() {
        console.log('🔄 ENHANCED FORCE REINITIALIZING AUDIO PLAYER...');
        
        try {
            // Step 1: Stop all audio and clear references
            if (window.unifiedApp?.modules.audioPlayer) {
                window.unifiedApp.modules.audioPlayer.stopAllAudio();
                window.unifiedApp.modules.audioPlayer.cleanup();
            }
            
            // Step 2: Remove ALL existing audio-related elements
            const elementsToRemove = [
                '#bottom-audio-player',
                '#right-sidebar', 
                '#main-audio-element',
                '.audio-player',
                '[id*="audio"]'
            ];
            
            elementsToRemove.forEach(selector => {
                document.querySelectorAll(selector).forEach(el => {
                    console.log(`🗑️ Removing element: ${el.id || el.className}`);
                    el.remove();
                });
            });
            
            // Step 3: Wait for DOM cleanup
            await new Promise(resolve => setTimeout(resolve, 200));
            
            // Step 4: Create fresh audio manager
            console.log('🆕 Creating fresh audio manager...');
            const freshAudioManager = new UnifiedAudioManager();
            await freshAudioManager.init();
            
            // Step 5: Replace in global app
            if (window.unifiedApp?.modules) {
                window.unifiedApp.modules.audioPlayer = freshAudioManager;
            }
            
            // Step 6: Verify initialization
            const verification = await verifyCompleteSetup();
            
            if (verification.success) {
                console.log('✅ Enhanced audio player reinitialization successful');
                if (window.showToast) {
                    showToast('Audio player berhasil di-reset dengan perbaikan', 'success');
                }
            } else {
                throw new Error('Verification failed: ' + verification.errors.join(', '));
            }
            
        } catch (error) {
            console.error('❌ Enhanced reinitialization failed:', error);
            alert('Reset gagal. Silakan refresh halaman untuk memulai ulang.');
        }
    };

    // Add verification function:
    async function verifyCompleteSetup() {
        const checks = {
            audioElement: !!document.getElementById('main-audio-element'),
            bottomPlayer: !!document.getElementById('bottom-audio-player'),
            sidebar: !!document.getElementById('right-sidebar'),
            playButton: !!document.getElementById('play-pause-btn'),
            progressBar: !!document.getElementById('progress-bar'),
            audioManager: !!window.unifiedApp?.modules.audioPlayer
        };
        
        const failed = Object.entries(checks).filter(([key, value]) => !value).map(([key]) => key);
        
        return {
            success: failed.length === 0,
            checks,
            errors: failed
        };
    }

    // =============================================================================
    // BROWSER CACHE CLEARING HELPER
    // =============================================================================

    window.clearAudioPlayerCache = function() {
        console.log('🧹 Clearing audio player cache...');
        
        // Clear localStorage related to audio player
        const audioKeys = Object.keys(localStorage).filter(key => 
            key.includes('audio') || key.includes('player') || key.includes('sound')
        );
        
        audioKeys.forEach(key => {
            localStorage.removeItem(key);
            console.log(`🗑️ Removed localStorage key: ${key}`);
        });
        
        // Clear sessionStorage
        const sessionKeys = Object.keys(sessionStorage).filter(key => 
            key.includes('audio') || key.includes('player')
        );
        
        sessionKeys.forEach(key => {
            sessionStorage.removeItem(key);
            console.log(`🗑️ Removed sessionStorage key: ${key}`);
        });
        
        // Force reload stylesheets
        const links = document.querySelectorAll('link[rel="stylesheet"]');
        links.forEach(link => {
            const href = link.href;
            link.href = href + (href.includes('?') ? '&' : '?') + 'v=' + Date.now();
        });
        
        console.log('✅ Cache cleared successfully');
    };

    // =============================================================================
    // MANUAL UI VERIFICATION FUNCTION
    // =============================================================================

    window.verifyAudioPlayerUI = function() {
        console.log('🔍 MANUAL UI VERIFICATION:');
        console.log('===========================');
        
        // Check all required elements
        const requiredElements = [
            'bottom-audio-player',
            'play-pause-btn',
            'progress-bar',
            'progress-container',
            'current-time-main',
            'total-time-main',
            'current-doc-title',
            'current-doc-indicator',
            'current-doc-cover'
        ];
        
        const missingElements = [];
        const foundElements = [];
        
        requiredElements.forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                foundElements.push(id);
                console.log(`✅ Found: ${id}`);
            } else {
                missingElements.push(id);
                console.log(`❌ Missing: ${id}`);
            }
        });
        
        // Check for duplicates
        const duplicates = [];
        requiredElements.forEach(id => {
            const elements = document.querySelectorAll(`#${id}`);
            if (elements.length > 1) {
                duplicates.push(`${id} (${elements.length} copies)`);
            }
        });
        
        // Summary
        console.log(`📊 SUMMARY:
        - Found Elements: ${foundElements.length}/${requiredElements.length}
        - Missing Elements: ${missingElements.length}
        - Duplicate Elements: ${duplicates.length}
        `);
        
        if (missingElements.length > 0) {
            console.log('❌ Missing Elements:', missingElements);
        }
        
        if (duplicates.length > 0) {
            console.log('⚠️ Duplicate Elements:', duplicates);
        }
        
        // Test click functionality
        const playBtn = document.getElementById('play-pause-btn');
        if (playBtn) {
            console.log('🧪 Testing play button...');
            const hasClickListener = playBtn.onclick !== null || playBtn.getAttribute('onclick') !== null;
            console.log(`🎯 Play button has click handler: ${hasClickListener}`);
        }
        
        return {
            foundElements: foundElements.length,
            totalElements: requiredElements.length,
            missingElements,
            duplicates,
            isHealthy: missingElements.length === 0 && duplicates.length === 0
        };
    };

    // =============================================================================
    // STEP-BY-STEP TROUBLESHOOTING GUIDE
    // =============================================================================

    window.troubleshootAudioPlayer = function() {
        console.log('🔧 AUDIO PLAYER TROUBLESHOOTING GUIDE:');
        console.log('=====================================');
        
        console.log('Step 1: Clear cache and force reload...');
        clearAudioPlayerCache();
        
        setTimeout(() => {
            console.log('Step 2: Verify UI elements...');
            const uiCheck = verifyAudioPlayerUI();
            
            if (!uiCheck.isHealthy) {
                console.log('Step 3: Force reinitialize...');
                forceReinitializeAudioPlayer();
                
                setTimeout(() => {
                    console.log('Step 4: Final verification...');
                    const finalCheck = verifyAudioPlayerUI();
                    
                    if (finalCheck.isHealthy) {
                        console.log('✅ TROUBLESHOOTING SUCCESSFUL!');
                        if (window.showToast) {
                            showToast('Audio player berhasil diperbaiki!', 'success');
                        }
                    } else {
                        console.log('❌ TROUBLESHOOTING FAILED - Manual refresh required');
                        if (confirm('Audio player masih bermasalah. Refresh halaman sekarang?')) {
                            window.location.reload();
                        }
                    }
                }, 2000);
            } else {
                console.log('✅ Audio player sudah dalam kondisi baik!');
            }
        }, 1000);
    };

    // =============================================================================
    // IMMEDIATE EXECUTION SCRIPT (For Testing)
    // =============================================================================

    // Auto-run troubleshooting if there are obvious issues
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(() => {
            if (window.unifiedApp?.modules.audioPlayer) {
                const diagnosis = window.unifiedApp.modules.audioPlayer.diagnoseAudioPlayer();
                
                if (!diagnosis.domElementsOk || !diagnosis.uiElementsOk) {
                    console.warn('⚠️ Audio player issues detected, running auto-fix...');
                    troubleshootAudioPlayer();
                }
            }
        }, 3000); // Run after everything else has loaded
    });

    console.log('🔧 Audio Player UI Conflicts Fix loaded successfully!');

    // =============================================================================
    // UNIVERSAL VOICE NAVIGATION SYSTEM - FIXED VERSION
    // =============================================================================
    // Mengatasi konflik dengan VoiceSearchManager dan memperbaiki masalah navigasi

    class UniversalVoiceNavigationManager {
        constructor() {
            this.isSupported = 'webkitSpeechRecognition' in window;
            this.recognition = null;
            this.isListening = false;
            this.isInitialized = false;
            this.currentPage = this.detectCurrentPage();
            this.documents = [];
            this.maxReadableDocuments = 5;
            this.restartTimeout = null;
            this.isSpeaking = false;
            this.isNavMode = false; // Flag to distinguish from voice search
            
            // Prevent multiple initialization
            if (window.voiceNavigationInstance) {
                console.log('🔄 Voice Navigation already exists, using existing instance');
                return window.voiceNavigationInstance;
            }
            
            this.commandHandlers = {
                // Document reading commands
                'baca dokumen': () => this.readDocuments(),
                'bacakan dokumen': () => this.readDocuments(),
                'dokumen apa saja': () => this.readDocuments(),
                
                // Document selection commands
                'pilih dokumen': (text) => this.selectDocument(text),
                'buka dokumen': (text) => this.selectDocument(text),
                'putar dokumen': (text) => this.playDocument(text),
                
                // Navigation commands
                'halaman selanjutnya': () => this.nextPage(),
                'halaman berikutnya': () => this.nextPage(),
                'halaman sebelumnya': () => this.previousPage(),
                'kembali': () => this.previousPage(),
                
                // Filter commands
                'filter publikasi': () => this.applyFilter('publications'),
                'filter brs': () => this.applyFilter('brs'),
                'filter tahun': (text) => this.applyYearFilter(text),
                'reset filter': () => this.resetFilters(),
                
                // Info commands
                'berapa dokumen': () => this.countDocuments(),
                'bantuan navigasi': () => this.showNavigationHelp(),
                'panduan': () => this.showNavigationHelp(),
                
                // Search commands (will be handled by VoiceSearchManager)
                'cari': (text) => this.delegateToSearch(text)
            };
            
            if (this.isSupported) {
                this.init();
            }
            
            // Store instance globally
            window.voiceNavigationInstance = this;
        }

        detectCurrentPage() {
            const path = window.location.pathname;
            if (path.includes('/search')) return 'search';
            if (path.includes('/publications')) return 'publications';
            if (path.includes('/brs')) return 'brs';
            return 'home';
        }

        async init() {
            if (this.isInitialized) {
                console.log('🔄 Voice Navigation already initialized');
                return;
            }

            console.log('🎯 Initializing Universal Voice Navigation Manager');
            
            this.setupPageSpecificData();
            this.initializeRecognition();
            
            // Start listening with delay to avoid conflicts
            setTimeout(() => {
                this.startNavigationMode();
            }, 2000);
            
            this.isInitialized = true;
        }

        setupPageSpecificData() {
            // Collect documents from current page
            this.updateDocuments();
            
            // Setup page-specific handlers
            if (this.currentPage === 'search') {
                this.setupSearchPageHandlers();
            } else if (this.currentPage === 'publications') {
                this.setupPublicationsPageHandlers();
            } else if (this.currentPage === 'brs') {
                this.setupBRSPageHandlers();
            }
        }

        updateDocuments() {
            // Find documents on current page
            const documentElements = document.querySelectorAll('#documents-grid [data-document-index], .document-card, .document-item');
            this.documents = Array.from(documentElements).map((el, index) => {
                const titleEl = el.querySelector('h3, .document-title, [data-document-title]');
                const linkEl = el.querySelector('a[href*="/documents/"]');
                
                return {
                    index: index + 1,
                    title: titleEl ? titleEl.textContent.trim() : `Dokumen ${index + 1}`,
                    element: el,
                    link: linkEl ? linkEl.href : null,
                    id: el.dataset.documentId || el.dataset.documentIndex
                };
            });
            
            console.log(`📄 Found ${this.documents.length} documents on page`);
        }

        initializeRecognition() {
            // Clean up any existing recognition
            this.cleanup();
            
            this.recognition = new webkitSpeechRecognition();
            this.recognition.continuous = true;
            this.recognition.interimResults = false;
            this.recognition.lang = 'id-ID';
            this.recognition.maxAlternatives = 1;
            
            this.recognition.onstart = () => {
                console.log('🎤 Voice navigation listening started');
                this.isListening = true;
                if (this.restartTimeout) {
                    clearTimeout(this.restartTimeout);
                    this.restartTimeout = null;
                }
            };

            this.recognition.onresult = (event) => {
                const transcript = event.results[event.results.length - 1][0].transcript.toLowerCase().trim();
                console.log('🎯 Navigation command detected:', transcript);
                
                // Only handle navigation commands, not search commands
                if (this.isNavigationCommand(transcript)) {
                    this.handleNavigationCommand(transcript);
                }
            };

            this.recognition.onerror = (event) => {
                console.warn('⚠️ Voice navigation error:', event.error);
                
                // Don't restart on certain errors
                if (event.error === 'no-speech' || event.error === 'audio-capture') {
                    this.scheduleRestart(2000);
                } else if (event.error === 'not-allowed') {
                    console.error('❌ Microphone permission denied');
                    this.isListening = false;
                } else {
                    this.scheduleRestart(1000);
                }
            };

            this.recognition.onend = () => {
                console.log('🛑 Voice navigation stopped');
                this.isListening = false;
                
                // Auto-restart if still in navigation mode
                if (this.isNavMode && !this.isSpeaking) {
                    this.scheduleRestart(1000);
                }
            };
        }

        isNavigationCommand(transcript) {
            const navigationKeywords = [
                'baca dokumen', 'bacakan dokumen', 'dokumen apa saja',
                'pilih dokumen', 'buka dokumen', 'putar dokumen',
                'halaman selanjutnya', 'halaman berikutnya', 'halaman sebelumnya', 'kembali',
                'filter publikasi', 'filter brs', 'filter tahun', 'reset filter',
                'berapa dokumen', 'bantuan navigasi', 'panduan'
            ];
            
            return navigationKeywords.some(keyword => transcript.includes(keyword));
        }

        handleNavigationCommand(transcript) {
            // Stop listening during command processing
            this.pauseListening();
            
            // Find matching command
            for (const [command, handler] of Object.entries(this.commandHandlers)) {
                if (transcript.includes(command)) {
                    try {
                        // Extract additional text after command
                        const additionalText = transcript.replace(command, '').trim();
                        handler(additionalText);
                    } catch (error) {
                        console.error(`❌ Error executing command '${command}':`, error);
                        this.speak('Maaf, terjadi kesalahan saat memproses perintah.');
                    }
                    return;
                }
            }
            
            // If no command matched, check for number-based commands
            this.handleNumberCommands(transcript);
        }

        handleNumberCommands(transcript) {
            const numberWords = {
                'satu': 1, 'dua': 2, 'tiga': 3, 'empat': 4, 'lima': 5,
                'enam': 6, 'tujuh': 7, 'delapan': 8, 'sembilan': 9, 'sepuluh': 10
            };
            
            // Extract number from transcript
            let number = null;
            
            // Check for digit numbers
            const digitMatch = transcript.match(/\d+/);
            if (digitMatch) {
                number = parseInt(digitMatch[0]);
            } else {
                // Check for word numbers
                for (const [word, num] of Object.entries(numberWords)) {
                    if (transcript.includes(word)) {
                        number = num;
                        break;
                    }
                }
            }
            
            if (number && number <= this.documents.length) {
                if (transcript.includes('pilih') || transcript.includes('buka')) {
                    this.selectDocumentByNumber(number);
                } else if (transcript.includes('putar')) {
                    this.playDocumentByNumber(number);
                }
            } else {
                this.speak('Perintah tidak dikenali. Katakan "bantuan navigasi" untuk panduan.');
            }
        }

        // ==================== COMMAND HANDLERS ====================

        readDocuments() {
            if (this.documents.length === 0) {
                this.speak('Tidak ada dokumen ditemukan pada halaman ini.');
                return;
            }
            
            if (this.documents.length > this.maxReadableDocuments) {
                this.speak(`Ditemukan ${this.documents.length} dokumen. Terlalu banyak untuk dibaca. Silakan gunakan filter untuk mempersempit pencarian.`);
                return;
            }
            
            this.speak(`Ditemukan ${this.documents.length} dokumen. Berikut judulnya:`);
            
            // Read document titles one by one
            this.documents.forEach((doc, index) => {
                setTimeout(() => {
                    this.speak(`Nomor ${doc.index}, ${doc.title}`);
                }, (index + 1) * 3000); // 3 second delay between each
            });
            
            // Provide guidance after reading all titles
            setTimeout(() => {
                this.speak('Untuk membuka dokumen, katakan "pilih dokumen nomor". Untuk memutar audio, katakan "putar dokumen nomor".');
            }, (this.documents.length + 1) * 3000);
        }

        selectDocument(text) {
            const number = this.extractNumber(text);
            if (number) {
                this.selectDocumentByNumber(number);
            } else {
                this.speak('Sebutkan nomor dokumen yang ingin dipilih. Misalnya: "pilih dokumen nomor 1".');
            }
        }

        selectDocumentByNumber(number) {
            const doc = this.documents.find(d => d.index === number);
            if (doc && doc.link) {
                this.speak(`Membuka dokumen nomor ${number}: ${doc.title}`);
                setTimeout(() => {
                    window.location.href = doc.link;
                }, 1000);
            } else {
                this.speak(`Dokumen nomor ${number} tidak ditemukan.`);
            }
        }

        playDocument(text) {
            const number = this.extractNumber(text);
            if (number) {
                this.playDocumentByNumber(number);
            } else {
                this.speak('Sebutkan nomor dokumen yang ingin diputar. Misalnya: "putar dokumen nomor 1".');
            }
        }

        playDocumentByNumber(number) {
            const doc = this.documents.find(d => d.index === number);
            if (doc && doc.id) {
                this.speak(`Memutar audio dokumen nomor ${number}: ${doc.title}`);
                
                // Try to play audio using different methods
                setTimeout(() => {
                    this.playDocumentAudio(doc);
                }, 1000);
            } else {
                this.speak(`Dokumen nomor ${number} tidak ditemukan atau tidak memiliki audio.`);
            }
        }

        playDocumentAudio(doc) {
            // Method 1: Try unified audio manager
            if (window.unifiedApp && window.unifiedApp.modules.audioPlayer) {
                const audioUrl = `/documents/${doc.id}/audio/mp3/stream`;
                window.unifiedApp.modules.audioPlayer.loadAndPlay(audioUrl, doc.title);
                return;
            }
            
            // Method 2: Try direct audio play
            const audioUrl = `/documents/${doc.id}/audio/mp3/stream`;
            const audio = new Audio(audioUrl);
            audio.play()
                .then(() => console.log(`▶️ Playing: ${doc.title}`))
                .catch(err => {
                    console.error('❌ Audio play failed:', err);
                    this.speak('Maaf, audio tidak dapat diputar. Pastikan dokumen memiliki file audio.');
                });
        }

        nextPage() {
            const nextLink = document.querySelector('a[rel="next"]') || 
                            document.querySelector('.pagination-next') ||
                            document.querySelector('[data-page-next]');
            
            if (nextLink) {
                this.speak('Membuka halaman selanjutnya');
                setTimeout(() => nextLink.click(), 1000);
            } else {
                this.speak('Sudah di halaman terakhir.');
            }
        }

        previousPage() {
            const prevLink = document.querySelector('a[rel="prev"]') || 
                            document.querySelector('.pagination-prev') ||
                            document.querySelector('[data-page-prev]');
            
            if (prevLink) {
                this.speak('Membuka halaman sebelumnya');
                setTimeout(() => prevLink.click(), 1000);
            } else {
                this.speak('Sudah di halaman pertama.');
            }
        }

        applyFilter(type) {
            if (type === 'publications') {
                this.speak('Menerapkan filter publikasi');
                setTimeout(() => {
                    window.location.href = '/documents/publications';
                }, 1000);
            } else if (type === 'brs') {
                this.speak('Menerapkan filter BRS');
                setTimeout(() => {
                    window.location.href = '/documents/brs';
                }, 1000);
            }
        }

        applyYearFilter(text) {
            const year = this.extractYear(text);
            if (year) {
                const yearSelect = document.querySelector('#year, select[name="year"]');
                if (yearSelect) {
                    yearSelect.value = year;
                    this.speak(`Menerapkan filter tahun ${year}`);
                    setTimeout(() => {
                        yearSelect.form.submit();
                    }, 1000);
                } else {
                    this.speak('Filter tahun tidak tersedia pada halaman ini.');
                }
            } else {
                this.speak('Sebutkan tahun yang ingin difilter. Misalnya: "filter tahun 2023".');
            }
        }

        resetFilters() {
            const resetButton = document.querySelector('[data-reset-filters]') ||
                            document.querySelector('.reset-filters') ||
                            document.querySelector('a[href*="reset"]');
            
            if (resetButton) {
                this.speak('Mereset semua filter');
                setTimeout(() => resetButton.click(), 1000);
            } else {
                // Navigate to base URL without parameters
                const baseUrl = window.location.pathname;
                this.speak('Mereset semua filter');
                setTimeout(() => {
                    window.location.href = baseUrl;
                }, 1000);
            }
        }

        countDocuments() {
            this.speak(`Ditemukan ${this.documents.length} dokumen pada halaman ini.`);
        }

        showNavigationHelp() {
            const helpText = 'Perintah navigasi yang tersedia: ' +
                            'Katakan "baca dokumen" untuk mendengar daftar dokumen. ' +
                            'Katakan "pilih dokumen nomor" untuk membuka dokumen. ' +
                            'Katakan "putar dokumen nomor" untuk memutar audio. ' +
                            'Katakan "halaman selanjutnya" atau "halaman sebelumnya" untuk navigasi halaman. ' +
                            'Katakan "filter publikasi" atau "filter BRS" untuk filter dokumen. ' +
                            'Katakan "berapa dokumen" untuk mengetahui jumlah dokumen.';
            
            this.speak(helpText);
        }

        delegateToSearch(text) {
            // Delegate search commands to VoiceSearchManager
            console.log('🔍 Delegating search to VoiceSearchManager:', text);
            
            if (window.unifiedApp && window.unifiedApp.modules.voiceSearch) {
                // Use unified voice search
                window.unifiedApp.modules.voiceSearch.performSearch(text);
            } else if (window.AudioSystem && window.AudioSystem.handleSearchCommand) {
                // Use compatibility bridge
                window.AudioSystem.handleSearchCommand(text);
            } else {
                // Fallback: manual search
                const searchInput = document.querySelector('input[name="search"]');
                if (searchInput) {
                    searchInput.value = text;
                    searchInput.form.submit();
                }
            }
        }

        // ==================== PAGE-SPECIFIC HANDLERS ====================

        setupSearchPageHandlers() {
            // Add search-specific commands
            this.commandHandlers['persempit pencarian'] = () => {
                this.speak('Gunakan filter di sidebar untuk mempersempit pencarian, atau katakan "filter publikasi" atau "filter BRS".');
            };
        }

        setupPublicationsPageHandlers() {
            // Add publication-specific commands
            this.commandHandlers['filter kategori'] = (text) => {
                this.applyPublicationCategoryFilter(text);
            };
        }

        setupBRSPageHandlers() {
            // Add BRS-specific commands
            this.commandHandlers['filter indikator'] = (text) => {
                this.applyIndicatorFilter(text);
            };
        }

        applyIndicatorFilter(text) {
            const indicatorSelect = document.querySelector('#indicator, select[name="indicator"]');
            if (indicatorSelect) {
                // Try to find matching option
                const options = Array.from(indicatorSelect.options);
                const matchingOption = options.find(option => 
                    option.textContent.toLowerCase().includes(text.toLowerCase())
                );
                
                if (matchingOption) {
                    indicatorSelect.value = matchingOption.value;
                    this.speak(`Menerapkan filter indikator: ${matchingOption.textContent}`);
                    setTimeout(() => {
                        indicatorSelect.form.submit();
                    }, 1000);
                } else {
                    this.speak('Indikator tidak ditemukan. Sebutkan nama indikator yang lebih spesifik.');
                }
            } else {
                this.speak('Filter indikator tidak tersedia pada halaman ini.');
            }
        }

        // ==================== UTILITY METHODS ====================

        extractNumber(text) {
            const digitMatch = text.match(/\d+/);
            if (digitMatch) {
                return parseInt(digitMatch[0]);
            }
            
            const numberWords = {
                'satu': 1, 'dua': 2, 'tiga': 3, 'empat': 4, 'lima': 5,
                'enam': 6, 'tujuh': 7, 'delapan': 8, 'sembilan': 9, 'sepuluh': 10
            };
            
            for (const [word, num] of Object.entries(numberWords)) {
                if (text.includes(word)) {
                    return num;
                }
            }
            
            return null;
        }

        extractYear(text) {
            const yearMatch = text.match(/\b(20\d{2})\b/);
            return yearMatch ? yearMatch[1] : null;
        }

        speak(text) {
            this.isSpeaking = true;
            
            const utterance = new SpeechSynthesisUtterance(text);
            utterance.lang = 'id-ID';
            utterance.rate = 0.9;
            
            utterance.onend = () => {
                this.isSpeaking = false;
                // Resume listening after speaking
                setTimeout(() => {
                    this.resumeListening();
                }, 500);
            };
            
            utterance.onerror = () => {
                this.isSpeaking = false;
                this.resumeListening();
            };
            
            window.speechSynthesis.speak(utterance);
        }

        // ==================== LIFECYCLE MANAGEMENT ====================

        startNavigationMode() {
            if (!this.isSupported || this.isListening) return;
            
            this.isNavMode = true;
            console.log('🎯 Starting navigation mode');
            
            try {
                this.recognition.start();
            } catch (error) {
                console.warn('⚠️ Could not start navigation recognition:', error);
                this.scheduleRestart(2000);
            }
        }

        pauseListening() {
            if (this.recognition && this.isListening) {
                try {
                    this.recognition.stop();
                } catch (error) {
                    console.warn('⚠️ Error stopping recognition:', error);
                }
            }
        }

        resumeListening() {
            if (!this.isNavMode || this.isListening || this.isSpeaking) return;
            
            setTimeout(() => {
                if (!this.isListening && this.isNavMode && !this.isSpeaking) {
                    try {
                        this.recognition.start();
                    } catch (error) {
                        console.warn('⚠️ Error resuming recognition:', error);
                        this.scheduleRestart(1000);
                    }
                }
            }, 500);
        }

        scheduleRestart(delay = 1000) {
            if (this.restartTimeout) {
                clearTimeout(this.restartTimeout);
            }
            
            this.restartTimeout = setTimeout(() => {
                if (this.isNavMode && !this.isListening && !this.isSpeaking) {
                    console.log('🔄 Restarting voice navigation');
                    this.startNavigationMode();
                }
            }, delay);
        }

        announcePageLoad() {
            const pageAnnouncements = {
                'search': 'Halaman pencarian dimuat. Gunakan perintah navigasi suara.',
                'publications': 'Halaman publikasi dimuat. Gunakan perintah navigasi suara.',
                'brs': 'Halaman BRS dimuat. Gunakan perintah navigasi suara.',
                'home': 'Halaman beranda dimuat. Selamat datang di Audio Statistik.'
            };
            
            const announcement = pageAnnouncements[this.currentPage] || 'Halaman dimuat.';
            
            // Delay announcement to ensure page is fully loaded
            setTimeout(() => {
                if (!this.isSpeaking) {
                    this.speak(announcement);
                }
            }, 3000);
        }

        cleanup() {
            if (this.restartTimeout) {
                clearTimeout(this.restartTimeout);
                this.restartTimeout = null;
            }
            
            if (this.recognition) {
                try {
                    this.recognition.stop();
                } catch (error) {
                    console.warn('⚠️ Error stopping recognition during cleanup:', error);
                }
            }
            
            this.isListening = false;
            this.isNavMode = false;
            this.isSpeaking = false;
        }

        destroy() {
            this.cleanup();
            this.isSupported = false;
            this.isInitialized = false;
            window.voiceNavigationInstance = null;
        }
    }

    // ==================== INITIALIZATION ====================

    // Initialize voice navigation system with singleton pattern
    let universalVoiceNavigation = null;

    document.addEventListener('DOMContentLoaded', function() {
        // Wait for other systems to initialize first
        setTimeout(() => {
            if (!universalVoiceNavigation) {
                universalVoiceNavigation = new UniversalVoiceNavigationManager();
                
                // Announce page load for accessibility
                setTimeout(() => {
                    if (universalVoiceNavigation && !universalVoiceNavigation.isSpeaking) {
                        universalVoiceNavigation.announcePageLoad();
                    }
                }, 2000);
            }
        }, 3000); // Wait 3 seconds to avoid conflicts
    });

    // Re-initialize when page content changes (for AJAX updates)
    const documentObserver = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                const hasNewDocuments = Array.from(mutation.addedNodes).some(node => 
                    node.nodeType === 1 && node.querySelector && 
                    (node.querySelector('[data-document-index]') || node.querySelector('.document-card'))
                );
                
                if (hasNewDocuments && universalVoiceNavigation) {
                    console.log('📄 New documents detected, updating navigation');
                    universalVoiceNavigation.updateDocuments();
                }
            }
        });
    });

    documentObserver.observe(document.body, {
        childList: true,
        subtree: true
    });

    // Cleanup on page unload
    window.addEventListener('beforeunload', function() {
        if (universalVoiceNavigation) {
            universalVoiceNavigation.destroy();
        }
    });

    // Export for global access
    window.UniversalVoiceNavigation = universalVoiceNavigation;

})();