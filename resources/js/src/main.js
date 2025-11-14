import { initializeApplication } from "./app/initialize.js";

document.addEventListener("DOMContentLoaded", () => {
  try {
    initializeApplication();
  } catch (e) {
    console.error("Initialization error:", e);
  }
});
