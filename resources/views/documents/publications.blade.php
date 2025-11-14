@extends('layouts.app')

@section('title', 'Publikasi - Audio Statistik')

@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-4">Publikasi</h1>
            <p class="text-gray-600">Publikasi terkini dari BPS Sulawesi Utara dalam format audio yang mudah diakses</p>
        </div>

        <!-- Filter Section -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
            <form method="GET" action="{{ route('documents.publications') }}" class="flex flex-wrap gap-4 items-end">
                <div class="flex-1 min-w-64">
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-2">
                        Cari Publikasi
                    </label>
                    <div class="relative">
                        <input type="text" id="search" name="search"
                            value="{{ $query ?? ($originalQuery ?? request('search')) }}"
                            placeholder="Cari judul Publikasi..."
                            class="w-full px-4 py-2 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 hover-sound">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400" aria-hidden="true"></i>
                        </div>
                    </div>
                </div>

                <div class="w-32">
                    <label for="year" class="block text-sm font-medium text-gray-700 mb-2">Tahun</label>
                    <select id="year" name="year"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 hover-sound">
                        <option value="">Semua</option>
                        @foreach ($years as $y)
                            <option value="{{ $y }}"
                                {{ (string) request('year') === (string) $y ? 'selected' : '' }}>
                                {{ $y }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="w-48">
                    <label for="indicator" class="block text-sm font-medium text-gray-700 mb-2">Indikator</label>
                    <select id="indicator" name="indicator"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Semua Indikator</option>
                        @foreach ($indicators as $indicatorOption)
                            <option value="{{ $indicatorOption->id }}"
                                {{ (request('indicator') ?? $indicator) == $indicatorOption->id ? 'selected' : '' }}>
                                {{ $indicatorOption->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button type="submit"
                    class="px-6 py-2 bg-blue-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors focus:ring-2 focus:ring-green-500 focus:ring-offset-2 hover-sound">
                    <i class="fas fa-search mr-2" aria-hidden="true"></i>Filter
                </button>
            </form>
        </div>

        <!-- Results Count -->
        @if ($documents->count() > 0)
            <div class="mb-6">
                <p class="text-gray-600">
                    Menampilkan {{ number_format($documents->count()) }} dari {{ number_format($documents->total()) }}
                    Publikasi
                </p>
            </div>
        @endif

        <!-- Documents Grid -->
        @if ($documents->count() > 0)
            <div id="documents-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
                @foreach ($documents as $index => $document)
                    <x-document_card :document="$document" :index="$index" />
                    {{-- <div class="bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-shadow group hover-sound"
                        data-document-index="{{ $index + 1 }}" data-document-id="{{ $document->id }}">

                        <!-- Document Cover -->
                        <div class="aspect-[3/4] bg-gray-200 relative overflow-hidden">
                            <img src="{{ Storage::disk('documents')->url($document->cover_path) }}?v={{ $document->updated_at->timestamp }}"
                                alt="Cover {{ $document->title }}"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                loading="lazy" onerror="this.src='/images/default-document-cover.jpg'">

                            <!-- Play Overlay -->
                            <div
                                class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-40 transition-all duration-300 flex items-center justify-center">
                                @if ($document->mp3_path)
                                    <button type="button"
                                        class="play-document-btn w-16 h-16 bg-blue-600 hover:bg-blue-700 text-white rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center transform hover:scale-110 hover-sound"
                                        data-document='@json($document)' data-document-id="{{ $document->id }}"
                                        data-title="{{ $document->title }}"
                                        aria-label="Putar audio {{ $document->title }}">
                                        <i class="fas fa-play text-xl ml-1" aria-hidden="true"></i>
                                    </button>
                                @else
                                    <div
                                        class="w-16 h-16 bg-gray-400 text-white rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center">
                                        <i class="fas fa-clock text-xl" aria-hidden="true"></i>
                                    </div>
                                @endif
                            </div>

                            <!-- Type Badge -->
                            <div class="absolute top-3 left-3">
                                <span
                                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium 
                                    {{ $document->type === 'publication' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                                    <i
                                        class="fas fa-{{ $document->type === 'publication' ? 'book' : 'newspaper' }} mr-1"></i>
                                    {{ $document->type === 'publication' ? 'Publikasi' : 'BRS' }}
                                </span>
                            </div>

                            <!-- Audio Available Indicator -->
                            @if ($document->mp3_path)
                                <div class="absolute top-3 right-3">
                                    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center"
                                        title="Audio tersedia">
                                        <i class="fas fa-headphones text-white text-sm"></i>
                                    </div>
                                </div>
                            @endif

                            <!-- Document Number for Voice Navigation -->
                            <div class="absolute bottom-3 right-3">
                                <span
                                    class="inline-flex items-center justify-center w-8 h-8 bg-black bg-opacity-70 text-white text-sm font-bold rounded-full">
                                    {{ $index + 1 }}
                                </span>
                            </div>
                        </div>

                        <!-- Document Info -->
                        <div class="p-4">
                            <div class="mb-2">
                                <span class="text-xs text-gray-500">{{ $document->indicator->name }}</span>
                                <span class="text-xs text-gray-400 mx-2">•</span>
                                <span class="text-xs text-gray-500">{{ $document->year }}</span>
                            </div>

                            <h3
                                class="font-semibold text-gray-900 text-sm mb-2 line-clamp-2 group-hover:text-blue-600 transition-colors">
                                <a href="{{ route('documents.show', $document) }}" class="hover-sound text-sound">
                                    {{ $document->title }}
                                </a>
                            </h3>

                            @if ($document->description)
                                <p class="text-xs text-gray-600 mb-3 line-clamp-2 text-sound">
                                    {{ $document->description }}
                                </p>
                            @else
                                <p class="text-xs text-gray-400 mb-3 italic text-sound">
                                    Maaf deskripsi dari dokumen ini masih belum tersedia.
                                </p>
                            @endif

                            <div class="flex items-center justify-between text-xs text-gray-500">
                                <span class="flex items-center text-sound">
                                    <i class="fas fa-calendar mr-1" aria-hidden="true"></i>
                                    {{ $document->created_at->format('M Y') }}
                                </span>

                                @if ($document->mp3_path)
                                    <span class="flex items-center text-sound">
                                        <i class="fas fa-clock mr-1" aria-hidden="true"></i>
                                        {{ $document->getAudioDurationFormatted() }}
                                    </span>
                                @endif
                            </div>

                            <!-- Action Buttons -->
                            <div class="mt-4 flex space-x-2">
                                @if ($document->mp3_path)
                                    <button type="button"
                                        class="play-document-btn flex-1 px-3 py-2 bg-{{ $document->type === 'publication' ? 'blue' : 'green' }}-600 hover:bg-{{ $document->type === 'publication' ? 'blue' : 'green' }}-700 text-white text-xs font-medium rounded-md transition-colors hover-sound"
                                        data-document='@json($document)'>
                                        <i class="fas fa-play mr-1" aria-hidden="true"></i>
                                        <span class="text-sound">Putar</span>
                                    </button>
                                @else
                                    <div
                                        class="flex-1 px-3 py-2 bg-gray-300 text-gray-500 text-xs font-medium rounded-md text-center">
                                        <i class="fas fa-clock mr-1" aria-hidden="true"></i>
                                        <span class="text-sound">Diproses</span>
                                    </div>
                                @endif

                                <a href="{{ route('documents.show', $document) }}"
                                    class="px-3 py-2 border border-gray-300 hover:border-gray-400 text-gray-700 hover:text-gray-900 text-xs font-medium rounded-md transition-colors hover-sound">
                                    <i class="fas fa-eye" aria-hidden="true"></i>
                                </a>
                            </div>
                        </div>
                    </div> --}}
                @endforeach
            </div>

            <!-- Pagination -->
            @if ($documents->hasPages())
                <div class="flex justify-center">
                    {{ $documents->withQueryString()->links() }}
                </div>
            @endif
        @else
            <!-- No Results -->
            <div class="text-center py-16">
                <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-newspaper text-3xl text-gray-400" aria-hidden="true"></i>
                </div>
                <h2 class="text-xl font-semibold text-gray-900 mb-2">Tidak ada Publikasi ditemukan</h2>
                <p class="text-gray-600 mb-6">
                    @if (request()->hasAny(['search', 'year', 'indicator']))
                        Coba ubah filter pencarian atau hapus beberapa filter.
                    @else
                        Belum ada Publikasi yang tersedia saat ini.
                    @endif
                </p>
                @if (request()->hasAny(['search', 'year', 'indicator']))
                    <a href="{{ route('documents.publications') }}"
                        class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors hover-sound">
                        <i class="fas fa-refresh mr-2" aria-hidden="true"></i>Reset Filter
                    </a>
                @endif
            </div>
        @endif
    </div>

    {{-- Voice Navigation Controls --}}
    <div id="voice-navigation-controls" class="fixed bottom-4 right-4 z-40">
        <div class="flex flex-col space-y-2">
            {{-- Voice Activation Button --}}
            <button id="voice-activation-btn"
                class="w-14 h-14 bg-blue-600 hover:bg-blue-700 text-white rounded-full shadow-lg flex items-center justify-center transition-all duration-300 hover:scale-110"
                aria-label="Aktivasi Voice Navigation"
                title="Tekan untuk mengaktifkan/menonaktifkan voice navigation (Ctrl+P)">
                <i class="fas fa-microphone text-xl"></i>
            </button>

            {{-- Voice Help Button --}}
            <button id="voice-help-btn"
                class="w-14 h-14 bg-green-600 hover:bg-green-700 text-white rounded-full shadow-lg flex items-center justify-center transition-all duration-300 hover:scale-110"
                aria-label="Bantuan Voice Navigation" title="Bantuan voice navigation (Ctrl+H)">
                <i class="fas fa-question text-xl"></i>
            </button>
        </div>
    </div>

    {{-- Voice Status Indicator --}}
    <div id="voice-status" class="fixed top-4 left-4 z-50 hidden">
        <div class="bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg flex items-center space-x-2">
            <div class="w-3 h-3 bg-white rounded-full animate-pulse"></div>
            <span>Voice Navigation Aktif</span>
        </div>
    </div>

    {{-- Voice Commands Modal --}}
    <div id="voice-commands-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg max-w-2xl w-full p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-gray-900">Perintah Voice Navigation - Publikasi</h3>
                    <button id="close-voice-modal" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="font-semibold text-gray-900 mb-3">📄 Perintah Dokumen</h4>
                        <ul class="space-y-2 text-sm text-gray-700">
                            <li><code class="bg-gray-100 px-2 py-1 rounded">"Baca dokumen"</code> - Bacakan daftar dokumen
                            </li>
                            <li><code class="bg-gray-100 px-2 py-1 rounded">"Pilih dokumen 1"</code> - Buka dokumen nomor 1
                            </li>
                            <li><code class="bg-gray-100 px-2 py-1 rounded">"Putar dokumen 2"</code> - Putar audio dokumen
                                2</li>
                            <li><code class="bg-gray-100 px-2 py-1 rounded">"Berapa dokumen"</code> - Jumlah dokumen</li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="font-semibold text-gray-900 mb-3">🔍 Perintah Filter</h4>
                        <ul class="space-y-2 text-sm text-gray-700">
                            <li><code class="bg-gray-100 px-2 py-1 rounded">"Filter tahun 2023"</code> - Filter berdasarkan
                                tahun</li>
                            <li><code class="bg-gray-100 px-2 py-1 rounded">"Filter indikator inflasi"</code> - Filter
                                indikator</li>
                            <li><code class="bg-gray-100 px-2 py-1 rounded">"Cari penduduk"</code> - Pencarian kata kunci
                            </li>
                            <li><code class="bg-gray-100 px-2 py-1 rounded">"Hapus filter"</code> - Reset semua filter</li>
                            <li><code class="bg-gray-100 px-2 py-1 rounded">"Persempit pencarian"</code> - Saran filter
                            </li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="font-semibold text-gray-900 mb-3">🧭 Perintah Navigasi</h4>
                        <ul class="space-y-2 text-sm text-gray-700">
                            <li><code class="bg-gray-100 px-2 py-1 rounded">"Halaman selanjutnya"</code> - Halaman berikut
                            </li>
                            <li><code class="bg-gray-100 px-2 py-1 rounded">"Halaman sebelumnya"</code> - Halaman sebelum
                            </li>
                            <li><code class="bg-gray-100 px-2 py-1 rounded">"Bantuan"</code> - Tampilkan bantuan</li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="font-semibold text-gray-900 mb-3">⌨️ Keyboard Shortcuts</h4>
                        <ul class="space-y-2 text-sm text-gray-700">
                            <li><kbd class="bg-gray-200 px-2 py-1 rounded">Ctrl + P</kbd> - Toggle voice navigation</li>
                            <li><kbd class="bg-gray-200 px-2 py-1 rounded">Ctrl + H</kbd> - Bantuan voice</li>
                            <li><kbd class="bg-gray-200 px-2 py-1 rounded">Ctrl + R</kbd> - Baca dokumen</li>
                            <li><kbd class="bg-gray-200 px-2 py-1 rounded">Esc</kbd> - Tutup modal</li>
                        </ul>
                    </div>
                </div>

                <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                    <h5 class="font-medium text-blue-900 mb-2">💡 Tips Penggunaan:</h5>
                    <ul class="text-sm text-blue-800 space-y-1">
                        <li>• Jika dokumen ≤ 5, akan dibacakan otomatis</li>
                        <li>• Jika dokumen > 5, gunakan filter untuk mempersempit</li>
                        <li>• Pastikan microphone diizinkan oleh browser</li>
                        <li>• Ucapkan perintah dengan jelas dalam bahasa Indonesia</li>
                    </ul>
                </div>

                <div class="mt-4 flex justify-end">
                    <button id="close-voice-modal-btn"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function clearAllFilters() {
                window.location.href = "{{ route('documents.publications') }}";
            }
        </script>
    @endpush
@endsection
