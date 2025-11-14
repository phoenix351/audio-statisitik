export function bindCopyButtons(attr = "data-copy-url") {
  document.querySelectorAll(`[${attr}]`).forEach((btn) => {
    btn.addEventListener("click", async () => {
      const url = btn.getAttribute(attr);
      if (!url) return;
      try {
        if (navigator.clipboard?.writeText) {
          await navigator.clipboard.writeText(url);
        } else {
          const ta = document.createElement("textarea");
          ta.value = url;
          document.body.appendChild(ta);
          ta.select();
          document.execCommand("copy");
          document.body.removeChild(ta);
        }
        alert("URL dokumen berhasil disalin ke clipboard");
      } catch {
        alert("Gagal menyalin URL");
      }
    });
  });
}
