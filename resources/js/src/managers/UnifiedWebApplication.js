// Auto-extracted from app1.js
export class UnifiedWebApplication {
  constructor({ config, managers, utils } = {}) {
    this.config = config || {};
    this.managers = managers || {};
    this.utils = utils || {};
    this.modules = {};
    this.initialized = false;
    this.debug = window.location.hostname === "localhost";

    this.init();
  }
  async init() {
    try {
      console.log("🎯 Initializing Unified Web Application");

      // Initialize all modules
      await this.initializeModules();

      // Setup global event handlers
      this.setupGlobalEvents();

      // Mark as initialized
      this.initialized = true;

      console.log("✅ Unified Web Application initialized successfully");

      // Perform post-initialization tasks
      this.postInit();
    } catch (error) {
      console.error("❌ Failed to initialize web application:", error);
      this.handleInitializationError(error);
    }
  }

  async initializeModules() {
    // Initialize in order of dependency
    // this.modules.stickyNav = new StickyNavManager();
    this.modules.soundEffects = this.managers.soundFx;
    const path = window.location.pathname;
    const isAdminPage = path.startsWith("/admin");
    const isAuthPage = path.startsWith("/login") || path.startsWith("/logout");

    if (!isAdminPage && !isAuthPage) {
      this.modules.hoverText = this.managers.hoverText;
    }
    this.modules.audioPlayer = this.managers.audio;
    this.modules.accessibility = this.managers.a11y;
    // this.modules.healthMonitor = new HealthMonitorManager();

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
    window.addEventListener("error", (e) => this.handleGlobalError(e));

    // Page visibility changes
    document.addEventListener("visibilitychange", () =>
      this.handleVisibilityChange()
    );

    // Before unload cleanup
    window.addEventListener("beforeunload", () => this.cleanup());

    // Resize handling
    window.addEventListener("resize", () => this.handleResize());
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
    console.error("🚨 Global error detected:", error);
    this.modules.accessibility?.announceError("Terjadi kesalahan sistem");
  }

  handleInitializationError(error) {
    // Fallback initialization
    console.warn("🔄 Attempting fallback initialization...");
    this.initializeFallbackMode();
  }

  initializeFallbackMode() {
    // Basic functionality without advanced features
    console.log("🆘 Running in fallback mode");

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
    const loadingElements = document.querySelectorAll(
      ".loading-screen, .loading-overlay"
    );
    loadingElements.forEach((el) => {
      el.style.opacity = "0";
      setTimeout(() => (el.style.display = "none"), 300);
    });
  }

  initializePageSpecificFeatures() {
    const path = window.location.pathname;

    if (path.includes("/admin")) {
      this.initializeAdminFeatures();
    } else if (path.includes("/publikasi") || path.includes("/brs")) {
      this.initializePublicationFeatures();
    } else if (path === "/") {
      this.initializeHomeFeatures();
    }
  }

  initializeAdminFeatures() {
    console.log("🔧 Initializing admin-specific features");
    // Admin-specific audio button handling
    this.modules.audioPlayer?.setupAdminIntegration();
  }

  initializePublicationFeatures() {
    console.log("📚 Initializing publication-specific features");
    // Publication-specific audio button handling
    this.modules.audioPlayer?.setupPublicationIntegration();
  }

  initializeHomeFeatures() {
    console.log("🏠 Initializing home page features");
    // Home page specific features
  }

  setupBasicAudioPlayback() {
    document.addEventListener("click", (e) => {
      const playBtn = e.target.closest(
        ".play-audio-btn, .admin-play-btn, .play-document-btn"
      );
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
    document.querySelectorAll("[data-hover-text]").forEach((el) => {
      el.addEventListener("mouseenter", (e) => {
        const text = e.target.dataset.hoverText;
        if (text) console.log("Hover:", text);
      });
    });
  }
}
