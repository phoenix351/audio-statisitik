// app/initialize.js
import { CONFIG } from "../config.js";
import { UnifiedWebApplication } from "../managers/UnifiedWebApplication.js";
import { StickyNavManager } from "../managers/StickyNavManager.js";
import { SoundEffectsManager } from "../managers/SoundEffectsManager.js";
import { UniversalHoverTextManager } from "../managers/UniversalHoverTextManager.js";
import { UnifiedAudioManager } from "../managers/UnifiedAudioManager.js";
import { AccessibilityManager } from "../managers/AccessibilityManager.js";
import { HealthMonitorManager } from "../managers/HealthMonitorManager.js";
import * as Utils from "../utils.js";
import {
  setupBackwardCompatibility,
  setupDevelopmentTools,
} from "./legacy-bridge.js";

let app;

export function initializeApplication() {
  if (app) return app; // idempotent

  app = new UnifiedWebApplication({
    config: CONFIG,
    managers: {
      stickyNav: new StickyNavManager(),
      soundFx: new SoundEffectsManager(),
      hoverText: new UniversalHoverTextManager(),
      audio: new UnifiedAudioManager(),
      a11y: new AccessibilityManager(),
      health: new HealthMonitorManager(),
    },
    utils: Utils,
  });
  setupBackwardCompatibility();

  // expose for legacy code
  window.unifiedApp = app;

  // legacy glue AFTER app exists

  // dev helpers
  if (["localhost", "127.0.0.1"].includes(window.location.hostname)) {
    setupDevelopmentTools();
  }

  app.initialize?.();
  return app;
}

export { app as unifiedWebApplication };
