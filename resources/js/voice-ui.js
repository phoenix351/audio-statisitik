// Status voice features
window.voiceFeaturesStatus = {
  coordinator: true,
  search: false,
  welcome: false,
  navigation: false,
};

// Debug helpers
window.checkVoiceFeatures = function () {
  console.group("🎤 Voice Features Status");
  const st = window.voiceFeaturesStatus || {};
  console.log("Voice Search:", st.search ? "✅" : "❌");
  console.log("Welcome Message:", st.welcome ? "✅" : "❌");
  console.log("Voice Navigation:", st.navigation ? "✅" : "❌");

  if (window.AudioSystem) console.log("Legacy AudioSystem:", "✅ Available");
  if (window.AudioStatistik?.Voice?.Search)
    console.log("Enhanced Voice Search:", "✅ Available");
  if (window.commandRecognition)
    console.log(
      "Wake Recognition:",
      window.commandRecognition.state || "Available"
    );
  if (window.voiceRecognition)
    console.log(
      "Search Recognition:",
      window.voiceRecognition.state || "Available"
    );
  console.groupEnd();
};

window.activateVoiceSearch = function () {
  console.log("🎤 Manually activating voice search...");
  if (window.startVoiceSearch) {
    window.startVoiceSearch();
  } else if (window.AudioSystem?.openVoiceSearchModal) {
    window.AudioSystem.openVoiceSearchModal();
  } else {
    console.warn("⚠️ Voice search not available");
  }
};

window.resetVoiceSystem = function () {
  console.log("🔄 Resetting voice system...");
  if (window.stopVoiceSearch) window.stopVoiceSearch();
  sessionStorage.removeItem("welcomed");
  setTimeout(() => location.reload(), 1000);
};

window.testVoiceSearch = function () {
  console.log("🧪 Testing voice search...");
  if ("webkitSpeechRecognition" in window) {
    console.log("✅ Speech recognition supported");
    if (window.startVoiceSearch) {
      console.log("✅ Voice search functions available");
      window.startVoiceSearch();
    } else {
      console.warn("❌ Voice search functions not found");
    }
  } else {
    console.warn("❌ Speech recognition not supported");
  }
};
