export function initCoverImage({
  inputId = "cover_image",
  dropZoneId = "cover-drop-zone",
  previewId = "cover-preview",
  placeholderId = "cover-placeholder",
  fileNameId = "cover-file-name",
  imgId = "cover-preview-img",
  removeBtnId = "remove-cover",
  maxBytes = 2 * 1024 * 1024,
} = {}) {
  const input = document.getElementById(inputId);
  if (!input) return;

  const dropZone = document.getElementById(dropZoneId);
  const preview = document.getElementById(previewId);
  const placeholder = document.getElementById(placeholderId);
  const fileName = document.getElementById(fileNameId);
  const img = document.getElementById(imgId);
  const removeBtn = document.getElementById(removeBtnId);

  input.addEventListener("change", handleSelect);
  if (removeBtn) removeBtn.addEventListener("click", clearSelection);
  if (dropZone) setupDragDrop(dropZone, input);

  function setupDragDrop(zone, fileInput) {
    zone.addEventListener("dragover", (e) => {
      e.preventDefault();
      e.stopPropagation();
      zone.classList.add("border-purple-400", "bg-purple-50");
    });
    zone.addEventListener("dragleave", (e) => {
      e.preventDefault();
      e.stopPropagation();
      zone.classList.remove("border-purple-400", "bg-purple-50");
    });
    zone.addEventListener("drop", (e) => {
      e.preventDefault();
      e.stopPropagation();
      zone.classList.remove("border-purple-400", "bg-purple-50");
      const [file] = e.dataTransfer.files;
      if (!file) return;
      const dt = new DataTransfer();
      dt.items.add(file);
      fileInput.files = dt.files;
      handleSelect();
    });
  }

  function handleSelect() {
    const file = input.files?.[0];
    if (!file) return;

    if (!file.type.startsWith("image/")) {
      alert("File cover harus berupa gambar.");
      input.value = "";
      return;
    }
    if (file.size > maxBytes) {
      alert("Ukuran cover terlalu besar. Maksimal 2MB.");
      input.value = "";
      return;
    }

    const reader = new FileReader();
    reader.onload = (e) => {
      if (img) img.src = e.target.result;
      if (fileName) fileName.textContent = file.name;
      if (placeholder) placeholder.classList.add("hidden");
      if (preview) preview.classList.remove("hidden");
      announce("Cover image berhasil dipilih");
    };
    reader.readAsDataURL(file);
  }

  function clearSelection() {
    input.value = "";
    if (preview) preview.classList.add("hidden");
    if (placeholder) placeholder.classList.remove("hidden");
    announce("Cover image dihapus");
  }

  function announce(msg) {
    const el = document.createElement("div");
    el.setAttribute("aria-live", "polite");
    el.setAttribute("aria-atomic", "true");
    el.className = "sr-only";
    el.textContent = msg;
    document.body.appendChild(el);
    setTimeout(
      () => document.body.contains(el) && document.body.removeChild(el),
      800
    );
  }
}
