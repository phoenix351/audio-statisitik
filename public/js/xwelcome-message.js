(function () {
    'use strict';

    // Config
    const SESSION_KEY = 'audiostatistik_welcomed';
    const WELCOME_TEXT = 'Selamat datang di Audio Statistik, portal audio untuk publikasi dan berita resmi statistik BPS Sulawesi Utara. ' +
        'Gunakan tombol Ctrl untuk pencarian suara, atau katakan "Hai Audio Statistik".';
    const VOICE_LANG = 'id-ID';
    const VOICE_RATE = 1.0;
    const VOICE_VOLUME = 1.0;
    const VOICE_PITCH = 1.0;
    const AFTER_PLAY_DELAY = 800; // ms, delay before requesting voice-search priority after welcome ends
    const START_CHECK_TIMEOUT = 1200; // ms, jika tidak start dalam waktu ini -> asumsikan diblokir

    function pickFemaleVoice(lang = 'id-ID') {
        const voices = speechSynthesis.getVoices();
        // Cari voice perempuan dengan bahasa Indonesia
        let female = voices.find(v => v.lang === lang && /female/i.test(v.name));
        if (!female) {
            // fallback: cari voice apa saja dengan lang id-ID
            female = voices.find(v => v.lang === lang);
        }
        return female || null;
    }

    // Utility
    function isHomePage() {
        const path = window.location.pathname;
        // adjust according to your routing: this matches '/', '/home', or path that ends with '/'
        return path === '/' || path === '/home' || path.endsWith('/');
    }

    function waitForVoiceCoordinator(cb) {
        if (window.AudioStatistik?.VoiceCoordinator) return cb();
        // poll until available
        const t = setInterval(() => {
            if (window.AudioStatistik?.VoiceCoordinator) {
                clearInterval(t);
                cb();
            }
        }, 300);
        // after 5s give up (still call cb so welcome can try)
        setTimeout(() => {
            clearInterval(t);
            cb();
        }, 5000);
    }

    function waitForVoices(cb) {
        try {
            const sv = window.speechSynthesis;
            if (!sv) return cb();
            const voices = sv.getVoices();
            if (voices && voices.length > 0) return cb();
            sv.addEventListener('voiceschanged', function onv() {
                sv.removeEventListener('voiceschanged', onv);
                cb();
            }, { once: true });
            // fallback timeout
            setTimeout(cb, 1000);
        } catch (e) {
            cb();
        }
    }

    // Play flow
    let gestureListenerAttached = false;
    let attemptInProgress = false;

    function playWelcomeOnce() {
        // mark in session only when playback actually starts (onstart) or after gesture-triggered play
        if (sessionStorage.getItem(SESSION_KEY) === 'true') {
            return;
        }

        // If speechSynthesis not supported, mark and return
        if (!('speechSynthesis' in window)) {
            console.warn('[Welcome] SpeechSynthesis not supported, skipping welcome.');
            sessionStorage.setItem(SESSION_KEY, 'true');
            return;
        }

        // Prevent concurrent attempts
        if (attemptInProgress) return;
        attemptInProgress = true;

        // Try to request voice coordinator priority (if present)
        if (window.AudioStatistik?.VoiceCoordinator?.requestWithPriority) {
            try {
                window.AudioStatistik.VoiceCoordinator.requestWithPriority('welcome-message');
            } catch (e) { /* ignore */ }
        }

        let played = false;
        let started = false;
        let timeoutId = null;

        const utter = new SpeechSynthesisUtterance(WELCOME_TEXT);
        utter.lang = VOICE_LANG;
        utter.rate = VOICE_RATE;
        utter.volume = VOICE_VOLUME;
        utter.pitch = VOICE_PITCH;

        const femaleVoice = pickFemaleVoice(VOICE_LANG);
        if (femaleVoice) {
            utter.voice = femaleVoice;
            console.log('[Welcome] using voice:', femaleVoice.name);
        }

        utter.onstart = () => {
            started = true;
            played = true;
            console.log('[Welcome] started');
            // mark session immediately so it won't retry in same session
            try { sessionStorage.setItem(SESSION_KEY, 'true'); } catch (e) {}
            // cleanup gesture if attached
            removeGestureListener();
        };

        utter.onend = () => {
            console.log('[Welcome] ended');
            // release coordinator if available
            try {
                if (window.AudioStatistik?.VoiceCoordinator?.releaseRecognition) {
                    window.AudioStatistik.VoiceCoordinator.releaseRecognition('welcome-message');
                }
                // small delay then request voice-search priority
                setTimeout(() => {
                    if (window.AudioStatistik?.VoiceCoordinator?.requestWithPriority) {
                        window.AudioStatistik.VoiceCoordinator.requestWithPriority('voice-search');
                    }
                }, AFTER_PLAY_DELAY);
            } catch (e) { /* ignore */ }
            attemptInProgress = false;
            clearTimeoutIfAny();
        };

        utter.onerror = (ev) => {
            console.error('[Welcome] error:', ev && ev.error ? ev.error : ev);
            attemptInProgress = false;
            clearTimeoutIfAny();
            // If blocked (common error is 'not-allowed'), attach gesture fallback
            attachGestureListenerIfNeeded();
        };

        function clearTimeoutIfAny() {
            if (timeoutId) {
                clearTimeout(timeoutId);
                timeoutId = null;
            }
        }

        // After attempt to speak, wait short time; if not started then likely blocked -> wait for gesture
        function scheduleStartCheck() {
            timeoutId = setTimeout(() => {
                if (!started && !window.speechSynthesis.speaking) {
                    console.warn('[Welcome] start not detected — likely blocked by autoplay policy. Waiting for first user gesture.');
                    attemptInProgress = false;
                    attachGestureListenerIfNeeded();
                }
            }, START_CHECK_TIMEOUT);
        }

        // Perform speak attempt
        try {
            waitForVoices(() => {
                try {
                    // cancel previous queued speeches to avoid overlap
                    window.speechSynthesis.cancel();
                    window.speechSynthesis.speak(utter);
                    scheduleStartCheck();
                } catch (e) {
                    console.error('[Welcome] speak() threw:', e);
                    attemptInProgress = false;
                    attachGestureListenerIfNeeded();
                }
            });
        } catch (e) {
            console.error('[Welcome] error preparing speak:', e);
            attemptInProgress = false;
            attachGestureListenerIfNeeded();
        }
    }

    function attachGestureListenerIfNeeded() {
        if (gestureListenerAttached) return;
        gestureListenerAttached = true;

        const handler = function onFirstGesture() {
            try {
                // Play now (this call will set sessionStorage when onstart fires)
                playWelcomeOnce();
            } catch (e) {
                console.error('[Welcome] gesture play failed:', e);
                // still mark to avoid infinite retries
                try { sessionStorage.setItem(SESSION_KEY, 'true'); } catch (err) {}
            } finally {
                // remove listener (once)
                document.removeEventListener('pointerdown', onFirstGesture, { once: true });
                gestureListenerAttached = false;
            }
        };

        // Use pointerdown so it captures touch/click
        document.addEventListener('pointerdown', handler, { once: true, passive: true });
        // also add keydown for keyboard users
        document.addEventListener('keydown', function onceKey() {
            // remove both handlers and trigger play
            document.removeEventListener('pointerdown', handler, { once: true });
            try { playWelcomeOnce(); } catch (e) {}
            document.removeEventListener('keydown', onceKey);
            gestureListenerAttached = false;
        });
    }

    // Public helper to force show (useful for debug)
    function forceShowWelcome() {
        try { sessionStorage.removeItem(SESSION_KEY); } catch (e) {}
        playWelcomeOnce();
    }

    // Init on DOMContentLoaded, only on home and only if not already shown this session
    document.addEventListener('DOMContentLoaded', function () {
        try {
            if (!isHomePage()) return;
            // if already shown this session -> do nothing
            if (sessionStorage.getItem(SESSION_KEY) === 'true') return;

            // Wait briefly for voice coordinator then attempt play
            waitForVoiceCoordinator(() => {
                // try immediate play; if blocked, fallback will attach listener
                playWelcomeOnce();
            });

            // expose debug API
            window.forceShowWelcome = forceShowWelcome;

        } catch (e) {
            console.error('[Welcome] initialization failed:', e);
        }
    });

})();

