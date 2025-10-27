@extends('layouts.app')

@section('title', 'Cari Dokumen via API BPS')

@section('content')
<div class="flex flex-col items-center justify-center h-[70vh] text-center">
    <i class="fas fa-tools text-6xl text-gray-400 mb-6 animate-pulse"></i>
    <h1 class="text-3xl font-semibold text-gray-800 mb-4">
        Fitur Sedang Dalam Pengembangan
    </h1>
    <p class="text-gray-600 mb-6">
        Halaman pencarian dokumen melalui API BPS masih dalam tahap pengembangan.<br>
        Silakan kembali lagi nanti untuk melihat pembaruan.
    </p>
    <a href="{{ route('admin.dashboard') }}" 
       class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
        <i class="fas fa-arrow-left mr-2"></i>Kembali ke Dashboard
    </a>
</div>
@endsection