@extends('layouts.app')

@section('title', 'Beranda - Audio Statistik')

@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Hero Section -->
        <div class="text-center mb-12">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-blue-100 rounded-full mb-6">
                <i class="fas fa-volume-up text-3xl text-blue-600" aria-hidden="true"></i>
            </div>
            <h1 class="text-4xl font-bold text-gray-900 mb-4">
                Selamat Datang di Audio Statistik
            </h1>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto mb-8">
                Nikmati akses audio yang mudah untuk publikasi dan berita resmi statistik BPS Sulawesi Utara.
                Dirancang khusus untuk mendukung aksesibilitas pengguna dengan gangguan penglihatan.
            </p>

            <!-- Voice Search Button -->
            <button type="button" id="voice-search-trigger"
                class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 hover-sound">
                <i class="fas fa-microphone mr-2" aria-hidden="true"></i>
                Mulai Pencarian Suara
            </button>
            <p class="text-sm text-gray-500 mt-2">
                Atau tekan Spasi untuk mengaktifkan pencarian suara, atau katakan "Hai Audio Statistik"
            </p>
        </div>

        <!-- Indicators Grid (3 columns as requested) -->
        <section aria-labelledby="indicators-heading">
            <h2 id="indicators-heading" class="text-2xl font-bold text-gray-900 mb-6 text-center">Indikator Statistik</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
                @foreach ($indicators as $indicator)
                    <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow p-8 group cursor-pointer hover-sound text-sound"
                        onclick="location.href='{{ route('search', ['indicator' => $indicator->id]) }}'" tabindex="0"
                        onkeydown="if(event.key==='Enter'||event.key===' '){location.href='{{ route('search', ['indicator' => $indicator->id]) }}'}"
                        role="button" aria-label="Lihat dokumen untuk indikator {{ $indicator->name }}">

                        <div class="text-center">
                            @if ($indicator->icon)
                                <div
                                    class="w-20 h-20 bg-blue-50 rounded-lg flex items-center justify-center mx-auto mb-6 group-hover:bg-blue-100 transition-colors">
                                    <i class="{{ $indicator->icon }} text-3xl text-blue-600" aria-hidden="true"></i>
                                </div>
                            @else
                                <div
                                    class="w-20 h-20 bg-gray-100 rounded-lg flex items-center justify-center mx-auto mb-6 group-hover:bg-gray-200 transition-colors">
                                    <i class="fas fa-chart-bar text-3xl text-gray-400" aria-hidden="true"></i>
                                </div>
                            @endif

                            <h3
                                class="text-lg font-semibold text-gray-900 mb-3 group-hover:text-blue-600 transition-colors">
                                {{ $indicator->name }}
                            </h3>

                            <p class="text-sm text-gray-500 mb-4">
                                {{ $indicator->active_documents_count }} dokumen tersedia
                            </p>

                            @if ($indicator->description)
                                <p class="text-xs text-gray-400 line-clamp-3">
                                    {{ $indicator->description }}
                                </p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <!-- Recent Documents -->
        @if ($recentDocuments->count() > 0)
            <section aria-labelledby="recent-heading">
                <h2 id="recent-heading" class="text-2xl font-bold text-gray-900 mb-6 text-center">Dokumen Terbaru</h2>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    @foreach ($recentDocuments as $index => $document)
                        <x-document_card :document="$document" :index="$index" />
                    @endforeach
                </div>
            </section>
        @endif

        <!-- Accessibility Features -->
        <section class="py-16 bg-blue-50">
            <div class="max-w-5xl mx-auto px-6">
                <h2 class="text-3xl font-bold text-gray-900 text-center mb-10">Fitur Aksesibilitas</h2>

                <div class="space-y-8">
                    <!-- Text-to-Speech -->
                    <div class="flex items-start space-x-5">
                        <div class="flex-shrink-0">
                            <div
                                class="w-14 h-14 bg-blue-600 text-white flex items-center justify-center rounded-xl shadow-md">
                                <i class="fas fa-volume-up text-2xl"></i>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-xl font-semibold text-gray-800">Text-to-Speech</h3>
                            <p class="text-gray-700 leading-relaxed">
                                Dokumen otomatis dikonversi menjadi audio dengan suara natural,
                                membantu pengguna mendengarkan isi statistik tanpa perlu membaca.
                            </p>
                        </div>
                    </div>

                    <!-- Pencarian Suara -->
                    <div class="flex items-start space-x-5">
                        <div class="flex-shrink-0">
                            <div
                                class="w-14 h-14 bg-blue-600 text-white flex items-center justify-center rounded-xl shadow-md">
                                <i class="fas fa-microphone text-2xl"></i>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-xl font-semibold text-gray-800">Pencarian Suara</h3>
                            <p class="text-gray-700 leading-relaxed">
                                Temukan dokumen dengan cepat menggunakan perintah suara sederhana,
                                tanpa perlu mengetik kata kunci secara manual.
                            </p>
                        </div>
                    </div>

                    <!-- Navigasi Keyboard -->
                    <div class="flex items-start space-x-5">
                        <div class="flex-shrink-0">
                            <div
                                class="w-14 h-14 bg-blue-600 text-white flex items-center justify-center rounded-xl shadow-md">
                                <i class="fas fa-keyboard text-2xl"></i>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-xl font-semibold text-gray-800">Navigasi Keyboard</h3>
                            <p class="text-gray-700 leading-relaxed">
                                Jelajahi seluruh halaman dan fitur hanya dengan keyboard,
                                memastikan aksesibilitas penuh bagi semua pengguna.
                            </p>
                        </div>
                    </div>

                    <!-- Screen Reader -->
                    <div class="flex items-start space-x-5">
                        <div class="flex-shrink-0">
                            <div
                                class="w-14 h-14 bg-blue-600 text-white flex items-center justify-center rounded-xl shadow-md">
                                <i class="fas fa-universal-access text-2xl"></i>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-xl font-semibold text-gray-800">Screen Reader</h3>
                            <p class="text-gray-700 leading-relaxed">
                                Seluruh konten dioptimalkan agar kompatibel dengan teknologi pembaca layar,
                                memudahkan pengguna dengan keterbatasan visual.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
