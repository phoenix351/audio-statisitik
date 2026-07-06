# CHECKLIST TUGAS PENGEMBANGAN: AUDIO STATISTIK BPS SULAWESI UTARA

Dokumen ini berisi panduan teknis langkah-demi-langkah tanpa ambiguitas untuk mempermudah pengerjaan fitur perbaikan keamanan, penyimpanan berkas audio, dan aksesibilitas frontend.

---

## 📁 1. Keamanan & Pembersihan Berkas Sampah

### Tugas 1.1: Proteksi Akses Berkas Sensitif via `.htaccess`
* **Tujuan**: Mencegah pengunduhan berkas sensitif dari browser jika domain menunjuk ke root proyek (bukan ke public).
* **Lokasi Berkas**: [./.htaccess](file:///d:/Ponimin/Projek/audio-statisitik/.htaccess)
* **Instruksi**:
  1. Buka file `.htaccess` di root directory.
  2. Temukan baris `RewriteEngine On` (Line 3).
  3. Sisipkan kode berikut tepat di bawah baris `RewriteEngine On`:
     ```apache
     # Blokir akses ke file konfigurasi dan berkas database/log sensitif
     RedirectMatch 403 /\.(key|env|git|sql|log)$
     ```
  4. Simpan file tersebut.

### Tugas 1.2: Hapus Berkas Dump Database `Austat.sql`
* **Tujuan**: Menghapus data rawan dari server repositori.
* **Berkas yang Dihapus**: [./Austat.sql](file:///d:/Ponimin/Projek/audio-statisitik/Austat.sql)
* **Instruksi**: Hapus file `Austat.sql` secara fisik dari root folder proyek.

### Tugas 1.3: Hapus Controller Cadangan `DocumentManagementController0.php`
* **Tujuan**: Menjaga kebersihan codebase (clean code).
* **Berkas yang Dihapus**: [./app/Http/Controllers/Admin/DocumentManagementController0.php](file:///d:/Ponimin/Projek/audio-statisitik/app/Http/Controllers/Admin/DocumentManagementController0.php)
* **Instruksi**: Hapus file `DocumentManagementController0.php` secara fisik dari direktori controller admin BPS.

---

## 💾 2. Penyelarasan Penyimpanan Audio (Backend & Controller)

### Tugas 2.1: Ubah Disk Simpan ke 'documents' pada Background Job
* **Tujuan**: Menyelaraskan tujuan penyimpanan audio ke folder yang dapat di-resolve dengan benar oleh model.
* **Lokasi Berkas**: [./app/Jobs/ProcessDocumentJob.php](file:///d:/Ponimin/Projek/audio-statisitik/app/Jobs/ProcessDocumentJob.php)
* **Instruksi**:
  1. Cari fungsi `saveFinalResults` (Line 594).
  2. Ganti blok pendefinisian disk (Line 600-602) yang sebelumnya:
     ```php
     $disk = 'public'; // Or 's3', 'local', etc.
     $destinationPath = 'documents/' . $document->id . '/audio.mp3';
     ```
     Menjadi:
     ```php
     $disk = 'documents'; // Diselaraskan ke disk 'documents'
     $destinationPath = $document->id . '/audio.mp3'; // Menghilangkan prefix documents/
     ```
  3. Simpan berkas.

### Tugas 2.2: Perbaiki Validasi Playable Audio di Streaming Controller
* **Tujuan**: Memastikan pemutar audio mendeteksi berkas fisik lokal di storage, bukan kolom BLOB biner di database.
* **Lokasi Berkas**: [./app/Http/Controllers/AudioController.php](file:///d:/Ponimin/Projek/audio-statisitik/app/Http/Controllers/AudioController.php)
* **Instruksi**:
  1. Buka file `AudioController.php`.
  2. Cari fungsi `canPlayAudio` (Line 412).
  3. Ubah isi fungsinya dari:
     ```php
     private function canPlayAudio(Document $document): bool
     {
         return $document->status === 'completed' &&
             (!empty($document->mp3_content) || !empty($document->flac_content));
     }
     ```
     Menjadi:
     ```php
     private function canPlayAudio(Document $document): bool
     {
         // Mengecek berkas fisik di disk 'documents'
         if ($document->status !== 'completed' || empty($document->mp3_path)) {
             return false;
         }
         return \Illuminate\Support\Facades\Storage::disk('documents')->exists($document->mp3_path);
     }
     ```
  4. Simpan berkas.

---

## 🚨 3. Penghapusan Pembersihan Cache Global (Safe Health Check)

### Tugas 3.1: Ganti Cache::flush() dengan Pembersihan Spesifik
* **Tujuan**: Menghindari pemutusan paksa semua sesi user (session logout) saat melakukan health check antrean.
* **Lokasi Berkas**: [./app/Console/Commands/MonitorQueueHealth.php](file:///d:/Ponimin/Projek/audio-statisitik/app/Console/Commands/MonitorQueueHealth.php)
* **Instruksi**:
  1. Cari fungsi `fixIssues` (Line 101).
  2. Temukan baris `Cache::flush();` (Line 128).
  3. Ganti baris `Cache::flush();` dan log info di bawahnya menjadi:
     ```php
     // Menghapus cache progress dokumen macet saja secara selektif
     $stuckDocuments = Document::where('status', 'processing')
         ->where('processing_started_at', '<', now()->subHours(2))
         ->get();

     foreach ($stuckDocuments as $doc) {
         Cache::forget("document_progress_{$doc->id}");
         Cache::forget("document_progress_{$doc->id}_completed");
     }
     $this->info("✅ Cleared specific stuck document progress caches");
     ```
  4. Simpan berkas.

---

## ♿ 4. Aksesibilitas Frontend (Penyandang Disabilitas)

### Tugas 4.1: Selesaikan Konflik Pintasan Tombol Spasi
* **Tujuan**: Menghindari terbukanya Modal Pencarian Suara secara tidak sengaja ketika pengguna menekan tombol Spasi untuk menjeda (pause) audio.
* **Lokasi Berkas**: [./resources/js/enhanced-voice-search.js](file:///d:/Ponimin/Projek/audio-statisitik/resources/js/enhanced-voice-search.js)
* **Instruksi**:
  1. Cari baris event listener keydown untuk `"Space"` (sekitar Line 1417).
  2. Tambahkan pengecekan visibilitas pemutar audio di baris awal fungsi:
     ```javascript
     document.addEventListener("keydown", function (event) {
       const active = document.activeElement;
       const isTyping =
         active &&
         (active.tagName.toLowerCase() == "input" ||
           active.tagName.toLowerCase() == "textarea" ||
           active.isContentEditable);

       if (isTyping) return;
       
       // Deteksi pemutar audio di layar
       const bottomPlayer = document.getElementById("bottom-audio-player");
       const isPlayerActive = bottomPlayer && !bottomPlayer.classList.contains("hidden");

       // Jika pemutar audio aktif, biarkan tombol Spasi dikonsumsi oleh player
       if (isPlayerActive) return;
     ```
  3. Simpan berkas.

### Tugas 4.2: Aktifkan Pembaca Teks Suara pada Fokus Keyboard (Tab Key)
* **Tujuan**: Memastikan pengguna tunanetra/keyboard-only mendapat respon suara hover ketika elemen terpilih menggunakan tombol `Tab`.
* **Lokasi Berkas**: [./resources/js/src/managers/UniversalHoverTextManager.js](file:///d:/Ponimin/Projek/audio-statisitik/resources/js/src/managers/UniversalHoverTextManager.js)
* **Instruksi**:
  1. Cari lokasi registrasi event listener hover (Line 73-74).
  2. Tepat di bawah baris:
     ```javascript
     element.addEventListener("mouseenter", enterHandler);
     element.addEventListener("mouseleave", leaveHandler);
     ```
     Tambahkan dua baris event listener berikut:
     ```javascript
     // Daftarkan listener fokus keyboard agar text-to-speech feedback berfungsi tanpa mouse
     element.addEventListener("focusin", enterHandler);
     element.addEventListener("focusout", leaveHandler);
     ```
  3. Simpan berkas.
