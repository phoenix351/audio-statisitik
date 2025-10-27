@extends('layouts.app')

@section('title', 'Edit Dokumen - Admin')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center space-x-2 text-sm text-gray-500 mb-2">
            <a href="{{ route('admin.dashboard') }}" class="hover:text-blue-600 hover-sound text-sound">Dashboard</a>
            <i class="fas fa-chevron-right" aria-hidden="true"></i>
            <a href="{{ route('admin.documents.index') }}" class="hover:text-blue-600 hover-sound text-sound">Dokumen</a>
            <i class="fas fa-chevron-right" aria-hidden="true"></i>
            <span class="text-sound">Edit Dokumen</span>
        </div>
        <h1 class="text-3xl font-bold text-gray-900 text-sound">Edit Dokumen</h1>
        <p class="text-gray-600 mt-2 text-sound">Perbarui informasi dokumen yang sudah ada</p>
    </div>

    <!-- Messages -->
    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex">
                <i class="fas fa-check-circle text-green-400 mr-3 mt-1" aria-hidden="true"></i>
                <div class="text-sm text-green-800 text-sound">{{ session('success') }}</div>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex">
                <i class="fas fa-exclamation-circle text-red-400 mr-3 mt-1" aria-hidden="true"></i>
                <div class="text-sm text-red-800 text-sound">{{ session('error') }}</div>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex">
                <i class="fas fa-exclamation-triangle text-red-400 mr-3 mt-1" aria-hidden="true"></i>
                <div>
                    <h3 class="text-sm font-medium text-red-800 text-sound">Terdapat kesalahan:</h3>
                    <ul class="mt-2 text-sm text-red-700 space-y-1">
                        @foreach($errors->all() as $error)
                            <li class="text-sound">• {{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Main Edit Form -->
        <div class="lg:col-span-2">
            <form method="POST" action="{{ route('admin.documents.update', $document) }}" enctype="multipart/form-data" class="space-y-8">
                @csrf
                @method('PUT')
                
                <!-- Document Information -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4 text-sound">
                        <i class="fas fa-edit mr-2 text-blue-600" aria-hidden="true"></i>
                        Informasi Dokumen
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Title -->
                        <div class="md:col-span-2">
                            <label for="title" class="block text-sm font-medium text-gray-700 mb-2 text-sound">
                                <i class="fas fa-heading mr-1" aria-hidden="true"></i>
                                Judul Dokumen *
                            </label>
                            <input type="text" 
                                   id="title" 
                                   name="title" 
                                   value="{{ old('title', $document->title) }}"
                                   placeholder="Masukkan judul dokumen yang jelas dan deskriptif"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 hover-sound"
                                   required maxlength="255">
                        </div>

                        <!-- Document Type -->
                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700 mb-2 text-sound">
                                <i class="fas fa-tags mr-1" aria-hidden="true"></i>
                                Jenis Dokumen *
                            </label>
                            <select id="type" name="type" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 hover-sound" required>
                                <option value="">Pilih jenis dokumen</option>
                                <option value="publication" {{ old('type', $document->type) === 'publication' ? 'selected' : '' }}>
                                    📚 Publikasi
                                </option>
                                <option value="brs" {{ old('type', $document->type) === 'brs' ? 'selected' : '' }}>
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
                            <select id="year" name="year" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 hover-sound" required>
                                <option value="">Pilih tahun</option>
                                @for($year = date('Y') + 1; $year >= 2020; $year--)
                                    <option value="{{ $year }}" {{ old('year', $document->year) == $year ? 'selected' : '' }}>
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
                            <select id="indicator_id" name="indicator_id" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 hover-sound" required>
                                <option value="">Pilih indikator yang sesuai</option>
                                @foreach($indicators as $indicator)
                                    <option value="{{ $indicator->id }}" {{ old('indicator_id', $document->indicator_id) == $indicator->id ? 'selected' : '' }}>
                                        {{ $indicator->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Description -->
                        <div class="md:col-span-2">
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-2 text-sound">
                                <i class="fas fa-align-left mr-1" aria-hidden="true"></i>
                                Deskripsi
                            </label>
                            <textarea id="description" 
                                      name="description" 
                                      rows="4" 
                                      placeholder="Berikan deskripsi singkat tentang isi dokumen..."
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 hover-sound"
                                      maxlength="1000">{{ old('description', $document->description) }}</textarea>
                            <div class="flex justify-between items-center mt-1">
                                <p class="text-xs text-gray-500 text-sound">Deskripsi akan muncul di halaman detail dan hasil pencarian</p>
                                <p class="text-xs text-gray-400 text-sound"><span id="desc-count">{{ strlen($document->description ?? '') }}</span>/1000</p>
                            </div>
                        </div>

                        <!-- Active Status -->
                        <div class="md:col-span-2">
                            <div class="flex items-center">
                                <input type="checkbox" 
                                       id="is_active" 
                                       name="is_active" 
                                       value="1"
                                       {{ old('is_active', $document->is_active) ? 'checked' : '' }}
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="is_active" class="ml-2 block text-sm text-gray-700 text-sound">
                                    Dokumen aktif (ditampilkan di website)
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cover Image Update -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4 text-sound">
                        <i class="fas fa-image mr-2 text-purple-600" aria-hidden="true"></i>
                        Update Cover Dokumen
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Current Cover -->
                        <div>
                            <h3 class="text-sm font-medium text-gray-700 mb-2 text-sound">Cover Saat Ini</h3>
                            <img id="current-doc-cover"
                                src="{{ route('documents.cover', $document) }}?v={{ $document->updated_at->timestamp }}"
                                alt="Cover {{ $document->title }}"
                                class="w-full aspect-[3/4] object-cover rounded-lg border border-gray-300"
                                onerror="this.src='/images/default-document-cover.jpg'">
                        </div>
                        
                        <!-- New Cover Upload -->
                        <div>
                            <h3 class="text-sm font-medium text-gray-700 mb-2 text-sound">Upload Cover Baru (Opsional)</h3>
                            <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 hover:border-purple-400 transition-colors text-center" id="cover-drop-zone">
                                <div id="cover-placeholder">
                                    <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                        <i class="fas fa-image text-xl text-purple-600" aria-hidden="true"></i>
                                    </div>
                                    <label for="cover_image" class="cursor-pointer">
                                        <span class="text-sm font-medium text-gray-900 hover:text-purple-600 transition-colors text-sound">Pilih cover baru</span>
                                        <input type="file" id="cover_image" name="cover_image" class="sr-only" accept="image/*">
                                    </label>
                                    <p class="text-xs text-gray-500 mt-1 text-sound">JPEG, PNG (Max 2MB)</p>
                                </div>
                                
                                <!-- Cover Preview -->
                                <div id="cover-preview" class="hidden">
                                    <img id="cover-preview-img" src="" alt="Cover Preview" class="w-full aspect-[3/4] object-cover rounded-lg mb-3">
                                    <div class="flex justify-between items-center">
                                        <span id="cover-file-name" class="text-sm text-gray-700 text-sound"></span>
                                        <button type="button" id="remove-cover" class="text-red-600 hover:text-red-700 hover-sound">
                                            <i class="fas fa-trash text-sm" aria-hidden="true"></i>
                                        </button>
                                    </div>
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
                            <a href="{{ route('documents.show', $document) }}" 
                               target="_blank"
                               class="inline-flex items-center px-6 py-3 border border-green-600 text-green-600 hover:bg-green-50 font-medium rounded-lg transition-colors hover-sound">
                                <i class="fas fa-eye mr-2" aria-hidden="true"></i>
                                <span class="text-sound">Lihat Dokumen</span>
                            </a>
                            
                            <button type="submit" 
                                    class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors hover-sound">
                                <i class="fas fa-save mr-2" aria-hidden="true"></i>
                                <span class="text-sound">Simpan Perubahan</span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Sidebar Info -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-sm p-6 sticky top-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 text-sound">
                    <i class="fas fa-info-circle mr-2 text-blue-600" aria-hidden="true"></i>
                    Informasi Dokumen
                </h3>
                
                <div class="space-y-4 text-sm">
                    <div>
                        <dt class="font-medium text-gray-700 text-sound">Dibuat:</dt>
                        <dd class="mt-1 text-gray-600 text-sound">{{ $document->created_at->format('d M Y H:i') }}</dd>
                    </div>
                    
                    <div>
                        <dt class="font-medium text-gray-700 text-sound">Diperbarui:</dt>
                        <dd class="mt-1 text-gray-600 text-sound">{{ $document->updated_at->format('d M Y H:i') }}</dd>
                    </div>
                    
                    {{-- <div>
                        <dt class="font-medium text-gray-700 text-sound">Dibuat oleh:</dt>
                        <dd class="mt-1 text-gray-600 text-sound">{{ $document->creator->name ?? 'Unknown' }}</dd>
                    </div> --}}
                    
                    <div>
                        <dt class="font-medium text-gray-700 text-sound">Download:</dt>
                        <dd class="mt-1 text-gray-600 text-sound">{{ number_format($document->download_count) }}x</dd>
                    </div>
                    
                    <div>
                        <dt class="font-medium text-gray-700 text-sound">Pemutaran:</dt>
                        <dd class="mt-1 text-gray-600 text-sound">{{ number_format($document->play_count) }}x</dd>
                    </div>
                </div>
                
                @if($document->status === 'failed')
                <div class="mt-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <h4 class="text-sm font-medium text-red-800 text-sound">Pemrosesan Gagal</h4>
                    <p class="text-xs text-red-600 mt-1 text-sound">Dokumen ini gagal diproses. Anda dapat mencoba memproses ulang.</p>
                    <form action="{{ route('admin.documents.reprocess', $document) }}" method="POST" class="mt-3">
                        @csrf
                        <button type="submit" 
                                class="inline-flex items-center px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-medium rounded-md transition-colors hover-sound"
                                onclick="return confirm('Proses ulang dokumen ini?')">
                            <i class="fas fa-redo mr-1" aria-hidden="true"></i>
                            <span class="text-sound">Proses Ulang</span>
                        </button>
                    </form>
                </div>
                @endif
                
                @if($document->hasAudio())
                <div class="mt-6">
                    <h4 class="text-sm font-medium text-gray-700 mb-3 text-sound">Test Audio</h4>
                    @include('components.audio-player', ['document' => $document])
                </div>
                @endif
                
                <!-- Quick Actions -->
                <div class="mt-6 space-y-2">
                    <h4 class="text-sm font-medium text-gray-700 text-sound">Aksi Cepat</h4>
                    
                    @if($document->hasAudio())
                    <a href="{{ route('documents.audio.download', [$document, 'mp3']) }}" 
                       class="block w-full text-center px-3 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-medium rounded-md transition-colors hover-sound">
                        <i class="fas fa-download mr-1" aria-hidden="true"></i>
                        <span class="text-sound">Download MP3</span>
                    </a>
                    
                    <a href="{{ route('documents.audio.download', [$document, 'flac']) }}" 
                       class="block w-full text-center px-3 py-2 bg-green-50 hover:bg-green-100 text-green-700 text-xs font-medium rounded-md transition-colors hover-sound">
                        <i class="fas fa-download mr-1" aria-hidden="true"></i>
                        <span class="text-sound">Download FLAC</span>
                    </a>
                    @endif
                    
                    <button onclick="copyDocumentUrl()" 
                            class="block w-full text-center px-3 py-2 bg-gray-50 hover:bg-gray-100 text-gray-700 text-xs font-medium rounded-md transition-colors hover-sound">
                        <i class="fas fa-copy mr-1" aria-hidden="true"></i>
                        <span class="text-sound">Copy URL</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const coverInput = document.getElementById('cover_image');
    const coverDropZone = document.getElementById('cover-drop-zone');
    const coverPreview = document.getElementById('cover-preview');
    const coverPlaceholder = document.getElementById('cover-placeholder');
    const coverFileName = document.getElementById('cover-file-name');
    const coverPreviewImg = document.getElementById('cover-preview-img');
    const removeCoverBtn = document.getElementById('remove-cover');
    const descriptionTextarea = document.getElementById('description');
    const descCount = document.getElementById('desc-count');

    // Cover image handling
    coverInput.addEventListener('change', handleCoverSelect);
    
    // Drag and drop for cover
    setupDragDrop(coverDropZone, coverInput);

    // Remove cover handler
    removeCoverBtn.addEventListener('click', function() {
        coverInput.value = '';
        coverPreview.classList.add('hidden');
        coverPlaceholder.classList.remove('hidden');
        announceToScreenReader('Cover image dihapus');
    });

    // Description character counter
    descriptionTextarea.addEventListener('input', function() {
        const count = this.value.length;
        descCount.textContent = count;
        if (count > 900) {
            descCount.classList.add('text-red-500');
            descCount.classList.remove('text-gray-400');
        } else {
            descCount.classList.remove('text-red-500');
            descCount.classList.add('text-gray-400');
        }
    });

    function setupDragDrop(dropZone, input) {
        dropZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.add('border-purple-400', 'bg-purple-50');
        });

        dropZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.remove('border-purple-400', 'bg-purple-50');
        });

        dropZone.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.remove('border-purple-400', 'bg-purple-50');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                const dt = new DataTransfer();
                dt.items.add(files[0]);
                input.files = dt.files;
                handleCoverSelect();
            }
        });
    }

    function handleCoverSelect() {
        const file = coverInput.files[0];
        if (!file) return;

        // Validate image file
        if (!file.type.startsWith('image/')) {
            alert('File cover harus berupa gambar.');
            coverInput.value = '';
            return;
        }

        // Validate file size (2MB)
        if (file.size > 2 * 1024 * 1024) {
            alert('Ukuran cover terlalu besar. Maksimal 2MB.');
            coverInput.value = '';
            return;
        }

        // Show preview
        const reader = new FileReader();
        reader.onload = function(e) {
            coverPreviewImg.src = e.target.result;
            coverFileName.textContent = file.name;
            coverPlaceholder.classList.add('hidden');
            coverPreview.classList.remove('hidden');
            announceToScreenReader(`Cover image ${file.name} berhasil dipilih`);
        };
        reader.readAsDataURL(file);
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

    // Initialize text hover sounds
    const textElements = document.querySelectorAll('.text-sound');
    textElements.forEach(element => {
        element.addEventListener('mouseenter', function() {
            playTextHoverSound();
        });
    });

    function playTextHoverSound() {
        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
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
});

// Copy document URL function
function copyDocumentUrl() {
    const url = '{{ route("documents.show", $document) }}';
    
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(function() {
            alert('URL dokumen berhasil disalin ke clipboard');
        });
    } else {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = url;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        alert('URL dokumen berhasil disalin ke clipboard');
    }
}
</script>
@endpush
@endsection