@extends('layouts.app')

@section('title', 'Dashboard Admin - Audio Statistik')

@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Dashboard Admin</h1>
            <p class="text-gray-600 mt-2">Selamat datang, {{ auth()->user()->name }}! Kelola dokumen dan pantau statistik
                penggunaan Audio Statistik</p>
        </div>

        <!-- Success Message -->
        @if (session('success'))
            <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="flex">
                    <i class="fas fa-check-circle text-green-400 mr-3 mt-1" aria-hidden="true"></i>
                    <div class="text-sm text-green-800">{{ session('success') }}</div>
                </div>
            </div>
        @endif

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-sm p-6 hover-sound">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-users text-xl text-blue-600" aria-hidden="true"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Pengunjung (30 hari)</p>
                        <p class="text-2xl font-bold text-gray-900">{{ number_format($statistics['total_visitors'] ?? 0) }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6 hover-sound">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-book text-xl text-green-600" aria-hidden="true"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Publikasi</p>
                        <p class="text-2xl font-bold text-gray-900">
                            {{ number_format($statistics['total_publications'] ?? 0) }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6 hover-sound">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-newspaper text-xl text-orange-600" aria-hidden="true"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">BRS</p>
                        <p class="text-2xl font-bold text-gray-900">{{ number_format($statistics['total_brs'] ?? 0) }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6 hover-sound">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-headphones text-xl text-purple-600" aria-hidden="true"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">File Audio</p>
                        <p class="text-2xl font-bold text-gray-900">
                            {{ number_format($statistics['total_audio_files'] ?? 0) }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6 hover-sound">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-clock text-xl text-yellow-600" aria-hidden="true"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Sedang Diproses</p>
                        <p class="text-2xl font-bold text-gray-900">
                            {{ number_format($statistics['pending_conversions'] ?? 0) }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6 hover-sound">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-xl text-red-600" aria-hidden="true"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Gagal Konversi</p>
                        <p class="text-2xl font-bold text-gray-900">
                            {{ number_format($statistics['failed_conversions'] ?? 0) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Aksi Cepat</h2>
            <div class="flex flex-wrap gap-4">
                <a href="{{ route('admin.documents.create') }}"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors hover-sound">
                    <i class="fas fa-plus mr-2" aria-hidden="true"></i>Upload Dokumen Baru
                </a>
                <a href="{{ route('admin.documents.index') }}"
                    class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition-colors hover-sound">
                    <i class="fas fa-folder mr-2" aria-hidden="true"></i>Kelola Dokumen
                </a>
                <a href="{{ route('admin.bps-documents.index') }}"
                    class="inline-flex items-center px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white font-medium rounded-lg transition-colors hover-sound">
                    <i class="fas fa-cogs mr-2" aria-hidden="true"></i>Cari Dokumen via API BPS
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-8">
            <!-- Recent Documents -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Upload Terbaru</h3>
                    <a href="{{ route('admin.documents.index') }}"
                        class="text-sm text-blue-600 hover:text-blue-700 hover-sound">Lihat semua</a>
                </div>
                <div class="space-y-4">
                    @forelse(($recentUploads ?? [])->take(5) as $doc)
                        <div class="flex items-center space-x-3 p-3 border border-gray-200 rounded-lg hover-sound">
                            <div class="flex-shrink-0">
                                <div
                                    class="w-8 h-8 bg-{{ $doc->type === 'publication' ? 'blue' : 'green' }}-100 rounded-lg flex items-center justify-center">
                                    <i
                                        class="fas fa-{{ $doc->type === 'publication' ? 'book' : 'newspaper' }} text-{{ $doc->type === 'publication' ? 'blue' : 'green' }}-600 text-xs"></i>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-gray-900 text-sm truncate">{{ Str::limit($doc->title, 100) }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    {{ $doc->creator->name ?? 'Unknown' }} • {{ $doc->created_at->diffForHumans() }}
                                </p>
                            </div>
                            <div class="flex-shrink-0">
                                <span
                                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                    @if ($doc->status === 'completed') bg-green-100 text-green-800
                                    @elseif($doc->status === 'processing') bg-yellow-100 text-yellow-800
                                    @elseif($doc->status === 'failed') bg-red-100 text-red-800
                                    @else bg-gray-100 text-gray-800 @endif">
                                    @if ($doc->status === 'completed')
                                        <i class="fas fa-check mr-1"></i>Selesai
                                    @elseif($doc->status === 'processing')
                                        <i class="fas fa-spinner fa-spin mr-1"></i>Proses
                                    @elseif($doc->status === 'failed')
                                        <i class="fas fa-times mr-1"></i>Gagal
                                    @else
                                        <i class="fas fa-clock mr-1"></i>Menunggu
                                    @endif
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-inbox text-3xl mb-3"></i>
                            <p class="text-sm">Belum ada dokumen yang diupload.</p>
                            <a href="{{ route('admin.documents.create') }}"
                                class="text-blue-600 hover:text-blue-700 font-medium hover-sound">
                                Upload dokumen pertama
                            </a>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Info Section -->
            <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-8">

                <!-- Aktivitas Terbaru -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Aktivitas Terbaru</h3>
                    <div class="space-y-3">
                        @forelse($monthlyVisitors ?? [] as $visitor)
                            <div class="flex items-center space-x-3 text-sm">
                                <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                <span class="text-gray-600">{{ $visitor->count ?? 0 }} pengunjung pada
                                    {{ $visitor->date ?? 'today' }}</span>
                            </div>
                        @empty
                            <div class="text-center py-4 text-gray-500">
                                <p class="text-sm">Belum ada data aktivitas.</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Tips bagi Admin -->
                <div class="p-6 bg-blue-50 shadow-sm rounded-lg">
                    <h4 class="text-lg font-semibold text-blue-800 mb-4">Tips bagi Admin</h4>
                    <ul class="text-sm text-blue-700 space-y-1">
                        <li>• Upload dokumen dalam format PDF atau DOC untuk hasil terbaik</li>
                        <li>• Pastikan file tidak lebih dari 10MB</li>
                        <li>• Audio akan diproses otomatis setelah upload</li>
                        <li>• Gunakan deskripsi yang jelas untuk setiap dokumen</li>
                    </ul>
                </div>

            </div>
        </div>
    @endsection
