// Auto-extracted from app1.js
export class HealthMonitorManager {
    constructor() {
      this.healthChecks = [];
      this.healthInterval = null;
      this.healthCheckCount = 0;
      this.maxHealthChecks = 10;
      this.isMonitoring = false;
    }

    async init() {
      console.log("🏥 Initializing Health Monitor Manager");
      this.setupHealthChecks();
    }

    setupHealthChecks() {
      this.healthChecks = [
        () => this.checkAudioManagerHealth(),
        () => this.checkUIElementsHealth(),
        () => this.checkEventListenersHealth(),
        () => this.checkMemoryUsage(),
        () => this.checkPerformanceMetrics(),
      ];
    }

    startHealthChecks() {
      if (this.isMonitoring) return;

      this.isMonitoring = true;
      this.healthInterval = setInterval(() => {
        this.runHealthCheck();
      }, CONFIG.audio.healthCheckInterval);

      console.log("🟢 Health monitoring started");
    }

    stopHealthChecks() {
      if (this.healthInterval) {
        clearInterval(this.healthInterval);
        this.healthInterval = null;
      }
      this.isMonitoring = false;
      console.log("🔴 Health monitoring stopped");
    }

    runHealthCheck() {
      this.healthCheckCount++;
      const results = this.healthChecks.map((check) => check());
      const isHealthy = results.every((result) => result.healthy);

      if (isHealthy) {
        if (this.healthCheckCount % 20 === 0) {
          // Log every 20 checks (1 minute)
          console.log("✅ System health check passed");
        }
      } else {
        console.warn(
          "⚠️ System health issues detected:",
          results.filter((r) => !r.healthy)
        );

        if (this.healthCheckCount >= this.maxHealthChecks) {
          this.triggerRecovery(results);
        }
      }
    }

    checkAudioManagerHealth() {
      const audioManager = window.unifiedApp?.modules.audioPlayer;

      if (!audioManager) {
        return { healthy: false, issue: "Audio manager not found" };
      }

      if (audioManager.currentAudio && audioManager.currentAudio.error) {
        return {
          healthy: false,
          issue: "Audio element error",
          error: audioManager.currentAudio.error,
        };
      }

      return { healthy: true };
    }

    checkUIElementsHealth() {
      const requiredElements = ["bottom-audio-player", "main-audio-element"];

      for (const id of requiredElements) {
        if (!document.getElementById(id)) {
          return { healthy: false, issue: `Missing UI element: ${id}` };
        }
      }

      return { healthy: true };
    }

    checkEventListenersHealth() {
      const audioButtons = document.querySelectorAll(
        ".play-audio-btn, .admin-play-btn, .play-document-btn"
      );
      const handledButtons = document.querySelectorAll("[data-audio-handled]");

      console.log(
        `🔍 Health check: ${audioButtons.length} audio buttons, ${handledButtons.length} handled`
      );

      // More lenient health check - allow some buttons to be unhandled
      if (audioButtons.length > 0 && handledButtons.length === 0) {
        return {
          healthy: false,
          issue: "Audio buttons not properly initialized",
          details: {
            totalButtons: audioButtons.length,
            handledButtons: handledButtons.length,
          },
        };
      }

      // If less than 50% are handled, consider it unhealthy
      if (
        audioButtons.length > 0 &&
        handledButtons.length / audioButtons.length < 0.5
      ) {
        return {
          healthy: false,
          issue: "Many audio buttons not properly initialized",
          details: {
            totalButtons: audioButtons.length,
            handledButtons: handledButtons.length,
            percentage: Math.round(
              (handledButtons.length / audioButtons.length) * 100
            ),
          },
        };
      }

      return { healthy: true };
    }

    checkMemoryUsage() {
      if ("memory" in performance) {
        const memInfo = performance.memory;
        const usedMB = memInfo.usedJSHeapSize / 1024 / 1024;
        const limitMB = memInfo.jsHeapSizeLimit / 1024 / 1024;

        if (usedMB > limitMB * 0.8) {
          return {
            healthy: false,
            issue: "High memory usage",
            usage: usedMB,
            limit: limitMB,
          };
        }
      }

      return { healthy: true };
    }

    checkPerformanceMetrics() {
      const entries = performance.getEntriesByType("navigation");
      if (entries.length > 0) {
        const loadTime = entries[0].loadEventEnd - entries[0].loadEventStart;
        if (loadTime > 5000) {
          // 5 seconds
          return { healthy: false, issue: "Slow page load", loadTime };
        }
      }

      return { healthy: true };
    }

    triggerRecovery(healthResults) {
      console.warn("🚨 Triggering system recovery due to health issues");

      // Reset health check count
      this.healthCheckCount = 0;

      // Attempt targeted recovery based on issues
      healthResults.forEach((result) => {
        if (!result.healthy) {
          this.handleSpecificIssue(result);
        }
      });
    }

    handleSpecificIssue(result) {
      switch (result.issue) {
        case "Audio manager not found":
          this.recoverAudioManager();
          break;
        case "Audio buttons not properly initialized":
          this.recoverAudioButtons();
          break;
        default:
          console.log("🔧 General recovery for:", result.issue);
      }
    }

    recoverAudioManager() {
      console.log("🔄 Attempting audio manager recovery");
      try {
        if (window.unifiedApp?.modules.audioPlayer) {
          window.unifiedApp.modules.audioPlayer.cleanup();
          window.unifiedApp.modules.audioPlayer = new UnifiedAudioManager();
          window.unifiedApp.modules.audioPlayer.init();
        }
      } catch (error) {
        console.error("❌ Audio manager recovery failed:", error);
      }
    }

    recoverAudioButtons() {
      console.log("🔄 Attempting audio buttons recovery");
      try {
        // Remove existing handlers
        document.querySelectorAll("[data-audio-handled]").forEach((btn) => {
          btn.removeAttribute("data-audio-handled");
        });

        // Reinitialize buttons
        if (window.unifiedApp?.modules.audioPlayer) {
          window.unifiedApp.modules.audioPlayer.setupUniversalIntegration();
        }
      } catch (error) {
        console.error("❌ Audio buttons recovery failed:", error);
      }
    }

    // Public API for manual health checks
    getSystemHealth() {
      const results = this.healthChecks.map((check) => check());
      const isHealthy = results.every((result) => result.healthy);

      return {
        healthy: isHealthy,
        checks: results,
        timestamp: new Date().toISOString(),
      };
    }

    cleanup() {
      this.stopHealthChecks();
    }
  }
