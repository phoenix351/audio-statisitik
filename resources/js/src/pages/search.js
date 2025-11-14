export function initSearch() {
  // Hitung jumlah hasil pencarian
  function clearAllFilters() {
    window.location.href = "{{ route('documents.publications') }}";
  }
  function searchResultAnnounce(count, query) {
    let message = `Terdapat ${count} hasil pencarian`;
    if (query) {
      message += ` untuk ${query}`;
    }

    const utter = new SpeechSynthesisUtterance(message);
    utter.lang = "id-ID";
    window.speechSynthesis.speak(utter);
  }
  let count = document.getElementById("documents-grid").dataset.size ?? 0;
  let query =
    document.getElementById("documents-grid").dataset.query ?? "query kosong";
  searchResultAnnounce(count, query);
}
