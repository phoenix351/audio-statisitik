@php
    use Illuminate\Support\Str;
@endphp
@extends('layouts.app')

@section('title', 'Cari Dokumen via API BPS')

@section('content')
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Breadcrumb / Header --}}
        <div class="mb-6">
            <div class="flex items-center text-sm text-gray-500 mb-2">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-blue-600 hover-sound text-sound">
                    Dashboard
                </a>
                <i class="fas fa-chevron-right mx-2" aria-hidden="true"></i>
                <span class="text-sound">Cari Dokumen via API BPS</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 text-sound">
                Cari & Pilih Dokumen dari API BPS
            </h1>
            <p class="text-gray-600 mt-1 text-sound">
                Tarik daftar rilis berita resmi statistik dari API BPS, lalu pilih dokumen yang ingin Anda gunakan.
            </p>
        </div>

        {{-- Flash message --}}
        @if (session('status'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-2 rounded-md">
                {{ session('status') }}
            </div>
        @endif

        {{-- Error dari API --}}
        @if ($error)
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-2 rounded-md">
                {{ $error }}
            </div>
        @endif

        {{-- Filter tahun / bulan --}}
        <div class="bg-white rounded-lg shadow-sm p-4 mb-6 border border-gray-100">
            <form method="GET" action="{{ route('admin.bps-documents.index') }}" class="flex flex-wrap items-end gap-4">
                <div>
                    <label for="model" class="block text-sm font-medium text-gray-700 mb-1">Jenis Dokumen</label>
                    <select name="model" id="model"
                        class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 w-44">
                        <option value="pressrelease" @selected(($model ?? 'pressrelease') === 'pressrelease')>
                            Berita Resmi Statistik
                        </option>
                        <option value="publication" @selected(($model ?? '') === 'publication')>
                            Publikasi (di-filter)
                        </option>
                    </select>
                </div>
                <div>
                    <label for="year" class="block text-sm font-medium text-gray-700 mb-1">Tahun</label>
                    <input type="number" name="year" id="year" value="{{ $year }}"
                        class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 w-28">
                </div>

                @if (($model ?? 'pressrelease') === 'pressrelease')
                    <div>
                        <label for="month" class="block text-sm font-medium text-gray-700 mb-1">Bulan</label>
                        <select name="month" id="month"
                            class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 w-40">
                            @for ($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" @selected($m == $month)>
                                    {{ \Carbon\Carbon::create()->month($m)->locale('id')->translatedFormat('F') }}
                                </option>
                            @endfor
                        </select>
                    </div>
                @endif

                {{-- 🔹 Input halaman hanya muncul kalau total halaman > 1 --}}
                @if (!$maxPages || $maxPages > 1)
                    <div>
                        <label for="page" class="block text-sm font-medium text-gray-700 mb-1">Halaman</label>
                        <input type="number" name="page" id="page" value="{{ $page }}" min="1"
                            class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 w-24">
                    </div>
                @endif
                <div class="flex gap-2">
                    <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium shadow-sm">
                        <i class="fas fa-search mr-2"></i>Cari
                    </button>
                    <a href="{{ route('admin.bps-documents.index') }}"
                        class="inline-flex items-center px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm">
                        Reset
                    </a>
                </div>

                @if ($meta)
                    <div class="ml-auto text-sm text-gray-500">
                        @if (($maxPages ?? 1) > 1)
                            Menampilkan halaman {{ $meta['page'] ?? '?' }} dari {{ $meta['pages'] ?? '?' }},
                            total {{ $meta['total'] ?? ($meta['count'] ?? '?') }} dokumen.
                        @else
                            Total {{ $meta['total'] ?? ($meta['count'] ?? '?') }} dokumen.
                        @endif
                    </div>
                @endif
            </form>
        </div>

        {{-- Tabel dokumen --}}
        @if (!empty($documents))
            <form method="POST" action="{{ route('admin.bps-documents.import') }}">
                @csrf
                {{-- kirim juga filter supaya setelah import tetap di view yang sama --}}
                <input type="hidden" name="model" value="{{ $model }}">
                <input type="hidden" name="year" value="{{ $year }}">
                <input type="hidden" name="month" value="{{ $month }}">

                <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm text-left">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-4 py-3 w-10">
                                        <input type="checkbox" id="select-all"
                                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    </th>
                                    <th class="px-4 py-3 w-20">Cover</th>
                                    <th class="px-4 py-3">Judul</th>
                                    <th class="px-4 py-3 w-48">Subjek</th>
                                    <th class="px-4 py-3 w-40">Tgl Rilis</th>
                                    <th class="px-4 py-3 w-32">Ukuran</th>
                                    <th class="px-4 py-3 w-40">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($documents as $doc)
                                    <tr class="hover:bg-gray-50 text-sound">
                                        <td class="px-4 py-3 align-top">
                                            {{-- Checkbox utama: hanya item yang dicentang yang punya id di request --}}
                                            <input type="checkbox" name="documents[{{ $loop->index }}][id]"
                                                value="{{ $doc['id'] }}"
                                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 doc-checkbox">

                                            {{-- field lain dikirim lewat hidden --}}
                                            <input type="hidden" name="documents[{{ $loop->index }}][model]"
                                                value="{{ $doc['model'] }}">
                                            <input type="hidden" name="documents[{{ $loop->index }}][title]"
                                                value="{{ e($doc['title']) }}">
                                            <input type="hidden" name="documents[{{ $loop->index }}][abstract_plain]"
                                                value="{{ e($doc['abstract_plain']) }}">
                                            <input type="hidden" name="documents[{{ $loop->index }}][rl_date]"
                                                value="{{ $doc['rl_date'] }}">
                                            <input type="hidden" name="documents[{{ $loop->index }}][pdf_url]"
                                                value="{{ $doc['pdf_url'] }}">
                                            <input type="hidden" name="documents[{{ $loop->index }}][slide_url]"
                                                value="{{ $doc['slide_url'] }}">
                                            <input type="hidden" name="documents[{{ $loop->index }}][cover_url]"
                                                value="{{ $doc['cover_url'] }}">
                                            <input type="hidden" name="documents[{{ $loop->index }}][size]"
                                                value="{{ $doc['size'] }}">
                                            <input type="hidden" name="documents[{{ $loop->index }}][subject]"
                                                value="{{ e($doc['subject'] ?? '') }}">
                                        </td>

                                        <td class="px-4 py-3 align-top">
                                            @if ($doc['cover_url'])
                                                <img src="{{ $doc['cover_url'] }}" alt="Cover"
                                                    class="w-16 h-20 object-cover rounded border border-gray-200"
                                                    loading="lazy" onerror="this.style.display='none'">
                                            @else
                                                <div
                                                    class="w-16 h-20 bg-gray-100 border border-gray-200 flex items-center justify-center text-xs text-gray-400">
                                                    No Cover
                                                </div>
                                            @endif
                                        </td>

                                        <td class="px-4 py-3 align-top">
                                            <div class="font-medium text-gray-900 mb-1">
                                                {{ $doc['title'] }}
                                            </div>
                                            <div class="text-xs text-gray-500 line-clamp-2">
                                                {{ Str::limit($doc['abstract_plain'] ?? '', 180) }}
                                            </div>
                                        </td>

                                        <td class="px-4 py-3 align-top text-gray-700">
                                            <div class="text-sm">
                                                {{ $doc['subject'] ?? '-' }}
                                            </div>
                                        </td>

                                        <td class="px-4 py-3 align-top text-gray-700">
                                            @if ($doc['rl_date'])
                                                {{ \Carbon\Carbon::parse($doc['rl_date'])->locale('id')->translatedFormat('d F Y') }}
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>

                                        <td class="px-4 py-3 align-top text-gray-700">
                                            {{ $doc['size'] ?? '-' }}
                                        </td>

                                        <td class="px-4 py-3 align-top">
                                            <div class="flex flex-col gap-1">
                                                @if ($doc['pdf_url'])
                                                    <a href="{{ $doc['pdf_url'] }}" target="_blank"
                                                        class="inline-flex items-center px-2 py-1 text-xs bg-blue-50 text-blue-700 rounded hover:bg-blue-100">
                                                        <i class="fas fa-file-pdf mr-1"></i> PDF
                                                    </a>
                                                @endif

                                                @if (!empty($doc['slide_url']))
                                                    <a href="{{ $doc['slide_url'] }}" target="_blank"
                                                        class="inline-flex items-center px-2 py-1 text-xs bg-emerald-50 text-emerald-700 rounded hover:bg-emerald-100">
                                                        <i class="fas fa-file-powerpoint mr-1"></i> Slide
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Footer: tombol pilih --}}
                    <div class="flex items-center justify-between px-4 py-3 bg-gray-50 border-t border-gray-200">
                        <div class="text-xs text-gray-500">
                            Centang dokumen yang ingin disimpan / digunakan, lalu klik tombol di kanan.
                        </div>
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg disabled:opacity-50"
                            id="btn-import">
                            <i class="fas fa-check mr-2"></i>
                            Gunakan Dokumen Terpilih
                        </button>
                    </div>
                </div>
            </form>
        @else
            <div class="flex flex-col items-center justify-center h-[40vh] text-center">
                <i class="fas fa-file-alt text-5xl text-gray-300 mb-4"></i>
                <p class="text-gray-600 text-sound">
                    Tidak ada dokumen untuk bulan dan tahun yang dipilih.<br>
                    Silakan ubah filter dan klik tombol <span class="font-semibold">Cari</span>.
                </p>
            </div>
        @endif
    </div>

    {{-- Script kecil untuk select all --}}
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const selectAll = document.getElementById('select-all');
                const checkboxes = document.querySelectorAll('.doc-checkbox');
                const btnImport = document.getElementById('btn-import');

                if (!selectAll || checkboxes.length === 0 || !btnImport) return;

                const refreshButtonState = () => {
                    const anyChecked = Array.from(checkboxes).some(cb => cb.checked);
                    btnImport.disabled = !anyChecked;
                };

                selectAll.addEventListener('change', function() {
                    checkboxes.forEach(cb => cb.checked = selectAll.checked);
                    refreshButtonState();
                });

                checkboxes.forEach(cb => {
                    cb.addEventListener('change', function() {
                        if (!cb.checked) {
                            selectAll.checked = false;
                        }
                        refreshButtonState();
                    });
                });

                refreshButtonState();
            });
        </script>
    @endpush
@endsection
