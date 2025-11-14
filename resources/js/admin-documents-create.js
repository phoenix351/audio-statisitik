export default function initDocumentCreate() {
  const fileInput = document.getElementById("file");
  const coverInput = document.getElementById("cover_image");
  const fileDropZone = document.getElementById("file-drop-zone");
  const coverDropZone = document.getElementById("cover-drop-zone");
  const filePreview = document.getElementById("file-preview");
  const coverPreview = document.getElementById("cover-preview");
  const coverPlaceholder = document.getElementById("cover-placeholder");
  const fileName = document.getElementById("file-name");
  const fileSize = document.getElementById("file-size");
  const coverFileName = document.getElementById("cover-file-name");
  const coverPreviewImg = document.getElementById("cover-preview-img");
  const removeFileBtn = document.getElementById("remove-file");
  const removeCoverBtn = document.getElementById("remove-cover");
  const submitBtn = document.getElementById("submit-btn");
  const previewBtn = document.getElementById("preview-btn");
  const descriptionTextarea = document.getElementById("description");
  const descCount = document.getElementById("desc-count");
  const previewModal = document.getElementById("preview-modal");
  const closePreview = document.getElementById("close-preview");
  const closePreviewBtn = document.getElementById("close-preview-btn");

  // File & cover change handlers
  fileInput.addEventListener("change", handleFileSelect);
  coverInput.addEventListener("change", handleCoverSelect);

  // Drag & drop
  setupDragDrop(fileDropZone, fileInput, "file");
  setupDragDrop(coverDropZone, coverInput, "cover");

  // Remove file/cover
  removeFileBtn.addEventListener("click", () => {
    fileInput.value = "";
    filePreview.classList.add("hidden");
    updateSubmitButton();
    announceToScreenReader("File dokumen dihapus");
  });

  removeCoverBtn.addEventListener("click", () => {
    coverInput.value = "";
    coverPreview.classList.add("hidden");
    coverPlaceholder.classList.remove("hidden");
    updateSubmitButton();
    announceToScreenReader("Cover image dihapus");
  });

  // Description counter
  descriptionTextarea.addEventListener("input", () => {
    const count = descriptionTextarea.value.length;
    descCount.textContent = count;
    descCount.classList.toggle("text-red-500", count > 900);
    descCount.classList.toggle("text-gray-400", count <= 900);
  });

  // Preview modal
  previewBtn.addEventListener("click", showPreview);
  closePreview.addEventListener("click", () =>
    previewModal.classList.add("hidden")
  );
  closePreviewBtn.addEventListener("click", () =>
    previewModal.classList.add("hidden")
  );

  // Form submit
  const form = document.querySelector("form");
  form.addEventListener("submit", (e) => {
    if (!validateForm()) {
      e.preventDefault();
      return false;
    }

    submitBtn.disabled = true;
    submitBtn.innerHTML =
      '<i class="fas fa-spinner fa-spin mr-2"></i><span class="text-sound">Mengunggah dan memproses...</span>';
    announceToScreenReader(
      "Mengunggah dokumen dan memulai proses TTS, mohon tunggu..."
    );
  });

  // Helper functions
  function setupDragDrop(dropZone, input, type) {
    dropZone.addEventListener("dragover", (e) => {
      e.preventDefault();
      dropZone.classList.add("border-blue-400", "bg-blue-50");
    });
    dropZone.addEventListener("dragleave", (e) => {
      e.preventDefault();
      dropZone.classList.remove("border-blue-400", "bg-blue-50");
    });
    dropZone.addEventListener("drop", (e) => {
      e.preventDefault();
      dropZone.classList.remove("border-blue-400", "bg-blue-50");
      if (e.dataTransfer.files.length > 0) {
        const dt = new DataTransfer();
        dt.items.add(e.dataTransfer.files[0]);
        input.files = dt.files;
        type === "file" ? handleFileSelect() : handleCoverSelect();
      }
    });
  }

  function handleFileSelect() {
    const file = fileInput.files[0];
    if (!file) return;

    const allowedTypes = [
      "application/pdf",
      "application/msword",
      "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
    ];
    if (!allowedTypes.includes(file.type)) {
      alert("Format file tidak didukung. Gunakan PDF, DOC, atau DOCX.");
      fileInput.value = "";
      updateSubmitButton();
      return;
    }

    if (file.size > 10 * 1024 * 1024) {
      alert("Ukuran file terlalu besar. Maksimal 10MB.");
      fileInput.value = "";
      updateSubmitButton();
      return;
    }

    fileName.textContent = file.name;
    fileSize.textContent = formatFileSize(file.size);
    filePreview.classList.remove("hidden");

    // ✅ Auto-populate title + capitalize words
    const titleInput = document.getElementById("title");
    if (!titleInput.value) {
      let cleanTitle = file.name
        .replace(/\.[^/.]+$/, "") // hapus ekstensi
        .replace(/[_-]/g, " ") // ganti _ atau - jadi spasi
        .trim();

      // Capitalize tiap kata
      cleanTitle = cleanTitle.replace(/\b\w/g, (c) => c.toUpperCase());

      titleInput.value = cleanTitle;

      // ✅ Cek apakah ada tahun (2020–2030 misalnya)
      const yearMatch = cleanTitle.match(/\b(20\d{2})\b/);
      if (yearMatch) {
        const yearSelect = document.getElementById("year");
        const year = yearMatch[1];
        if ([...yearSelect.options].some((opt) => opt.value == year)) {
          yearSelect.value = year;
        }
      }
    }

    updateSubmitButton();
    announceToScreenReader(`File ${file.name} berhasil dipilih`);
  }

  function handleCoverSelect() {
    const file = coverInput.files[0];
    if (!file) return;

    if (!file.type.startsWith("image/")) {
      alert("File cover harus berupa gambar.");
      coverInput.value = "";
      updateSubmitButton();
      return;
    }

    if (file.size > 2 * 1024 * 1024) {
      alert("Ukuran cover terlalu besar. Maksimal 2MB.");
      coverInput.value = "";
      updateSubmitButton();
      return;
    }

    // ✅ Tampilkan preview kalau lolos validasi
    const reader = new FileReader();
    reader.onload = (ev) => {
      coverPreviewImg.src = ev.target.result;
      coverFileName.textContent = file.name;
      coverPlaceholder.classList.add("hidden");
      coverPreview.classList.remove("hidden");
    };
    reader.readAsDataURL(file);

    // ✅ Update tombol
    updateSubmitButton();
  }

  function updateSubmitButton() {
    const hasFile = fileInput.files.length > 0;
    const hasCover = coverInput.files.length > 0;
    const hasType = document.getElementById("type").value !== "";
    const hasYear = document.getElementById("year").value !== "";
    const hasIndicator = document.getElementById("indicator_id").value !== "";

    const isValid = hasFile && hasCover && hasType && hasYear && hasIndicator;

    submitBtn.disabled = !isValid;
    previewBtn.disabled = !isValid;

    if (isValid) {
      submitBtn.classList.remove("bg-gray-400", "cursor-not-allowed");
      submitBtn.classList.add("bg-blue-600", "hover:bg-blue-700");
      previewBtn.classList.remove("border-gray-300", "text-gray-400");
      previewBtn.classList.add("border-blue-600", "text-blue-600");
    } else {
      submitBtn.classList.add("bg-gray-400", "cursor-not-allowed");
      submitBtn.classList.remove("bg-blue-600", "hover:bg-blue-700");
      previewBtn.classList.add("border-gray-300", "text-gray-400");
      previewBtn.classList.remove("border-blue-600", "text-blue-600");
    }
    // console.log("File:", fileInput.files);
    // console.log("Cover:", coverInput.files);
  }

  function validateForm() {
    let isValid = true;
    const errors = [];

    if (!fileInput.files.length) {
      isValid = false;
      errors.push("File dokumen harus diisi");
    }
    if (!coverInput.files.length) {
      isValid = false;
      errors.push("Cover dokumen harus diisi");
    }
    if (!document.getElementById("type").value) {
      isValid = false;
      errors.push("Jenis dokumen harus dipilih");
    }
    if (!document.getElementById("year").value) {
      isValid = false;
      errors.push("Tahun harus dipilih");
    }
    if (!document.getElementById("indicator_id").value) {
      isValid = false;
      errors.push("Indikator harus dipilih");
    }

    if (!isValid)
      announceToScreenReader("Formulir tidak valid: " + errors.join(", "));

    return isValid;
  }

  function showPreview() {
    const formData = {
      title: document.getElementById("title").value,
      type: document.getElementById("type").selectedOptions[0].text,
      year: document.getElementById("year").value,
      indicator:
        document.getElementById("indicator_id").selectedOptions[0].text,
      description: descriptionTextarea.value,
      fileName: fileInput.files[0]?.name || "",
      fileSize: fileInput.files[0]
        ? formatFileSize(fileInput.files[0].size)
        : "",
      hasCover: coverInput.files.length > 0,
      coverName: coverInput.files[0]?.name || "",
    };

    const previewContent = document.getElementById("preview-content");
    previewContent.innerHTML = `
            <div class="md:col-span-2">
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-gray-900 mb-3 text-sound">Informasi Dokumen</h4>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                        <div><dt class="font-medium text-gray-700 text-sound">Judul:</dt><dd class="text-gray-900 text-sound">${
                          formData.title
                        }</dd></div>
                        <div><dt class="font-medium text-gray-700 text-sound">Jenis:</dt><dd class="text-gray-900 text-sound">${
                          formData.type
                        }</dd></div>
                        <div><dt class="font-medium text-gray-700 text-sound">Tahun:</dt><dd class="text-gray-900 text-sound">${
                          formData.year
                        }</dd></div>
                        <div><dt class="font-medium text-gray-700 text-sound">Indikator:</dt><dd class="text-gray-900 text-sound">${
                          formData.indicator
                        }</dd></div>
                        <div class="sm:col-span-2"><dt class="font-medium text-gray-700 text-sound">File:</dt><dd class="text-gray-900 text-sound">${
                          formData.fileName
                        } (${formData.fileSize})</dd></div>
                        <div class="sm:col-span-2"><dt class="font-medium text-gray-700 text-sound">Cover:</dt><dd class="text-gray-900 text-sound">${
                          formData.coverName
                        }</dd></div>
                        ${
                          formData.description
                            ? `<div class="sm:col-span-2"><dt class="font-medium text-gray-700 text-sound">Deskripsi:</dt><dd class="text-gray-900 text-sound">${formData.description}</dd></div>`
                            : ""
                        }
                    </dl>
                </div>
            </div>
            <div class="md:col-span-1">
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-gray-900 mb-3 text-sound">Cover Preview</h4>
                    <img src="${
                      coverPreviewImg.src
                    }" alt="Cover Preview" class="w-full aspect-[3/4] object-cover rounded-lg">
                </div>
            </div>
        `;

    previewModal.classList.remove("hidden");
    announceToScreenReader("Menampilkan preview dokumen");
  }

  function formatFileSize(bytes) {
    if (bytes === 0) return "0 Bytes";
    const k = 1024;
    const sizes = ["Bytes", "KB", "MB", "GB"];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i];
  }

  // Field listeners
  ["title", "type", "year", "indicator_id"].forEach((id) => {
    const el = document.getElementById(id);
    el.addEventListener("change", updateSubmitButton);
    el.addEventListener("input", updateSubmitButton);
  });

  function playTextHoverSound() {
    try {
      const audioContext = new (window.AudioContext ||
        window.webkitAudioContext)();
      const oscillator = audioContext.createOscillator();
      const gainNode = audioContext.createGain();

      oscillator.connect(gainNode);
      gainNode.connect(audioContext.destination);

      oscillator.frequency.setValueAtTime(900, audioContext.currentTime);
      gainNode.gain.setValueAtTime(0, audioContext.currentTime);
      gainNode.gain.linearRampToValueAtTime(
        0.04,
        audioContext.currentTime + 0.01
      );
      gainNode.gain.exponentialRampToValueAtTime(
        0.001,
        audioContext.currentTime + 0.1
      );

      oscillator.start(audioContext.currentTime);
      oscillator.stop(audioContext.currentTime + 0.1);
    } catch (e) {
      // Silently fail if Web Audio API is not supported
    }
  }

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

  function hasCover() {
    const coverInput = document.getElementById("cover_image");
    // Hanya valid jika user memilih file manual
    return coverInput && coverInput.files && coverInput.files.length > 0;
  }

  updateSubmitButton();
}
