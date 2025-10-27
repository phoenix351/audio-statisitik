@extends('layouts.app')

@section('title', 'Pencarian - Audio Statistik')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    <!-- Search Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-4">Pencarian Dokumen</h1>
        
        <!-- Search Form -->
        <form method="GET" action="{{ route('search') }}" class="space-y-4">
            <div class="flex flex-col lg:flex-row lg:items-end lg:space-x-4 space-y-4 lg:space-y-0">
                <!-- Text Search -->
                <div class="flex-1">
                    <label for="query" class="block text-sm font-medium text-gray-700 mb-2">
                        Kata Kunci
                    </label>
                    <div class="relative">
                        <input type="text" 
                               id="query"
                               name="query" 
                               value="{{ $query ?? request('query') }}"
                               placeholder="Cari judul, deskripsi, tahun, atau indikator..."
                               class="w-full px-4 py-2 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400" aria-hidden="true"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Type Filter -->
                <div class="w-full lg:w-48">
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-2">
                        Jenis Dokumen
                    </label>
                    <select id="type" name="type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Semua Jenis</option>
                        <option value="publication" {{ (request('type') ?? $type) === 'publication' ? 'selected' : '' }}>Publikasi</option>
                        <option value="brs" {{ (request('type') ?? $type) === 'brs' ? 'selected' : '' }}>BRS</option>
                    </select>
                </div>
                
                <!-- Year Filter -->
                <div class="w-full lg:w-32">
                    <label for="year" class="block text-sm font-medium text-gray-700 mb-2">
                        Tahun
                    </label>
                    <select id="year" name="year" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Semua</option>
                        @foreach($years as $yearOption)
                            <option value="{{ $yearOption }}" {{ (request('year') ?? $year) == $yearOption ? 'selected' : '' }}>
                                {{ $yearOption }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <!-- Indicator Filter -->
                <div class="w-full lg:w-64">
                    <label for="indicator" class="block text-sm font-medium text-gray-700 mb-2">
                        Indikator
                    </label>
                    <select id="indicator" name="indicator" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Semua Indikator</option>
                        @foreach($indicators as $indicatorOption)
                            <option value="{{ $indicatorOption->id }}" {{ (request('indicator') ?? $indicator) == $indicatorOption->id ? 'selected' : '' }}>
                                {{ $indicatorOption->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <!-- Search Button -->
                <div class="w-full lg:w-auto">
                    <button type="submit" 
                            class="w-full lg:w-auto px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        <i class="fas fa-search mr-2" aria-hidden="true"></i>Cari
                    </button>
                </div>
            </div>
        </form>
        
        <!-- Voice Search Indicator -->
        @if(request('voice'))
            <div class="mt-4 p-4 bg-blue-50 rounded-lg flex items-center">
                <i class="fas fa-microphone text-blue-600 mr-3" aria-hidden="true"></i>
                <div>
                    <p class="text-sm text-blue-800">
                        <strong>Pencarian Suara:</strong> "{{ $originalQuery }}"
                    </p>
                    <p class="text-xs text-blue-600">Hasil pencarian berdasarkan perintah suara Anda</p>
                </div>
            </div>
        @endif
    </div>

    <!-- Search Results -->
    @if($documents->count() > 0)
        <div class="mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
                Ditemukan {{ number_format($documents->total()) }} dokumen
                @if($query) untuk "{{ $query }}" @endif
            </h2>
        </div>
        
        <!-- Grid Layout (sama seperti publikasi dan BRS) -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-4 gap-6 mb-8" id="documents-grid">
            @foreach($documents as $index => $document)
            <div class="bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-shadow group hover-sound text-sound" 
                 data-document-index="{{ $index + 1 }}" 
                 data-document-title="{{ $document->title }}"
                 data-document-id="{{ $document->id }}">
                
                <!-- Document Cover -->
                <div class="aspect-[3/4] bg-gray-200 relative overflow-hidden">
                    <img src="{{ route('documents.cover', $document) }}" 
                        alt="Cover {{ $document->title }}"
                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                        loading="lazy"
                        onerror="this.src='/images/default-document-cover.jpg'">
                    
                    <!-- Play Overlay -->
                    <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-40 transition-all duration-300 flex items-center justify-center">
                        @if($document->mp3_content)
                            <button type="button"
                                    class="play-document-btn w-16 h-16 bg-blue-600 hover:bg-blue-700 text-white rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center transform hover:scale-110 hover-sound"
                                    data-document='@json($document)'
                                    aria-label="Putar audio {{ $document->title }}">
                                <i class="fas fa-play text-xl ml-1" aria-hidden="true"></i>
                            </button>
                        @else
                            <div class="w-16 h-16 bg-gray-400 text-white rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center">
                                <i class="fas fa-clock text-xl" aria-hidden="true"></i>
                            </div>
                        @endif
                    </div>
                    
                    <!-- Type Badge -->
                    <div class="absolute top-3 left-3">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium 
                                    {{ $document->type === 'publication' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                            <i class="fas fa-{{ $document->type === 'publication' ? 'book' : 'newspaper' }} mr-1" aria-hidden="true"></i>
                            {{ $document->type === 'publication' ? 'Publikasi' : 'BRS' }}
                        </span>
                    </div>
                    
                    <!-- Document Number for Voice Navigation -->
                    <div class="absolute top-3 right-3">
                        <span class="inline-flex items-center justify-center w-8 h-8 bg-black bg-opacity-70 text-white text-sm font-bold rounded-full">
                            {{ $index + 1 }}
                        </span>
                    </div>
                </div>
                
                <!-- Document Info -->
                <div class="p-4">
                    <h3 class="font-semibold text-gray-900 mb-2 line-clamp-2 group-hover:text-blue-600 transition-colors text-sound hover-sound">
                        {{ $document->title }}
                    </h3>
                    
                    <div class="space-y-2 text-sm text-gray-600">
                        <div class="flex items-center">
                            <i class="fas fa-calendar-alt mr-2 text-gray-400" aria-hidden="true"></i>
                            <span class="text-sound">{{ $document->year }}</span>
                        </div>
                        
                        @if($document->indicator)
                        <div class="flex items-center">
                            <i class="fas fa-tag mr-2 text-gray-400" aria-hidden="true"></i>
                            <span class="text-sound line-clamp-1">{{ $document->indicator->name }}</span>
                        </div>
                        @endif
                        
                        @if($document->description)
                        <p class="text-sound line-clamp-2 text-xs">{{ Str::limit($document->description, 100) }}</p>
                        @endif
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="flex items-center space-x-2 mt-4">
                        @if($document->mp3_content)
                            <button type="button"
                                    class="flex-1 px-3 py-2 bg-{{ $document->type === 'publication' ? 'blue' : 'green' }}-600 hover:bg-{{ $document->type === 'publication' ? 'blue' : 'green' }}-700 text-white text-xs font-medium rounded-md transition-colors hover-sound play-document-btn"
                                    data-document='@json($document)'>
                                <i class="fas fa-play mr-1" aria-hidden="true"></i>
                                <span class="text-sound">Putar</span>
                            </button>
                        @else
                            <div class="flex-1 px-3 py-2 bg-gray-300 text-gray-500 text-xs font-medium rounded-md text-center">
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
            </div>
            @endforeach
        </div>
        
        <!-- Pagination -->
        @if($documents->hasPages())
            <div class="flex justify-center">
                {{ $documents->withQueryString()->links() }}
            </div>
        @endif
        
    @else
        <!-- No Results -->
        <div class="text-center py-16">
            <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-search text-3xl text-gray-400" aria-hidden="true"></i>
            </div>
            <h2 class="text-xl font-semibold text-gray-900 mb-2">Tidak ada dokumen ditemukan</h2>
            <p class="text-gray-600 mb-6">
                @if(request()->hasAny(['query', 'type', 'year', 'indicator']))
                    Coba ubah kata kunci atau filter pencarian.
                @else
                    Masukkan kata kunci untuk memulai pencarian.
                @endif
            </p>
            
            <!-- Voice suggestions for empty results -->
            <div class="bg-blue-50 rounded-lg p-6 max-w-md mx-auto">
                <i class="fas fa-microphone text-blue-600 text-2xl mb-3"></i>
                <h3 class="font-medium text-blue-900 mb-2">Coba Pencarian Suara</h3>
                <p class="text-sm text-blue-700">
                    Katakan "cari [kata kunci]" atau gunakan tombol Ctrl untuk memulai pencarian suara.
                </p>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script src="{{ asset('js/enhanced-voice-search.js') }}"></script>

<script>
function clearAllFilters() {
    window.location.href = "{{ route('documents.publications') }}";
}
</script>

<!-- Include auto filter script -->
<script src="{{ asset('js/auto-filter.js') }}"></script>
@if(request('voice'))
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Hitung jumlah hasil pencarian
            const total = {{ $documents->total() }};
            const query = @json($query ?? request('query'));
            
            let message = `Terdapat ${total} hasil pencarian`;
            if (query) {
                message += ` untuk ${query}`;
            }

            const utter = new SpeechSynthesisUtterance(message);
            utter.lang = 'id-ID';
            window.speechSynthesis.speak(utter);
        });
    </script>
    @endpush
@endif
@endpush
@endsection