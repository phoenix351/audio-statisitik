<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>403 Forbidden</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Pastikan Tailwind sudah ter-load di app kamu --}}
    {{-- Kalau pakai Vite: --}}
    @vite('resources/css/app.css')
</head>

<body class="min-h-screen bg-slate-100 flex items-center justify-center">
    <div class="max-w-lg w-full px-6 py-10 bg-white shadow-lg rounded-xl text-center">
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-red-100 mb-6">
            <span class="text-3xl font-semibold text-red-500">403</span>
        </div>

        <h1 class="text-2xl md:text-3xl font-bold text-slate-800 mb-3">
            Access Forbidden
        </h1>

        <p class="text-slate-500 mb-6">
            You don’t have permission to access this page.
            If you believe this is a mistake, please contact the administrator.
        </p>

        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="{{ url()->previous() }}"
                class="inline-flex items-center justify-center px-5 py-2.5 rounded-lg border border-slate-300 text-slate-700 text-sm font-medium hover:bg-slate-50 transition">
                Go Back
            </a>

            <a href="{{ url('/') }}"
                class="inline-flex items-center justify-center px-5 py-2.5 rounded-lg bg-slate-800 text-white text-sm font-medium hover:bg-slate-900 transition">
                Go to Homepage
            </a>
        </div>

        @isset($exception)
            <p class="mt-6 text-xs text-slate-400">
                {{-- Optional: info tambahan untuk debugging internal --}}
                {{-- {{ $exception->getMessage() }} --}}
            </p>
        @endisset
        </
