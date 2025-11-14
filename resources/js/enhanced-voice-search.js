(function () {
  "use strict";

  // Namespace management
  window.AudioStatistik = window.AudioStatistik || {};
  window.AudioStatistik.Voice = window.AudioStatistik.Voice || {};

  // Prevent double initialization
  if (window.AudioStatistik.Voice.Search || window.__voiceInitDone) {
    console.log("🔄 Enhanced Voice Search already initialized");
    return;
  }

  function initializeEnhancedVoiceSearch(searchRoute = "/search") {
    if (!("webkitSpeechRecognition" in window)) {
      console.warn("⚠️ Browser tidak mendukung Web Speech API");
      return;
    }

    // Prevent double initialization
    if (window.__voiceInitDone) {
      console.log("🔄 Voice search already initialized");
      return;
    }
    window.__voiceInitDone = true;

    console.log("🎤 Initializing Enhanced Voice Search...");

    // Helper Functions
    const sanitizeQuery = (query) => {
      return query
        .trim()
        .replace(/[.,!?;:]+$/, "")
        .replace(/\s+/g, " ")
        .toLowerCase()
        .replace(
          /^(carikan|carilah|dokumen|cariin|tahun|indikator|tolong|jenis dokumen|halo|hai|ya|saya|bps|ingin|mau|lihat|tampilkan|tampilin|cari|data|berikan|beriin|search|temukan)\s*/i,
          ""
        )
        .trim();
    };

    // const convertWordToNumber = (word) => {
    //   const numberMap = {
    //     satu: 1,
    //     dua: 2,
    //     tiga: 3,
    //     empat: 4,
    //     lima: 5,
    //     enam: 6,
    //     tujuh: 7,
    //     delapan: 8,
    //     sembilan: 9,
    //     sepuluh: 10,
    //   };
    //   return numberMap[word.toLowerCase()] || null;
    // };

    const selectDocument = (docNumber) => {
      try {
        const documents = document.querySelectorAll(
          "#documents-grid [data-document-index]"
        );
        const doc = documents[docNumber - 1];

        if (!doc) {
          speak(`Dokumen nomor ${docNumber} tidak ditemukan`);
          return;
        }

        // cari tombol view
        const viewButton = doc.querySelector(".fa-eye")?.closest("a, button");
        if (viewButton) {
          viewButton.click();
          console.log(`✅ Opened document ${docNumber}`);
        } else {
          speak(
            `Tombol lihat untuk dokumen nomor ${docNumber} tidak ditemukan`
          );
        }
      } catch (error) {
        console.error("❌ Error selecting document:", error);
        speak(`Gagal membuka dokumen nomor ${docNumber}`);
      }
    };
    function getAudioState() {
      const playPauseBtn = document.querySelector("#play-pause-btn");
      if (!playPauseBtn) {
        console.warn("🎛️ playpause-btn not found");
        return null;
      }

      // Find the icon element inside the button
      const icon = playPauseBtn.querySelector("i");
      if (!icon) {
        console.warn("🎵 Icon element not found inside playpause-btn");
        return null;
      }

      // Determine state based on the icon class
      if (icon.classList.contains("fa-play")) {
        return "paused";
      } else if (icon.classList.contains("fa-pause")) {
        return "playing";
      } else {
        return "unknown";
      }
    }

    const playDocument = (docNumber) => {
      try {
        const documents = document.querySelectorAll(
          "#documents-grid [data-document-index]"
        );
        const doc = documents[docNumber - 1];

        if (!doc) {
          speak(`Dokumen nomor ${docNumber} tidak ditemukan`);
          return;
        }

        // cari tombol play
        const playButton = doc.querySelector(".play-document-btn");
        if (playButton) {
          playButton.click();
          console.log(`✅ Playing document ${docNumber}`);
        } else {
          speak(
            `Tombol putar untuk dokumen nomor ${docNumber} tidak ditemukan`
          );
        }
      } catch (error) {
        console.error("❌ Error playing document:", error);
        speak(`Gagal memutar dokumen nomor ${docNumber}`);
      }
    };

    // Enhanced Helper Functions for Voice Commands
    const readDocuments = () => {
      const grid = document.querySelector("#documents-grid");
      if (!grid) return;

      const documents = grid.querySelectorAll("[data-document-index]");
      const countOnPage = documents.length;

      // Ambil total dokumen dari Blade
      const countElement = document.querySelector(".mb-6 p.text-gray-600");
      let totalDocs = countOnPage;
      if (countElement) {
        const match = countElement.innerText.match(/dari\s+([\d.,]+)/i);
        if (match) {
          totalDocs = parseInt(match[1].replace(/[.,]/g, ""));
        }
      }

      // Voice feedback awal
      let intro = `Ditemukan ${totalDocs} dokumen secara keseluruhan. `;
      if (countOnPage <= 5) {
        intro += "Berikut judul dokumen di halaman ini: ";
      } else {
        intro += `Halaman ini menampilkan ${countOnPage} dokumen. Gunakan filter untuk mempersempit pencarian.`;
      }

      const utter = new SpeechSynthesisUtterance(intro);
      utter.lang = "id-ID";
      speechSynthesis.speak(utter);

      // Bacakan judul dokumen jika <=5
      if (countOnPage <= 5) {
        documents.forEach((doc, i) => {
          const title = doc.querySelector("h3").innerText;
          const msg = new SpeechSynthesisUtterance(`Nomor ${i + 1}, ${title}`);
          msg.lang = "id-ID";
          speechSynthesis.speak(msg);
        });

        const guide = new SpeechSynthesisUtterance(
          "Untuk membuka dokumen, ucapkan pilih dokumen nomor. " +
            "Untuk memutar audio, ucapkan putar dokumen nomor. " +
            "Untuk bantuan, ucapkan bantuan."
        );
        guide.lang = "id-ID";
        speechSynthesis.speak(guide);
      } else {
        // Guide filter / navigasi
        const yearSelected = document.querySelector("#year").value;
        const indicatorSelected = document.querySelector("#indicator").value;

        let guideText = "Anda dapat mengatakan ";
        if (!yearSelected) guideText += "filter tahun diikuti tahun, atau ";
        if (!indicatorSelected)
          guideText += "filter indikator diikuti nama indikator, atau ";
        guideText += "ucapkan cari diikuti kata kunci.";

        const guide = new SpeechSynthesisUtterance(guideText);
        guide.lang = "id-ID";
        speechSynthesis.speak(guide);
      }
    };

    // 🔹 Auto jalan saat halaman pertama load
    // Jalankan readDocuments pertama kali
    readDocuments();

    // Awasi perubahan isi dokumen (misal karena filter/pencarian)
    const target = document.getElementById("documents-grid");
    if (target) {
      const observer = new MutationObserver((mutationsList, observer) => {
        // Kalau ada perubahan jumlah dokumen, panggil ulang
        readDocuments();
      });

      observer.observe(target, { childList: true, subtree: true });
    }

    function speakWithoutInterrupt(text, audioElement) {
      if (!audioElement) {
        speak(text);
        return;
      }

      const wasPlaying = !audioElement.paused;

      if (wasPlaying) {
        audioElement.pause();
      }

      const msg = new SpeechSynthesisUtterance(text);
      msg.lang = "id-ID";
      msg.onend = () => {
        if (wasPlaying) {
          audioElement.play();
        }
      };
      speechSynthesis.speak(msg);
    }

    const audioPlay = (audioElement) => {
      if (getAudioState() == "playing") return;
      try {
        const playButton = document.querySelector("#play-pause-btn");

        if (playButton) {
          speak("Audio diputar");
          playButton.click();
          console.log("▶️ Audio play button clicked");
        } else {
          speak("Tidak ada audio yang dapat diputar");
        }
      } catch (error) {
        console.error("❌ Error playing audio:", error);
        speak("Gagal memutar audio");
      }
    };

    const audioPause = (audioElement) => {
      if (getAudioState() == "paused") return;
      try {
        const audioElement = document.querySelector(
          "#main-audio, audio, .audio-player audio"
        );
        const pauseButton = document.querySelector("#play-pause-btn");

        if (pauseButton) {
          pauseButton.click();
          speak("Audio dijeda");
          console.log("⏸️ Audio pause button clicked");
        } else {
          speak("Tidak ada audio yang sedang diputar");
        }
      } catch (error) {
        console.error("❌ Error pausing audio:", error);
        speak("Gagal menjeda audio");
      }
    };

    const audioStop = (audioElement) => {
      try {
        if (audioElement) {
          audioElement.pause();
          audioElement.currentTime = 0;
          speak("Audio dihentikan");
          console.log("⏹️ Audio stopped");
        } else {
          speak("Tidak ada audio yang dapat dihentikan");
        }
      } catch (error) {
        console.error("❌ Error stopping audio:", error);
        speak("Gagal menghentikan audio");
      }
    };

    // Helper umum: pastikan ada audio
    const ensureAudio = (audioElement) => {
      if (!audioElement) {
        speak("Tidak ada audio yang sedang diputar");
        return false;
      }
      return true;
    };

    // Helper umum: bungkus aksi dengan try/catch + pesan error kustom
    const withAudioAction = (audioElement, action, errorMessage) => {
      if (!ensureAudio(audioElement)) return;

      try {
        action();
      } catch (error) {
        console.error("❌ Error:", error);
        speak(errorMessage);
      }
    };

    // Helper: jalankan callback setelah metadata siap
    const runWhenMetadataReady = (audioElement, callback) => {
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

    const audioSeekForward = (audioElement) => {
      withAudioAction(
        audioElement,
        () => {
          runWhenMetadataReady(audioElement, () => {
            audioElement.currentTime = Math.min(
              audioElement.currentTime + 10,
              audioElement.duration
            );
            speakWithoutInterrupt("Maju 10 detik", audioElement);
            console.log("⏩ Audio seeked forward 10s");
          });
        },
        "Gagal memajukan audio"
      );
    };

    const audioSeekBackward = (audioElement) => {
      withAudioAction(
        audioElement,
        () => {
          audioElement.currentTime = Math.max(audioElement.currentTime - 10, 0);
          speakWithoutInterrupt("Mundur 10 detik", audioElement);
          console.log("⏪ Audio seeked backward 10s");
        },
        "Gagal memundurkan audio"
      );
    };

    const audioSpeedUp = (audioElement) => {
      withAudioAction(
        audioElement,
        () => {
          audioElement.playbackRate = Math.min(
            audioElement.playbackRate + 0.25,
            2.0
          );
          speakWithoutInterrupt(
            `Kecepatan ${audioElement.playbackRate}x`,
            audioElement
          );
          console.log(`⚡ Audio speed: ${audioElement.playbackRate}x`);
        },
        "Gagal mengubah kecepatan audio"
      );
    };

    const audioSpeedNormal = (audioElement) => {
      withAudioAction(
        audioElement,
        () => {
          audioElement.playbackRate = 1.0;
          speakWithoutInterrupt("Kecepatan normal", audioElement);
          console.log("🔄 Audio speed normalized");
        },
        "Gagal menormalisasi kecepatan audio"
      );
    };

    const audioDownload = (audioElement) => {
      // Di sini audioElement sebenarnya opsional, tapi tetap bisa pakai helper
      withAudioAction(
        audioElement,
        () => {
          const downloadButton = document.querySelector(
            '.download-btn, .fa-download, [title*="Download"], [title*="Unduh"]'
          );

          if (downloadButton) {
            downloadButton.click();
            speakWithoutInterrupt("Mengunduh audio", audioElement);
            console.log("💾 Audio download initiated");
          } else {
            speak("Tombol unduh tidak ditemukan");
          }
        },
        "Gagal mengunduh audio"
      );
    };

    const filterByYear = (year) => {
      try {
        const yearSelect = document.querySelector(
          'select[name="year"], #year-filter, .year-select'
        );
        if (yearSelect) {
          yearSelect.value = year;
          yearSelect.dispatchEvent(new Event("change"));
          speak(`Filter tahun ${year} diterapkan`);
          console.log(`📅 Year filter applied: ${year}`);
        } else {
          const url = new URL(window.location.href);
          url.searchParams.set("year", year);
          window.location.href = url.toString();
        }
      } catch (error) {
        console.error("❌ Error filtering by year:", error);
        speak("Gagal menerapkan filter tahun");
      }
    };

    const filterByIndicator = (indicator) => {
      try {
        const indicatorSelect = document.querySelector(
          'select[name="indicator"], #indicator-filter, .indicator-select'
        );
        if (indicatorSelect) {
          const options = indicatorSelect.querySelectorAll("option");
          for (const option of options) {
            if (
              option.textContent.toLowerCase().includes(indicator.toLowerCase())
            ) {
              indicatorSelect.value = option.value;
              indicatorSelect.dispatchEvent(new Event("change"));
              speak(`Filter indikator ${indicator} diterapkan`);
              console.log(`📊 Indicator filter applied: ${indicator}`);
              return;
            }
          }
          speak(`Indikator ${indicator} tidak ditemukan`);
        } else {
          const url = new URL(window.location.href);
          url.searchParams.set("indicator", indicator);
          window.location.href = url.toString();
        }
      } catch (error) {
        console.error("❌ Error filtering by indicator:", error);
        speak("Gagal menerapkan filter indikator");
      }
    };

    // =============================
    // 🔎 Fungsi Search/Filter Utama
    // =============================
    const searchKeyword = (keyword) => {
      try {
        if (!keyword) return;

        let workingQuery = keyword.toLowerCase().trim();
        let applied = false;

        const searchInput = document.querySelector(
          'input[name="query"], #search-input, .search-input'
        );
        const yearDropdown = document.querySelector("#year");
        const indicatorDropdown = document.querySelector("#indicator");

        let year = null;
        let indicatorFound = null;

        // --------------------------
        // 1. Deteksi Tahun (20xx)
        // --------------------------
        const yearMatch = workingQuery.match(/\b(20\d{2})\b/);
        if (yearMatch) {
          year = yearMatch[1];
          if (yearDropdown) {
            yearDropdown.value = year;
            yearDropdown.dispatchEvent(new Event("change"));
            speak(`Filter tahun diatur ke ${year}`);
            applied = true;
          }
          // hapus tahun dari query biar gak masuk searchbox
          workingQuery = workingQuery
            .replace(year, "")
            .replace(/\btahun\b/g, "")
            .trim();
        }

        // --------------------------
        // 2. Deteksi Indikator
        // --------------------------
        if (indicatorDropdown) {
          Array.from(indicatorDropdown.options).forEach((opt) => {
            if (workingQuery.includes(opt.text.toLowerCase())) {
              indicatorFound = opt.value;
            }
          });

          if (indicatorFound) {
            indicatorDropdown.value = indicatorFound;
            indicatorDropdown.dispatchEvent(new Event("change"));
            speak(`Filter indikator diatur ke ${workingQuery}`);
            applied = true;
            // hapus kata indikator agar gak masuk ke searchbox
            workingQuery = workingQuery.replace(/\bindikator\b/g, "").trim();
          }
        }

        // --------------------------
        // 3. Keyword bebas (searchbox) → hanya sisanya
        // --------------------------
        if (workingQuery) {
          if (searchInput) {
            let currentValue = searchInput.value.trim();

            // tambahkan kata baru ke searchbox (append)
            if (currentValue) {
              if (
                !currentValue.toLowerCase().includes(workingQuery.toLowerCase())
              ) {
                searchInput.value = `${currentValue} ${workingQuery}`.trim();
              }
            } else {
              searchInput.value = workingQuery;
            }

            searchInput.dispatchEvent(new Event("input"));
            speak(`Pencarian diatur ke ${searchInput.value}`);
            applied = true;
          }
        }

        if (!applied) {
          speak("Tidak ada filter yang diterapkan dari perintah Anda");
        }
      } catch (error) {
        console.error("❌ Error in searchKeyword:", error);
        speak("Terjadi kesalahan saat mencari dokumen");
      }
    };

    const getDocumentCount = () => {
      try {
        const documents = document.querySelectorAll(
          ".document-item, .publication-item, .brs-item, .search-result-item"
        );
        const count = documents.length;
        speak(`Terdapat ${count} dokumen`);
        console.log(`📊 Document count: ${count}`);
      } catch (error) {
        console.error("❌ Error getting document count:", error);
        speak("Gagal menghitung dokumen");
      }
    };

    const nextPage = () => {
      try {
        const nextButton = document.querySelector(
          '.pagination .next, .next-page, [aria-label*="Next"], .fa-chevron-right'
        );
        if (nextButton && !nextButton.classList.contains("disabled")) {
          nextButton.click();
          speak("Ke halaman selanjutnya");
          console.log("➡️ Navigated to next page");
        } else {
          speak("Tidak ada halaman selanjutnya");
        }
      } catch (error) {
        console.error("❌ Error navigating to next page:", error);
        speak("Gagal ke halaman selanjutnya");
      }
    };

    const previousPage = () => {
      try {
        const url = new URL(window.location.href);
        const currentPage = parseInt(url.searchParams.get("page") || "1");

        if (currentPage > 1) {
          // kalau lagi di halaman > 2, cukup kurangi 1
          url.searchParams.set("page", currentPage - 1);

          // khusus kalau targetnya halaman 1 → hapus param ?page
          if (currentPage - 1 === 1) {
            url.searchParams.delete("page");
          }

          window.location.href = url.toString();
          speak("Ke halaman sebelumnya");
          console.log("⬅️ Navigated to previous page");
        } else {
          speak("Sudah di halaman pertama");
          console.log("⚠️ Already at first page");
        }
      } catch (error) {
        console.error("❌ Error navigating to previous page:", error);
        speak("Gagal ke halaman sebelumnya");
      }
    };

    const resetFilters = () => {
      try {
        const resetButton = document.querySelector(
          ".reset-filter, .clear-filter, #reset-filters"
        );
        const searchInput = document.querySelector(
          '#search, input[name="query"], .search-input'
        );

        if (resetButton) {
          // klik tombol reset kalau ada
          resetButton.click();
          if (searchInput) {
            searchInput.value = "";
            searchInput.dispatchEvent(new Event("input"));
          }
          speak("Filter direset");
          console.log("🔄 Filters reset via button");
        } else {
          // manual reset dari URL
          const url = new URL(window.location.href);
          url.searchParams.delete("year");
          url.searchParams.delete("indicator");
          url.searchParams.delete("type");
          url.searchParams.delete("query"); // 🔥 hapus query juga

          if (searchInput) {
            searchInput.value = "";
            searchInput.dispatchEvent(new Event("input"));
          }

          window.location.href = url.toString();
          console.log("🔄 Filters reset via URL");
          speak("Filter direset");
        }
      } catch (error) {
        console.error("❌ Error resetting filters:", error);
        speak("Gagal mereset filter");
      }
    };

    const showVoiceHelp = () => {
      const helpText =
        "Perintah suara yang tersedia: " +
        'Untuk pencarian, katakan "Hai Audio Statistik" kemudian sebutkan kata kunci. ' +
        'Untuk dokumen: "Baca dokumen", "Pilih dokumen nomor", "Putar dokumen nomor". ' +
        'Untuk audio: "Play/Putar", "Pause/Jeda", "Stop/Berhenti", "Maju", "Mundur", "Percepat", "Normal", "Unduh". ' +
        'Untuk filter: "Filter tahun", "Filter indikator", dan ucapkan "Reset Filter" untuk menghapus filter. ' +
        'Untuk navigasi: "Halaman selanjutnya", "Halaman sebelumnya". ' +
        'Untuk informasi: "Berapa dokumen", "Bantuan".';
      speak(helpText);
      console.log("❓ Voice help provided");
    };

    // Voice Command Processor
    // --- Helpers ---------------------------------------------------------------

    const PUNCT_REGEX = /[.,!?;:()"'“”‘’[\]{}/\\|@#$%^&*_+=~`<>…–—-]/g;

    function normalizeCommand(raw) {
      if (!raw) return "";
      // Lowercase + remove diacritics + strip punctuation + collapse spaces
      let s = raw
        .toLowerCase()
        .normalize("NFD")
        .replace(/\p{Diacritic}/gu, "")
        .replace(PUNCT_REGEX, " ")
        .replace(/\s+/g, " ")
        .trim();

      // Common synonym normalizations
      s = s.replace(/\bno\b/g, "nomor").replace(/\bpage\b/g, "halaman");

      return s;
    }

    function convertWordToNumber(word) {
      if (!word) return null;
      const base = {
        nol: 0,
        kosong: 0,
        satu: 1,
        dua: 2,
        tiga: 3,
        empat: 4,
        lima: 5,
        enam: 6,
        tujuh: 7,
        delapan: 8,
        sembilan: 9,
        sepuluh: 10,
        sebelas: 11,
      };

      const tokens = word
        .toLowerCase()
        .normalize("NFD")
        .replace(/\p{Diacritic}/gu, "")
        .split(/\s+/);

      // Single word quick map
      if (tokens.length === 1 && base[tokens[0]] !== undefined) {
        return base[tokens[0]];
      }

      // Handle "X belas" (12–19), "dua belas", "tiga belas", etc.
      if (tokens.includes("belas")) {
        let n = 10;
        const idx = tokens.indexOf("belas");
        const before = tokens.slice(0, idx).join(" ");
        if (before && base[before] !== undefined) n += base[before];
        else if (before === "" || before === "se") n += 1; // safety
        return n;
      }

      // Handle "X puluh" (20,30,...,90) and "X puluh Y" (21–99)
      const puluhIdx = tokens.indexOf("puluh");
      if (puluhIdx !== -1) {
        let tens = 0;
        const before = tokens.slice(0, puluhIdx).join(" ");
        if (before === "se" || before === "satu")
          tens = 10; // "sepuluh" already handled above, but ok
        else if (base[before] !== undefined) tens = base[before] * 10;

        let ones = 0;
        const after = tokens.slice(puluhIdx + 1).join(" ");
        if (after && base[after] !== undefined) ones = base[after];

        const total = tens + ones;
        return total || null;
      }

      // Try direct lookup again if phrase equals the key (e.g., "dua belas" was not tokenized above)
      if (base[word] !== undefined) return base[word];

      return null;
    }

    function parseMaybeNumber(token) {
      // Token may be digits or Indonesian number words (possibly multiple words)
      if (!token) return null;
      if (/^\d+$/.test(token)) return parseInt(token, 10);

      // Also try split-number phrase like "dua belas", "tiga puluh dua"
      // Allow token to be a phrase already (the caller may pass it).
      return convertWordToNumber(token);
    }

    // --- Main ------------------------------------------------------------------

    const processVoiceCommand = (rawCommand) => {
      const currentAudio = document.getElementById("main-audio-element");
      console.log("🔄 Raw command:", rawCommand);

      const command = normalizeCommand(rawCommand);
      console.log("🧼 Normalized command:", command);

      // Early exit if empty after normalization
      if (!command) {
        console.log("❌ Empty command after normalization");
        return false;
      }

      // === Document selection commands ===
      const selectPatterns = [
        /\bpilih dokumen nomor\s+(.+)\b/i,
        /\bpilih nomor\s+(.+)\b/i,
      ];

      for (const pattern of selectPatterns) {
        const match = command.match(pattern);
        if (match) {
          const numStr = match[1].trim();
          const docNumber = parseMaybeNumber(numStr);
          if (docNumber) {
            console.log(`📄 Document selection command: ${docNumber}`);
            speak(`Membuka dokumen nomor ${docNumber}`, () => {
              selectDocument(docNumber);
              speak(
                'Dokumen telah terbuka. Anda dapat mengucapkan "putar audio" untuk mendengarkan dokumen ini, atau "unduh audio" untuk mengunduh file suara.'
              );
            });
            return true;
          }
        }
      }

      // === Commands after document is opened ===
      // Putar audio
      if (/\bputar audio\b/i.test(command)) {
        const playBtn = document.querySelector(
          "button[onclick^='playDocumentAudio']"
        );
        if (playBtn) {
          speak("Memutar audio", () => playBtn.click());
        } else {
          speak("Audio tidak tersedia untuk dokumen ini");
        }
        return true;
      }

      // Unduh audio (default MP3)
      if (/\bunduh audio\b/i.test(command) || /\bunduh mp3\b/i.test(command)) {
        const buttons = Array.from(document.querySelectorAll("a, button"));
        const downloadBtn = buttons.find((btn) =>
          (btn.textContent || "").trim().toLowerCase().includes("unduh mp3")
        );
        if (downloadBtn) {
          speak("Mengunduh audio", () => downloadBtn.click());
        } else {
          speak("File audio tidak tersedia");
        }
        return true;
      }

      // === Document playback by number ===
      const playPatterns = [
        /\bputar dokumen nomor\s+(.+)\b/i,
        /\bputar nomor\s+(.+)\b/i,
      ];

      for (const pattern of playPatterns) {
        const match = command.match(pattern);
        if (match) {
          const numStr = match[1].trim();
          const docNumber = parseMaybeNumber(numStr);
          if (docNumber) {
            console.log(`▶️ Document playback command: ${docNumber}`);
            speak(`Memutar dokumen nomor ${docNumber}`, () => {
              playDocument(docNumber);
            });
            return true;
          }
        }
      }

      // === Audio player controls ===
      if (/^(play|putar|mainkan)$/.test(command)) {
        console.log("▶️ Audio play command detected");
        audioPlay(currentAudio);
        return true;
      }

      if (/^(pause|jeda)$/.test(command)) {
        console.log("⏸️ Audio pause command detected");
        audioPause(currentAudio);
        return true;
      }

      if (/^(stop|berhenti)$/.test(command)) {
        console.log("⏹️ Audio stop command detected");
        audioStop(currentAudio);
        return true;
      }

      if (/^maju$/.test(command)) {
        console.log("⏩ Audio seek forward command detected");
        audioSeekForward(currentAudio);
        return true;
      }

      if (/^mundur$/.test(command)) {
        console.log("⏪ Audio seek backward command detected");
        audioSeekBackward(currentAudio);
        return true;
      }

      if (/^percepat$/.test(command)) {
        console.log("⚡ Audio speed up command detected");
        audioSpeedUp(currentAudio);
        return true;
      }

      if (/^normal$/.test(command)) {
        console.log("🔄 Audio normal speed command detected");
        audioSpeedNormal(currentAudio);
        return true;
      }

      if (/^(unduh|download|simpan)$/.test(command)) {
        console.log("💾 Audio download command detected");
        audioDownload(currentAudio);
        return true;
      }

      // === Filter commands ===
      // "filter tahun 2021"
      let m = command.match(/\bfilter\s+tahun\s+(20\d{2}|19\d{2})\b/);
      if (m) {
        console.log("📅 Year filter command detected:", m[1]);
        filterByYear(m[1]);
        return true;
      }

      // "filter [indicator text]"
      m = command.match(/\bfilter\s+(.+)\b/);
      if (m) {
        const indicatorText = m[1].trim();
        if (indicatorText && !/^tahun\s/.test(indicatorText)) {
          console.log("📊 Indicator filter command detected:", indicatorText);
          filterByIndicator(indicatorText);
          return true;
        }
      }

      // === Search commands ===
      m = command.match(/^(?:cari|cariin|carikan|carilah|pencarian)\s+(.+)/i);
      if (m) {
        const searchInput = document.querySelector("#search");
        const yearDropdown = document.querySelector("#year");
        const indicatorDropdown = document.querySelector("#indicator");

        let query = m[1].trim();

        // Extract year (4 digits)
        const yearHit = query.match(/\b(19|20)\d{2}\b/);
        let year = null;
        if (yearHit) {
          year = yearHit[0];
          query = query
            .replace(year, "")
            .replace(/\btahun\b/gi, "")
            .trim();
        }

        // Extract indicator by matching against dropdown options
        let indicatorFound = null;
        if (indicatorDropdown) {
          const qLower = query.toLowerCase();
          Array.from(indicatorDropdown.options).forEach((opt) => {
            const text = (opt.text || "").toLowerCase();
            if (text && qLower.includes(text)) {
              indicatorFound = opt.value;
            }
          });
          if (indicatorFound) {
            indicatorDropdown.value = indicatorFound;
            indicatorDropdown.dispatchEvent(new Event("change"));
            speak(
              `Filter indikator diatur ke ${
                indicatorDropdown.options[indicatorDropdown.selectedIndex].text
              }`
            );
            // Remove the indicator text from query to avoid duplication
            const chosenText = (
              indicatorDropdown.options[indicatorDropdown.selectedIndex].text ||
              ""
            ).toLowerCase();
            query = query
              .replace(new RegExp(chosenText, "gi"), "")
              .replace(/\bindikator\b/gi, "")
              .trim();
          }
        }

        // Apply year if found
        if (year && yearDropdown) {
          yearDropdown.value = year;
          yearDropdown.dispatchEvent(new Event("change"));
          speak(`Filter tahun diatur ke ${year}`);
        }

        // Apply remaining query to search box
        if (query && searchInput) {
          const currentValue = (searchInput.value || "").trim();
          if (!currentValue.toLowerCase().includes(query.toLowerCase())) {
            searchInput.value = [currentValue, query]
              .filter(Boolean)
              .join(" ")
              .trim();
            searchInput.dispatchEvent(new Event("input"));
          }
          speak(`Pencarian diatur ke ${searchInput.value}`);
        }

        // Sync to URL
        const params = new URLSearchParams(window.location.search);
        if (searchInput && searchInput.value)
          params.set("query", searchInput.value);
        if (yearDropdown && yearDropdown.value)
          params.set("year", yearDropdown.value);
        if (indicatorDropdown && indicatorDropdown.value)
          params.set("indicator", indicatorDropdown.value);

        window.location.search = params.toString();
        return true;
      }

      // === Information commands ===
      if (/^(berapa dokumen|jumlah dokumen|total dokumen)$/.test(command)) {
        console.log("📊 Document count command detected");
        getDocumentCount();
        return true;
      }

      // === Navigation commands ===
      if (/^(halaman selanjutnya|halaman berikutnya)$/.test(command)) {
        console.log("➡️ Next page command detected");
        nextPage();
        return true;
      }

      if (/^(halaman sebelumnya|page sebelumnya)$/.test(command)) {
        console.log("⬅️ Previous page command detected");
        previousPage();
        return true;
      }

      // === Filter management ===
      if (/^(reset filter|hapus filter|bersihkan filter)$/.test(command)) {
        console.log("🔄 Reset filter command detected");
        resetFilters();
        return true;
      }

      // === Help commands ===
      if (/^(bantuan|help|perintah|panduan)$/.test(command)) {
        console.log("❓ Help command detected");
        showVoiceHelp();
        return true;
      }

      console.log("❌ No matching command pattern found for:", command);
      return false;
    };

    let isVoiceSearchActive = false;
    let isWakeListeningActive = false;

    const hideModal = () => {
      const modal = document.getElementById("voice-search-modal");
      if (modal) modal.classList.add("hidden");
    };

    const openModal = () => {
      const path = window.location.pathname;

      // Only run on non-auth/admin pages

      if (
        path.includes("login") ||
        path.includes("register") ||
        path.includes("admin") ||
        path.includes("search") ||
        path.includes("publikasi") ||
        path.includes("brs")
      ) {
        return;
      }
      if (isVoiceSearchActive) {
        console.log("🔄 Voice search already active");
        return;
      }

      console.log("🎤 Opening voice search modal...");
      isVoiceSearchActive = true;

      try {
        window.speechSynthesis.cancel();
      } catch {}

      const modal = document.getElementById("voice-search-modal");
      if (modal) {
        modal.classList.remove("hidden");
      } else {
        console.warn("⚠️ Voice search modal not found");
        showVoiceIndicator();
      }

      if (wakeRec && isWakeListeningActive) {
        try {
          wakeRec.stop();
          isWakeListeningActive = false;
        } catch (error) {
          console.warn("⚠️ Error stopping wake recognition:", error);
        }
      }

      setTimeout(() => {
        if (!searchRec) {
          console.error("❌ Search recognition not initialized");
          resetVoiceSearch();
          return;
        }

        try {
          const state = searchRec.state || "inactive";
          console.log("🔍 Search recognition state:", state);

          if (state === "inactive") {
            searchRec.start();
            console.log("🎤 Search recognition started successfully");
          } else {
            console.log(
              "⚠️ Search recognition not ready, current state:",
              state
            );
            setTimeout(() => {
              try {
                if (
                  searchRec &&
                  (searchRec.state || "inactive") === "inactive"
                ) {
                  searchRec.start();
                  console.log("🎤 Search recognition started on retry");
                }
              } catch (retryError) {
                console.error("❌ Retry failed:", retryError);
                resetVoiceSearch();
              }
            }, 500);
          }
        } catch (error) {
          console.error("❌ Failed to start search recognition:", error);
          resetVoiceSearch();
        }
      }, 300);
    };

    const showVoiceIndicator = () => {
      const indicator = document.createElement("div");
      indicator.id = "voice-indicator";
      indicator.innerHTML = `
                <div style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); 
                           background: rgba(0,0,0,0.8); color: white; padding: 20px; border-radius: 10px; 
                           z-index: 9999; text-align: center;">
                    <div style="font-size: 18px; margin-bottom: 10px;">🎤 Listening...</div>
                    <div style="font-size: 14px;">Silakan sebutkan kata kunci pencarian</div>
                    <button onclick="this.parentElement.parentElement.remove(); window.stopVoiceSearch();" 
                            style="margin-top: 10px; padding: 5px 10px; cursor: pointer;">Stop</button>
                </div>
            `;
      document.body.appendChild(indicator);
    };

    const hideVoiceIndicator = () => {
      const indicator = document.getElementById("voice-indicator");
      if (indicator) indicator.remove();
    };

    const speak = (text, callback = null) => {
      try {
        window.speechSynthesis.cancel();

        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = "id-ID";
        utterance.rate = 1.1;
        utterance.volume = 0.8;

        if (callback) {
          utterance.onend = callback;
        }

        window.speechSynthesis.speak(utterance);
      } catch (error) {
        console.warn("⚠️ Speech synthesis error:", error);
        if (callback) callback();
      }
    };

    const resetVoiceSearch = () => {
      console.log("🔄 Resetting voice search state...");
      isVoiceSearchActive = false;
      hideModal();
      hideVoiceIndicator();

      try {
        if (searchRec && searchRec.state && searchRec.state !== "inactive") {
          searchRec.stop();
        }
      } catch (error) {
        console.warn("⚠️ Error stopping search recognition:", error);
      }

      try {
        if (wakeRec && wakeRec.state && wakeRec.state !== "inactive") {
          wakeRec.stop();
        }
        isWakeListeningActive = false;
      } catch (error) {
        console.warn("⚠️ Error stopping wake recognition:", error);
      }

      setTimeout(() => {
        restartWakeListening();
      }, 1000);
    };

    const restartWakeListening = () => {
      if (isVoiceSearchActive) {
        console.log("⏸️ Skipping wake restart - voice search active");
        return;
      }

      setTimeout(() => {
        if (!wakeRec) {
          console.error("❌ Wake recognition not initialized");
          return;
        }

        try {
          const state = wakeRec.state || "inactive";
          if (state === "inactive" && !isWakeListeningActive) {
            isWakeListeningActive = true;
            wakeRec.start();
            console.log("👂 Wake word listening restarted");
          } else {
            console.log(
              "⏸️ Wake restart skipped, state:",
              state,
              "active:",
              isWakeListeningActive
            );
          }
        } catch (error) {
          console.warn("⚠️ Failed to restart wake listening:", error);
          isWakeListeningActive = false;
        }
      }, 1000);
    };

    // Speech Recognition Setup
    let wakeRec = null;
    let searchRec = null;

    try {
      const SpeechRecognition =
        window.SpeechRecognition || window.webkitSpeechRecognition;

      wakeRec = new SpeechRecognition();
      wakeRec.continuous = true;
      wakeRec.interimResults = false;
      wakeRec.lang = "id-ID";

      searchRec = new SpeechRecognition();
      searchRec.continuous = false;
      searchRec.interimResults = false;
      searchRec.lang = "id-ID";

      console.log("🎤 Recognition objects created successfully");
    } catch (error) {
      console.error("❌ Failed to create recognition objects:", error);
      return;
    }

    window.commandRecognition = wakeRec;
    window.voiceRecognition = searchRec;

    // Search Recognition Events
    searchRec.onstart = () => {
      console.log("🎤 Search recognition started");
    };

    searchRec.onresult = function (event) {
      const rawQuery = event.results[0][0].transcript;
      const searchQuery = sanitizeQuery(rawQuery);

      console.log("🔍 Raw query:", rawQuery);
      console.log("🔍 Sanitized query:", searchQuery);

      isVoiceSearchActive = false;
      hideModal();
      hideVoiceIndicator();

      const searchUrl = searchRoute;
      speak(`Mencari ${searchQuery}`);

      try {
        fetch(searchUrl + `?query=${encodeURIComponent(searchQuery)}&voice=1`, {
          headers: { Accept: "application/json" },
        })
          .then((res) => res.json())
          .then((data) => {
            if (data.voiceMessage) {
              speak(data.voiceMessage);
            }
          })
          .catch((err) => {
            console.warn("⚠️ Voice feedback fetch error:", err);
          })
          .finally(() => {
            setTimeout(() => {
              window.location.href =
                searchUrl + `?query=${encodeURIComponent(searchQuery)}`;
            }, 500);
          });
      } catch (error) {
        console.error("❌ Search error:", error);
        window.location.href =
          searchUrl + `?query=${encodeURIComponent(searchQuery)}`;
      }
    };

    searchRec.onerror = function (event) {
      console.log("❌ Search recognition error:", event.error);
      isVoiceSearchActive = false;
      hideModal();
      hideVoiceIndicator();

      switch (event.error) {
        case "no-speech":
          speak("Tidak ada suara yang terdeteksi, silakan coba lagi");
          break;
        case "not-allowed":
          speak("Akses mikrofon diperlukan untuk pencarian suara");
          break;
        default:
          speak("Terjadi kesalahan, silakan coba lagi");
      }

      restartWakeListening();
    };

    searchRec.onend = function () {
      console.log("🎤 Search recognition ended");
      isVoiceSearchActive = false;
      hideModal();
      hideVoiceIndicator();
      restartWakeListening();
    };

    // Wake Word Recognition Events
    wakeRec.onstart = () => {
      isWakeListeningActive = true;
      console.log("👂 Wake word listening started");
    };

    wakeRec.onresult = function (event) {
      const command = event.results[event.results.length - 1][0].transcript
        .toLowerCase()
        .trim();
      console.log("👂 Wake word result:", command);

      // Daftar wake words
      const wakeWords = [
        "hai audio statistik",
        "hey audio statistik",
        "halo audio statistik",
        "audio statistik",
      ];

      // Frasa TTS/instruksi yang akan diabaikan
      const ignoredPhrases = [
        "selamat datang di audio statistik",
        "gunakan tombol spasi",
      ];

      // Abaikan jika command adalah instruksi TTS
      if (ignoredPhrases.some((p) => command.includes(p))) {
        console.log("ℹ️ Ignored TTS message:", command);
        return;
      }

      // Cek wake word
      let isWakeWord = wakeWords.some((wakeWord) =>
        command.startsWith(wakeWord)
      );
      if (isWakeWord) {
        console.log("✅ Wake word detected, activating search...");
        openModal();
        return;
      }

      // Process all other voice commands
      console.log("🔍 Processing voice command:", command);
      const commandProcessed = processVoiceCommand(command);

      if (commandProcessed) {
        console.log("✅ Command processed successfully");
        return;
      }

      // Fallback untuk command tak dikenal
      console.log("❌ Command not recognized:", command);
    };

    wakeRec.onerror = function (event) {
      console.log("👂 Wake word error:", event.error);
      isWakeListeningActive = false;

      setTimeout(() => {
        if (!isVoiceSearchActive && wakeRec?.state === "inactive") {
          try {
            isWakeListeningActive = true;
            wakeRec.start();
          } catch (error) {
            console.warn("⚠️ Failed to restart wake recognition:", error);
            isWakeListeningActive = false;
          }
        }
      }, 1000);
    };

    wakeRec.onend = () => {
      console.log("👂 Wake word listening ended");
      isWakeListeningActive = false;
      restartWakeListening();
    };

    // UI Event Handlers
    const triggerBtn = document.getElementById("voice-search-trigger");
    if (triggerBtn) {
      triggerBtn.addEventListener("click", openModal);
    }

    // Keyboard shortcut (Ctrl key)
    document.addEventListener("keydown", function (event) {
      const active = document.activeElement; // define 'active'
      const isTyping =
        active &&
        (active.tagName.toLowerCase() == "input" ||
          active.tagName.toLowerCase() == "textarea" ||
          active.isContentEditable);

      if (isTyping) return;
      if (
        event.code == "Space" &&
        !event.shiftKey &&
        !event.altKey &&
        !event.metaKey
      ) {
        event.preventDefault();

        if (isVoiceSearchActive) {
          console.log("🔄 Voice search already active, ignoring space press");
          return;
        }

        console.log("⌨️ Manual voice search activation");
        openModal();
      }
    });

    // Stop button in modal
    const stopBtn = document.getElementById("stop-listening");
    if (stopBtn) {
      stopBtn.addEventListener("click", function () {
        try {
          searchRec.stop();
        } catch {}
        hideModal();
        hideVoiceIndicator();
        restartWakeListening();
      });
    }

    // Modal click outside to close
    const modal = document.getElementById("voice-search-modal");
    if (modal) {
      modal.addEventListener("click", function (event) {
        if (event.target === modal) {
          try {
            searchRec.stop();
          } catch {}
          hideModal();
          restartWakeListening();
        }
      });
    }

    // Global Functions
    window.startVoiceSearch = openModal;
    window.stopVoiceSearch = () => {
      console.log("🛑 Stopping voice search...");
      resetVoiceSearch();
    };

    window.toggleVoiceSearch = () => {
      if (isVoiceSearchActive || isWakeListeningActive) {
        window.stopVoiceSearch();
      } else {
        openModal();
      }
    };

    window.resetVoiceSearch = resetVoiceSearch;

    // Make all functions globally accessible for debugging
    window.selectDocument = selectDocument;
    window.playDocument = playDocument;
    window.readDocuments = readDocuments;
    window.audioPlay = audioPlay;
    window.audioPause = audioPause;
    window.audioStop = audioStop;
    window.audioSeekForward = audioSeekForward;
    window.audioSeekBackward = audioSeekBackward;
    window.audioSpeedUp = audioSpeedUp;
    window.audioSpeedNormal = audioSpeedNormal;
    window.audioDownload = audioDownload;
    window.filterByYear = filterByYear;
    window.filterByIndicator = filterByIndicator;
    window.searchKeyword = searchKeyword;
    window.getDocumentCount = getDocumentCount;
    window.nextPage = nextPage;
    window.previousPage = previousPage;
    window.resetFilters = resetFilters;
    window.showVoiceHelp = showVoiceHelp;
    window.processVoiceCommand = processVoiceCommand;

    // Enhanced debugging functions
    // window.debugVoiceSearch = () => {
    //     console.group('🐛 Voice Search Debug Info');
    //     console.log('Browser support:', 'webkitSpeechRecognition' in window);
    //     console.log('Wake recognition:', wakeRec ? 'Available' : 'Not initialized');
    //     console.log('Search recognition:', searchRec ? 'Available' : 'Not initialized');
    //     console.log('Wake state:', wakeRec?.state || 'undefined');
    //     console.log('Search state:', searchRec?.state || 'undefined');
    //     console.log('isVoiceSearchActive:', isVoiceSearchActive);
    //     console.log('isWakeListeningActive:', isWakeListeningActive);
    //     console.log('Global recognition objects:');
    //     console.log('  - window.commandRecognition:', !!window.commandRecognition);
    //     console.log('  - window.voiceRecognition:', !!window.voiceRecognition);
    //     console.groupEnd();
    // };

    // window.debugDocumentButtons = () => {
    //     console.group('📄 Document Buttons Debug');

    //     const eyeButtons = document.querySelectorAll('.fa-eye, [title*="Lihat"], [title*="View"], .view-btn');
    //     console.log('Eye/View buttons found:', eyeButtons.length);
    //     eyeButtons.forEach((btn, index) => {
    //         console.log(`  ${index + 1}:`, btn.outerHTML.substring(0, 100));
    //     });

    //     const playButtons = document.querySelectorAll(
    //         '.play-document-btn, .fa-play, [title*="Play"], [title*="Putar"], .audio-play-btn, .play-btn'
    //     );
    //     console.log('Play buttons found:', playButtons.length);
    //     playButtons.forEach((btn, index) => {
    //         console.log(`  ${index + 1}:`, btn.outerHTML.substring(0, 100));
    //     });

    //     console.groupEnd();
    // };

    window.testDocumentCommand = (command) => {
      console.log(`🧪 Testing command: "${command}"`);
      return processVoiceCommand(command.toLowerCase().trim());
    };

    // window.testAllVoiceCommands = () => {
    //     console.group('🧪 Testing All Voice Commands');

    //     const testCommands = [
    //         'baca dokumen',
    //         'pilih dokumen nomor 1',
    //         'putar dokumen nomor 2',
    //         'play',
    //         'pause',
    //         'stop',
    //         'maju',
    //         'mundur',
    //         'percepat',
    //         'normal',
    //         'unduh',
    //         'filter tahun 2024',
    //         'filter indikator inflasi',
    //         'cari statistik',
    //         'halaman selanjutnya',
    //         'halaman sebelumnya',
    //         'berapa dokumen',
    //         'reset filter',
    //         'bantuan'
    //     ];

    //     testCommands.forEach(command => {
    //         console.log(`\n🔍 Testing: "${command}"`);
    //         const result = processVoiceCommand(command);
    //         console.log(`${result ? '✅ SUCCESS' : '❌ FAILED'} - "${command}"`);
    //     });

    //     console.groupEnd();
    // };

    window.simulateVoiceCommand = (command) => {
      console.log(`🎤 Simulating voice command: "${command}"`);
      const result = processVoiceCommand(command.toLowerCase().trim());
      console.log(`Result: ${result ? "SUCCESS" : "FAILED"}`);
      return result;
    };

    // window.showAvailableCommands = () => {
    //     console.group('📋 Available Voice Commands');
    //     console.log('🏠 Wake Words: "Hai/Hey/Halo Audio Statistik"');
    //     console.log('📄 Documents: "Baca dokumen", "Pilih/Buka/Lihat dokumen nomor [1-10]", "Putar/Play/Mainkan dokumen nomor [1-10]"');
    //     console.log('🎵 Audio Controls: "Play/Putar/Mainkan", "Pause/Jeda", "Stop/Berhenti", "Maju", "Mundur", "Percepat", "Normal", "Unduh/Download/Simpan"');
    //     console.log('🔍 Search: "Cari/Pencarian [keyword]"');
    //     console.log('📊 Filters: "Filter tahun [year]", "Filter indikator [indicator]"');
    //     console.log('📑 Navigation: "Halaman selanjutnya", "Halaman sebelumnya"');
    //     console.log('ℹ️ Information: "Berapa/Jumlah/Total dokumen"');
    //     console.log('🔧 Management: "Reset/Hapus/Bersihkan filter"');
    //     console.log('❓ Help: "Bantuan/Help/Perintah/Panduan"');
    //     console.groupEnd();
    // };

    window.forceRestartVoice = () => {
      console.log("🔄 Force restarting voice system...");
      resetVoiceSearch();
      setTimeout(() => {
        if (wakeRec) {
          try {
            isWakeListeningActive = true;
            wakeRec.start();
            console.log("✅ Force restart successful");
          } catch (error) {
            console.error("❌ Force restart failed:", error);
          }
        }
      }, 2000);
    };

    // Voice Coordinator Integration
    const voiceSearchInstance = {
      name: "voice-search",
      requestRecognition: () => {
        restartWakeListening();
        return true;
      },
      releaseRecognition: () => {
        try {
          wakeRec.stop();
          searchRec.stop();
        } catch {}
        return true;
      },
      forceStop: () => {
        window.stopVoiceSearch();
      },
      getPriority: () => 10,
    };

    if (window.AudioStatistik?.VoiceCoordinator) {
      window.AudioStatistik.VoiceCoordinator.register(
        "voice-search",
        voiceSearchInstance
      );
      console.log("✅ Registered with Voice Coordinator");
    }

    window.AudioStatistik.Voice.Search = voiceSearchInstance;

    // Start Wake Word Listening
    if (!wakeRec || !searchRec) {
      console.error("❌ Recognition objects not properly initialized");
      return;
    }

    try {
      isWakeListeningActive = true;
      wakeRec.start();
      console.log("✅ Enhanced Voice Search initialized successfully");

      document.dispatchEvent(
        new CustomEvent("voiceSearchReady", {
          detail: { instance: voiceSearchInstance },
        })
      );
    } catch (error) {
      console.error("❌ Failed to start wake word listening:", error);
      isWakeListeningActive = false;
    }
  }

  // Initialization
  function init() {
    const role = document.getElementById("role-user");
    const isEmpty =
      !role ||
      ("value" in role && String(role.value).trim() == "") ||
      (role.textContent && role.textContent == "");
    if (isEmpty) {
      const searchRoute = document.body.dataset.searchUrl || "/search";
      initializeEnhancedVoiceSearch(searchRoute);
    }
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    setTimeout(init, 100);
  }

  window.addEventListener("beforeunload", () => {
    if (window.stopVoiceSearch) {
      window.stopVoiceSearch();
    }

    window.__voiceInitDone = false;
  });

  console.log("✅ Enhanced Voice Search script loaded");

  document.addEventListener("DOMContentLoaded", function () {
    const role = document.getElementById("role-user");
    const isEmpty =
      !role ||
      ("value" in role && String(role.value).trim() == "") ||
      (role.textContent && role.textContent == "");
    if (isEmpty) {
      initializeEnhancedVoiceSearch();
    }
  });
})();
