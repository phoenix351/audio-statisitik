{{-- resources/views/unsupported.blade.php --}}
<!DOCTYPE html>
<html lang="id" class="h-full antialiased">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Perangkat Tidak Didukung</title>
    {{-- Jika pakai Vite, gunakan baris di bawah. Kalau tidak, ganti dengan CDN Tailwind. --}}
    @vite('resources/css/app.css')
    {{-- Fallback CDN (hapus jika sudah pakai Vite) --}}
    <script>
        // Jika Tailwind belum terpasang via Vite, uncomment baris di bawah ini:
        // document.write('<script src="https://cdn.tailwindcss.com"><\/script>');
    </script>
</head>

<body class="h-full bg-white text-gray-800 dark:bg-gray-950 dark:text-gray-100">
    <main class="min-h-full grid place-items-center p-6">
        <section class="w-full max-w-xl">
            <div
                class="rounded-2xl border border-gray-200/70 bg-white/70 p-8 shadow-sm backdrop-blur
                  dark:border-gray-800 dark:bg-gray-900/60">
                <div
                    class="mx-auto mb-6 flex h-14 w-14 items-center justify-center rounded-xl
                    bg-red-50 ring-1 ring-red-100 dark:bg-red-900/20 dark:ring-red-900/40">
                    {{-- Icon "no access" (SVG) --}}
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                        class="h-7 w-7 fill-red-600 dark:fill-red-400" aria-hidden="true">
                        <path
                            d="M12 2a10 10 0 1 0 10 10A10.011 10.011 0 0 0 12 2Zm6.32 5.25-11.07 11.5A8 8 0 0 1 18.32 7.25Zm-12.64 9.5L16.75 5.68A8 8 0 0 1 5.68 16.75Z" />
                    </svg>
                </div>

                <h1 class="text-2xl font-semibold tracking-tight">
                    Perangkat tidak didukung
                </h1>
                <p class="mt-3 leading-relaxed text-gray-600 dark:text-gray-300">
                    Maaf, <strong>Audio Statistik</strong> saat ini belum mendukung perangkat
                    <strong>macOS</strong>,
                    <strong>iPadOS</strong>, atau <strong>iOS</strong>.
                    Kami sedang bekerja agar dukungan tersedia di waktu mendatang.
                </p>

                <div class="mt-6 space-y-3">
                    <div
                        class="rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-800 ring-1 ring-amber-200
                      dark:bg-amber-900/20 dark:text-amber-200 dark:ring-amber-900/40">
                        Coba akses dari perangkat <span class="font-medium">Windows</span> atau <span
                            class="font-medium">Android</span> untuk pengalaman terbaik.
                    </div>

                    <div class="flex flex-wrap gap-3 pt-1">
                        <a href="{{ url()->previous() ?: url('/') }}"
                            class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium
                      ring-1 ring-gray-300 hover:bg-gray-50
                      dark:ring-gray-700 dark:hover:bg-gray-800">
                            ← Kembali
                        </a>

                        <a href="mailto:ipds7100@bps.go.id"
                            class="inline-flex items-center justify-center rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white
                      hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-200">
                            Hubungi Dukungan
                        </a>
                    </div>
                </div>

                <footer
                    class="mt-8 border-t border-gray-200 pt-4 text-xs text-gray-500
                       dark:border-gray-800 dark:text-gray-400">
                    Kode kesalahan: <code class="font-mono">PLATFORM_UNSUPPORTED</code>
                </footer>
            </div>

            {{-- Optional: catatan teknis untuk QA (hanya terlihat di DOM, tidak mencolok) --}}
            <p class="sr-only">Halaman ini ditampilkan karena perangkat terdeteksi sebagai Apple OS.</p>
        </section>
    </main>
</body>

</html>
