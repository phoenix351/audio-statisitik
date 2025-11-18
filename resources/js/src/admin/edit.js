import { initCoverImage } from "../../cover-image";

export function initEditAdmin() {
  initCoverImage();
  const docInput = document.getElementById("document_file");
  const infoBox = document.getElementById("document-file-info");
  const nameSpan = document.getElementById("document-file-name");

  if (!docInput) return;

  docInput.addEventListener("change", function () {
    if (this.files && this.files.length > 0) {
      const file = this.files[0];
      if (nameSpan) {
        nameSpan.textContent = file.name;
      }
      if (infoBox) {
        infoBox.classList.remove("hidden");
      }
    } else {
      // kalau user batal pilih file
      if (nameSpan) {
        nameSpan.textContent = "";
      }
      if (infoBox) {
        infoBox.classList.add("hidden");
      }
    }
  });
}
