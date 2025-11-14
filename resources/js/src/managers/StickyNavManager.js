// Auto-extracted from app1.js
export class StickyNavManager {
    constructor() {
      this.navbar = null;
      this.isSticky = false;
      this.originalTop = 0;
      this.scrollHandler = null;
    }

    async init() {
      this.navbar = document.querySelector(".navbar, .main-nav, .navigation");
      if (!this.navbar) return;

      this.originalTop = this.navbar.offsetTop;
      this.scrollHandler = this.handleScroll.bind(this);

      window.addEventListener("scroll", this.scrollHandler);

      // Initial check
      this.handleScroll();
    }

    handleScroll() {
      const scrollY = window.pageYOffset || document.documentElement.scrollTop;

      if (scrollY > this.originalTop + CONFIG.ui.stickyNavOffset) {
        if (!this.isSticky) {
          this.makeSticky();
        }
      } else {
        if (this.isSticky) {
          this.removeSticky();
        }
      }
    }

    makeSticky() {
      this.navbar.classList.add("sticky", "navbar-sticky");
      this.isSticky = true;

      // Add padding to body to prevent jump
      document.body.style.paddingTop = this.navbar.offsetHeight + "px";
    }

    removeSticky() {
      this.navbar.classList.remove("sticky", "navbar-sticky");
      this.isSticky = false;

      // Remove padding
      document.body.style.paddingTop = "0";
    }

    cleanup() {
      if (this.scrollHandler) {
        window.removeEventListener("scroll", this.scrollHandler);
      }
    }
  }
