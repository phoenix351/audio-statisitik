// Auto-extracted utilities
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
        const clickable = targetElement.closest("button, .btn, a, .clickable");
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

const safeHoverHandler = () => {
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
};

function announceToScreenReader(message) {
  const announcement = document.createElement("div");
  announcement.setAttribute("aria-live", "polite");
  announcement.setAttribute("aria-atomic", "true");
  announcement.className = "sr-only";
  announcement.textContent = message;
  document.body.appendChild(announcement);

  setTimeout(() => {
    if (document.body.contains(announcement)) {
      document.body.removeChild(announcement);
    }
  }, 1000);
}

function showKeyboardHelp() {
  const helpModal = document.createElement("div");
  helpModal.className =
    "fixed inset-0 bg-black/50 z-50 flex items-center justify-center";
  helpModal.innerHTML = `
            <div class="bg-white rounded-lg p-6 max-w-md mx-4">
                <h3 class="text-lg font-semibold mb-4 text-sound">Pintasan Keyboard</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <kbd class="px-2 py-1 bg-gray-100 rounded text-xs">H</kbd>
                        <span class="text-sound">Beranda</span>
                    </div>
                    <div class="flex justify-between">
                        <kbd class="px-2 py-1 bg-gray-100 rounded text-xs">P</kbd>
                        <span class="text-sound">Publikasi</span>
                    </div>
                    <div class="flex justify-between">
                        <kbd class="px-2 py-1 bg-gray-100 rounded text-xs">B</kbd>
                        <span class="text-sound">BRS</span>
                    </div>
                    <div class="flex justify-between">
                        <kbd class="px-2 py-1 bg-gray-100 rounded text-xs">Ctrl</kbd>
                        <span class="text-sound">Pencarian Suara</span>
                    </div>
                    <div class="flex justify-between">
                        <kbd class="px-2 py-1 bg-gray-100 rounded text-xs">Ctrl + T</kbd>
                        <span class="text-sound">Toggle Suara Hover</span>
                    </div>
                    <div class="flex justify-between">
                        <kbd class="px-2 py-1 bg-gray-100 rounded text-xs">Spasi</kbd>
                        <span class="text-sound">Putar/Jeda Audio</span>
                    </div>
                    <div class="flex justify-between">
                        <kbd class="px-2 py-1 bg-gray-100 rounded text-xs">?</kbd>
                        <span class="text-sound">Bantuan ini</span>
                    </div>
                </div>
                <button onclick="this.closest('.fixed').remove()" 
                        class="mt-4 w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sound">
                    Tutup
                </button>
            </div>
        `;

  document.body.appendChild(helpModal);
  announceToScreenReader("Menampilkan bantuan pintasan keyboard");
}

const resizeHandler = () => {
  if (window.innerWidth < 480) {
    btn.style.fontSize = "12px";
    btn.style.padding = "6px 10px";
  } else {
    btn.style.fontSize = "14px";
    btn.style.padding = "8px 12px";
  }
};

const upgradePreload = () => {
  if (this.currentAudio) {
    this.currentAudio.preload = "auto";
    console.log("📥 Upgraded audio preloading to auto");
  }
};

const checkBuffer = () => {
  if (this.currentAudio.buffered.length > 0) {
    const bufferedEnd = this.currentAudio.buffered.end(
      this.currentAudio.buffered.length - 1
    );
    const duration = this.currentAudio.duration || 0;
    const bufferPercent = duration > 0 ? (bufferedEnd / duration) * 100 : 0;

    console.log(`📊 Audio buffered: ${bufferPercent.toFixed(1)}%`);

    // Update buffer indicator if exists
    const bufferIndicator = document.getElementById("buffer-indicator");
    if (bufferIndicator) {
      bufferIndicator.style.width = `${bufferPercent}%`;
    }
  }
};

const cleanup = () => {
  clearTimeout(seekTimeout);
  this.currentAudio.removeEventListener("seeked", onSeeked);
  this.currentAudio.removeEventListener("error", onError);
  this.currentAudio.removeEventListener("stalled", onStalled);
};

export {
  safeClickHandler,
  safeHoverHandler,
  announceToScreenReader,
  showKeyboardHelp,
  resizeHandler,
  upgradePreload,
  checkBuffer,
  cleanup,
};
