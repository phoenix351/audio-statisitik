import "./bootstrap"; // (opsional, default Laravel)
import "./voice-ui"; // util & helper voice system (di bawah)
import "./scroll-top"; // contoh utility terpisah (opsional)
import "./src/main"; // hasil refactor app1.js kamu
import "./enhanced-voice-search"; // jika ada file terpisah untuk voice
import "./auto-filter";
import "./welcome-message";
import { initApiMonitor } from "./api-test";
import { initCoverImage } from "./cover-image";
import { initCharCounter } from "./char-counter";
import { bindCopyButtons } from "./clipboard";
import initDocumentCreate from "./admin-documents-create";

import { initSearch } from "./src/pages/search";

document.addEventListener("DOMContentLoaded", () => {
  const body = document.body;
  const routeName = body.dataset.routeName || "";
  const enableVoice = body.dataset.enableVoice === "1";
  const searchUrl = body.dataset.searchUrl || "/search";

  // Simpan ke global minimalis bila perlu oleh modul lain
  window.AudioStatistik = window.AudioStatistik || {};
  window.AudioStatistik.runtime = { routeName, enableVoice, searchUrl };

  // Inisialisasi voice search (enhanced atau legacy) berdasar flag
  if (enableVoice) {
    if (window.AudioSystem?.initializeVoiceSearch) {
      console.log("🌉 Using legacy AudioSystem voice search");
      window.AudioSystem.initializeVoiceSearch(searchUrl);
    } else if (window.AudioStatistik?.Voice?.Search?.init) {
      console.log("🎤 Using enhanced voice search system");
      window.AudioStatistik.Voice.Search.init(searchUrl);
    }
  }

  // Auto-check status (dipindah dari inline script)
  setTimeout(() => {
    console.log("🔍 Auto-checking voice features status...");
    window.voiceFeaturesStatus = window.voiceFeaturesStatus || {
      coordinator: true,
      search: !!window.startVoiceSearch,
      welcome: false,
      navigation: false,
    };
    window.checkVoiceFeatures && window.checkVoiceFeatures();
    window.testVoiceSearch && window.testVoiceSearch();
  }, 3000);

  // Clear filters helper (dipindah dari inline)
  window.clearAllFilters = function () {
    // gunakan route name jika mau beda perilaku per-halaman
    window.location.href =
      body.dataset.documentsIndex ?? "{{ route('documents.index') }}";
  };
  if (routeName.includes("api-monitor")) {
    initApiMonitor();
  }
  if (routeName.includes("edit")) {
    initCoverImage();
    initCharCounter({
      textareaId: "description",
      counterId: "desc-count",
      warnAt: 900,
    });
    initCharCounter({
      textareaId: "extracted_text",
      counterId: "extracted-count",
      warnAt: 900,
    });

    bindCopyButtons("data-copy-url");
  }
  if (routeName.includes("documents") && routeName.includes("create")) {
    initDocumentCreate();
  }
  if (routeName.includes("search")) {
    initSearch();
  }
  if (routeName.includes("brs") || routeName.includes("publikasi")) {
    function clearAllFilters() {
      window.location.href = window.location.pathname;
    }
    window.clearAllFilters = clearAllFilters;
  }
});
