<footer class="bg-gray-800 text-white" role="contentinfo">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div>
                <div class="flex items-center space-x-3 mb-4">
                    <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-volume-up text-white text-sm" aria-hidden="true"></i>
                    </div>
                    <span class="font-bold text-sound">Audio Statistik</span>
                </div>
                <p class="text-gray-300 text-sm text-sound">
                    Layanan audio untuk publikasi dan berita resmi statistik BPS Sulawesi Utara
                    yang mendukung aksesibilitas untuk pengguna dengan gangguan penglihatan.
                </p>
            </div>

            <div>
                <h3 class="font-semibold mb-3 text-sound">Tautan</h3>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ route('home') }}"
                            class="text-gray-300 hover:text-white transition-colors hover-sound text-sound">Beranda</a>
                    </li>
                    <li><a href="{{ route('documents.publications') }}"
                            class="text-gray-300 hover:text-white transition-colors hover-sound text-sound">Publikasi</a>
                    </li>
                    <li><a href="{{ route('documents.brs') }}"
                            class="text-gray-300 hover:text-white transition-colors hover-sound text-sound">BRS</a>
                    </li>
                    <li><a href="https://sulut.bps.go.id" target="_blank"
                            class="text-gray-300 hover:text-white transition-colors hover-sound text-sound">BPS
                            Sulut</a></li>
                </ul>
            </div>

            <div>
                <h3 class="font-semibold mb-3 text-sound">Aksesibilitas</h3>
                <ul class="space-y-2 text-sm text-gray-300">
                    <li class="text-sound"><i class="fas fa-check mr-2 text-green-400"
                            aria-hidden="true"></i>Text-to-Speech Otomatis</li>
                    <li class="text-sound"><i class="fas fa-check mr-2 text-green-400" aria-hidden="true"></i>Navigasi
                        Keyboard</li>
                    <li class="text-sound"><i class="fas fa-check mr-2 text-green-400" aria-hidden="true"></i>Screen
                        Reader Friendly</li>
                    <li class="text-sound"><i class="fas fa-check mr-2 text-green-400" aria-hidden="true"></i>Pencarian
                        Suara</li>
                    <li class="text-sound"><i class="fas fa-check mr-2 text-green-400" aria-hidden="true"></i>Suara
                        Hover untuk Teks</li>
                </ul>
            </div>
        </div>

        <div class="border-t border-gray-700 mt-8 pt-6 text-center text-sm text-gray-400">
            <p class="text-sound">&copy; {{ date('Y') }} BPS Provinsi Sulawesi Utara. Semua hak dilindungi.</p>
        </div>
    </div>
</footer>
