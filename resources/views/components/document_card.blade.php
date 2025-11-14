@props(['document', 'index'])
<div class="bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md group hover-sound text-sound
         h-full flex flex-col"
    data-document-index="{{ $index + 1 }}" data-document-title="{{ $document->title }}"
    data-document-id="{{ $document->id }}">

    <!-- Document Cover -->
    <div class="aspect-[3/4] bg-gray-200 relative overflow-hidden shrink-0"> <img
            src="{{ Storage::disk('documents')->url($document->cover_path) }}" alt="Cover {{ $document->title }}"
            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy"
            onerror="this.src='/images/default-document-cover.jpg'">

        <!-- Play Overlay -->
        <div
            class="absolute inset-0 bg-black/0 group-hover:bg-black/40 transition-all duration-300 flex items-center justify-center cursor-pointer">

            @if ($document->mp3_path)
                <button type="button"
                    class="play-document-btn w-16 h-16 bg-blue-600 hover:bg-blue-700 text-white rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center transform hover:scale-110 hover-sound"
                    data-document='@json($document)' aria-label="Putar audio {{ $document->title }}">
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
                <i class="fas fa-{{ $document->type === 'publication' ? 'book' : 'newspaper' }} mr-1"
                    aria-hidden="true"></i>
                {{ $document->type === 'publication' ? 'Publikasi' : 'BRS' }}
            </span>
        </div>

        <!-- Document Number for Voice Navigation -->
        <div class="absolute top-3 right-3">
            <span
                class="inline-flex items-center justify-center w-8 h-8 bg-black bg-opacity-70 text-white text-sm font-bold rounded-full">
                {{ $index + 1 }}
            </span>
        </div>
    </div>

    <!-- Document Info -->
    <div class="p-4 flex-1 flex flex-col">
        <h3
            class="font-semibold text-gray-900 mb-2 line-clamp-2 group-hover:text-blue-600 transition-colors text-sound hover-sound">
            {{ $document->title }}
        </h3>

        <div class="space-y-2 text-sm text-gray-600">
            <div class="flex items-center">
                <i class="fas fa-calendar-alt mr-2 text-gray-400" aria-hidden="true"></i>
                <span class="text-sound">{{ $document->year }}</span>
            </div>

            @if ($document->indicator)
                <div class="flex items-center">
                    <i class="fas fa-tag mr-2 text-gray-400" aria-hidden="true"></i>
                    <span class="text-sound line-clamp-1">{{ $document->indicator->name }}</span>
                </div>
            @endif

            @if ($document->description)
                <p class="text-sound line-clamp-2 text-xs">
                    {{ Str::limit($document->description, 100) }}</p>
            @endif
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center space-x-2 mt-auto pt-4">
            @if ($document->mp3_path)
                <button type="button"
                    class="flex-1 cursor-pointer px-3 py-2 bg-{{ $document->type === 'publication' ? 'blue' : 'green' }}-600 hover:bg-{{ $document->type === 'publication' ? 'blue' : 'green' }}-700 text-white text-xs font-medium rounded-md transition-colors hover-sound play-document-btn"
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

            <a href="{{ route('documents.uuid.show', $document->uuid) }}"
                class="px-3 py-2 border border-gray-300 hover:border-gray-400 text-gray-700 hover:text-gray-900 text-xs font-medium rounded-md transition-colors hover-sound">
                <i class="fas fa-eye" aria-hidden="true"></i>
            </a>
        </div>
    </div>
</div>
