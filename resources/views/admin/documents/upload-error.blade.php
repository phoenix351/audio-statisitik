@extends('layouts.app')

@section('title', 'Upload Error - Admin')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-red-600">Upload Error</h1>
        <p class="text-gray-600 mt-2">Terjadi masalah saat mengupload dokumen</p>
    </div>

    @if(session('error_type') === 'pdf_protected')
    <!-- PDF Protection Error -->
    <div class="bg-red-50 border border-red-200 rounded-lg p-6 mb-6">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <i class="fas fa-lock text-red-400 text-2xl" aria-hidden="true"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-lg font-medium text-red-800">PDF Terproteksi</h3>
                <div class="mt-2 text-sm text-red-700">
                    <p>{{ session('error') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Solution Steps -->
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Cara Mengatasi PDF Terproteksi</h3>
        
        <div class="space-y-6">
            <!-- Method 1 -->
            <div class="border-l-4 border-blue-500 pl-4">
                <h4 class="font-medium text-gray-900">1. Hapus Proteksi PDF</h4>
                <p class="text-sm text-gray-600 mt-1">Gunakan software untuk menghapus proteksi:</p>
                <ul class="mt-2 text-sm text-gray-600 space-y-1">
                    <li>• <strong>Adobe Acrobat:</strong> File → Properties → Security → No Security</li>
                    <li>• <strong>SmallPDF.com:</strong> Tool "Unlock PDF" (online, gratis)</li>
                    <li>• <strong>ILovePDF.com:</strong> Tool "Unlock PDF" (online, gratis)</li>
                    <li>• <strong>PDFtk:</strong> Command line tool (untuk tech-savvy users)</li>
                </ul>
            </div>

            <!-- Method 2 -->
            <div class="border-l-4 border-green-500 pl-4">
                <h4 class="font-medium text-gray-900">2. Konversi ke Format Lain</h4>
                <p class="text-sm text-gray-600 mt-1">Alternatif jika tidak bisa menghapus proteksi:</p>
                <ul class="mt-2 text-sm text-gray-600 space-y-1">
                    <li>• Export sebagai Word (.docx) dari aplikasi asli</li>
                    <li>• Print to PDF tanpa security settings</li>
                    <li>• Scan ulang dokumen sebagai PDF biasa</li>
                </ul>
            </div>

            <!-- Method 3 -->
            <div class="border-l-4 border-yellow-500 pl-4">
                <h4 class="font-medium text-gray-900">3. Bantuan Manual</h4>
                <p class="text-sm text-gray-600 mt-1">
                    Jika kedua cara di atas tidak berhasil, hubungi administrator sistem 
                    untuk bantuan manual processing dokumen.
                </p>
            </div>
        </div>
    </div>
    @endif

    <!-- Action Buttons -->
    <div class="flex items-center space-x-4">
        <a href="{{ route('admin.documents.create') }}" 
           class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
            <i class="fas fa-arrow-left mr-2" aria-hidden="true"></i>
            Coba Upload Lagi
        </a>
        
        <a href="{{ route('admin.documents.index') }}" 
           class="inline-flex items-center px-6 py-3 border border-gray-300 text-gray-700 hover:bg-gray-50 font-medium rounded-lg transition-colors">
            <i class="fas fa-list mr-2" aria-hidden="true"></i>
            Kembali ke Daftar Dokumen
        </a>
    </div>
</div>
@endsection