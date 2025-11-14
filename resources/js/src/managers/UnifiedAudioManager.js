import { CONFIG } from "../config";
export class UnifiedAudioManager {
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
    console.log("🎵 Initializing Unified Audio Manager");

    await this.initializeAudioElement();
    await this.initializeUI();
    await this.setupEventListeners();
    await this.setupIntegrations();
    await this.setupKeyboardShortcuts();

    console.log("✅ Unified Audio Manager initialized");
  }

  async initializeAudioElement() {
    console.log("🎵 Initializing audio element with enhanced setup");

    // Remove existing main audio element if any
    const existingAudio = document.getElementById("main-audio-element");
    if (existingAudio) {
      console.log("🗑️ Removing existing main audio element");
      existingAudio.remove();
    }

    // Create fresh audio element
    this.currentAudio = document.createElement("audio");
    this.currentAudio.id = "main-audio-element";
    this.currentAudio.preload = "none";
    this.currentAudio.crossOrigin = "anonymous";

    // Add to DOM but keep hidden
    this.currentAudio.style.display = "none";
    document.body.appendChild(this.currentAudio);

    console.log("✅ Fresh main audio element created and added to DOM");

    // Setup audio event listeners
    this.setupAudioEventListeners();

    // Verify audio element
    const verification = document.getElementById("main-audio-element");
    if (!verification) {
      throw new Error("Failed to create main audio element");
    }

    console.log("✅ Audio element verification passed");
  }

  async initializeUI() {
    console.log("🎨 Initializing Audio Player UI with forced cleanup");

    // STEP 1: Force remove ALL existing audio players
    this.forceCleanupExistingPlayers();

    // STEP 2: Wait a bit for DOM cleanup
    await new Promise((resolve) => setTimeout(resolve, 100));

    // STEP 3: Create fresh player
    await this.createBottomPlayer();

    // STEP 4: Cache UI elements
    this.cacheUIElements();

    // STEP 5: Verify UI integrity
    this.verifyUIIntegrity();
  }

  forceCleanupExistingPlayers() {
    console.log("🧹 Force cleaning existing audio players...");

    // Remove by ID (most specific)
    const existingPlayer = document.getElementById("bottom-audio-player");
    if (existingPlayer) {
      console.log("🗑️ Removing existing bottom-audio-player");
      existingPlayer.remove();
    }

    // Remove by class (backup)
    const playersByClass = document.querySelectorAll(
      ".audio-player, .bottom-audio-player, .fixed.bottom-0"
    );
    playersByClass.forEach((player, index) => {
      if (
        player.id !== "bottom-audio-player" &&
        (player.classList.contains("audio-player") ||
          player.innerHTML.includes("play-pause-btn") ||
          player.innerHTML.includes("current-doc-title"))
      ) {
        console.log(`🗑️ Removing duplicate audio player ${index}`);
        player.remove();
      }
    });

    // Remove sidebar if exists
    const existingSidebar = document.getElementById("right-sidebar");
    if (existingSidebar) {
      console.log("🗑️ Removing existing sidebar");
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
    console.log("🏗️ Creating responsive Spotify-like audio player");

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
    const existingPlayer = document.getElementById("bottom-audio-player");
    const existingSidebar = document.getElementById("right-sidebar");

    if (existingPlayer) existingPlayer.remove();
    if (existingSidebar) existingSidebar.remove();

    // Add new elements
    document.body.insertAdjacentHTML("beforeend", playerHTML);
    document.body.insertAdjacentHTML("beforeend", sidebarHTML);

    this.bottomPlayer = document.getElementById("bottom-audio-player");

    console.log("✅ Responsive audio player created successfully");
  }

  cacheUIElements() {
    console.log("💾 Caching responsive UI elements...");

    // Cache primary elements (desktop)
    this.playPauseBtn = document.getElementById("play-pause-btn");
    this.progressBar = document.getElementById("progress-bar");
    this.progressContainer = document.getElementById("progress-container");
    this.currentTimeEl = document.getElementById("current-time-main");
    this.totalTimeEl = document.getElementById("total-time-main");

    // Cache mobile elements
    this.playPauseBtnMobile = document.getElementById("play-pause-btn-mobile");
    this.progressBarMobile = document.getElementById("progress-bar-mobile");
    this.progressContainerMobile = document.getElementById(
      "progress-container-mobile"
    );

    // Verify core elements exist
    const coreElements = {
      "Play/Pause Button (Desktop)": this.playPauseBtn,
      "Play/Pause Button (Mobile)": this.playPauseBtnMobile,
      "Progress Bar (Desktop)": this.progressBar,
      "Progress Bar (Mobile)": this.progressBarMobile,
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
    console.log("🔍 Verifying UI integrity...");

    const playerCount = document.querySelectorAll(
      "#bottom-audio-player"
    ).length;
    const sidebarCount = document.querySelectorAll("#right-sidebar").length;

    console.log(`📊 UI Integrity Check:
            - Bottom Players: ${playerCount}
            - Sidebars: ${sidebarCount}
            - Play Button Exists: ${!!this.playPauseBtn}
            - Progress Bar Exists: ${!!this.progressBar}`);

    if (playerCount > 1) {
      console.warn("⚠️ Multiple bottom players detected! Cleaning up...");
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
      volumechange: () => this.handleVolumeChange(),
    };

    // Add event listeners
    Object.entries(this.boundEventHandlers).forEach(([event, handler]) => {
      this.currentAudio.addEventListener(event, handler);
    });
    this.currentAudio.addEventListener("pause", () =>
      this.saveProgressToBackend(true)
    );
    this.currentAudio.addEventListener("ended", () =>
      this.saveProgressToBackend(true)
    );
    this.currentAudio.addEventListener("seeked", () =>
      this.saveProgressToBackend(true)
    );
    document.addEventListener("visibilitychange", () => {
      if (document.visibilityState === "hidden")
        this.saveProgressToBackend(true);
    });
    window.addEventListener("beforeunload", () =>
      this.saveProgressToBackend(true)
    );
  }

  removeAudioEventListeners() {
    if (!this.currentAudio || !this.boundEventHandlers) return;

    Object.entries(this.boundEventHandlers).forEach(([event, handler]) => {
      this.currentAudio.removeEventListener(event, handler);
    });
  }

  async setupEventListeners() {
    console.log("🎛️ Setting up responsive event listeners...");

    // Verify UI elements exist
    if (!this.cacheUIElements()) {
      console.error("❌ Cannot setup event listeners - missing UI elements");
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

    console.log("✅ Responsive event listeners setup complete");
    return true;
  }

  setupDesktopListeners() {
    // Desktop play/pause
    const playPauseBtn = document.getElementById("play-pause-btn");
    if (playPauseBtn) {
      this.attachListener(playPauseBtn, "click", () => this.togglePlayPause());
    }

    // Desktop progress
    const progressContainer = document.getElementById("progress-container");
    if (progressContainer) {
      this.attachListener(progressContainer, "click", (e) =>
        this.handleProgressClick(e)
      );
    }

    // Desktop skip buttons
    this.attachListener(
      document.getElementById("skip-backward-btn"),
      "click",
      () => this.skipBackward(10)
    );
    this.attachListener(
      document.getElementById("skip-forward-btn"),
      "click",
      () => this.skipForward(10)
    );

    // Volume controls
    const volumeSlider = document.getElementById("volume-slider");
    const muteBtn = document.getElementById("mute-btn");

    if (volumeSlider) {
      this.attachListener(volumeSlider, "input", (e) => {
        console.log("🔊 Volume changed:", e.target.value);
        this.setVolume(e.target.value / 100);
      });
    }

    if (muteBtn) {
      this.attachListener(muteBtn, "click", () => {
        console.log("🔇 Mute button clicked");
        this.toggleMute();
      });
    }

    // Speed control
    this.attachListener(document.getElementById("speed-btn"), "click", () =>
      this.cyclePlaybackSpeed()
    );

    // More options
    this.attachListener(
      document.getElementById("more-options-btn"),
      "click",
      () => this.showSidebar()
    );

    // Close button
    this.attachListener(
      document.getElementById("close-player-btn"),
      "click",
      () => this.closePlayer()
    );
  }

  setupMobileListeners() {
    // Mobile play/pause
    this.attachListener(
      document.getElementById("play-pause-btn-mobile"),
      "click",
      () => this.Pause()
    );

    // Mobile progress
    this.attachListener(
      document.getElementById("progress-container-mobile"),
      "click",
      (e) => this.handleProgressClick(e)
    );

    // Mobile skip buttons
    this.attachListener(
      document.getElementById("skip-backward-btn-mobile"),
      "click",
      () => this.skipBackward(10)
    );
    this.attachListener(
      document.getElementById("skip-forward-btn-mobile"),
      "click",
      () => this.skipForward(10)
    );

    // Mobile more options
    this.attachListener(
      document.getElementById("more-options-btn-mobile"),
      "click",
      () => this.showSidebar()
    );
  }

  setupSharedListeners() {
    // Sidebar listeners
    this.attachListener(
      document.getElementById("close-sidebar-btn"),
      "click",
      () => this.hideSidebar()
    );
    this.attachListener(
      document.getElementById("download-audio-btn"),
      "click",
      () => this.downloadCurrentAudio()
    );
    this.attachListener(document.getElementById("share-btn"), "click", () =>
      this.shareCurrentDocument()
    );

    // View document button - use direct route navigation
    const viewDocBtn = document.getElementById("view-document-btn");
    if (viewDocBtn) {
      viewDocBtn.addEventListener("click", () => {
        if (this.currentDocument) {
          const docUrl = `/storage/documents/files/${this.currentDocument.year}/${this.currentDocument.id}-document.pdf`;
          window.open(docUrl, "_blank");
          console.log("✅ Opening document:", this.currentDocument.title);
        }
      });
      viewDocBtn.setAttribute("data-listener-attached", "true");
    }

    // Title clicks for sidebar
    this.attachListener(
      document.getElementById("current-doc-title"),
      "click",
      () => this.showSidebar()
    );
    this.attachListener(
      document.getElementById("current-doc-title-mobile"),
      "click",
      () => this.showSidebar()
    );
  }

  // Helper method to attach listeners with verification
  attachListener(element, event, handler) {
    if (element && handler) {
      element.addEventListener(event, handler);
      element.setAttribute("data-listener-attached", "true");
      // console.log(`✅ Attached ${event} listener to ${element.id || element.className}`);
    }
  }
  setupPlayPauseListener() {
    const playPauseBtn = document.getElementById("play-pause-btn");
    if (playPauseBtn) {
      // Remove existing listeners
      const newBtn = playPauseBtn.cloneNode(true);
      playPauseBtn.parentNode.replaceChild(newBtn, playPauseBtn);

      // Cache new reference
      this.playPauseBtn = newBtn;

      // Add new listener
      this.playPauseBtn.addEventListener("click", (e) => {
        e.preventDefault();
        e.stopPropagation();
        console.log("🎵 Play/Pause button clicked (enhanced)");
        this.togglePlayPause();
      });

      // Verify listener attachment
      this.playPauseBtn.setAttribute("data-listener-attached", "true");
      console.log("✅ Play/Pause listener attached and verified");
    } else {
      console.error("❌ Play/Pause button not found for listener attachment");
    }
  }

  setupProgressListener() {
    const progressContainer = document.getElementById("progress-container");
    if (progressContainer) {
      // Remove existing listeners
      const newContainer = progressContainer.cloneNode(true);
      progressContainer.parentNode.replaceChild(
        newContainer,
        progressContainer
      );

      // Cache new reference
      this.progressContainer = newContainer;
      this.progressBar = newContainer.querySelector("#progress-bar");

      // Add new listener
      this.progressContainer.addEventListener("click", (e) => {
        e.preventDefault();
        e.stopPropagation();
        console.log("📊 Progress bar clicked (enhanced)");
        this.handleProgressClick(e);
      });

      // Verify listener attachment
      this.progressContainer.setAttribute("data-listener-attached", "true");
      console.log("✅ Progress listener attached and verified");
    } else {
      console.error("❌ Progress container not found for listener attachment");
    }
  }

  setupVolumeListeners() {
    // Volume slider
    if (this.volumeSlider) {
      this.volumeSlider.addEventListener("input", (e) => {
        console.log("🔊 Volume changed:", e.target.value);
        this.setVolume(e.target.value / 100);
      });
      console.log("✅ Volume slider listener attached");
    }

    // Mute button
    if (this.muteBtn) {
      this.muteBtn.addEventListener("click", () => {
        console.log("🔇 Mute button clicked");
        this.toggleMute();
      });
      console.log("✅ Mute button listener attached");
    }
  }

  setupControlListeners() {
    // Skip buttons
    const skipBackBtn = document.getElementById("skip-backward-btn");
    const skipForwardBtn = document.getElementById("skip-forward-btn");

    if (skipBackBtn) {
      skipBackBtn.addEventListener("click", () => {
        console.log("⏮️ Skip backward clicked");
        this.skipBackward(10);
      });
    }

    if (skipForwardBtn) {
      skipForwardBtn.addEventListener("click", () => {
        console.log("⏭️ Skip forward clicked");
        this.skipForward(10);
      });
    }

    // Speed control
    if (this.speedBtn) {
      this.speedBtn.addEventListener("click", () => {
        console.log("⚡ Speed button clicked");
        this.cyclePlaybackSpeed();
      });
    }

    // More options (show sidebar)
    const moreOptionsBtn = document.getElementById("more-options-btn");
    if (moreOptionsBtn) {
      moreOptionsBtn.addEventListener("click", () => {
        console.log("⚙️ More options clicked");
        this.showSidebar();
      });
    }

    // Close button
    const closeBtn = document.getElementById("close-player-btn");
    if (closeBtn) {
      closeBtn.addEventListener("click", () => {
        console.log("❌ Close button clicked");
        this.closePlayer();
      });
    }

    // Title click (show sidebar)
    if (this.titleEl) {
      this.titleEl.addEventListener("click", () => {
        console.log("📄 Title clicked");
        this.showSidebar();
      });
    }
  }

  setupSidebarListeners() {
    // Download button
    const downloadBtn = document.getElementById("download-audio-btn");
    if (downloadBtn) {
      downloadBtn.addEventListener("click", () => {
        console.log("💾 Download button clicked");
        this.downloadCurrentAudio();
      });
    }

    // Share button
    const shareBtn = document.getElementById("share-btn");
    if (shareBtn) {
      shareBtn.addEventListener("click", () => {
        console.log("🔗 Share button clicked");
        this.shareCurrentDocument();
      });
    }

    // Sidebar close
    const closeSidebarBtn = document.getElementById("close-sidebar-btn");
    if (closeSidebarBtn) {
      closeSidebarBtn.addEventListener("click", () => {
        console.log("❌ Sidebar close clicked");
        this.hideSidebar();
      });
    }
  }

  removeAllEventListeners() {
    // Clone and replace elements to remove all listeners
    const elementsToClean = [
      "play-pause-btn",
      "progress-container",
      "volume-slider",
      "mute-btn",
      "speed-btn",
      "skip-backward-btn",
      "skip-forward-btn",
      "more-options-btn",
      "close-player-btn",
      "current-doc-title",
    ];

    elementsToClean.forEach((id) => {
      const element = document.getElementById(id);
      if (element) {
        const newElement = element.cloneNode(true);
        element.parentNode.replaceChild(newElement, element);
      }
    });

    console.log("🧹 Cleaned existing event listeners");
  }

  verifyEventListeners() {
    console.log("🔍 Enhanced event listener verification...");

    const testResults = {
      playPause: this.testElementListener("play-pause-btn", "click"),
      progress: this.testElementListener("progress-container", "click"),
      volume: this.testElementListener("volume-slider", "input"),
      mute: this.testElementListener("mute-btn", "click"),
    };

    console.log("📊 Enhanced Event Listener Verification:", testResults);

    const workingListeners = Object.values(testResults).filter(Boolean).length;
    const totalListeners = Object.keys(testResults).length;

    console.log(
      `✅ ${workingListeners}/${totalListeners} core listeners verified (enhanced)`
    );

    return workingListeners >= 2; // At least play and progress should work
  }

  testElementListener(elementId, eventType) {
    const element = document.getElementById(elementId);
    if (!element) {
      console.warn(`⚠️ Element ${elementId} not found for listener test`);
      return false;
    }

    // Check for data attribute we set during listener attachment
    const hasListener = element.hasAttribute("data-listener-attached");

    // Also check for actual event listeners (basic check)
    const hasOnClick = element.onclick !== null;
    const hasAttribute = element.getAttribute(`on${eventType}`) !== null;

    const result = hasListener || hasOnClick || hasAttribute;
    // console.log(`🔍 Listener test for ${elementId}: ${result}`);

    return result;
  }

  hasEventListener(element, eventType) {
    if (!element) return false;

    // Check if element has event listeners (this is a simplified check)
    return (
      element.getAttribute(`on${eventType}`) !== null ||
      element[`on${eventType}`] !== null
    );
  }

  async setupIntegrations() {
    // Setup page-specific integrations
    this.setupPublicationIntegration();
    this.setupAdminIntegration();
    this.setupGridIntegration();
    this.setupUniversalIntegration();
  }

  setupPublicationIntegration() {
    this.integrationHandlers.set("publication", {
      selector: ".play-audio-btn, .document-play-btn",
      handler: (button) => this.handlePublicationButtonClick(button),
    });
  }

  setupAdminIntegration() {
    this.integrationHandlers.set("admin", {
      selector: ".admin-play-btn, .document-play-button",
      handler: (button) => this.handleAdminButtonClick(button),
    });
  }

  setupGridIntegration() {
    this.integrationHandlers.set("grid", {
      selector: ".play-document-btn",
      handler: (button) => this.handleGridButtonClick(button),
    });
  }

  setupUniversalIntegration() {
    // Universal click handler for all audio buttons
    document.addEventListener("click", (e) => {
      const button = e.target.closest(
        ".play-audio-btn, .admin-play-btn, .play-document-btn, .document-play-button"
      );
      if (button && !button.hasAttribute("data-audio-handled")) {
        button.setAttribute("data-audio-handled", "true");
        this.handleUniversalButtonClick(button);
      }
    });
  }

  async setupKeyboardShortcuts() {
    document.addEventListener("keydown", (e) => {
      // Only activate when player is visible and not typing
      const playerVisible =
        this.bottomPlayer && !this.bottomPlayer.classList.contains("hidden");
      const isTyping =
        e.target.tagName === "INPUT" ||
        e.target.tagName === "TEXTAREA" ||
        e.target.isContentEditable;

      if (!playerVisible || isTyping) return;

      switch (e.code) {
        case "Space":
          e.preventDefault();
          this.togglePlayPause();
          break;
        case "ArrowLeft":
          e.preventDefault();
          this.skipBackward(10);
          break;
        case "ArrowRight":
          e.preventDefault();
          this.skipForward(10);
          break;
        case "KeyM":
          e.preventDefault();
          this.toggleMute();
          break;
        case "Escape":
          e.preventDefault();
          this.closePlayer();
          break;
      }
    });
  }

  // Audio Event Handlers
  handleLoadStart() {
    console.log("📥 Audio loading started");
    this.showLoadingState(true);
  }

  handleCanPlay() {
    console.log("✅ Audio can play");
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
    console.log("🏁 Audio playback ended");
    this.isPlaying = false;
    this.updatePlayPauseButton();
    this.announceToScreenReader("Audio selesai diputar");
  }

  handleError(e) {
    console.error("❌ Audio error:", e);
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
    const docData = this.extractDocumentDataFromButton(button, "publication");
    if (docData) {
      this.playDocument(docData);
    }
  }

  handleAdminButtonClick(button) {
    const docData = this.extractDocumentDataFromButton(button, "admin");
    if (docData) {
      this.playDocument(docData);
    }
  }

  handleGridButtonClick(button) {
    const docData = this.extractDocumentDataFromButton(button, "grid");
    if (docData) {
      this.playDocument(docData);
    }
  }

  handleUniversalButtonClick(button) {
    console.log("🎵 Universal button click detected");
    console.log("🔍 Button element:", button);
    console.log("🔍 Button classes:", button.className);
    console.log("🔍 Button dataset:", button.dataset);

    // Determine context based on page URL and button location
    let context = "publication"; // default

    if (button.closest(".admin-panel, .admin-content, .admin-table")) {
      context = "admin";
    } else if (button.closest(".publication-grid, .document-grid, .grid")) {
      context = "grid";
    } else if (window.location.pathname.includes("/admin")) {
      context = "admin";
    } else if (window.location.pathname.includes("/publikasi")) {
      context = "publication";
    } else if (window.location.pathname.includes("/brs")) {
      context = "brs";
    }

    console.log("📍 Detected context:", context);

    let docData = null;

    try {
      // Try context-specific extraction first
      switch (context) {
        case "admin":
          docData = this.extractDocumentDataFromButton(button, "admin");
          break;
        case "grid":
        case "publication":
        case "brs":
          docData = this.extractDocumentDataFromButton(button, "publication");
          break;
        default:
          docData = this.extractDocumentDataFromButton(button, "publication");
      }

      if (docData && docData.id) {
        console.log("✅ Successfully extracted document data:", docData.title);
        this.playDocument(docData);
      } else {
        console.warn("⚠️ Could not extract document data from button");
        console.log("🔍 Attempted extraction result:", docData);

        // Show user-friendly error
        this.showErrorMessage(
          "Tidak dapat memutar audio. Data dokumen tidak ditemukan."
        );

        // Try fallback method
        this.tryFallbackPlayback(button);
      }
    } catch (error) {
      console.error("❌ Error in universal button click handler:", error);
      this.showErrorMessage("Terjadi kesalahan saat mencoba memutar audio.");

      // Try fallback method
      this.tryFallbackPlayback(button);
    }
  }

  // Fallback playback method
  tryFallbackPlayback(button) {
    console.log("🔄 Attempting fallback playback...");

    // Try to find any ID in the button or nearby elements
    const possibleIds = [
      button.dataset.documentId,
      button.dataset.id,
      button.getAttribute("data-document-id"),
      button.getAttribute("data-id"),
      button.closest("[data-document-id]")?.dataset.documentId,
      button.closest("[data-id]")?.dataset.id,
    ].filter(Boolean);

    if (possibleIds.length > 0) {
      const id = possibleIds[0];
      console.log("🆔 Found fallback ID:", id);

      // Create minimal document data
      const fallbackData = {
        id: id,
        title:
          button.getAttribute("aria-label") ||
          button
            .closest(".group")
            ?.querySelector(".font-medium")
            ?.textContent?.trim() ||
          "Audio Document",
        type: "publication",
        indicator: { name: "Unknown" },
      };

      console.log("🎵 Attempting fallback playback with:", fallbackData);
      this.playDocument(fallbackData);
    } else {
      console.error("❌ No fallback options available");
      this.announceToScreenReader("Tidak dapat memutar audio dokumen");
    }
  }

  extractDocumentDataFromButton(button, context) {
    console.log("🔍 Extracting document data from button, context:", context);

    let docData = {};

    // Method 1: From data-document attribute (publications.blade.php menggunakan ini)
    const documentDataAttr =
      button.dataset.document || button.getAttribute("data-document");
    if (documentDataAttr) {
      try {
        console.log("📄 Found data-document attribute");
        const parsedData = JSON.parse(documentDataAttr);

        // Ensure we have required fields
        if (parsedData.id && parsedData.title) {
          console.log(
            "✅ Successfully parsed document data from data-document:",
            parsedData.title
          );

          // Normalize the data structure
          docData = {
            id: parsedData.id,
            title: parsedData.title,
            slug: parsedData.slug,
            year: parsedData.year || new Date().getFullYear(),
            indicator: parsedData.indicator || { name: "Unknown" },
            audio_duration_formatted:
              parsedData.audio_duration_formatted ||
              this.formatDuration(parsedData.audio_duration_seconds) ||
              "00:00",
            description: parsedData.description || parsedData.excerpt || "",
            type: parsedData.type || "publication",
            // Copy all other fields
            ...parsedData,
          };

          return docData;
        }
      } catch (error) {
        console.error("❌ Failed to parse data-document:", error);
      }
    }

    // Method 2: From individual data attributes
    const id =
      button.dataset.documentId ||
      button.dataset.id ||
      button.getAttribute("data-document-id");
    if (id) {
      console.log("📄 Found individual data attributes, ID:", id);

      docData = {
        id: id,
        title:
          button.dataset.title ||
          button.getAttribute("data-title") ||
          button.getAttribute("aria-label") ||
          "Unknown Document",
        slug: button.dataset.slug || button.getAttribute("data-slug"),
        indicator: {
          name:
            button.dataset.indicator ||
            button.getAttribute("data-indicator") ||
            "Unknown",
        },
        year:
          button.dataset.year ||
          button.getAttribute("data-year") ||
          new Date().getFullYear(),
        audio_duration_formatted:
          button.dataset.duration ||
          button.getAttribute("data-duration") ||
          "00:00",
        type:
          button.dataset.type ||
          button.getAttribute("data-type") ||
          "publication",
      };

      console.log("✅ Extracted from individual attributes:", docData.title);
      return docData;
    }

    // Method 3: From parent document card (untuk grid layout)
    const documentCard = button.closest(
      ".document-card, .bg-white, .publication-item, .group"
    );
    if (documentCard) {
      console.log("📄 Searching in parent document card");

      // Look for title in various selectors
      const titleEl =
        documentCard.querySelector(
          ".document-title, .doc-title, h3, h4, .font-medium, .text-lg"
        ) ||
        documentCard.querySelector("[data-title]") ||
        documentCard.querySelector('a[href*="/dokumen/"]');

      // Look for indicator
      const indicatorEl =
        documentCard.querySelector(".indicator, .category, .type, .badge") ||
        documentCard.querySelector("[data-indicator]");

      // Look for year
      const yearEl =
        documentCard.querySelector(".year, [data-year]") ||
        documentCard.querySelector(".text-gray-500, .text-sm");

      // Try to get ID from various sources
      const cardId =
        documentCard.dataset.documentId ||
        documentCard.dataset.id ||
        documentCard.querySelector("[data-document-id]")?.dataset.documentId ||
        documentCard.querySelector("[data-id]")?.dataset.id;

      if (titleEl || cardId) {
        docData = {
          id: cardId || this.extractIdFromElement(titleEl),
          title:
            titleEl?.textContent?.trim() ||
            titleEl?.dataset.title ||
            "Unknown Document",
          indicator: {
            name:
              indicatorEl?.textContent?.trim() ||
              indicatorEl?.dataset.indicator ||
              "Unknown",
          },
          year:
            this.extractYear(yearEl?.textContent) || new Date().getFullYear(),
          type: "publication",
        };

        if (docData.id) {
          console.log("✅ Extracted from document card:", docData.title);
          return docData;
        }
      }
    }

    // Method 4: From URL or href (untuk link-based buttons)
    const link =
      button.closest("a") ||
      button.querySelector("a") ||
      button.parentElement?.querySelector("a");
    if (link && link.href) {
      console.log("📄 Searching from link href:", link.href);

      const urlMatch = link.href.match(/\/dokumen\/(.+?)(?:\/|$|\?|#)/);
      if (urlMatch) {
        const slug = urlMatch[1];

        docData = {
          slug: slug,
          title:
            link.textContent?.trim() ||
            link.getAttribute("title") ||
            "Document",
          id: this.getIdFromSlug(slug), // We'll implement this
          type: "publication",
        };

        console.log("✅ Extracted from URL:", docData.title);
        return docData;
      }
    }

    // Method 5: Try to find document data in nearby elements
    const nearbyElements = [
      button.parentElement,
      button.parentElement?.parentElement,
      button.closest(".grid > div"),
      button.closest("[data-document]"),
    ].filter(Boolean);

    for (const element of nearbyElements) {
      const foundData = this.searchElementForDocumentData(element);
      if (foundData && foundData.id) {
        console.log(
          "✅ Found document data in nearby element:",
          foundData.title
        );
        return foundData;
      }
    }

    console.warn("⚠️ Could not extract document data from any method");
    console.log("🔍 Button details:", {
      className: button.className,
      dataset: button.dataset,
      attributes: Array.from(button.attributes).map(
        (attr) => `${attr.name}="${attr.value}"`
      ),
      innerHTML: button.innerHTML.substring(0, 100),
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

    return `${minutes.toString().padStart(2, "0")}:${remainingSeconds
      .toString()
      .padStart(2, "0")}`;
  }

  searchElementForDocumentData(element) {
    if (!element) return null;

    // Check for data-document attribute
    const documentData =
      element.dataset.document || element.getAttribute("data-document");
    if (documentData) {
      try {
        return JSON.parse(documentData);
      } catch (e) {
        console.warn("Failed to parse document data from element");
      }
    }

    // Check for individual data attributes
    const id = element.dataset.documentId || element.dataset.id;
    const title =
      element.dataset.title ||
      element.querySelector(".font-medium, h3, h4")?.textContent?.trim();

    if (id && title) {
      return {
        id: id,
        title: title,
        type: "publication",
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
    console.log("📞 Making API call to resolve slug to ID:", slug);
    this.resolveDocumentFromSlug(slug).then((doc) => {
      if (doc) {
        // Re-trigger playback with resolved data
        this.playDocument(doc);
      }
    });

    return null;
  }
  // Helper umum: pastikan ada audio
  ensureAudio = (audioElement) => {
    if (!audioElement) {
      speak("Tidak ada audio yang sedang diputar");
      return false;
    }
    return true;
  };

  // Helper umum: bungkus aksi dengan try/catch + pesan error kustom
  withAudioAction = (audioElement, action, errorMessage) => {
    if (!this.ensureAudio(audioElement)) return;

    try {
      action();
    } catch (error) {
      console.error("❌ Error:", error);
      speak(errorMessage);
    }
  };

  // Helper: jalankan callback setelah metadata siap
  runWhenMetadataReady = (audioElement, callback) => {
    if (audioElement.readyState > 0) {
      callback();
    } else {
      // harusnya "loadedmetadata"
      audioElement.addEventListener(
        "loadedmetadata",
        () => {
          callback();
        },
        { once: true }
      );
    }
  };

  // Enhanced slug resolution with API call
  async resolveDocumentFromSlug(slug) {
    try {
      console.log("🔍 Resolving document from slug:", slug);

      // Try the API endpoint
      const response = await fetch(`/api/documents/by-slug/${slug}`);
      if (response.ok) {
        const docData = await response.json();
        console.log("✅ Resolved document from API:", docData.title);
        return docData;
      }

      // Fallback: try to extract from current page context
      const pageContext = this.getDocumentFromPageContext(slug);
      if (pageContext) {
        console.log(
          "✅ Resolved document from page context:",
          pageContext.title
        );
        return pageContext;
      }
    } catch (error) {
      console.error("❌ Failed to resolve document from slug:", error);
    }

    return null;
  }

  getDocumentFromPageContext(slug) {
    // Try to find document data in the current page
    const scripts = document.querySelectorAll("script");

    for (const script of scripts) {
      if (script.textContent.includes(slug)) {
        // Try to extract document data from script content
        const match = script.textContent.match(
          new RegExp(`"slug":\\s*"${slug}"[^}]+}`)
        );
        if (match) {
          try {
            // This is a simplified extraction, you may need to adjust
            const jsonStr = "{" + match[0];
            return JSON.parse(jsonStr);
          } catch (e) {
            console.warn("Failed to parse document from page context");
          }
        }
      }
    }

    return null;
  }

  // Core Playback Methods
  async playDocument(docData) {
    try {
      console.log("🎵 Starting playDocument for:", docData.title);

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

      console.log("✅ Document playback initiated successfully");
    } catch (error) {
      console.error("❌ playDocument error:", error);
      this.showErrorMessage("Gagal memutar dokumen audio");
      this.showLoadingState(false);
    }
  }

  async loadAndPlayAudio(docData) {
    if (!this.currentAudio) {
      throw new Error("Audio element not found");
    }

    // Construct audio URL using AudioController route
    const audioUrl = `/audio/stream/${docData.id}/${this.currentFormat}`;
    console.log("🔗 Loading audio from:", audioUrl);

    // Show loading state
    this.showLoadingState(true);

    // Set audio source
    this.currentAudio.src = audioUrl;
    this.currentAudio.load();

    // Wait for loading to complete
    return new Promise((resolve, reject) => {
      const timeout = setTimeout(() => {
        reject(new Error("Audio loading timeout"));
      }, CONFIG.audio.loadTimeout);

      const onCanPlay = async () => {
        clearTimeout(timeout);
        this.currentAudio.removeEventListener("canplay", onCanPlay);
        this.currentAudio.removeEventListener("error", onError);
        this.showLoadingState(false);

        try {
          const saved = await this.loadProgressFromBackend(docData.id);
          if (
            saved > 2 &&
            this.currentAudio.duration &&
            saved < this.currentAudio.duration - 2
          ) {
            try {
              this.currentAudio.currentTime = saved;
            } catch {}
          }
          await this.currentAudio.play();

          console.log("▶️ Audio auto-played:", docData.title);

          // update tombol UI jadi pause
          const playPauseBtn = document.querySelector("#play-pause-btn i");
          if (playPauseBtn) {
            playPauseBtn.classList.remove("fa-play");
            playPauseBtn.classList.add("fa-pause");
          }

          resolve();
        } catch (err) {
          console.warn("⚠️ Autoplay gagal:", err);
          reject(err);
        }
      };

      const onError = (error) => {
        clearTimeout(timeout);
        this.currentAudio.removeEventListener("canplay", onCanPlay);
        this.currentAudio.removeEventListener("error", onError);
        this.showLoadingState(false);
        reject(new Error("Audio loading failed"));
      };

      this.currentAudio.addEventListener("canplay", onCanPlay, {
        once: true,
      });
      this.currentAudio.addEventListener("error", onError, { once: true });
    });
  }

  async togglePlayPause() {
    if (!this.currentAudio || !this.currentDocument) {
      console.warn("⚠️ No audio loaded for play/pause");
      this.announceToScreenReader("Tidak ada audio yang dimuat");
      return;
    }

    try {
      console.log(
        `🎵 Toggle play/pause - Current state: ${
          this.isPlaying ? "Playing" : "Paused"
        }`
      );

      if (this.isPlaying) {
        // Pause audio
        this.currentAudio.pause();
        this.isPlaying = false;
        console.log("⏸️ Audio paused successfully");
        this.announceToScreenReader("Audio dijeda");
      } else {
        // Play audio with enhanced error handling
        await this.playAudioSafely();
      }

      this.updatePlayPauseButton();
    } catch (error) {
      console.error("❌ Play/pause error:", error);

      // Handle specific AbortError
      if (error.name === "AbortError") {
        console.log("🔄 Handling AbortError - retrying playback");

        // Wait a moment then retry
        setTimeout(async () => {
          try {
            if (!this.isPlaying && this.currentAudio) {
              await this.playAudioSafely();
              this.updatePlayPauseButton();
            }
          } catch (retryError) {
            console.error("❌ Retry failed:", retryError);
            this.showErrorMessage(
              "Gagal memutar audio setelah percobaan ulang"
            );
          }
        }, 100);
      } else {
        this.showErrorMessage("Gagal memutar/menjeda audio");
      }
    }
  }

  async playAudioSafely() {
    if (!this.currentAudio) {
      throw new Error("No audio element available");
    }

    // Check if audio is ready
    if (this.currentAudio.readyState < 2) {
      console.log("⏳ Waiting for audio to be ready...");
      await this.waitForAudioReady();
    }

    // Check if source is loaded
    if (
      !this.currentAudio.src ||
      this.currentAudio.src === window.location.href
    ) {
      console.log("🔄 Reloading audio source...");
      await this.loadAndPlayAudio(this.currentDocument);
      return;
    }

    // Play with promise handling
    const playPromise = this.currentAudio.play();

    if (playPromise !== undefined) {
      await playPromise;
      this.isPlaying = true;
      console.log("▶️ Audio playing successfully");
      this.announceToScreenReader("Audio diputar");
    }
  }

  async waitForAudioReady() {
    return new Promise((resolve, reject) => {
      const timeout = setTimeout(() => {
        reject(new Error("Audio ready timeout"));
      }, 5000);

      if (this.currentAudio.readyState >= 2) {
        clearTimeout(timeout);
        resolve();
        return;
      }

      const onCanPlay = () => {
        clearTimeout(timeout);
        this.currentAudio.removeEventListener("canplay", onCanPlay);
        this.currentAudio.removeEventListener("error", onError);
        resolve();
      };

      const onError = (error) => {
        clearTimeout(timeout);
        this.currentAudio.removeEventListener("canplay", onCanPlay);
        this.currentAudio.removeEventListener("error", onError);
        reject(error);
      };

      this.currentAudio.addEventListener("canplay", onCanPlay, {
        once: true,
      });
      this.currentAudio.addEventListener("error", onError, { once: true });
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
      console.log("🔗 New format URL:", audioUrl);

      // Set new source
      this.currentAudio.src = audioUrl;
      this.currentAudio.load();

      // Wait for new format to load with better timeout handling
      await new Promise((resolve, reject) => {
        const timeout = setTimeout(() => {
          reject(new Error("Format switch timeout after 30 seconds"));
        }, 30000); // Increased timeout

        const onCanPlay = () => {
          clearTimeout(timeout);
          this.currentAudio.removeEventListener("canplay", onCanPlay);
          this.currentAudio.removeEventListener("error", onError);

          // Restore position and playing state
          if (currentTime > 0 && this.currentAudio.duration) {
            this.currentAudio.currentTime = Math.min(
              currentTime,
              this.currentAudio.duration
            );
          }

          if (wasPlaying) {
            this.currentAudio.play().catch(console.error);
          }

          resolve();
        };

        const onError = (error) => {
          clearTimeout(timeout);
          this.currentAudio.removeEventListener("canplay", onCanPlay);
          this.currentAudio.removeEventListener("error", onError);
          reject(
            new Error(
              `Failed to load ${format} format: ${
                error.message || "Unknown error"
              }`
            )
          );
        };

        this.currentAudio.addEventListener("canplay", onCanPlay, {
          once: true,
        });
        this.currentAudio.addEventListener("error", onError, { once: true });
      });

      this.showLoadingState(false);
      this.updateSidebarFormat();
      this.announceToScreenReader(
        `Format audio diubah ke ${format.toUpperCase()}`
      );

      console.log(`✅ Successfully switched to ${format} format`);
    } catch (error) {
      console.error("❌ Error switching format:", error);
      this.showLoadingState(false);

      // Revert to previous format
      this.currentFormat = format === "mp3" ? "flac" : "mp3";
      this.updateFormatButtons();

      this.showErrorMessage(
        `Gagal mengganti ke format ${format.toUpperCase()}. ${error.message}`
      );
    }
  }

  stopAllAudio() {
    // Stop main audio
    if (this.currentAudio && !this.currentAudio.paused) {
      this.currentAudio.pause();
      this.currentAudio.currentTime = 0;
    }

    // Stop all inline audio players
    document.querySelectorAll("audio").forEach((audio) => {
      if (!audio.paused) {
        audio.pause();
        audio.currentTime = 0;
      }
    });

    this.isPlaying = false;
    console.log("🛑 All audio stopped");
  }

  closePlayer() {
    this.stopAllAudio();
    if (this.bottomPlayer) {
      this.bottomPlayer.classList.add("hidden");
      this.bottomPlayer.classList.remove("translate-y-0");
      this.bottomPlayer.classList.add("translate-y-full");
    }
    this.currentDocument = null;
    this.announceToScreenReader("Pemutar audio ditutup");
  }

  // Additional Controls
  // skipForward(seconds = 10) {
  //   if (this.currentAudio) {
  //     console.log(this.currentAudio);

  //     this.currentAudio.currentTime = Math.min(
  //       this.currentAudio.currentTime + seconds,
  //       this.currentAudio.duration || 0
  //     );
  //     this.announceToScreenReader(`Maju ${seconds} detik`);
  //   }
  // }
  // ⏩ Maju
  skipForward(step = 10) {
    console.log("DEBUG seek:", {
      currentTime: this.currentAudio.currentTime,
      duration: this.currentAudio.duration,
      isDurationNaN: Number.isNaN(this.currentAudio.duration),
      readyState: this.currentAudio.readyState,
    });

    this.withAudioAction(
      this.currentAudio,
      () => {
        this.runWhenMetadataReady(this.currentAudio, () => {
          const newTime = Math.min(
            this.currentAudio.currentTime + step,
            this.currentAudio.duration || this.currentAudio.currentTime + step // fallback kalau duration belum ada
          );
          this.currentAudio.currentTime = newTime;
          this.announceToScreenReader(`Maju ${step} detik`);
          console.log(`⏩ Audio seeked forward ${step}s, to: ${newTime}`);
        });
      },
      "Gagal memajukan audio"
    );
  }

  // ⏪ Mundur
  skipBackward(step = 10) {
    console.log("DEBUG seek:", {
      currentTime: this.currentAudio.currentTime,
      duration: this.currentAudio.duration,
      isDurationNaN: Number.isNaN(this.currentAudio.duration),
      readyState: this.currentAudio.readyState,
    });

    this.withAudioAction(
      this.currentAudio,
      () => {
        // Mundur sebenarnya tidak perlu tahu duration, jadi gak wajib nunggu metadata
        const newTime = Math.max(this.currentAudio.currentTime - step, 0);
        this.currentAudio.currentTime = newTime;
        this.announceToScreenReader(`Mundur ${step} detik`);
        console.log(`⏪ Audio seeked backward ${step}s, to: ${newTime}`);
      },
      "Gagal memundurkan audio"
    );
  }

  // skipBackward(seconds = 10) {
  //   if (this.currentAudio) {
  //     this.currentAudio.currentTime = Math.max(
  //       this.currentAudio.currentTime - seconds,
  //       0
  //     );
  //     this.announceToScreenReader(`Mundur ${seconds} detik`);
  //   }
  // }

  toggleMute() {
    if (this.currentAudio) {
      this.currentAudio.muted = !this.currentAudio.muted;
      this.announceToScreenReader(
        this.currentAudio.muted ? "Audio dibisukan" : "Audio tidak dibisukan"
      );
    }
  }

  handleProgressClick(e) {
    if (!this.currentAudio || !this.currentAudio.duration) {
      console.warn("Cannot seek: audio not ready");
      return;
    }

    // Prevent rapid clicking
    const now = Date.now();
    if (now - this.lastSeekTime < this.seekCooldown) {
      console.log("Seek cooldown active, ignoring click");
      return;
    }

    const rect = e.currentTarget.getBoundingClientRect();
    const clickX = e.clientX - rect.left;
    const progress = Math.max(0, Math.min(1, clickX / rect.width));
    const seekTime = progress * this.currentAudio.duration;

    console.log(
      `🎯 Seeking to ${this.formatTime(seekTime)} (${Math.round(
        progress * 100
      )}%)`
    );

    this.performSeek(seekTime);
    this.lastSeekTime = now;
  }

  async performSeek(targetTime) {
    if (this.isSeeking) {
      console.log("Already seeking, queuing new seek time");
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
      console.error("Seek failed:", error);
      this.showErrorMessage("Gagal melompat ke posisi tersebut");

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
        reject(new Error("Seek timeout - audio may not be fully loaded"));
      }, 5000);

      // Audio event handlers for seek completion
      const onSeeked = () => {
        console.log("✅ Seek completed successfully");
        cleanup();
        resolve();
      };

      const onError = (error) => {
        console.error("❌ Seek error:", error);
        cleanup();
        reject(error);
      };

      const onStalled = () => {
        console.warn("⚠️ Audio stalled during seek");
        // Don't reject immediately, give it more time
      };

      const cleanup = () => {
        clearTimeout(seekTimeout);
        this.currentAudio.removeEventListener("seeked", onSeeked);
        this.currentAudio.removeEventListener("error", onError);
        this.currentAudio.removeEventListener("stalled", onStalled);
      };

      // Attach event listeners
      this.currentAudio.addEventListener("seeked", onSeeked, { once: true });
      this.currentAudio.addEventListener("error", onError, { once: true });
      this.currentAudio.addEventListener("stalled", onStalled, {
        once: true,
      });

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
    const progressBars = ["progress-bar", "progress-bar-mobile"];

    progressBars.forEach((id) => {
      const bar = document.getElementById(id);
      if (bar) {
        if (show) {
          bar.classList.add("seeking");
          bar.style.transition = "none"; // Disable transition during seek
        } else {
          bar.classList.remove("seeking");
          bar.style.transition = ""; // Restore transition
        }
      }
    });

    // Show seeking indicator
    const seekingIndicators = document.querySelectorAll(".seeking-indicator");
    seekingIndicators.forEach((indicator) => {
      if (show) {
        indicator.classList.remove("hidden");
        indicator.classList.add("animate-spin");
      } else {
        indicator.classList.add("hidden");
        indicator.classList.remove("animate-spin");
      }
    });

    // Update progress container with seeking state
    const progressContainers = [
      "progress-container",
      "progress-container-mobile",
    ];
    progressContainers.forEach((id) => {
      const container = document.getElementById(id);
      if (container) {
        if (show) {
          container.classList.add("seeking");
          container.style.cursor = "wait";
        } else {
          container.classList.remove("seeking");
          container.style.cursor = "pointer";
        }
      }
    });

    console.log(show ? "⏳ Showing seeking state" : "✅ Hiding seeking state");
  }

  // Optimistic progress bar update for immediate feedback
  updateProgressBarOptimistic(seekTime) {
    if (!this.currentAudio || !this.currentAudio.duration) return;

    const progress = (seekTime / this.currentAudio.duration) * 100;

    // Update both desktop and mobile progress bars
    const progressBars = ["progress-bar", "progress-bar-mobile"];
    progressBars.forEach((id) => {
      const bar = document.getElementById(id);
      if (bar) {
        bar.style.width = `${Math.max(0, Math.min(100, progress))}%`;
        bar.classList.add("seeking");
      }
    });

    // Update time display optimistically
    const timeElements = [
      "current-time-main",
      "current-time-desktop",
      "current-time-mobile",
    ];
    timeElements.forEach((id) => {
      this.updateElementSafely(id, this.formatTime(seekTime));
    });

    console.log(
      `🎯 Optimistic update: ${this.formatTime(seekTime)} (${Math.round(
        progress
      )}%)`
    );
  }

  // Enhanced progress bar with drag support
  setupEnhancedProgressBar() {
    const progressContainers = [
      "progress-container",
      "progress-container-mobile",
    ];

    progressContainers.forEach((containerId) => {
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
    container.addEventListener("mousedown", (e) => {
      if (!this.currentAudio || !this.currentAudio.duration) return;

      isDragging = true;
      dragStartX = e.clientX;
      dragStartTime = this.currentAudio.currentTime;

      container.classList.add("dragging");
      document.body.style.userSelect = "none";

      e.preventDefault();
    });

    document.addEventListener("mousemove", (e) => {
      if (!isDragging || !this.currentAudio || !this.currentAudio.duration)
        return;

      const rect = container.getBoundingClientRect();
      const progress = Math.max(
        0,
        Math.min(1, (e.clientX - rect.left) / rect.width)
      );
      const seekTime = progress * this.currentAudio.duration;

      // Update visual progress immediately
      this.updateProgressBarOptimistic(seekTime);

      e.preventDefault();
    });

    document.addEventListener("mouseup", (e) => {
      if (!isDragging) return;

      isDragging = false;
      container.classList.remove("dragging");
      document.body.style.userSelect = "";

      if (this.currentAudio && this.currentAudio.duration) {
        const rect = container.getBoundingClientRect();
        const progress = Math.max(
          0,
          Math.min(1, (e.clientX - rect.left) / rect.width)
        );
        const seekTime = progress * this.currentAudio.duration;

        this.performSeek(seekTime);
      }
    });

    // Touch events for mobile
    container.addEventListener("touchstart", (e) => {
      if (!this.currentAudio || !this.currentAudio.duration) return;

      const touch = e.touches[0];
      isDragging = true;
      dragStartX = touch.clientX;

      container.classList.add("dragging");
      e.preventDefault();
    });

    container.addEventListener("touchmove", (e) => {
      if (!isDragging || !this.currentAudio || !this.currentAudio.duration)
        return;

      const touch = e.touches[0];
      const rect = container.getBoundingClientRect();
      const progress = Math.max(
        0,
        Math.min(1, (touch.clientX - rect.left) / rect.width)
      );
      const seekTime = progress * this.currentAudio.duration;

      this.updateProgressBarOptimistic(seekTime);
      e.preventDefault();
    });

    container.addEventListener("touchend", (e) => {
      if (!isDragging) return;

      isDragging = false;
      container.classList.remove("dragging");

      if (
        this.currentAudio &&
        this.currentAudio.duration &&
        e.changedTouches.length > 0
      ) {
        const touch = e.changedTouches[0];
        const rect = container.getBoundingClientRect();
        const progress = Math.max(
          0,
          Math.min(1, (touch.clientX - rect.left) / rect.width)
        );
        const seekTime = progress * this.currentAudio.duration;

        this.performSeek(seekTime);
      }
    });

    // Click for non-drag users
    container.addEventListener("click", (e) => {
      if (isDragging) return; // Ignore clicks during drag

      this.handleProgressClick(e);
    });
  }

  // Preload optimization
  optimizeAudioPreloading() {
    if (!this.currentAudio) return;

    // Set preload to metadata initially
    this.currentAudio.preload = "metadata";

    // Upgrade to auto preloading after user interaction
    const upgradePreload = () => {
      if (this.currentAudio) {
        this.currentAudio.preload = "auto";
        console.log("📥 Upgraded audio preloading to auto");
      }
    };

    // Upgrade preload on first user interaction
    ["click", "touchstart"].forEach((event) => {
      document.addEventListener(event, upgradePreload, { once: true });
    });

    // Enable range requests for better seeking
    if (this.currentAudio.crossOrigin !== "anonymous") {
      this.currentAudio.crossOrigin = "anonymous";
    }
  }

  // Audio buffer monitoring
  monitorAudioBuffer() {
    if (!this.currentAudio) return;

    const checkBuffer = () => {
      if (this.currentAudio.buffered.length > 0) {
        const bufferedEnd = this.currentAudio.buffered.end(
          this.currentAudio.buffered.length - 1
        );
        const duration = this.currentAudio.duration || 0;
        const bufferPercent = duration > 0 ? (bufferedEnd / duration) * 100 : 0;

        console.log(`📊 Audio buffered: ${bufferPercent.toFixed(1)}%`);

        // Update buffer indicator if exists
        const bufferIndicator = document.getElementById("buffer-indicator");
        if (bufferIndicator) {
          bufferIndicator.style.width = `${bufferPercent}%`;
        }
      }
    };

    this.currentAudio.addEventListener("progress", checkBuffer);

    // Initial check
    setTimeout(checkBuffer, 1000);
  }

  showSidebar() {
    const sidebar = document.getElementById("right-sidebar");
    const bottomPlayer = document.getElementById("bottom-audio-player");

    if (sidebar) {
      // Adjust bottom player margin to accommodate sidebar
      if (bottomPlayer && window.innerWidth >= 768) {
        bottomPlayer.style.right = "380px";
        bottomPlayer.style.width = "calc(100% - 380px)";
      }

      sidebar.classList.remove("translate-x-full");
      sidebar.classList.add("translate-x-0");

      // Focus management
      const firstFocusable = sidebar.querySelector("button");
      if (firstFocusable) {
        setTimeout(() => firstFocusable.focus(), 300);
      }

      this.announceToScreenReader("Detail dokumen dibuka");
      console.log("✅ Sidebar shown with proper spacing");
    }
  }

  hideSidebar() {
    const sidebar = document.getElementById("right-sidebar");
    const bottomPlayer = document.getElementById("bottom-audio-player");

    if (sidebar) {
      // Reset bottom player to full width
      if (bottomPlayer) {
        bottomPlayer.style.right = "0";
        bottomPlayer.style.width = "100%";
      }

      sidebar.classList.add("translate-x-full");
      sidebar.classList.remove("translate-x-0");

      this.announceToScreenReader("Detail dokumen ditutup");
      console.log("✅ Sidebar hidden and player restored to full width");
    }
  }

  shareCurrentDocument() {
    if (!this.currentDocument) return;

    const shareData = {
      title: this.currentDocument.title,
      text: `Dengarkan: ${this.currentDocument.title}`,
      url:
        window.location.origin + `/documents/uuid/${this.currentDocument.uuid}`,
    };

    if (navigator.share) {
      navigator.share(shareData).catch(console.error);
    } else {
      // Fallback: copy to clipboard
      navigator.clipboard
        .writeText(shareData.url)
        .then(() => {
          alert("berhasil menyalin link dokumen");
        })
        .catch(() => {
          this.showToast("Gagal menyalin link", "error");
        });
    }
  }

  updateSidebarFormat() {
    const formatElement = document.getElementById("sidebar-audio-format");
    if (formatElement) {
      formatElement.textContent = this.currentFormat.toUpperCase();
    }
  }

  cyclePlaybackSpeed() {
    const speeds = [0.5, 0.75, 1.0, 1.25, 1.5, 2.0];
    const currentIndex = speeds.indexOf(this.playbackRate);
    const nextIndex = (currentIndex + 1) % speeds.length;

    this.setPlaybackRate(speeds[nextIndex]);

    const speedDisplay = document.getElementById("speed-display");
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

    const downloadUrl = `/storage/documents/${this.currentDocument.id}/audio.mp3`;
    const link = document.createElement("a");
    link.href = downloadUrl;
    link.download = `${this.currentDocument.title}.${this.currentFormat}`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    this.announceToScreenReader(
      `Mengunduh audio dalam format ${this.currentFormat.toUpperCase()}`
    );
  }

  showLoadingState(show) {
    const loadingIndicator = document.getElementById("loading-indicator");
    if (loadingIndicator) {
      if (show) {
        loadingIndicator.classList.remove("hidden", "opacity-0");
        loadingIndicator.classList.add("opacity-100");
      } else {
        loadingIndicator.classList.add("opacity-0");
        setTimeout(() => {
          loadingIndicator.classList.add("hidden");
        }, 300);
      }
    }
  }

  // UI Update Methods
  showAudioPlayer() {
    if (this.bottomPlayer) {
      this.bottomPlayer.classList.remove("hidden", "translate-y-full");
      this.bottomPlayer.classList.add("translate-y-0");
    }
  }

  updatePlayerUI(docData) {
    console.log("🎨 Updating responsive player UI for:", docData?.title);

    if (!docData) {
      console.warn("⚠️ No document data provided for UI update");
      return;
    }

    // Update desktop elements
    this.updateElementSafely("current-doc-title", docData.title);
    this.updateElementSafely(
      "current-doc-indicator",
      docData.indicator?.name || "Unknown"
    );
    this.updateElementSafely(
      "total-time-main",
      docData.audio_duration_formatted || "00:00"
    );
    this.updateElementSafely(
      "total-time-desktop",
      docData.audio_duration_formatted || "00:00"
    );

    // Update mobile elements
    this.updateElementSafely("current-doc-title-mobile", docData.title);
    this.updateElementSafely(
      "current-doc-indicator-mobile",
      docData.indicator?.name || "Unknown"
    );
    this.updateElementSafely(
      "total-time-mobile",
      docData.audio_duration_formatted || "00:00"
    );

    // Update cover images
    if (docData.id) {
      const coverUrl = `/storage/documents/covers/${docData.year}/${docData.id}-cover.jpg`;

      const coverDesktop = document.getElementById("current-doc-cover");
      const coverMobile = document.getElementById("current-doc-cover-mobile");

      if (coverDesktop)
        this.updateImageSafely(
          coverDesktop,
          coverUrl,
          `Cover ${docData.title}`
        );
      if (coverMobile)
        this.updateImageSafely(coverMobile, coverUrl, `Cover ${docData.title}`);
    }

    // Update sidebar
    this.updateSidebarInfo(docData);

    console.log("✅ Responsive player UI updated successfully");
  }

  updateSidebarInfo(docData) {
    console.log("🎨 Updating sidebar info for:", docData?.title);

    if (!docData) {
      console.warn("⚠️ No document data provided for sidebar update");
      return;
    }

    const sidebarElements = {
      "sidebar-doc-title": docData.title || "Unknown Document",
      "sidebar-doc-indicator": docData.indicator?.name || "Unknown Category",
      "sidebar-doc-description":
        docData.description ||
        docData.excerpt ||
        "Tidak ada deskripsi tersedia.",
      "sidebar-doc-date": docData.year
        ? `Tahun ${docData.year}`
        : "Tahun tidak diketahui",
      "sidebar-audio-duration":
        docData.audio_duration_formatted || docData.duration || "00:00",
      "sidebar-audio-format": this.currentFormat.toUpperCase(),
    };

    Object.entries(sidebarElements).forEach(([id, text]) => {
      this.updateElementSafely(id, text);
    });

    // Update sidebar cover image
    const sidebarCover = document.getElementById("sidebar-doc-cover");
    if (sidebarCover && docData.id) {
      const coverUrl = `/storage/documents/covers/${docData.year}/${docData.id}-cover.jpg`;
      this.updateImageSafely(sidebarCover, coverUrl, `Cover ${docData.title}`);
    }

    console.log("✅ Sidebar info updated successfully");
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
      const loadingEl = document.getElementById("cover-loading");
      if (loadingEl) loadingEl.classList.remove("hidden");

      imgElement.onload = () => {
        if (loadingEl) loadingEl.classList.add("hidden");
        console.log("✅ Cover image loaded");
      };

      imgElement.onerror = () => {
        if (loadingEl) loadingEl.classList.add("hidden");
        imgElement.src = "/images/default-document-cover.jpg";
        console.warn("⚠️ Cover image failed to load, using default");
      };

      imgElement.src = src;
      imgElement.alt = alt;
    }
  }

  updatePlayPauseButton() {
    const buttons = [
      document.getElementById("play-pause-btn"),
      document.getElementById("play-pause-btn-mobile"),
    ];

    buttons.forEach((button) => {
      if (button) {
        const icon = button.querySelector("i");
        if (icon) {
          if (this.isPlaying) {
            icon.classList.remove("fa-play");
            icon.classList.add("fa-pause");
          } else {
            icon.classList.remove("fa-pause");
            icon.classList.add("fa-play");
          }
        }
      }
    });
  }

  updateFormatButtons() {
    const mp3Btn = document.getElementById("format-mp3");
    const flacBtn = document.getElementById("format-flac");

    if (mp3Btn && flacBtn) {
      if (this.currentFormat === "mp3") {
        mp3Btn.classList.add("bg-gray-700");
        mp3Btn.classList.remove("text-gray-400");
        flacBtn.classList.remove("bg-gray-700");
        flacBtn.classList.add("text-gray-400");
      } else {
        flacBtn.classList.add("bg-gray-700");
        flacBtn.classList.remove("text-gray-400");
        mp3Btn.classList.remove("bg-gray-700");
        mp3Btn.classList.add("text-gray-400");
      }
    }
  }

  updateProgressBar() {
    if (!this.currentAudio) return;

    if (this.currentAudio.duration && this.currentAudio.duration > 0) {
      const progress =
        (this.currentAudio.currentTime / this.currentAudio.duration) * 100;
      const progressValue = `${Math.max(0, Math.min(100, progress))}%`;

      // Update desktop progress bar
      const progressBarDesktop = document.getElementById("progress-bar");
      if (progressBarDesktop) {
        progressBarDesktop.style.width = progressValue;
      }

      // Update mobile progress bar
      const progressBarMobile = document.getElementById("progress-bar-mobile");
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
    const totalTime = this.currentAudio.duration
      ? this.formatTime(this.currentAudio.duration)
      : "00:00";

    // Update all time displays
    const timeElements = [
      "current-time-main",
      "current-time-desktop",
      "current-time-mobile",
    ];

    const totalTimeElements = [
      "total-time-main",
      "total-time-desktop",
      "total-time-mobile",
    ];

    timeElements.forEach((id) => {
      this.updateElementSafely(id, currentTime);
    });

    totalTimeElements.forEach((id) => {
      this.updateElementSafely(id, totalTime);
    });
  }

  updateDurationDisplay() {
    if (!this.currentAudio || !this.totalTimeEl) return;

    if (this.currentAudio.duration) {
      this.totalTimeEl.textContent = this.formatTime(
        this.currentAudio.duration
      );
    }
  }

  updateVolumeDisplay() {
    // Update volume display if volume control exists
    const volumeSlider = document.getElementById("volume-slider");
    if (volumeSlider && this.currentAudio) {
      volumeSlider.value = this.currentAudio.volume * 100;
    }
  }

  showLoadingState(show) {
    const loadingIndicator = document.getElementById("loading-indicator");
    if (loadingIndicator) {
      if (show) {
        loadingIndicator.classList.remove("hidden");
      } else {
        loadingIndicator.classList.add("hidden");
      }
    }
  }

  // Error Handling
  handleAudioError(error) {
    console.error("💥 Audio error:", error);

    if (this.retryCount < CONFIG.audio.maxRetries) {
      this.retryCount++;
      console.log(
        `🔄 Retrying audio playback (${this.retryCount}/${CONFIG.audio.maxRetries})`
      );

      setTimeout(() => {
        if (this.currentDocument) {
          this.loadAndPlayAudio(this.currentDocument).catch((retryError) => {
            console.error("❌ Retry failed:", retryError);
            this.handlePlaybackError(retryError);
          });
        }
      }, 2000);
    } else {
      this.handlePlaybackError(error);
    }
  }

  handlePlaybackError(error) {
    console.error("💥 Playback failed permanently:", error);
    this.showLoadingState(false);
    this.retryCount = 0;
    this.showErrorMessage("Gagal memutar audio. Silakan coba lagi.");
  }

  showErrorMessage(message) {
    console.error("🔔 Error:", message);
    this.announceToScreenReader(message);

    // Show toast notification if available
    if (window.showToast) {
      window.showToast(message, "error");
    } else {
      // Fallback alert
      setTimeout(() => alert(message), 100);
    }
  }

  // Progress Management
  saveProgressToBackend(force = false) {
    if (!this.currentDocument || !this.currentAudio) return;

    const nowMs = Date.now();
    const cur = Math.floor(this.currentAudio.currentTime || 0);
    const dur = Math.floor(this.currentAudio.duration || 0);

    const enoughInterval =
      nowMs - this.lastProgressSentAt >= this.progressMinInterval;
    const enoughDelta =
      Math.abs(cur - this.lastProgressSeconds) >= this.progressMinDelta;

    if (!force && !(enoughInterval && enoughDelta)) return;

    try {
      localStorage.setItem(
        `audio_progress_${this.currentDocument.id}`,
        JSON.stringify({ current_time: cur, duration: dur })
      );
      this.lastProgressSentAt = nowMs;
      this.lastProgressSeconds = cur;
    } catch (e) {
      // storage full / disabled – diamkan saja
      console.warn("Local progress save failed:", e);
    }
  }
  async loadProgressFromBackend(documentId) {
    try {
      const stored = localStorage.getItem(`audio_progress_${documentId}`);
      if (stored) {
        const data = JSON.parse(stored);
        return Number.isFinite(data.current_time) ? data.current_time : 0;
      }
    } catch (e) {
      console.warn("Local progress load failed:", e);
    }
    return 0;
  }

  // Utility Methods
  formatTime(seconds) {
    if (isNaN(seconds) || seconds < 0) return "00:00";

    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = Math.floor(seconds % 60);

    return `${minutes.toString().padStart(2, "0")}:${remainingSeconds
      .toString()
      .padStart(2, "0")}`;
  }

  announceToScreenReader(message) {
    if (window.unifiedApp?.modules.accessibility) {
      window.unifiedApp.modules.accessibility.announce(message);
    } else {
      // Fallback screen reader announcement
      const announcement = document.createElement("div");
      announcement.setAttribute("aria-live", "polite");
      announcement.setAttribute("aria-atomic", "true");
      announcement.className = "sr-only";
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
    console.log("📱 Page hidden - continuing audio playback");
  }

  handlePageVisible() {
    // Refresh UI when page becomes visible
    console.log("📱 Page visible - refreshing UI");
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
      currentDocument: this.currentDocument,
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
    console.log("🔍 AUDIO PLAYER DIAGNOSIS:");
    console.log("========================");

    // Check DOM elements
    const playerCount = document.querySelectorAll(
      "#bottom-audio-player"
    ).length;
    const sidebarCount = document.querySelectorAll("#right-sidebar").length;

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
      totalTimeEl: !!this.totalTimeEl,
    };

    console.log("🎛️ UI Elements:", uiElements);

    // Check current state
    console.log(`🎵 Current State:
            - Is Playing: ${this.isPlaying}
            - Current Document: ${this.currentDocument?.title || "None"}
            - Current Format: ${this.currentFormat}
            - Audio Source: ${this.currentAudio?.src || "None"}`);

    // Check event listeners
    const listenersWorking = this.verifyEventListeners();
    console.log(`🎛️ Event Listeners Working: ${listenersWorking}`);

    return {
      domElementsOk: playerCount === 1 && sidebarCount === 1,
      uiElementsOk: Object.values(uiElements).every(Boolean),
      listenersOk: listenersWorking,
      audioOk: !!this.currentAudio,
      playerOk: !!this.bottomPlayer,
    };
  }
}
