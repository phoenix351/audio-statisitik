import "./bootstrap"; // (opsional, default Laravel)
import "./voice-ui"; // util & helper voice system (di bawah)
import "./scroll-top"; // contoh utility terpisah (opsional)
import "./src/main"; // hasil refactor app1.js kamu
import "./enhanced-voice-search"; // jika ada file terpisah untuk voice
import "./welcome-message";
import { initApiMonitor } from "./api-test";
import { initCharCounter } from "./char-counter";
import { bindCopyButtons } from "./clipboard";
import initDocumentCreate from "./admin-documents-create";
import { initLogin } from "./src/pages/login";

import { initSearch } from "./src/pages/search";
import { initAutoFilter } from "./auto-filter";
import { initEditAdmin } from "./src/admin/edit";

document.addEventListener("DOMContentLoaded", () => {
  const body = document.body;
  const routeName = body.dataset.routeName || "";
  const isAuthAdminRoute =
    routeName.includes("login") ||
    routeName.includes("admin") ||
    routeName.includes("auth") ||
    routeName.includes("register");

  const enableVoice = !isAuthAdminRoute;
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
    initEditAdmin();
    initCharCounter({
      textareaId: "description",
      counterId: "desc-count",
      warnAt: 900,
    });
    initCharCounter({
      textareaId: "extracted_text",
      counterId: "extracted-count",
      warnAt: 45000,
    });

    bindCopyButtons("data-copy-url");
  }
  if (routeName.includes("documents") && routeName.includes("create")) {
    initDocumentCreate();
  }
  if (routeName.includes("login")) {
    initLogin();
    if (routeName.includes("search")) {
      initSearch();
      initAutoFilter();
    }
    if (routeName.includes("brs") || routeName.includes("publikasi")) {
      initAutoFilter();
      function clearAllFilters() {
        window.location.href = window.location.pathname;
      }
      window.clearAllFilters = clearAllFilters;
    }
  }
});
