// Auto-extracted from app1.js
export class UniversalHoverTextManager {
    constructor() {
      this.textSoundEnabled = true;
      this._textSelector =
        ".text-sound, .hover-sound, p, h1, h2, h3, h4, h5, h6, span, div, label, button, a, td, th, li";
      this._lastSpoken = ""; // untuk mencegah pengulangan cepat
      this._hoverTimers = new WeakMap();
      this._hoverDelay = 300; // delay sebelum baca
      this._hoverCooldownMs = 3000; // cooldown default 3 detik
    }

    async init() {
      this.addHoverSpeechToTextElements();
      this.setupToggleShortcut();
    }

    addHoverSpeechToTextElements() {
      const textElements = document.querySelectorAll(this._textSelector);

      textElements.forEach((element) => {
        const text = element.textContent || "";
        if (!text.trim()) return;
        if (element.dataset._hoverSpeechAttached === "true") return;

        const manager = this;

        function enterHandler() {
          if (!manager.textSoundEnabled) return;

          const txt = element.textContent.trim();
          if (txt.length < 2 || txt.length > 200) return;

          // jangan bicara lagi kalau masih cooldown
          if (element.dataset._hoverCooldown === "true") return;

          // buat timer baca dengan delay
          const timer = setTimeout(() => {
            // skip kalau teks sama dengan terakhir
            if (manager._lastSpoken === txt) return;
            manager._lastSpoken = txt;

            if ("speechSynthesis" in window) {
              const utterance = new SpeechSynthesisUtterance(txt);
              utterance.lang = "id-ID";
              utterance.rate = 1.4;
              utterance.volume = 1;
              window.speechSynthesis.cancel();
              window.speechSynthesis.speak(utterance);

              // pasang cooldown 3 detik
              element.dataset._hoverCooldown = "true";
              setTimeout(() => {
                element.dataset._hoverCooldown = "false";
              }, 3000);
            }
          }, manager._hoverDelay);

          manager._hoverTimers.set(element, timer);
        }

        function leaveHandler() {
          // batalkan timer kalau user keluar sebelum delay habis
          const timer = manager._hoverTimers.get(element);
          if (timer) {
            clearTimeout(timer);
            manager._hoverTimers.delete(element);
          }
          // reset cooldown saat keluar supaya bisa dibaca lagi nanti
          element.dataset._hoverCooldown = "false";
        }

        element.addEventListener("mouseenter", enterHandler);
        element.addEventListener("mouseleave", leaveHandler);

        element.dataset._hoverSpeechAttached = "true";
      });
    }

    setupToggleShortcut() {
      document.addEventListener("keydown", (e) => {
        if (e.shiftKey && (e.key === "t" || e.key === "T")) {
          e.preventDefault();
          this.textSoundEnabled = !this.textSoundEnabled;
          this._announceToScreenReader(
            this.textSoundEnabled
              ? "Suara hover teks diaktifkan"
              : "Suara hover teks dinonaktifkan"
          );
        }
      });
    }

    createActivationButton() {
      const btn = document.createElement("button");
      btn.innerText = "🔊 Aktifkan suara hover";
      btn.style.cssText = `
                position: fixed;
                bottom: 16px;
                left: 16px;  
                z-index: 9999;
                background: #2563eb;
                color: white;
                padding: 8px 12px;
                border-radius: 6px;
                font-size: 14px;
                border: none;
                cursor: pointer;
                box-shadow: 0 2px 6px rgba(0,0,0,0.2);
                max-width: 90vw; 
                white-space: nowrap;
            `;

      // Responsif: kecilkan ukuran di layar sempit
      const resizeHandler = () => {
        if (window.innerWidth < 480) {
          btn.style.fontSize = "12px";
          btn.style.padding = "6px 10px";
        } else {
          btn.style.fontSize = "14px";
          btn.style.padding = "8px 12px";
        }
      };
      window.addEventListener("resize", resizeHandler);
      resizeHandler(); // panggil sekali saat load

      btn.addEventListener("click", () => {
        this.textSoundEnabled = true;
        // trigger dummy utterance untuk unlock audio
        if ("speechSynthesis" in window) {
          const dummy = new SpeechSynthesisUtterance("Audio hover aktif");
          dummy.lang = "id-ID";
          dummy.rate = 1.1;
          dummy.volume = 0;
          window.speechSynthesis.speak(dummy);
        }
        btn.remove();
        window.removeEventListener("resize", resizeHandler);
      });

      document.body.appendChild(btn);
    }

    _announceToScreenReader(message) {
      let live = document.getElementById("aria-live-region");
      if (!live) {
        live = document.createElement("div");
        live.id = "aria-live-region";
        live.setAttribute("aria-live", "polite");
        live.setAttribute("aria-atomic", "true");
        live.style.cssText =
          "position:absolute;left:-10000px;width:1px;height:1px;overflow:hidden;";
        document.body.appendChild(live);
      }
      live.textContent = message;
      setTimeout(() => {
        live.textContent = "";
      }, 1200);
    }
  }
