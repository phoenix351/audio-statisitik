@extends('layouts.app')

@section('title', 'Upload Dokumen - Admin')

@section('content')
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center space-x-2 text-sm text-gray-500 mb-2">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-blue-600 hover-sound text-sound">Dashboard</a>
                <i class="fas fa-chevron-right" aria-hidden="true"></i>
                <a href="{{ route('admin.documents.index') }}" class="hover:text-blue-600 hover-sound text-sound">Dokumen</a>
                <i class="fas fa-chevron-right" aria-hidden="true"></i>
                <span class="text-sound">Upload Baru</span>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 text-sound">Upload Dokumen Baru</h1>
            <p class="text-gray-600 mt-2 text-sound">Upload dokumen publikasi atau BRS dengan konversi audio otomatis</p>
        </div>

        <!-- Messages -->
        @if (session('success'))
            <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="flex">
                    <i class="fas fa-check-circle text-green-400 mr-3 mt-1" aria-hidden="true"></i>
                    <div class="text-sm text-green-800 text-sound">{{ session('success') }}</div>
                </div>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="flex">
                    <i class="fas fa-exclamation-circle text-red-400 mr-3 mt-1" aria-hidden="true"></i>
                    <div class="text-sm text-red-800 text-sound">{{ session('error') }}</div>
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="flex">
                    <i class="fas fa-exclamation-triangle text-red-400 mr-3 mt-1" aria-hidden="true"></i>
                    <div>
                        <h3 class="text-sm font-medium text-red-800 text-sound">Terdapat kesalahan:</h3>
                        <ul class="mt-2 text-sm text-red-700 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li class="text-sound">• {{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <!-- Upload Form -->
        <form method="POST" action="{{ route('admin.documents.store') }}" enctype="multipart/form-data" class="space-y-8">
            @csrf

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                <!-- Left Column: Document Upload -->
                <div class="lg:col-span-2 space-y-8">

                    <!-- File Upload Section -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4 text-sound">
                            <i class="fas fa-file-upload mr-2 text-blue-600" aria-hidden="true"></i>
                            1. Upload File Dokumen
                        </h2>

                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 hover:border-blue-400 transition-colors"
                            id="file-drop-zone">
                            <div class="text-center">
                                <div
                                    class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i class="fas fa-file-upload text-2xl text-blue-600" aria-hidden="true"></i>
                                </div>
                                <label for="file" class="cursor-pointer">
                                    <span
                                        class="text-lg font-medium text-gray-900 hover:text-blue-600 transition-colors text-sound">Pilih
                                        file atau drag & drop di sini</span>
                                    <input type="file" id="file" name="file" class="sr-only"
                                        accept=".pdf,.doc,.docx" required>
                                </label>
                                <p class="text-sm text-gray-500 mt-2 text-sound">Format: PDF, DOC, DOCX (Maksimal 25MB)</p>

                                <!-- File Preview -->
                                <div id="file-preview" class="hidden mt-4 p-4 bg-gray-50 rounded-lg">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-3">
                                            <i class="fas fa-file-alt text-2xl text-blue-600" aria-hidden="true"></i>
                                            <div class="text-left">
                                                <p class="font-medium text-gray-900 text-sound" id="file-name"></p>
                                                <p class="text-sm text-gray-500 text-sound" id="file-size"></p>
                                            </div>
                                        </div>
                                        <button type="button" id="remove-file"
                                            class="text-red-600 hover:text-red-700 hover-sound">
                                            <i class="fas fa-times-circle text-xl" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Document Information -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4 text-sound">
                            <i class="fas fa-edit mr-2 text-green-600" aria-hidden="true"></i>
                            2. Informasi Dokumen
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Title -->
                            <div class="md:col-span-2">
                                <label for="title" class="block text-sm font-medium text-gray-700 mb-2 text-sound">
                                    <i class="fas fa-heading mr-1" aria-hidden="true"></i>
                                    Judul Dokumen *
                                </label>
                                <input type="text" id="title" name="title" value="{{ old('title') }}"
                                    placeholder="Masukkan judul dokumen yang jelas dan deskriptif"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 hover-sound"
                                    required maxlength="255">
                                <p class="text-xs text-gray-500 mt-1 text-sound">Judul akan muncul di website dan hasil
                                    pencarian</p>
                            </div>

                            <!-- Document Type -->
                            <div>
                                <label for="type" class="block text-sm font-medium text-gray-700 mb-2 text-sound">
                                    <i class="fas fa-tags mr-1" aria-hidden="true"></i>
                                    Jenis Dokumen *
                                </label>
                                <select id="type" name="type"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 hover-sound"
                                    required>
                                    <option value="">Pilih jenis dokumen</option>
                                    <option value="publication" {{ old('type') === 'publication' ? 'selected' : '' }}>
                                        📚 Publikasi
                                    </option>
                                    <option value="brs" {{ old('type') === 'brs' ? 'selected' : '' }}>
                                        📰 BRS (Berita Resmi Statistik)
                                    </option>
                                </select>
                            </div>

                            <!-- Year -->
                            <div>
                                <label for="year" class="block text-sm font-medium text-gray-700 mb-2 text-sound">
                                    <i class="fas fa-calendar-alt mr-1" aria-hidden="true"></i>
                                    Tahun *
                                </label>
                                <select id="year" name="year"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 hover-sound"
                                    required>
                                    <option value="">Pilih tahun</option>
                                    @for ($year = date('Y') + 1; $year >= 2020; $year--)
                                        <option value="{{ $year }}"
                                            {{ old('year', date('Y')) == $year ? 'selected' : '' }}>
                                            {{ $year }}
                                        </option>
                                    @endfor
                                </select>
                            </div>

                            <!-- Indicator -->
                            <div class="md:col-span-2">
                                <label for="indicator_id" class="block text-sm font-medium text-gray-700 mb-2 text-sound">
                                    <i class="fas fa-chart-line mr-1" aria-hidden="true"></i>
                                    Indikator Statistik *
                                </label>
                                <select id="indicator_id" name="indicator_id"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 hover-sound"
                                    required>
                                    <option value="">Pilih indikator yang sesuai</option>
                                    @foreach ($indicators as $indicator)
                                        <option value="{{ $indicator->id }}"
                                            {{ old('indicator_id') == $indicator->id ? 'selected' : '' }}>
                                            {{ $indicator->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="text-xs text-gray-500 mt-1 text-sound">Pilih indikator yang paling sesuai dengan
                                    konten dokumen</p>
                            </div>

                            <!-- Description -->
                            <div class="md:col-span-2">
                                <label for="description" class="block text-sm font-medium text-gray-700 mb-2 text-sound">
                                    <i class="fas fa-align-left mr-1" aria-hidden="true"></i>
                                    Deskripsi (Opsional)
                                </label>
                                <textarea id="description" name="description" rows="4"
                                    placeholder="Berikan deskripsi singkat tentang isi dokumen untuk membantu pengguna memahami konten..."
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 hover-sound"
                                    maxlength="1000">{{ old('description') }}</textarea>
                                <div class="flex justify-between items-center mt-1">
                                    <p class="text-xs text-gray-500 text-sound">Deskripsi akan muncul di halaman detail dan
                                        hasil pencarian</p>
                                    <p class="text-xs text-gray-400 text-sound"><span id="desc-count">0</span>/1000</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Right Column: Cover Upload -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-sm p-6 sticky top-8">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4 text-sound">
                            <i class="fas fa-image mr-2 text-purple-600" aria-hidden="true"></i>
                            3. Cover Dokumen
                        </h2>

                        <!-- Cover Upload -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2 text-sound">
                                Upload Cover *
                            </label>
                            <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 hover:border-purple-400 transition-colors text-center"
                                id="cover-drop-zone">
                                <div id="cover-placeholder">
                                    <div
                                        class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                        <i class="fas fa-image text-xl text-purple-600" aria-hidden="true"></i>
                                    </div>
                                    <label for="cover_image" class="cursor-pointer">
                                        <span
                                            class="text-sm font-medium text-gray-900 hover:text-purple-600 transition-colors text-sound">Pilih
                                            cover</span>
                                        <input type="file" id="cover_image" name="cover_image" class="sr-only"
                                            accept="image/*">
                                    </label>
                                    <p class="text-xs text-gray-500 mt-1 text-sound">JPEG, PNG (Max 2MB)</p>
                                </div>

                                <!-- Cover Preview -->
                                <div id="cover-preview" class="hidden">
                                    <img id="cover-preview-img" src="" alt="Cover Preview"
                                        class="w-full aspect-[3/4] object-cover rounded-lg mb-3">
                                    <div class="flex justify-between items-center">
                                        <span id="cover-file-name" class="text-sm text-gray-700 text-sound"></span>
                                        <button type="button" id="remove-cover"
                                            class="text-red-600 hover:text-red-700 hover-sound">
                                            <i class="fas fa-trash text-sm" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Processing Preview -->
                        <div class="bg-gradient-to-r from-blue-50 to-purple-50 rounded-lg p-4">
                            <h3 class="text-sm font-semibold text-gray-900 mb-3 text-sound">
                                <i class="fas fa-cogs mr-1" aria-hidden="true"></i>
                                Proses Otomatis
                            </h3>
                            <div class="space-y-3 text-xs">
                                <div class="flex items-center space-x-2">
                                    <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                    <span class="text-gray-700 text-sound">Ekstraksi teks dengan AI</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                    <span class="text-gray-700 text-sound">Konversi ke MP3 & FLAC</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <div class="w-2 h-2 bg-orange-500 rounded-full"></div>
                                    <span class="text-gray-700 text-sound">Publikasi ke website</span>
                                </div>
                            </div>

                            <div class="mt-4 p-3 bg-white rounded-md border-l-4 border-blue-500">
                                <p class="text-xs text-blue-800 text-sound">
                                    <i class="fas fa-clock mr-1" aria-hidden="true"></i>
                                    <strong>Estimasi waktu:</strong> 7-15 menit
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center justify-between">
                    <a href="{{ route('admin.documents.index') }}"
                        class="inline-flex items-center px-6 py-3 border border-gray-300 text-gray-700 hover:bg-gray-50 font-medium rounded-lg transition-colors hover-sound">
                        <i class="fas fa-arrow-left mr-2" aria-hidden="true"></i>
                        <span class="text-sound">Kembali</span>
                    </a>

                    <div class="flex items-center space-x-4">
                        <button type="button" id="preview-btn"
                            class="inline-flex items-center px-6 py-3 border border-blue-600 text-blue-600 hover:bg-blue-50 font-medium rounded-lg transition-colors hover-sound"
                            disabled>
                            <i class="fas fa-eye mr-2" aria-hidden="true"></i>
                            <span class="text-sound">Preview</span>
                        </button>

                        <button type="submit" id="submit-btn"
                            class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors hover-sound"
                            disabled>
                            <i class="fas fa-cloud-upload-alt mr-2" aria-hidden="true"></i>
                            <span class="text-sound">Upload & Proses dengan TTS</span>
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <!-- Preview Modal -->
        <div id="preview-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
            <div class="bg-white rounded-lg p-6 max-w-3xl mx-4 w-full max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 text-sound">Preview Upload</h3>
                    <button type="button" id="close-preview" class="text-gray-400 hover:text-gray-600 hover-sound">
                        <i class="fas fa-times text-xl" aria-hidden="true"></i>
                    </button>
                </div>

                <div id="preview-content" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Preview content will be populated by JavaScript -->
                </div>

                <div class="flex justify-end mt-6 pt-4 border-t border-gray-200">
                    <button type="button" id="close-preview-btn"
                        class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition-colors hover-sound">
                        <span class="text-sound">Tutup</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {

                const fileInput = document.getElementById('file');
                const coverInput = document.getElementById('cover_image');
                const fileDropZone = document.getElementById('file-drop-zone');
                const coverDropZone = document.getElementById('cover-drop-zone');
                const filePreview = document.getElementById('file-preview');
                const coverPreview = document.getElementById('cover-preview');
                const coverPlaceholder = document.getElementById('cover-placeholder');
                const fileName = document.getElementById('file-name');
                const fileSize = document.getElementById('file-size');
                const coverFileName = document.getElementById('cover-file-name');
                const coverPreviewImg = document.getElementById('cover-preview-img');
                const removeFileBtn = document.getElementById('remove-file');
                const removeCoverBtn = document.getElementById('remove-cover');
                const submitBtn = document.getElementById('submit-btn');
                const previewBtn = document.getElementById('preview-btn');
                const descriptionTextarea = document.getElementById('description');
                const descCount = document.getElementById('desc-count');
                const previewModal = document.getElementById('preview-modal');
                const closePreview = document.getElementById('close-preview');
                const closePreviewBtn = document.getElementById('close-preview-btn');

                // File & cover change handlers
                fileInput.addEventListener('change', handleFileSelect);
                coverInput.addEventListener('change', handleCoverSelect);

                // Drag & drop
                setupDragDrop(fileDropZone, fileInput, 'file');
                setupDragDrop(coverDropZone, coverInput, 'cover');

                // Remove file/cover
                removeFileBtn.addEventListener('click', () => {
                    fileInput.value = '';
                    filePreview.classList.add('hidden');
                    updateSubmitButton();
                    announceToScreenReader('File dokumen dihapus');
                });

                removeCoverBtn.addEventListener('click', () => {
                    coverInput.value = '';
                    coverPreview.classList.add('hidden');
                    coverPlaceholder.classList.remove('hidden');
                    updateSubmitButton();
                    announceToScreenReader('Cover image dihapus');
                });

                // Description counter
                descriptionTextarea.addEventListener('input', () => {
                    const count = descriptionTextarea.value.length;
                    descCount.textContent = count;
                    descCount.classList.toggle('text-red-500', count > 900);
                    descCount.classList.toggle('text-gray-400', count <= 900);
                });

                // Preview modal
                previewBtn.addEventListener('click', showPreview);
                closePreview.addEventListener('click', () => previewModal.classList.add('hidden'));
                closePreviewBtn.addEventListener('click', () => previewModal.classList.add('hidden'));

                // Form submit
                const form = document.querySelector('form');
                form.addEventListener('submit', (e) => {
                    if (!validateForm()) {
                        e.preventDefault();
                        return false;
                    }

                    submitBtn.disabled = true;
                    submitBtn.innerHTML =
                        '<i class="fas fa-spinner fa-spin mr-2"></i><span class="text-sound">Mengunggah dan memproses...</span>';
                    announceToScreenReader('Mengunggah dokumen dan memulai proses TTS, mohon tunggu...');
                });

                // Helper functions
                function setupDragDrop(dropZone, input, type) {
                    dropZone.addEventListener('dragover', (e) => {
                        e.preventDefault();
                        dropZone.classList.add('border-blue-400', 'bg-blue-50');
                    });
                    dropZone.addEventListener('dragleave', (e) => {
                        e.preventDefault();
                        dropZone.classList.remove('border-blue-400', 'bg-blue-50');
                    });
                    dropZone.addEventListener('drop', (e) => {
                        e.preventDefault();
                        dropZone.classList.remove('border-blue-400', 'bg-blue-50');
                        if (e.dataTransfer.files.length > 0) {
                            const dt = new DataTransfer();
                            dt.items.add(e.dataTransfer.files[0]);
                            input.files = dt.files;
                            type === 'file' ? handleFileSelect() : handleCoverSelect();
                        }
                    });
                }

                function handleFileSelect() {
                    const file = fileInput.files[0];
                    if (!file) return;

                    const allowedTypes = [
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                    ];
                    if (!allowedTypes.includes(file.type)) {
                        alert('Format file tidak didukung. Gunakan PDF, DOC, atau DOCX.');
                        fileInput.value = '';
                        updateSubmitButton();
                        return;
                    }

                    if (file.size > 10 * 1024 * 1024) {
                        alert('Ukuran file terlalu besar. Maksimal 10MB.');
                        fileInput.value = '';
                        updateSubmitButton();
                        return;
                    }

                    fileName.textContent = file.name;
                    fileSize.textContent = formatFileSize(file.size);
                    filePreview.classList.remove('hidden');

                    // ✅ Auto-populate title + capitalize words
                    const titleInput = document.getElementById('title');
                    if (!titleInput.value) {
                        let cleanTitle = file.name
                            .replace(/\.[^/.]+$/, "") // hapus ekstensi
                            .replace(/[_-]/g, ' ') // ganti _ atau - jadi spasi
                            .trim();

                        // Capitalize tiap kata
                        cleanTitle = cleanTitle.replace(/\b\w/g, (c) => c.toUpperCase());

                        titleInput.value = cleanTitle;

                        // ✅ Cek apakah ada tahun (2020–2030 misalnya)
                        const yearMatch = cleanTitle.match(/\b(20\d{2})\b/);
                        if (yearMatch) {
                            const yearSelect = document.getElementById('year');
                            const year = yearMatch[1];
                            if ([...yearSelect.options].some(opt => opt.value == year)) {
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

                    if (!file.type.startsWith('image/')) {
                        alert('File cover harus berupa gambar.');
                        coverInput.value = '';
                        updateSubmitButton();
                        return;
                    }

                    if (file.size > 2 * 1024 * 1024) {
                        alert('Ukuran cover terlalu besar. Maksimal 2MB.');
                        coverInput.value = '';
                        updateSubmitButton();
                        return;
                    }

                    // ✅ Tampilkan preview kalau lolos validasi
                    const reader = new FileReader();
                    reader.onload = (ev) => {
                        coverPreviewImg.src = ev.target.result;
                        coverFileName.textContent = file.name;
                        coverPlaceholder.classList.add('hidden');
                        coverPreview.classList.remove('hidden');
                    };
                    reader.readAsDataURL(file);

                    // ✅ Update tombol
                    updateSubmitButton();
                }

                function updateSubmitButton() {
                    const hasFile = fileInput.files.length > 0;
                    const hasCover = coverInput.files.length > 0;
                    const hasType = document.getElementById('type').value !== '';
                    const hasYear = document.getElementById('year').value !== '';
                    const hasIndicator = document.getElementById('indicator_id').value !== '';

                    const isValid = hasFile && hasCover && hasType && hasYear && hasIndicator;

                    submitBtn.disabled = !isValid;
                    previewBtn.disabled = !isValid;

                    if (isValid) {
                        submitBtn.classList.remove('bg-gray-400', 'cursor-not-allowed');
                        submitBtn.classList.add('bg-blue-600', 'hover:bg-blue-700');
                        previewBtn.classList.remove('border-gray-300', 'text-gray-400');
                        previewBtn.classList.add('border-blue-600', 'text-blue-600');
                    } else {
                        submitBtn.classList.add('bg-gray-400', 'cursor-not-allowed');
                        submitBtn.classList.remove('bg-blue-600', 'hover:bg-blue-700');
                        previewBtn.classList.add('border-gray-300', 'text-gray-400');
                        previewBtn.classList.remove('border-blue-600', 'text-blue-600');
                    }
                    // console.log("File:", fileInput.files);
                    // console.log("Cover:", coverInput.files);

                }

                function validateForm() {
                    let isValid = true;
                    const errors = [];

                    if (!fileInput.files.length) {
                        isValid = false;
                        errors.push('File dokumen harus diisi');
                    }
                    if (!coverInput.files.length) {
                        isValid = false;
                        errors.push('Cover dokumen harus diisi');
                    }
                    if (!document.getElementById('type').value) {
                        isValid = false;
                        errors.push('Jenis dokumen harus dipilih');
                    }
                    if (!document.getElementById('year').value) {
                        isValid = false;
                        errors.push('Tahun harus dipilih');
                    }
                    if (!document.getElementById('indicator_id').value) {
                        isValid = false;
                        errors.push('Indikator harus dipilih');
                    }

                    if (!isValid) announceToScreenReader('Formulir tidak valid: ' + errors.join(', '));

                    return isValid;
                }

                function showPreview() {
                    const formData = {
                        title: document.getElementById('title').value,
                        type: document.getElementById('type').selectedOptions[0].text,
                        year: document.getElementById('year').value,
                        indicator: document.getElementById('indicator_id').selectedOptions[0].text,
                        description: descriptionTextarea.value,
                        fileName: fileInput.files[0]?.name || '',
                        fileSize: fileInput.files[0] ? formatFileSize(fileInput.files[0].size) : '',
                        hasCover: coverInput.files.length > 0,
                        coverName: coverInput.files[0]?.name || ''
                    };

                    const previewContent = document.getElementById('preview-content');
                    previewContent.innerHTML = `
            <div class="md:col-span-2">
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-gray-900 mb-3 text-sound">Informasi Dokumen</h4>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                        <div><dt class="font-medium text-gray-700 text-sound">Judul:</dt><dd class="text-gray-900 text-sound">${formData.title}</dd></div>
                        <div><dt class="font-medium text-gray-700 text-sound">Jenis:</dt><dd class="text-gray-900 text-sound">${formData.type}</dd></div>
                        <div><dt class="font-medium text-gray-700 text-sound">Tahun:</dt><dd class="text-gray-900 text-sound">${formData.year}</dd></div>
                        <div><dt class="font-medium text-gray-700 text-sound">Indikator:</dt><dd class="text-gray-900 text-sound">${formData.indicator}</dd></div>
                        <div class="sm:col-span-2"><dt class="font-medium text-gray-700 text-sound">File:</dt><dd class="text-gray-900 text-sound">${formData.fileName} (${formData.fileSize})</dd></div>
                        <div class="sm:col-span-2"><dt class="font-medium text-gray-700 text-sound">Cover:</dt><dd class="text-gray-900 text-sound">${formData.coverName}</dd></div>
                        ${formData.description ? `<div class="sm:col-span-2"><dt class="font-medium text-gray-700 text-sound">Deskripsi:</dt><dd class="text-gray-900 text-sound">${formData.description}</dd></div>` : ''}
                    </dl>
                </div>
            </div>
            <div class="md:col-span-1">
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-gray-900 mb-3 text-sound">Cover Preview</h4>
                    <img src="${coverPreviewImg.src}" alt="Cover Preview" class="w-full aspect-[3/4] object-cover rounded-lg">
                </div>
            </div>
        `;

                    previewModal.classList.remove('hidden');
                    announceToScreenReader('Menampilkan preview dokumen');
                }

                function formatFileSize(bytes) {
                    if (bytes === 0) return '0 Bytes';
                    const k = 1024;
                    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                    const i = Math.floor(Math.log(bytes) / Math.log(k));
                    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
                }

                // Field listeners
                ['title', 'type', 'year', 'indicator_id'].forEach(id => {
                    const el = document.getElementById(id);
                    el.addEventListener('change', updateSubmitButton);
                    el.addEventListener('input', updateSubmitButton);
                });

                function playTextHoverSound() {
                    try {
                        const audioContext = new(window.AudioContext || window.webkitAudioContext)();
                        const oscillator = audioContext.createOscillator();
                        const gainNode = audioContext.createGain();

                        oscillator.connect(gainNode);
                        gainNode.connect(audioContext.destination);

                        oscillator.frequency.setValueAtTime(900, audioContext.currentTime);
                        gainNode.gain.setValueAtTime(0, audioContext.currentTime);
                        gainNode.gain.linearRampToValueAtTime(0.04, audioContext.currentTime + 0.01);
                        gainNode.gain.exponentialRampToValueAtTime(0.001, audioContext.currentTime + 0.1);

                        oscillator.start(audioContext.currentTime);
                        oscillator.stop(audioContext.currentTime + 0.1);
                    } catch (e) {
                        // Silently fail if Web Audio API is not supported
                    }
                }

                function announceToScreenReader(message) {
                    const announcement = document.createElement('div');
                    announcement.setAttribute('aria-live', 'polite');
                    announcement.setAttribute('aria-atomic', 'true');
                    announcement.className = 'sr-only';
                    announcement.textContent = message;
                    document.body.appendChild(announcement);

                    setTimeout(() => {
                        if (document.body.contains(announcement)) {
                            document.body.removeChild(announcement);
                        }
                    }, 1000);
                }

                function hasCover() {
                    const coverInput = document.getElementById('cover_image');
                    // Hanya valid jika user memilih file manual
                    return coverInput && coverInput.files && coverInput.files.length > 0;
                }

                updateSubmitButton();

            });
        </script>
    @endpush
@endsection
