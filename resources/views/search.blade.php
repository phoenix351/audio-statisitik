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
                            <input type="text" id="query" name="query" value="{{ $query ?? request('query') }}"
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
                        <select id="type" name="type"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Semua Jenis</option>
                            <option value="publication"
                                {{ (request('type') ?? $type) === 'publication' ? 'selected' : '' }}>Publikasi</option>
                            <option value="brs" {{ (request('type') ?? $type) === 'brs' ? 'selected' : '' }}>BRS</option>
                        </select>
                    </div>

                    <!-- Year Filter -->
                    <div class="w-full lg:w-32">
                        <label for="year" class="block text-sm font-medium text-gray-700 mb-2">
                            Tahun
                        </label>
                        <select id="year" name="year"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Semua</option>
                            @foreach ($years as $yearOption)
                                <option value="{{ $yearOption }}"
                                    {{ (request('year') ?? $year) == $yearOption ? 'selected' : '' }}>
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
            @if (request('voice'))
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
        @if ($documents->count() > 0)
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">
                    Ditemukan {{ number_format($documents->total()) }} dokumen
                    @if ($query)
                        untuk "{{ $query }}"
                    @endif
                </h2>
            </div>

            <!-- Grid Layout (sama seperti publikasi dan BRS) -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-4 gap-6 mb-8" id="documents-grid"
                data-size="{{ $documents->count() }}" data-query="{{ $query }}">
                @foreach ($documents as $index => $document)
                    <x-document_card :document="$document" :index="$index" />
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
                    <i class="fas fa-search text-3xl text-gray-400" aria-hidden="true"></i>
                </div>
                <h2 class="text-xl font-semibold text-gray-900 mb-2">Tidak ada dokumen ditemukan</h2>
                <p class="text-gray-600 mb-6">
                    @if (request()->hasAny(['query', 'type', 'year', 'indicator']))
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


@endsection
