@extends('layouts.app')

@section('title', $document->title . ' - Audio Statistik')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    <!-- Document Header -->
    <div class="bg-white rounded-lg shadow-sm p-8 mb-8">
        <div class="flex flex-col lg:flex-row lg:items-start lg:space-x-8">
            <!-- Document Cover -->
            <div class="flex-shrink-0 mb-6 lg:mb-0">
                <img src="{{ route('documents.cover', $document) }}?v={{ $document->updated_at->timestamp }}" 
                    alt="Cover {{ $document->title }}"
                    class="w-48 h-64 object-cover rounded-lg shadow-md mx-auto lg:mx-0"
                    onerror="this.src='/images/default-document-cover.jpg'">
            </div>
                        
            <!-- Document Info -->
            <div class="flex-1">
                <!-- Badges -->
                <div class="flex items-center space-x-2 mb-4">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium 
                                 {{ $document->type === 'publication' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                        <i class="fas fa-{{ $document->type === 'publication' ? 'book' : 'newspaper' }} mr-1" aria-hidden="true"></i>
                        {{ $document->type === 'publication' ? 'Publikasi' : 'BRS' }}
                    </span>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                        <i class="fas fa-calendar mr-1" aria-hidden="true"></i>
                        {{ $document->year }}
                    </span>
                    @if($document->hasAudio())
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                            <i class="fas fa-headphones mr-1" aria-hidden="true"></i>
                            Audio Tersedia
                        </span>
                    @endif
                </div>
                
                <!-- Title -->
                <h1 class="text-3xl font-bold text-gray-900 mb-4">{{ $document->title }}</h1>
                
                <!-- Meta Info -->
                <div class="space-y-2 text-gray-600 mb-6">
                    <p class="flex items-center">
                        <i class="fas fa-tag w-4 mr-3 text-gray-400" aria-hidden="true"></i>
                        {{ $document->indicator->name }}
                    </p>
                    {{-- <p class="flex items-center">
                        <i class="fas fa-user w-4 mr-3 text-gray-400" aria-hidden="true"></i>
                        Dibuat oleh {{ $document->creator->name }}
                    </p> --}}
                    <p class="flex items-center">
                        <i class="fas fa-clock w-4 mr-3 text-gray-400" aria-hidden="true"></i>
                        {{ $document->created_at->format('d F Y') }}
                    </p>
                    @if($document->hasAudio())
                        <p class="flex items-center">
                            <i class="fas fa-volume-up w-4 mr-3 text-gray-400" aria-hidden="true"></i>
                            Durasi audio: {{ $document->getAudioDurationFormatted() }}
                        </p>
                    @endif
                    <p class="flex items-center">
                        <i class="fas fa-download w-4 mr-3 text-gray-400" aria-hidden="true"></i>
                        {{ number_format($document->download_count) }} unduhan
                    </p>
                </div>
                
                <!-- Action Buttons -->
                <div class="flex flex-wrap gap-3 mb-6">
                    @if($document->hasAudio())
                        <button onclick="playDocumentAudio({{ $document->toJson() }})" 
                                class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors hover-sound">
                            <i class="fas fa-play mr-2" aria-hidden="true"></i>
                            Putar Audio
                        </button>
                        
                        <a href="{{ route('documents.audio.download', [$document, 'mp3']) }}" 
                           class="inline-flex items-center px-6 py-3 border border-blue-600 text-blue-600 hover:bg-blue-50 font-medium rounded-lg transition-colors hover-sound">
                            <i class="fas fa-download mr-2" aria-hidden="true"></i>
                            Unduh MP3
                        </a>
                        
                        <a href="{{ route('documents.audio.download', [$document, 'flac']) }}" 
                           class="inline-flex items-center px-6 py-3 border border-green-600 text-green-600 hover:bg-green-50 font-medium rounded-lg transition-colors hover-sound">
                            <i class="fas fa-download mr-2" aria-hidden="true"></i>
                            Unduh FLAC
                        </a>
                    @else
                        <div class="inline-flex items-center px-6 py-3 bg-gray-100 text-gray-500 font-medium rounded-lg">
                            <i class="fas fa-clock mr-2" aria-hidden="true"></i>
                            Audio sedang diproses
                        </div>
                    @endif
                </div>

                <!-- Description -->
                @if($document->description)
                    <div class="mb-6">
                        <h3 class="font-semibold text-gray-900 mb-2">Deskripsi</h3>
                        <p class="text-gray-600 leading-relaxed">{{ $document->description }}</p>
                    </div>
                @else
                    <div class="mb-6">
                        <h3 class="font-semibold text-gray-900 mb-2">Deskripsi</h3>
                        <p class="text-gray-400 italic">Deskripsi dokumen masih belum tersedia.</p>
                    </div>
                @endif
                
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <div class="flex justify-between items-center">
        <a href="{{ $document->type === 'publication' ? route('documents.publications') : route('documents.brs') }}" 
           class="inline-flex items-center text-blue-600 hover:text-blue-700 font-medium hover-sound">
            <i class="fas fa-arrow-left mr-2" aria-hidden="true"></i>
            Kembali ke {{ $document->type === 'publication' ? 'Publikasi' : 'BRS' }}
        </a>
        
        <!-- Share buttons could go here -->
    </div>
</div>
@endsection