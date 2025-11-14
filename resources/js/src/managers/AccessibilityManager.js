// Auto-extracted from app1.js
export class AccessibilityManager {
  constructor() {
    this.announcements = [];
    this.lastAnnouncement = "";
    this.announceTimeout = null;
  }

  async init() {
    console.log("♿ Initializing Accessibility Manager");
    this.setupKeyboardNavigation();
    this.setupScreenReaderSupport();
    this.setupFocusManagement();
  }

  setupKeyboardNavigation() {
    // Tab navigation improvements
    document.addEventListener("keydown", (e) => {
      if (e.key === "Tab") {
        this.handleTabNavigation(e);
      }
    });
  }

  setupScreenReaderSupport() {
    // Create aria-live region for announcements
    this.createLiveRegion();

    // Announce page changes
    this.announcePageContent();
  }

  setupFocusManagement() {
    // Focus management for dynamic content
    this.setupFocusTrap();
    this.setupSkipLinks();
  }

  createLiveRegion() {
    const liveRegion = document.createElement("div");
    liveRegion.id = "aria-live-region";
    liveRegion.setAttribute("aria-live", "polite");
    liveRegion.setAttribute("aria-atomic", "true");
    liveRegion.style.cssText = `
                position: absolute;
                left: -10000px;
                width: 1px;
                height: 1px;
                overflow: hidden;
            `;

    document.body.appendChild(liveRegion);
  }

  announce(message, priority = "polite") {
    if (!message || message === this.lastAnnouncement) return;

    this.lastAnnouncement = message;

    // Clear existing timeout
    if (this.announceTimeout) {
      clearTimeout(this.announceTimeout);
    }

    // Delay announcement to prevent overwhelming screen readers
    this.announceTimeout = setTimeout(() => {
      const liveRegion = document.getElementById("aria-live-region");
      if (liveRegion) {
        liveRegion.setAttribute("aria-live", priority);
        liveRegion.textContent = message;

        // Clear after announcement
        setTimeout(() => {
          liveRegion.textContent = "";
        }, 1000);
      }
    }, 150);
  }

  announceError(message) {
    this.announce(message, "assertive");
  }

  handleTabNavigation(e) {
    // Improve tab navigation for audio player
    const focusableElements = this.getFocusableElements();
    const currentIndex = focusableElements.indexOf(document.activeElement);

    if (e.shiftKey && currentIndex === 0) {
      // Shift+Tab from first element
      e.preventDefault();
      focusableElements[focusableElements.length - 1].focus();
    } else if (!e.shiftKey && currentIndex === focusableElements.length - 1) {
      // Tab from last element
      e.preventDefault();
      focusableElements[0].focus();
    }
  }

  getFocusableElements() {
    const selector =
      'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])';
    return Array.from(document.querySelectorAll(selector)).filter((el) => {
      return !el.disabled && el.offsetParent !== null;
    });
  }

  setupFocusTrap() {
    // Focus trap for modals and audio player
    document.addEventListener("focusin", (e) => {
      const activeModal = document.querySelector(".modal:not(.hidden)");
      if (activeModal && !activeModal.contains(e.target)) {
        const firstFocusable = activeModal.querySelector(
          'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );
        if (firstFocusable) {
          firstFocusable.focus();
        }
      }
    });
  }

  setupSkipLinks() {
    // Add skip to content link
    const skipLink = document.createElement("a");
    skipLink.href = "#main-content";
    skipLink.textContent = "Skip to main content";
    skipLink.className = "skip-link";
    skipLink.style.cssText = `
                position: absolute;
                top: -40px;
                left: 6px;
                background: #000;
                color: #fff;
                padding: 8px;
                text-decoration: none;
                border-radius: 4px;
                z-index: 10001;
                transition: top 0.3s;
            `;

    skipLink.addEventListener("focus", () => {
      skipLink.style.top = "6px";
    });

    skipLink.addEventListener("blur", () => {
      skipLink.style.top = "-40px";
    });

    document.body.insertBefore(skipLink, document.body.firstChild);
  }

  announcePageContent() {
    // Announce page title and main content
    const pageTitle = document.title;
    const mainHeading = document.querySelector("h1");

    if (mainHeading) {
      this.announce(`Halaman ${pageTitle}. ${mainHeading.textContent}`);
    } else {
      this.announce(`Halaman ${pageTitle}`);
    }
  }

  cleanup() {
    if (this.announceTimeout) {
      clearTimeout(this.announceTimeout);
    }
  }
}
