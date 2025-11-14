// app/legacy-bridge.js
function setupBackwardCompatibility() {
  window.playDocumentAudio = function (documentData) {
    if (window.unifiedApp?.modules?.audioPlayer) {
      if (typeof documentData === "object" && documentData.id) {
        return window.unifiedApp.modules.audioPlayer.playDocument(documentData);
      } else if (
        typeof documentData === "number" ||
        typeof documentData === "string"
      ) {
        return window.unifiedApp.modules.audioPlayer.playDocument({
          id: documentData,
        });
      }
    }
    console.error("❌ Audio player not available or invalid document data");
  };

  window.stopAudio = function () {
    if (window.unifiedApp?.modules?.audioPlayer) {
      window.unifiedApp.modules.audioPlayer.stopCurrent();
      window.unifiedApp.modules.audioPlayer.hidePlayer();
    }
  };

  window.toggleAudioPlayPause = function () {
    if (window.unifiedApp?.modules?.audioPlayer) {
      window.unifiedApp.modules.audioPlayer.togglePlayPause();
    }
  };

  window.showToast = function (message, type = "info", duration) {
    if (window.unifiedApp?.modules?.toast) {
      return window.unifiedApp.modules.toast.show(message, type, duration);
    }
  };

  window.announceToScreenReader = function (message, priority = "polite") {
    if (window.unifiedApp?.modules?.accessibility) {
      window.unifiedApp.modules.accessibility.announce(message, priority);
    }
  };
}

function setupDevelopmentTools() {
  console.log("🔧 Setting up development tools...");
  window.debugUnifiedApp = function () {
    console.group("🔍 Clean Unified App Debug Info");
    console.log("Initialized:", window.unifiedApp?.initialized);
    console.log("Modules:", Object.keys(window.unifiedApp?.modules || {}));
    console.log(
      "Audio Player State:",
      window.unifiedApp?.modules?.audioPlayer?.getPlaybackState()
    );
    console.groupEnd();
  };
  window.testAudioPlayer = function () {
    if (window.unifiedApp?.modules?.audioPlayer) {
      console.log("🎵 Audio Player Available");
      console.log(
        "State:",
        window.unifiedApp.modules.audioPlayer.getPlaybackState()
      );
    } else {
      console.log("❌ Audio Player Not Available");
    }
  };
  window.testToast = function () {
    if (window.unifiedApp?.modules?.toast) {
      window.unifiedApp.modules.toast.success("Test Toast Message");
      console.log("✅ Toast system working");
    } else {
      console.log("❌ Toast system not available");
    }
  };
  window.reportAppError = function (error, context = "general") {
    console.error(`❌ App Error [${context}]:`, error);
    if (window.unifiedApp?.modules?.toast) {
      window.unifiedApp.modules.toast.error(
        `Error in ${context}: ${error.message}`
      );
    }
  };
}

export { setupBackwardCompatibility, setupDevelopmentTools };
