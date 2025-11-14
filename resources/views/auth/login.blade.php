@extends('layouts.app')

@section('title', 'Masuk - Audio Statistik')

@section('content')
    <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div class="text-center">
                <div class="flex items-center justify-center space-x-3 mb-6">
                    <div class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-volume-up text-white text-xl" aria-hidden="true"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Audio Statistik</h1>
                        <p class="text-sm text-gray-500">BPS Sulawesi Utara</p>
                    </div>
                </div>
                <h2 class="text-xl font-semibold text-gray-900 mb-2">Masuk sebagai Admin</h2>
                <p class="text-gray-600">Kelola dokumen dan sistem Audio Statistik</p>
            </div>

            <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-6">
                @csrf

                @if ($errors->any())
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4" role="alert">
                        <div class="flex">
                            <i class="fas fa-exclamation-circle text-red-400 mr-3 mt-1" aria-hidden="true"></i>
                            <div>
                                <h3 class="text-sm font-medium text-red-800">Terjadi kesalahan:</h3>
                                <ul class="mt-2 text-sm text-red-700 space-y-1">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif

                @if (session('success'))
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4" role="alert">
                        <div class="flex">
                            <i class="fas fa-check-circle text-green-400 mr-3 mt-1" aria-hidden="true"></i>
                            <div class="text-sm text-green-800">{{ session('success') }}</div>
                        </div>
                    </div>
                @endif

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        Alamat Email
                    </label>
                    <div class="relative">
                        <input id="email" name="email" type="email" autocomplete="email" required
                            value="{{ old('email') }}"
                            class="appearance-none relative block w-full px-4 py-3 pl-10 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="email@gmail.com">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-envelope text-gray-400" aria-hidden="true"></i>
                        </div>
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        Kata Sandi
                    </label>
                    <div class="relative">
                        <input id="password" name="password" type="password" autocomplete="current-password" required
                            class="appearance-none relative block w-full px-4 py-3 pl-10 pr-10 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Masukkan kata sandi">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400" aria-hidden="true"></i>
                        </div>
                        <button type="button" class="absolute inset-y-0 right-0 pr-3 flex items-center"
                            onclick="togglePassword('password')" aria-label="Toggle password visibility">
                            <i class="fas fa-eye text-gray-400 hover:text-gray-600" aria-hidden="true"
                                id="password-toggle-icon"></i>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between">


                    <div class="text-sm">
                        <a href="#" class="font-medium text-blue-600 hover:text-blue-500 transition-colors">
                            Lupa kata sandi?
                        </a>
                    </div>
                </div>

                <div>
                    <button type="submit"
                        class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <i class="fas fa-sign-in-alt text-blue-500 group-hover:text-blue-400" aria-hidden="true"></i>
                        </span>
                        Masuk sebagai Admin
                    </button>
                </div>

                <div class="text-center">
                    <p class="text-sm text-gray-600">
                        Kembali ke
                        <a href="{{ route('home') }}"
                            class="font-medium text-blue-600 hover:text-blue-500 transition-colors">
                            halaman utama
                        </a>
                    </p>
                </div>
            </form>

            <!-- Accessibility Info -->
            <div class="bg-gray-50 rounded-lg p-4 mt-6">
                <div class="flex">
                    <i class="fas fa-universal-access text-gray-400 mr-3 mt-1" aria-hidden="true"></i>
                    <div>
                        <h3 class="text-sm font-medium text-gray-800">Catatan Aksesibilitas</h3>
                        <p class="mt-1 text-xs text-gray-600">
                            Pencarian suara tidak aktif di halaman login untuk memudahkan input.
                            Gunakan Tab untuk navigasi keyboard.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function togglePassword(fieldId) {
                const field = document.getElementById(fieldId);
                const icon = document.getElementById(fieldId + '-toggle-icon');

                if (field.type === 'password') {
                    field.type = 'text';
                    icon.classList.replace('fa-eye', 'fa-eye-slash');
                } else {
                    field.type = 'password';
                    icon.classList.replace('fa-eye-slash', 'fa-eye');
                }
            }

            // Focus management for accessibility
            document.addEventListener('DOMContentLoaded', function() {
                // Focus on email field when page loads
                const emailField = document.getElementById('email');
                if (emailField) {
                    emailField.focus();
                }

                // Disable voice search on this page by overriding global functions
                window.recognition = null;

                // Remove any existing voice search event listeners
                document.removeEventListener('keydown', handleVoiceSearchKeydown);

                // Announce page context to screen readers
                const announcement = document.createElement('div');
                announcement.setAttribute('aria-live', 'polite');
                announcement.setAttribute('aria-atomic', 'true');
                announcement.className = 'sr-only';
                announcement.textContent = 'Halaman login admin. Pencarian suara tidak aktif di halaman ini.';
                document.body.appendChild(announcement);

                setTimeout(() => {
                    if (document.body.contains(announcement)) {
                        document.body.removeChild(announcement);
                    }
                }, 3000);
            });

            function handleVoiceSearchKeydown(e) {
                // This function is defined to prevent errors, but does nothing on auth pages
                return;
            }
        </script>
    @endpush
@endsection
