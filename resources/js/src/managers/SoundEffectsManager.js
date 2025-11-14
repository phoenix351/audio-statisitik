// Auto-extracted from app1.js
export class SoundEffectsManager {
    constructor() {
      this.sounds = new Map();
      this.enabled = true;
      this.volume = 0.3;
      this.soundsLoaded = false; // Add loading state
    }

    async init() {
      console.log("🔊 Initializing Sound Effects Manager");

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
        click: "/sounds/click.mp3",
        hover: "/sounds/hover.mp3",
        success: "/sounds/success.mp3",
        error: "/sounds/error.mp3",
        notification: "/sounds/notification.mp3",
      };

      const loadPromises = [];

      for (const [name, url] of Object.entries(soundEffects)) {
        loadPromises.push(this.loadSingleSound(name, url));
      }

      await Promise.allSettled(loadPromises);
      this.soundsLoaded = true;
      console.log("🔊 Sound effects loaded:", this.sounds.size, "sounds");
    }

    async loadSingleSound(name, url) {
      try {
        // Check if file exists first
        const response = await fetch(url, { method: "HEAD" });
        if (!response.ok) {
          console.warn(`⚠️ Sound file not found: ${url}`);
          return;
        }

        const audio = new Audio(url);
        audio.volume = this.volume;
        audio.preload = "auto";

        // Wait for audio to load
        await new Promise((resolve, reject) => {
          audio.addEventListener("canplaythrough", resolve, { once: true });
          audio.addEventListener("error", reject, { once: true });

          // Timeout after 5 seconds
          setTimeout(() => reject(new Error("Load timeout")), 5000);
        });

        this.sounds.set(name, audio);
        // console.log(`✅ Loaded sound: ${name}`);
      } catch (error) {
        console.warn(`⚠️ Failed to load sound effect: ${name}`, error);
      }
    }

    setupSoundTriggers() {
      console.log("🔊 Setting up safe sound triggers...");

      // Safe click handler with multiple fallbacks
      const safeClickHandler = (e) => {
        try {
          // Validate event and target
          if (!e || !e.target) return;

          // Get the actual element (handle text nodes)
          let targetElement = e.target;
          if (targetElement.nodeType === 3) {
            // Text node
            targetElement = targetElement.parentElement;
          }

          if (!targetElement || targetElement.nodeType !== 1) return;

          // Method 1: Try closest() if available
          if (typeof targetElement.closest === "function") {
            try {
              const clickable = targetElement.closest(
                "button, .btn, a, .clickable"
              );
              if (clickable) {
                this.playSound("click");
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
            if (
              tagName === "button" ||
              tagName === "a" ||
              classList.contains("btn") ||
              classList.contains("clickable")
            ) {
              this.playSound("click");
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
            if (typeof targetElement.closest === "function") {
              try {
                const hoverElement = targetElement.closest(".hover-sound");
                if (hoverElement) {
                  if (hoverTimeout) clearTimeout(hoverTimeout);
                  hoverTimeout = setTimeout(() => this.playSound("hover"), 100);
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
        document.addEventListener("click", safeClickHandler.bind(this));
        document.addEventListener(
          "mouseenter",
          safeHoverHandler.bind(this),
          true
        );
        console.log("✅ Safe sound triggers attached successfully");
      } catch (error) {
        console.error("Failed to attach sound triggers:", error);
      }
    }

    playSound(name) {
      if (!this.enabled || !this.soundsLoaded) return;

      const sound = this.sounds.get(name);
      if (sound) {
        try {
          sound.currentTime = 0;
          sound.play().catch((error) => {
            // Only log non-autoplay errors
            if (
              error.name !== "NotAllowedError" &&
              error.name !== "NotSupportedError"
            ) {
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
      const volumeIcon = document.getElementById("volume-icon");
      if (volumeIcon) {
        if (this.currentAudio && this.currentAudio.muted) {
          volumeIcon.className = "fas fa-volume-mute text-sm";
        } else if (this.volume === 0) {
          volumeIcon.className = "fas fa-volume-off text-sm";
        } else if (this.volume < 0.5) {
          volumeIcon.className = "fas fa-volume-down text-sm";
        } else {
          volumeIcon.className = "fas fa-volume-up text-sm";
        }
      }
    }

    toggleMute() {
      if (this.currentAudio) {
        this.currentAudio.muted = !this.currentAudio.muted;
        this.updateVolumeIcon();
        this.announceToScreenReader(
          this.currentAudio.muted ? "Audio dibisukan" : "Audio tidak dibisukan"
        );
      }
    }

    viewCurrentDocument() {
      if (!this.currentDocument) {
        console.warn("⚠️ No current document to view");
        this.showErrorMessage("Tidak ada dokumen yang sedang diputar");
        return;
      }

      try {
        // Use Laravel route pattern
        const docUrl = `/dokumen/${
          this.currentDocument.slug || this.currentDocument.id
        }`;

        // Open in new tab
        window.open(docUrl, "_blank");

        console.log("✅ Opening document via route:", {
          title: this.currentDocument.title,
          url: docUrl,
        });

        // Optional: Track view event
        if (typeof gtag !== "undefined") {
          gtag("event", "view_document", {
            document_title: this.currentDocument.title,
            document_id: this.currentDocument.id,
          });
        }
      } catch (error) {
        console.error("❌ Error opening document:", error);
        this.showErrorMessage("Gagal membuka dokumen");
      }
    }

    setEnabled(enabled) {
      this.enabled = enabled;
      localStorage.setItem("sound_effects_enabled", enabled);
    }

    isEnabled() {
      return this.enabled;
    }

    // Load preferences
    loadPreferences() {
      const enabled = localStorage.getItem("sound_effects_enabled");
      if (enabled !== null) {
        this.enabled = enabled === "true";
      }

      const volume = localStorage.getItem("sound_effects_volume");
      if (volume !== null) {
        this.setVolume(parseFloat(volume));
      }
    }

    // Disable sound effects if not needed
    disable() {
      this.enabled = false;
      localStorage.setItem("sound_effects_enabled", "false");
    }

    cleanup() {
      this.sounds.forEach((sound) => {
        sound.pause();
        sound.src = "";
      });
      this.sounds.clear();
    }
  }
