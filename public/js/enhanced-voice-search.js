(function () {
    'use strict';

    // Namespace management
    window.AudioStatistik = window.AudioStatistik || {};
    window.AudioStatistik.Voice = window.AudioStatistik.Voice || {};

    // Prevent double initialization
    if (window.AudioStatistik.Voice.Search || window.__voiceInitDone) {
        // console.log('🔄 Enhanced Voice Search already initialized');
        return;
    }

    function initializeEnhancedVoiceSearch(searchRoute = '/search') {
        if (!('webkitSpeechRecognition' in window)) {
            // console.warn("⚠️ Browser tidak mendukung Web Speech API");
            return;
        }

        // Prevent double initialization
        if (window.__voiceInitDone) {
            // console.log('🔄 Voice search already initialized');
            return;
        }
        window.__voiceInitDone = true;

        // console.log('🎤 Initializing Enhanced Voice Search...');

        // Helper Functions
        const sanitizeQuery = (query) => {
            return query
                .trim()
                .replace(/[.,!?;:]+$/, '')
                .replace(/\s+/g, ' ')
                .toLowerCase()
                .replace(/^(carikan|carilah|dokumen|cariin|tahun|indikator|tolong|jenis dokumen|halo|hai|ya|saya|bps|ingin|mau|lihat|tampilkan|tampilin|cari|data|berikan|beriin|search|temukan)\s*/i, '')
                .trim();
        };

        const convertWordToNumber = (word) => {
            const numberMap = {
                'satu': 1, 'dua': 2, 'tiga': 3, 'empat': 4, 'lima': 5,
                'enam': 6, 'tujuh': 7, 'delapan': 8, 'sembilan': 9, 'sepuluh': 10
            };
            return numberMap[word.toLowerCase()] || null;
        };

        const selectDocument = (docNumber) => {
            try {
                const documents = document.querySelectorAll('#documents-grid [data-document-index]');
                const doc = documents[docNumber - 1];

                if (!doc) {
                    speak(`Dokumen nomor ${docNumber} tidak ditemukan`);
                    return;
                }

                // cari tombol view
                const viewButton = doc.querySelector('.fa-eye')?.closest('a, button');
                if (viewButton) {
                    viewButton.click();
                    // console.log(`✅ Opened document ${docNumber}`);
                } else {
                    speak(`Tombol lihat untuk dokumen nomor ${docNumber} tidak ditemukan`);
                }
            } catch (error) {
                // console.error('❌ Error selecting document:', error);
                speak(`Gagal membuka dokumen nomor ${docNumber}`);
            }
        };

        const playDocument = (docNumber) => {
            try {
                const documents = document.querySelectorAll('#documents-grid [data-document-index]');
                const doc = documents[docNumber - 1];

                if (!doc) {
                    speak(`Dokumen nomor ${docNumber} tidak ditemukan`);
                    return;
                }

                // cari tombol play
                const playButton = doc.querySelector('.play-document-btn');
                if (playButton) {
                    playButton.click();
                    // console.log(`✅ Playing document ${docNumber}`);
                } else {
                    speak(`Tombol putar untuk dokumen nomor ${docNumber} tidak ditemukan`);
                }
            } catch (error) {
                // console.error('❌ Error playing document:', error);
                speak(`Gagal memutar dokumen nomor ${docNumber}`);
            }
        };

        // Enhanced Helper Functions for Voice Commands
        const readDocuments = () => {
            const grid = document.querySelector('#documents-grid');
            if (!grid) return;

            const documents = grid.querySelectorAll('[data-document-index]');
            const countOnPage = documents.length;

            // Ambil total dokumen dari Blade
            const countElement = document.querySelector('.mb-6 p.text-gray-600');
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
                    const title = doc.querySelector('h3').innerText;
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
                const yearSelected = document.querySelector('#year').value;
                const indicatorSelected = document.querySelector('#indicator').value;

                let guideText = "Anda dapat mengatakan ";
                if (!yearSelected) guideText += "filter tahun diikuti tahun, atau ";
                if (!indicatorSelected) guideText += "filter indikator diikuti nama indikator, atau ";
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
        const target = document.getElementById('documents-grid');
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

        const audioPlay = () => {
            try {
                const audioElement = document.querySelector('#main-audio, audio, .audio-player audio');
                const playButton = document.querySelector('.play-pause-btn, .fa-play, .play-button, #play-btn');

                if (audioElement && audioElement.paused) {
                    speak('Audio diputar');
                    setTimeout(() => {
                        audioElement.play();
                    }, 2200);
                    // console.log('▶️ Audio playing');
                } else if (playButton) {
                    speak('Audio diputar');
                    playButton.click();
                    // console.log('▶️ Audio play button clicked');
                } else {
                    speak('Tidak ada audio yang dapat diputar');
                }
            } catch (error) {
                // console.error('❌ Error playing audio:', error);
                speak('Gagal memutar audio');
            }
        };

        const audioPause = () => {
            try {
                const audioElement = document.querySelector('#main-audio, audio, .audio-player audio');
                const pauseButton = document.querySelector('.play-pause-btn, .fa-pause, .pause-button, #pause-btn');

                if (audioElement && !audioElement.paused) {
                    audioElement.pause();
                    speak('Audio dijeda');
                    // console.log('⏸️ Audio paused');
                } else if (pauseButton) {
                    pauseButton.click();
                    speak('Audio dijeda');
                    // console.log('⏸️ Audio pause button clicked');
                } else {
                    speak('Tidak ada audio yang sedang diputar');
                }
            } catch (error) {
                // console.error('❌ Error pausing audio:', error);
                speak('Gagal menjeda audio');
            }
        };

        const audioStop = () => {
            try {
                const audioElement = document.querySelector('#main-audio, audio, .audio-player audio');
                if (audioElement) {
                    audioElement.pause();
                    audioElement.currentTime = 0;
                    speak('Audio dihentikan');
                    // console.log('⏹️ Audio stopped');
                } else {
                    speak('Tidak ada audio yang dapat dihentikan');
                }
            } catch (error) {
                // console.error('❌ Error stopping audio:', error);
                speak('Gagal menghentikan audio');
            }
        };

        const audioSeekForward = () => {
            try {
                const audioElement = document.querySelector('#main-audio, audio, .audio-player audio');
                if (audioElement) {
                    audioElement.currentTime = Math.min(audioElement.currentTime + 10, audioElement.duration);
                    speakWithoutInterrupt('Maju 10 detik', audioElement);
                    // console.log('⏩ Audio seeked forward 10s');
                } else {
                    speak('Tidak ada audio yang sedang diputar');
                }
            } catch (error) {
                // console.error('❌ Error seeking forward:', error);
                speak('Gagal memajukan audio');
            }
        };

        const audioSeekBackward = () => {
            try {
                const audioElement = document.querySelector('#main-audio, audio, .audio-player audio');
                if (audioElement) {
                    audioElement.currentTime = Math.max(audioElement.currentTime - 10, 0);
                    speakWithoutInterrupt('Mundur 10 detik', audioElement);
                    // console.log('⏪ Audio seeked backward 10s');
                } else {
                    speak('Tidak ada audio yang sedang diputar');
                }
            } catch (error) {
                // console.error('❌ Error seeking backward:', error);
                speak('Gagal memundurkan audio');
            }
        };

        const audioSpeedUp = () => {
            try {
                const audioElement = document.querySelector('#main-audio, audio, .audio-player audio');
                if (audioElement) {
                    audioElement.playbackRate = Math.min(audioElement.playbackRate + 0.25, 2.0);
                    speakWithoutInterrupt(`Kecepatan ${audioElement.playbackRate}x`, audioElement);
                    // console.log(`⚡ Audio speed: ${audioElement.playbackRate}x`);
                } else {
                    speak('Tidak ada audio yang sedang diputar');
                }
            } catch (error) {
                // console.error('❌ Error changing speed:', error);
                speak('Gagal mengubah kecepatan audio');
            }
        };

        const audioSpeedNormal = () => {
            try {
                const audioElement = document.querySelector('#main-audio, audio, .audio-player audio');
                if (audioElement) {
                    audioElement.playbackRate = 1.0;
                    speakWithoutInterrupt('Kecepatan normal', audioElement);
                    // console.log('🔄 Audio speed normalized');
                } else {
                    speak('Tidak ada audio yang sedang diputar');
                }
            } catch (error) {
                // console.error('❌ Error normalizing speed:', error);
                speak('Gagal menormalisasi kecepatan audio');
            }
        };

        const audioDownload = () => {
            try {
                const audioElement = document.querySelector('#main-audio, audio, .audio-player audio');
                const downloadButton = document.querySelector('.download-btn, .fa-download, [title*="Download"], [title*="Unduh"]');

                if (downloadButton) {
                    downloadButton.click();
                    speakWithoutInterrupt('Mengunduh audio', audioElement);
                    // console.log('💾 Audio download initiated');
                } else {
                    speak('Tombol unduh tidak ditemukan');
                }
            } catch (error) {
                // console.error('❌ Error downloading audio:', error);
                speak('Gagal mengunduh audio');
            }
        };

        const filterByYear = (year) => {
            try {
                const yearSelect = document.querySelector('select[name="year"], #year-filter, .year-select');
                if (yearSelect) {
                    yearSelect.value = year;
                    yearSelect.dispatchEvent(new Event('change'));
                    speak(`Filter tahun ${year} diterapkan`);
                    // console.log(`📅 Year filter applied: ${year}`);
                } else {
                    const url = new URL(window.location.href);
                    url.searchParams.set('year', year);
                    window.location.href = url.toString();
                }
            } catch (error) {
                // console.error('❌ Error filtering by year:', error);
                speak('Gagal menerapkan filter tahun');
            }
        };

        const filterByIndicator = (indicator) => {
            try {
                const indicatorSelect = document.querySelector('select[name="indicator"], #indicator-filter, .indicator-select');
                if (indicatorSelect) {
                    const options = indicatorSelect.querySelectorAll('option');
                    for (const option of options) {
                        if (option.textContent.toLowerCase().includes(indicator.toLowerCase())) {
                            indicatorSelect.value = option.value;
                            indicatorSelect.dispatchEvent(new Event('change'));
                            speak(`Filter indikator ${indicator} diterapkan`);
                            // console.log(`📊 Indicator filter applied: ${indicator}`);
                            return;
                        }
                    }
                    speak(`Indikator ${indicator} tidak ditemukan`);
                } else {
                    const url = new URL(window.location.href);
                    url.searchParams.set('indicator', indicator);
                    window.location.href = url.toString();
                }
            } catch (error) {
                // console.error('❌ Error filtering by indicator:', error);
                speak('Gagal menerapkan filter indikator');
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

                const searchInput = document.querySelector('input[name="query"], #search-input, .search-input');
                const yearDropdown = document.querySelector('#year');
                const indicatorDropdown = document.querySelector('#indicator');

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
                        yearDropdown.dispatchEvent(new Event('change'));
                        speak(`Filter tahun diatur ke ${year}`);
                        applied = true;
                    }
                    // hapus tahun dari query biar gak masuk searchbox
                    workingQuery = workingQuery.replace(year, "").replace(/\btahun\b/g, "").trim();
                }

                // --------------------------
                // 2. Deteksi Indikator
                // --------------------------
                if (indicatorDropdown) {
                    Array.from(indicatorDropdown.options).forEach(opt => {
                        if (workingQuery.includes(opt.text.toLowerCase())) {
                            indicatorFound = opt.value;
                        }
                    });

                    if (indicatorFound) {
                        indicatorDropdown.value = indicatorFound;
                        indicatorDropdown.dispatchEvent(new Event('change'));
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
                            if (!currentValue.toLowerCase().includes(workingQuery.toLowerCase())) {
                                searchInput.value = `${currentValue} ${workingQuery}`.trim();
                            }
                        } else {
                            searchInput.value = workingQuery;
                        }

                        searchInput.dispatchEvent(new Event('input'));
                        speak(`Pencarian diatur ke ${searchInput.value}`);
                        applied = true;
                    }
                }

                if (!applied) {
                    speak("Tidak ada filter yang diterapkan dari perintah Anda");
                }

            } catch (error) {
                // console.error("❌ Error in searchKeyword:", error);
                speak("Terjadi kesalahan saat mencari dokumen");
            }
        };

        const getDocumentCount = () => {
            try {
                const documents = document.querySelectorAll('.document-item, .publication-item, .brs-item, .search-result-item');
                const count = documents.length;
                speak(`Terdapat ${count} dokumen`);
                // console.log(`📊 Document count: ${count}`);
            } catch (error) {
                // console.error('❌ Error getting document count:', error);
                speak('Gagal menghitung dokumen');
            }
        };

        const nextPage = () => {
            try {
                const nextButton = document.querySelector('.pagination .next, .next-page, [aria-label*="Next"], .fa-chevron-right');
                if (nextButton && !nextButton.classList.contains('disabled')) {
                    nextButton.click();
                    speak('Ke halaman selanjutnya');
                    // console.log('➡️ Navigated to next page');
                } else {
                    speak('Tidak ada halaman selanjutnya');
                }
            } catch (error) {
                // console.error('❌ Error navigating to next page:', error);
                speak('Gagal ke halaman selanjutnya');
            }
        };

        const previousPage = () => {
            try {
                const url = new URL(window.location.href);
                const currentPage = parseInt(url.searchParams.get('page') || "1");

                if (currentPage > 1) {
                    // kalau lagi di halaman > 2, cukup kurangi 1
                    url.searchParams.set('page', currentPage - 1);

                    // khusus kalau targetnya halaman 1 → hapus param ?page
                    if (currentPage - 1 === 1) {
                        url.searchParams.delete('page');
                    }

                    window.location.href = url.toString();
                    speak('Ke halaman sebelumnya');
                    // console.log('⬅️ Navigated to previous page');
                } else {
                    speak('Sudah di halaman pertama');
                    // console.log('⚠️ Already at first page');
                }
            } catch (error) {
                // console.error('❌ Error navigating to previous page:', error);
                speak('Gagal ke halaman sebelumnya');
            }
        };

        const resetFilters = () => {
            try {
                const resetButton = document.querySelector('.reset-filter, .clear-filter, #reset-filters');
                const searchInput = document.querySelector('#search, input[name="query"], .search-input');

                if (resetButton) {
                    // klik tombol reset kalau ada
                    resetButton.click();
                    if (searchInput) {
                        searchInput.value = "";
                        searchInput.dispatchEvent(new Event("input"));
                    }
                    speak('Filter direset');
                    // console.log('🔄 Filters reset via button');
                } else {
                    // manual reset dari URL
                    const url = new URL(window.location.href);
                    url.searchParams.delete('year');
                    url.searchParams.delete('indicator');
                    url.searchParams.delete('type');
                    url.searchParams.delete('query'); // 🔥 hapus query juga

                    if (searchInput) {
                        searchInput.value = "";
                        searchInput.dispatchEvent(new Event("input"));
                    }

                    window.location.href = url.toString();
                    // console.log('🔄 Filters reset via URL');
                    speak('Filter direset');
                }
            } catch (error) {
                // console.error('❌ Error resetting filters:', error);
                speak('Gagal mereset filter');
            }
        };

        const showVoiceHelp = () => {
            const helpText = 'Perintah suara yang tersedia: ' +
                'Untuk pencarian, katakan "Hai Audio Statistik" kemudian sebutkan kata kunci. ' +
                'Untuk dokumen: "Baca dokumen", "Pilih dokumen nomor", "Putar dokumen nomor". ' +
                'Untuk audio: "Play/Putar", "Pause/Jeda", "Stop/Berhenti", "Maju", "Mundur", "Percepat", "Normal", "Unduh". ' +
                'Untuk filter: "Filter tahun", "Filter indikator", dan ucapkan "Reset Filter" untuk menghapus filter. ' +
                'Untuk navigasi: "Halaman selanjutnya", "Halaman sebelumnya". ' +
                'Untuk informasi: "Berapa dokumen", "Bantuan".';
            speak(helpText);
            // console.log('❓ Voice help provided');
        };

        // Voice Command Processor
        const processVoiceCommand = (command) => {
            // console.log('🔄 Processing command:', command);

            // Document selection commands
            const selectPatterns = [
                /pilih dokumen nomor\s*(\d+|satu|dua|tiga|empat|lima|enam|tujuh|delapan|sembilan|sepuluh)/,
                /pilih nomor\s*(\d+|satu|dua|tiga|empat|lima|enam|tujuh|delapan|sembilan|sepuluh)/
            ];

            for (const pattern of selectPatterns) {
                const match = command.match(pattern);
                if (match) {
                    const numStr = match[1];
                    const docNumber = isNaN(numStr) ? convertWordToNumber(numStr) : parseInt(numStr);
                    if (docNumber) {
                        // console.log(`📄 Document selection command: ${docNumber}`);
                        speak(`Membuka dokumen nomor ${docNumber}`, () => {
                            selectDocument(docNumber);
                            // 🆕 Tambahkan panduan setelah dokumen terbuka
                            speak('Dokumen telah terbuka. Anda dapat mengucapkan, "putar audio" untuk mendengarkan dokumen ini, ' +
                                'atau ucapkan, "unduh audio" untuk mengunduh file suara.'
                            );
                        });
                    }
                    return true;
                }
            }

            // === Commands after document is opened ===
            // Putar audio
            if (command.includes("putar audio")) {
                const playBtn = document.querySelector("button[onclick^='playDocumentAudio']");
                if (playBtn) {
                    speak("Memutar audio", () => playBtn.click());
                } else {
                    speak("Audio tidak tersedia untuk dokumen ini");
                }
                return true;
            }

            // Unduh audio (default MP3)
            if (command.includes("unduh audio") || command.includes("unduh mp3")) {
                const buttons = Array.from(document.querySelectorAll("a, button"));
                const downloadBtn = buttons.find(btn => btn.textContent.trim().toLowerCase().includes("unduh mp3"));

                if (downloadBtn) {
                    speak("Mengunduh audio", () => downloadBtn.click());
                } else {
                    speak("File audio tidak tersedia");
                }
                return true;
            }

            // Document playback commands
            const playPatterns = [
                /putar dokumen nomor\s*(\d+|satu|dua|tiga|empat|lima|enam|tujuh|delapan|sembilan|sepuluh)/,
                /putar nomor\s*(\d+|satu|dua|tiga|empat|lima|enam|tujuh|delapan|sembilan|sepuluh)/
            ];

            for (const pattern of playPatterns) {
                const match = command.match(pattern);
                if (match) {
                    const numStr = match[1];
                    const docNumber = isNaN(numStr) ? convertWordToNumber(numStr) : parseInt(numStr);
                    if (docNumber) {
                        // console.log(`▶️ Document playback command: ${docNumber}`);
                        speak(`Memutar dokumen nomor ${docNumber}`, () => {
                            playDocument(docNumber);
                        });
                    }
                    return true;
                }
            }

            // Audio player controls
            if (command.match(/^(play|putar|mainkan)$/)) {
                // console.log('▶️ Audio play command detected');
                audioPlay();
                return true;
            }

            if (command.match(/^(pause|jeda)$/)) {
                // console.log('⏸️ Audio pause command detected');
                audioPause();
                return true;
            }

            if (command.match(/^(stop|berhenti)$/)) {
                // console.log('⏹️ Audio stop command detected');
                audioStop();
                return true;
            }

            if (command.match(/^(maju)$/)) {
                // console.log('⏩ Audio seek forward command detected');
                audioSeekForward();
                return true;
            }

            if (command.match(/^(mundur)$/)) {
                // console.log('⏪ Audio seek backward command detected');
                audioSeekBackward();
                return true;
            }

            if (command.match(/^(percepat)$/)) {
                // console.log('⚡ Audio speed up command detected');
                audioSpeedUp();
                return true;
            }

            if (command.match(/^(normal)$/)) {
                // console.log('🔄 Audio normal speed command detected');
                audioSpeedNormal();
                return true;
            }

            if (command.match(/^(unduh|download|simpan)$/)) {
                // console.log('💾 Audio download command detected');
                audioDownload();
                return true;
            }

            // Filter commands
            const yearMatch = command.match(/^filter\s+tahun\s+(20\d{2})/);
            if (yearMatch) {
                // console.log('📅 Year filter command detected:', yearMatch[1]);
                filterByYear(yearMatch[1]);
                return true;
            }

            const indicatorMatch = command.match(/^filter\s+(.+)$/);
            if (indicatorMatch) {
                // console.log('📊 Indicator filter command detected:', indicatorMatch[1]);
                filterByIndicator(indicatorMatch[1]);
                return true;
            }

            // Search commands
            const searchMatch = command.match(/^(?:cari|cariin|carikan|carilah|pencarian)\s+(.+)/i);
            if (searchMatch) {
                const searchInput = document.querySelector('#search');
                const yearDropdown = document.querySelector('#year');
                const indicatorDropdown = document.querySelector('#indicator');

                let query = searchMatch[1].trim();

                // 🔎 Cari angka tahun (format 4 digit)
                const yearMatch = query.match(/\b(19|20)\d{2}\b/);
                let year = null;
                if (yearMatch) {
                    year = yearMatch[0];
                    query = query.replace(year, "").replace(/\btahun\b/gi, "").trim();
                }

                // 🔎 Cari indikator
                let indicatorFound = null;
                if (indicatorDropdown) {
                    Array.from(indicatorDropdown.options).forEach(opt => {
                        const text = opt.text.toLowerCase();
                        if (query.toLowerCase().includes(text)) {
                            indicatorFound = opt.value;
                            query = query.replace(new RegExp(text, "gi"), "").replace(/\bindikator\b/gi, "").trim();
                        }
                    });
                }

                // ⚙️ Terapkan indikator kalau ketemu
                if (indicatorFound) {
                    indicatorDropdown.value = indicatorFound;
                    indicatorDropdown.dispatchEvent(new Event('change'));
                    speak(`Filter indikator diatur ke ${indicatorDropdown.options[indicatorDropdown.selectedIndex].text}`);
                }

                // ⚙️ Terapkan tahun kalau ketemu
                if (year) {
                    yearDropdown.value = year;
                    yearDropdown.dispatchEvent(new Event('change'));
                    speak(`Filter tahun diatur ke ${year}`);
                }

                // ⚙️ Kalau masih ada query → masukin ke searchbox
                if (query) {
                    if (searchInput) {
                        let currentValue = searchInput.value.trim();

                        // kalau search box sudah ada isi → gabungin
                        if (currentValue) {
                            // biar gak double kata
                            if (!currentValue.toLowerCase().includes(query.toLowerCase())) {
                                searchInput.value = `${currentValue} ${query}`.trim();
                            }
                        } else {
                            searchInput.value = query;
                        }

                        searchInput.dispatchEvent(new Event('input'));
                    }
                    speak(`Pencarian diatur ke ${searchInput.value}`);
                }

                // 🚀 Gabungkan semua filter ke URL (prioritas ke backend)
                const params = new URLSearchParams(window.location.search);
                if (searchInput && searchInput.value) params.set("query", searchInput.value);
                if (yearDropdown && yearDropdown.value) params.set("year", yearDropdown.value);
                if (indicatorDropdown && indicatorDropdown.value) params.set("indicator", indicatorDropdown.value);

                window.location.search = params.toString();
            }

            // Information commands
            if (command.match(/^(berapa dokumen|jumlah dokumen|total dokumen)/)) {
                // console.log('📊 Document count command detected');
                getDocumentCount();
                return true;
            }

            // Navigation commands
            if (command.match(/^(halaman selanjutnya|halaman berikutnya)/)) {
                // console.log('➡️ Next page command detected');
                nextPage();
                return true;
            }

            if (command.match(/^(halaman sebelumnya|page sebelumnya)/)) {
                // console.log('⬅️ Previous page command detected');
                previousPage();
                return true;
            }

            // Filter management
            if (command.match(/^(reset filter|hapus filter|bersihkan filter)/)) {
                // console.log('🔄 Reset filter command detected');
                resetFilters();
                return true;
            }

            // Help commands
            if (command.match(/^(bantuan|help|perintah|panduan)/)) {
                // console.log('❓ Help command detected');
                showVoiceHelp();
                return true;
            }

            // console.log('❌ No matching command pattern found for:', command);
            return false;
        };

        let isVoiceSearchActive = false;
        let isWakeListeningActive = false;

        const hideModal = () => {
            const modal = document.getElementById('voice-search-modal');
            if (modal) modal.classList.add('hidden');
        };

        const openModal = () => {
            if (isVoiceSearchActive) {
                // console.log('🔄 Voice search already active');
                return;
            }

            // console.log('🎤 Opening voice search modal...');
            isVoiceSearchActive = true;

            try {
                window.speechSynthesis.cancel();
            } catch { }

            const modal = document.getElementById('voice-search-modal');
            if (modal) {
                modal.classList.remove('hidden');
            } else {
                // console.warn('⚠️ Voice search modal not found');
                showVoiceIndicator();
            }

            if (wakeRec && isWakeListeningActive) {
                try {
                    wakeRec.stop();
                    isWakeListeningActive = false;
                } catch (error) {
                    // console.warn('⚠️ Error stopping wake recognition:', error);
                }
            }

            setTimeout(() => {
                if (!searchRec) {
                    // console.error('❌ Search recognition not initialized');
                    resetVoiceSearch();
                    return;
                }

                try {
                    const state = searchRec.state || 'inactive';
                    // console.log('🔍 Search recognition state:', state);

                    if (state === 'inactive') {
                        searchRec.start();
                        // console.log('🎤 Search recognition started successfully');
                    } else {
                        // console.log('⚠️ Search recognition not ready, current state:', state);
                        setTimeout(() => {
                            try {
                                if (searchRec && (searchRec.state || 'inactive') === 'inactive') {
                                    searchRec.start();
                                    // console.log('🎤 Search recognition started on retry');
                                }
                            } catch (retryError) {
                                // console.error('❌ Retry failed:', retryError);
                                resetVoiceSearch();
                            }
                        }, 500);
                    }
                } catch (error) {
                    // console.error('❌ Failed to start search recognition:', error);
                    resetVoiceSearch();
                }
            }, 300);
        };

        const showVoiceIndicator = () => {
            const indicator = document.createElement('div');
            indicator.id = 'voice-indicator';
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
            const indicator = document.getElementById('voice-indicator');
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
                // console.warn("⚠️ Speech synthesis error:", error);
                if (callback) callback();
            }
        };

        const resetVoiceSearch = () => {
            // console.log('🔄 Resetting voice search state...');
            isVoiceSearchActive = false;
            hideModal();
            hideVoiceIndicator();

            try {
                if (searchRec && searchRec.state && searchRec.state !== 'inactive') {
                    searchRec.stop();
                }
            } catch (error) {
                // console.warn('⚠️ Error stopping search recognition:', error);
            }

            try {
                if (wakeRec && wakeRec.state && wakeRec.state !== 'inactive') {
                    wakeRec.stop();
                }
                isWakeListeningActive = false;
            } catch (error) {
                // console.warn('⚠️ Error stopping wake recognition:', error);
            }

            setTimeout(() => {
                restartWakeListening();
            }, 1000);
        };

        const restartWakeListening = () => {
            if (isVoiceSearchActive) {
                // console.log('⏸️ Skipping wake restart - voice search active');
                return;
            }

            setTimeout(() => {
                if (!wakeRec) {
                    // console.error('❌ Wake recognition not initialized');
                    return;
                }

                try {
                    const state = wakeRec.state || 'inactive';
                    if (state === 'inactive' && !isWakeListeningActive) {
                        isWakeListeningActive = true;
                        wakeRec.start();
                        // console.log('👂 Wake word listening restarted');
                    } else {
                        // console.log('⏸️ Wake restart skipped, state:', state, 'active:', isWakeListeningActive);
                    }
                } catch (error) {
                    // console.warn('⚠️ Failed to restart wake listening:', error);
                    isWakeListeningActive = false;
                }
            }, 1000);
        };

        // Speech Recognition Setup
        let wakeRec = null;
        let searchRec = null;

        try {
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

            wakeRec = new SpeechRecognition();
            wakeRec.continuous = true;
            wakeRec.interimResults = false;
            wakeRec.lang = 'id-ID';

            searchRec = new SpeechRecognition();
            searchRec.continuous = false;
            searchRec.interimResults = false;
            searchRec.lang = 'id-ID';

            // console.log('🎤 Recognition objects created successfully');
        } catch (error) {
            // console.error('❌ Failed to create recognition objects:', error);
            return;
        }

        window.commandRecognition = wakeRec;
        window.voiceRecognition = searchRec;

        // Search Recognition Events
        searchRec.onstart = () => {
            // console.log('🎤 Search recognition started');
        };

        searchRec.onresult = function (event) {
            const rawQuery = event.results[0][0].transcript;
            const searchQuery = sanitizeQuery(rawQuery);

            // console.log('🔍 Raw query:', rawQuery);
            // console.log('🔍 Sanitized query:', searchQuery);

            isVoiceSearchActive = false;
            hideModal();
            hideVoiceIndicator();

            const searchUrl = searchRoute;
            speak(`Mencari ${searchQuery}`);

            try {
                fetch(searchUrl + `?query=${encodeURIComponent(searchQuery)}&voice=1`, {
                    headers: { "Accept": "application/json" }
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.voiceMessage) {
                            speak(data.voiceMessage);
                        }
                    })
                    .catch(err => {
                        // console.warn('⚠️ Voice feedback fetch error:', err);
                    })
                    .finally(() => {
                        setTimeout(() => {
                            window.location.href = searchUrl + `?query=${encodeURIComponent(searchQuery)}`;
                        }, 500);
                    });
            } catch (error) {
                // console.error('❌ Search error:', error);
                window.location.href = searchUrl + `?query=${encodeURIComponent(searchQuery)}`;
            }
        };

        searchRec.onerror = function (event) {
            // console.log('❌ Search recognition error:', event.error);
            isVoiceSearchActive = false;
            hideModal();
            hideVoiceIndicator();

            switch (event.error) {
                case 'no-speech':
                    speak('Tidak ada suara yang terdeteksi, silakan coba lagi');
                    break;
                case 'not-allowed':
                    speak('Akses mikrofon diperlukan untuk pencarian suara');
                    break;
                default:
                    speak('Terjadi kesalahan, silakan coba lagi');
            }

            restartWakeListening();
        };

        searchRec.onend = function () {
            // console.log('🎤 Search recognition ended');
            isVoiceSearchActive = false;
            hideModal();
            hideVoiceIndicator();
            restartWakeListening();
        };

        // Wake Word Recognition Events
        wakeRec.onstart = () => {
            isWakeListeningActive = true;
            // console.log('👂 Wake word listening started');
        };

        wakeRec.onresult = function (event) {
            const command = event.results[event.results.length - 1][0].transcript
                .toLowerCase()
                .trim();
            // console.log('👂 Wake word result:', command);

            // Daftar wake words
            const wakeWords = [
                'hai audio statistik',
                'hey audio statistik',
                'halo audio statistik',
                'audio statistik'
            ];

            // Frasa TTS/instruksi yang akan diabaikan
            const ignoredPhrases = [
                'selamat datang di audio statistik',
                'gunakan tombol ctrl'
            ];

            // Abaikan jika command adalah instruksi TTS
            if (ignoredPhrases.some(p => command.includes(p))) {
                // console.log('ℹ️ Ignored TTS message:', command);
                return;
            }

            // Cek wake word
            let isWakeWord = wakeWords.some(wakeWord => command.startsWith(wakeWord));
            if (isWakeWord) {
                // console.log('✅ Wake word detected, activating search...');
                openModal();
                return;
            }

            // Process all other voice commands
            // console.log('🔍 Processing voice command:', command);
            const commandProcessed = processVoiceCommand(command);

            if (commandProcessed) {
                // console.log('✅ Command processed successfully');
                return;
            }

            // Fallback untuk command tak dikenal
            // console.log('❌ Command not recognized:', command);
        };

        wakeRec.onerror = function (event) {
            // console.log('👂 Wake word error:', event.error);
            isWakeListeningActive = false;

            setTimeout(() => {
                if (!isVoiceSearchActive && wakeRec?.state === 'inactive') {
                    try {
                        isWakeListeningActive = true;
                        wakeRec.start();
                    } catch (error) {
                        // console.warn('⚠️ Failed to restart wake recognition:', error);
                        isWakeListeningActive = false;
                    }
                }
            }, 1000);
        };

        wakeRec.onend = () => {
            // console.log('👂 Wake word listening ended');
            isWakeListeningActive = false;
            restartWakeListening();
        };

        // UI Event Handlers
        const triggerBtn = document.getElementById('voice-search-trigger');
        if (triggerBtn) {
            triggerBtn.addEventListener('click', openModal);
        }

        // Keyboard shortcut (Ctrl key)
        document.addEventListener('keydown', function (event) {
            if (event.ctrlKey && !event.shiftKey && !event.altKey && !event.metaKey) {
                event.preventDefault();

                if (isVoiceSearchActive) {
                    // console.log('🔄 Voice search already active, ignoring Ctrl press');
                    return;
                }

                // console.log('⌨️ Manual voice search activation');
                openModal();
            }
        });

        // Stop button in modal
        const stopBtn = document.getElementById('stop-listening');
        if (stopBtn) {
            stopBtn.addEventListener('click', function () {
                try {
                    searchRec.stop();
                } catch { }
                hideModal();
                hideVoiceIndicator();
                restartWakeListening();
            });
        }

        // Modal click outside to close
        const modal = document.getElementById('voice-search-modal');
        if (modal) {
            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    try {
                        searchRec.stop();
                    } catch { }
                    hideModal();
                    restartWakeListening();
                }
            });
        }

        // Global Functions
        window.startVoiceSearch = openModal;
        window.stopVoiceSearch = () => {
            // console.log('🛑 Stopping voice search...');
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
        //     // console.log('Browser support:', 'webkitSpeechRecognition' in window);
        //     // console.log('Wake recognition:', wakeRec ? 'Available' : 'Not initialized');
        //     // console.log('Search recognition:', searchRec ? 'Available' : 'Not initialized');
        //     // console.log('Wake state:', wakeRec?.state || 'undefined');
        //     // console.log('Search state:', searchRec?.state || 'undefined');
        //     // console.log('isVoiceSearchActive:', isVoiceSearchActive);
        //     // console.log('isWakeListeningActive:', isWakeListeningActive);
        //     // console.log('Global recognition objects:');
        //     // console.log('  - window.commandRecognition:', !!window.commandRecognition);
        //     // console.log('  - window.voiceRecognition:', !!window.voiceRecognition);
        //     console.groupEnd();
        // };

        // window.debugDocumentButtons = () => {
        //     console.group('📄 Document Buttons Debug');

        //     const eyeButtons = document.querySelectorAll('.fa-eye, [title*="Lihat"], [title*="View"], .view-btn');
        //     // console.log('Eye/View buttons found:', eyeButtons.length);
        //     eyeButtons.forEach((btn, index) => {
        //         // console.log(`  ${index + 1}:`, btn.outerHTML.substring(0, 100));
        //     });

        //     const playButtons = document.querySelectorAll(
        //         '.play-document-btn, .fa-play, [title*="Play"], [title*="Putar"], .audio-play-btn, .play-btn'
        //     );
        //     // console.log('Play buttons found:', playButtons.length);
        //     playButtons.forEach((btn, index) => {
        //         // console.log(`  ${index + 1}:`, btn.outerHTML.substring(0, 100));
        //     });

        //     console.groupEnd();
        // };

        window.testDocumentCommand = (command) => {
            // console.log(`🧪 Testing command: "${command}"`);
            return processVoiceCommand(command.toLowerCase().trim());
        };

        // window.testAllVoiceCommands = () => {
        //     // console.group('🧪 Testing All Voice Commands');

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
        //         // console.log(`\n🔍 Testing: "${command}"`);
        //         const result = processVoiceCommand(command);
        //         // console.log(`${result ? '✅ SUCCESS' : '❌ FAILED'} - "${command}"`);
        //     });

        //     // console.groupEnd();
        // };

        window.simulateVoiceCommand = (command) => {
            // console.log(`🎤 Simulating voice command: "${command}"`);
            const result = processVoiceCommand(command.toLowerCase().trim());
            // console.log(`Result: ${result ? 'SUCCESS' : 'FAILED'}`);
            return result;
        };

        // window.showAvailableCommands = () => {
        //     console.group('📋 Available Voice Commands');
        //     // console.log('🏠 Wake Words: "Hai/Hey/Halo Audio Statistik"');
        //     // console.log('📄 Documents: "Baca dokumen", "Pilih/Buka/Lihat dokumen nomor [1-10]", "Putar/Play/Mainkan dokumen nomor [1-10]"');
        //     // console.log('🎵 Audio Controls: "Play/Putar/Mainkan", "Pause/Jeda", "Stop/Berhenti", "Maju", "Mundur", "Percepat", "Normal", "Unduh/Download/Simpan"');
        //     // console.log('🔍 Search: "Cari/Pencarian [keyword]"');
        //     // console.log('📊 Filters: "Filter tahun [year]", "Filter indikator [indicator]"');
        //     // console.log('📑 Navigation: "Halaman selanjutnya", "Halaman sebelumnya"');
        //     // console.log('ℹ️ Information: "Berapa/Jumlah/Total dokumen"');
        //     // console.log('🔧 Management: "Reset/Hapus/Bersihkan filter"');
        //     // console.log('❓ Help: "Bantuan/Help/Perintah/Panduan"');
        //     console.groupEnd();
        // };

        window.forceRestartVoice = () => {
            // console.log('🔄 Force restarting voice system...');
            resetVoiceSearch();
            setTimeout(() => {
                if (wakeRec) {
                    try {
                        isWakeListeningActive = true;
                        wakeRec.start();
                        // console.log('✅ Force restart successful');
                    } catch (error) {
                        // console.error('❌ Force restart failed:', error);
                    }
                }
            }, 2000);
        };

        // Voice Coordinator Integration
        const voiceSearchInstance = {
            name: 'voice-search',
            requestRecognition: () => {
                restartWakeListening();
                return true;
            },
            releaseRecognition: () => {
                try {
                    wakeRec.stop();
                    searchRec.stop();
                } catch { }
                return true;
            },
            forceStop: () => {
                window.stopVoiceSearch();
            },
            getPriority: () => 10
        };

        if (window.AudioStatistik?.VoiceCoordinator) {
            window.AudioStatistik.VoiceCoordinator.register('voice-search', voiceSearchInstance);
            // console.log('✅ Registered with Voice Coordinator');
        }

        window.AudioStatistik.Voice.Search = voiceSearchInstance;

        // Start Wake Word Listening
        if (!wakeRec || !searchRec) {
            // console.error('❌ Recognition objects not properly initialized');
            return;
        }

        try {
            isWakeListeningActive = true;
            wakeRec.start();
            // console.log('✅ Enhanced Voice Search initialized successfully');

            document.dispatchEvent(new CustomEvent('voiceSearchReady', {
                detail: { instance: voiceSearchInstance }
            }));

        } catch (error) {
            // console.error('❌ Failed to start wake word listening:', error);
            isWakeListeningActive = false;
        }
    }

    // Initialization
    function init() {
        const role = document.getElementById('role-user')
        if (role.textContent != 'admin') {
            const searchRoute = document.body.dataset.searchUrl || '/search';
            initializeEnhancedVoiceSearch(searchRoute);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        setTimeout(init, 100);
    }

    window.addEventListener('beforeunload', () => {
        if (window.stopVoiceSearch) {
            window.stopVoiceSearch();
        }

        window.__voiceInitDone = false;
    });

    // console.log('✅ Enhanced Voice Search script loaded');

    document.addEventListener('DOMContentLoaded', function () {
        const role = document.getElementById('role-user')
        if (role.textContent != 'admin') {
            initializeEnhancedVoiceSearch()
        }
    });
})();