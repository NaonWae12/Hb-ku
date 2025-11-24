@extends('layouts.form-builder')

@section('title', 'Formulir Baru - hb-ku')

@section('content')
@php
    $responsesStats = $responsesStats ?? [
        'total_responses' => 0,
        'question_count' => 0,
        'latest_response_at' => null,
    ];
@endphp
<div class="min-h-screen bg-gray-50" id="form-builder-root"
    data-initial='@json($formData ?? null)'
    data-mode="{{ $formMode ?? 'create' }}"
    data-form-id="{{ $formId }}"
    data-share-url="{{ $shareUrl ?? '' }}"
    data-responses-url="{{ $formId ? route('forms.responses.data', $formId) : '' }}"
    data-total-responses="{{ $responsesStats['total_responses'] }}"
    data-saved-rules='@json($savedRules ?? [])'>
    <!-- Top Bar dengan tombol -->
    <div class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Back Button -->
                <a href="{{ route('dashboard') }}" class="flex items-center space-x-2 text-gray-700 hover:text-red-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    <span class="text-sm font-medium">Kembali</span>
                </a>

                <!-- Action Buttons -->
                <div class="flex items-center space-x-3">
                    <!-- Share Link Button -->
                    <button id="share-link-btn" type="button"
                        class="flex items-center space-x-2 px-4 py-2 bg-white border border-red-600 text-red-600 rounded-lg hover:bg-red-50 transition-colors {{ $shareUrl ? '' : 'hidden' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path>
                        </svg>
                        <span class="text-sm font-medium">Share Link</span>
                    </button>

                    <!-- Save Form Button -->
                    <button id="save-form-btn" class="flex items-center space-x-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                        </svg>
                        <span class="text-sm font-medium">Save Form</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab Bar -->
    <div class="bg-white border-b border-gray-200 sticky top-16 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex space-x-8">
                <button class="tab-btn active px-4 py-3 text-sm font-medium text-red-600 border-b-2 border-red-600 transition-colors" data-tab="questions">
                    Pertanyaan
                </button>
                <button class="tab-btn px-4 py-3 text-sm font-medium text-gray-600 border-b-2 border-transparent hover:text-red-600 hover:border-red-300 transition-colors" data-tab="responses">
                    Jawaban
                </button>
                <button class="tab-btn px-4 py-3 text-sm font-medium text-gray-600 border-b-2 border-transparent hover:text-red-600 hover:border-red-300 transition-colors" data-tab="settings">
                    Setelan
                </button>
            </div>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Tab Content: Pertanyaan -->
        <div id="tab-questions" class="tab-content">
            <!-- Form Header -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
                <div class="p-6">
                    <!-- Title Input -->
                    <div class="mb-4">
                        <input
                            type="text"
                            placeholder="Judul formulir tanpa judul"
                            class="w-full text-2xl font-normal text-gray-900 border-none outline-none focus:ring-0 placeholder-gray-400 pb-2 border-b-2 border-transparent focus:border-red-600 transition-colors"
                            id="form-title">
                    </div>

                    <!-- Description Input -->
                    <div>
                        <input
                            type="text"
                            placeholder="Deskripsi formulir"
                            class="w-full text-sm text-gray-600 border-none outline-none focus:ring-0 placeholder-gray-400 pb-2 border-b-2 border-transparent focus:border-red-600 transition-colors"
                            id="form-description">
                    </div>
                </div>
            </div>

            <!-- Questions Container -->
            <div id="questions-container" class="space-y-4">
                <!-- Question akan ditambahkan di sini via JavaScript -->
            </div>

            <!-- Add Question Button -->
            <div class="mt-6 flex justify-center space-x-3">
                <button
                    id="add-question-btn"
                    class="flex items-center space-x-2 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md border border-gray-300 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    <span>Tambah pertanyaan</span>
                </button>
                <button
                    id="add-section-btn"
                    class="flex items-center space-x-2 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md border border-gray-300 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span>Tambahkan bagian</span>
                </button>
            </div>
        </div>

        <!-- Tab Content: Jawaban -->
        <div id="tab-responses" class="tab-content hidden">
            <div class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white rounded-lg border border-gray-200 p-5 shadow-sm">
                        <p class="text-sm text-gray-500">Total Jawaban</p>
                        <p id="builder-total-responses" class="text-3xl font-semibold text-gray-900 mt-1">{{ $responsesStats['total_responses'] }}</p>
                        <p class="text-xs text-gray-500 mt-2">Jawaban yang sudah terkumpul</p>
                    </div>
                    <div class="bg-white rounded-lg border border-gray-200 p-5 shadow-sm">
                        <p class="text-sm text-gray-500">Pertanyaan Aktif</p>
                        <p id="builder-question-count" class="text-3xl font-semibold text-gray-900 mt-1">{{ $responsesStats['question_count'] }}</p>
                        <p class="text-xs text-gray-500 mt-2">Pertanyaan yang ditampilkan ke responden</p>
                    </div>
                    <div class="bg-white rounded-lg border border-gray-200 p-5 shadow-sm">
                        <p class="text-sm text-gray-500">Jawaban Terbaru</p>
                        <p id="builder-latest-response" class="text-xl font-semibold text-gray-900 mt-1">{{ $responsesStats['latest_response_at'] ?? 'Belum ada data' }}</p>
                        @if($formId)
                            <a href="{{ route('forms.responses', $formId) }}" class="inline-flex items-center text-sm font-medium text-red-600 hover:text-red-700 mt-3">
                                Lihat halaman jawaban lengkap
                                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        @else
                            <p class="text-xs text-gray-500 mt-2">Simpan form untuk mulai menerima jawaban.</p>
                        @endif
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="flex items-center justify-between border-b border-gray-100 px-6">
                        <div class="flex space-x-8">
                            <button id="builder-summary-tab" type="button" class="builder-response-tab px-4 py-4 text-sm font-medium text-red-600 border-b-2 border-red-600">
                                Summary
                            </button>
                            <button id="builder-individual-tab" type="button" class="builder-response-tab px-4 py-4 text-sm font-medium text-gray-500 border-b-2 border-transparent hover:text-red-600">
                                Individual
                            </button>
                        </div>
                        @if($shareUrl)
                            <button id="builder-open-share" type="button" class="text-sm font-medium text-red-600 hover:text-red-700 flex items-center space-x-2" data-share-url="{{ $shareUrl }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 8a3 3 0 013 3v7a3 3 0 01-3 3H6a3 3 0 01-3-3v-7a3 3 0 013-3h9m4-3h-5m0 0V0m0 5l2.5-2.5" />
                                </svg>
                                <span>Bagikan Form</span>
                            </button>
                        @endif
                    </div>

                    <div class="px-6 pb-6">
                        <div id="builder-summary-panel" class="pt-6">
                            <div id="builder-summary-empty" class="{{ $responsesStats['total_responses'] > 0 ? 'hidden' : '' }}">
                                <div class="border-2 border-dashed border-gray-200 rounded-xl p-10 text-center">
                                    <p class="text-lg font-medium text-gray-900 mb-2">Belum ada jawaban</p>
                                    <p class="text-sm text-gray-500">Bagikan link form untuk mulai menerima jawaban responden.</p>
                                </div>
                            </div>
                            <div id="builder-summary-loading" class="hidden">
                                <div class="border border-gray-100 rounded-xl p-6 text-sm text-gray-500 bg-gray-50">Memuat ringkasan jawaban...</div>
                            </div>
                            <div id="builder-summary-content" class="space-y-6 hidden"></div>
                        </div>

                        <div id="builder-individual-panel" class="pt-6 hidden">
                            <div id="builder-individual-empty" class="{{ $responsesStats['total_responses'] > 0 ? 'hidden' : '' }}">
                                <div class="border-2 border-dashed border-gray-200 rounded-xl p-10 text-center">
                                    <p class="text-lg font-medium text-gray-900 mb-2">Belum ada jawaban</p>
                                    <p class="text-sm text-gray-500">Setelah ada respons, Anda dapat menelusuri jawaban satu per satu di sini.</p>
                                </div>
                            </div>
                            <div id="builder-individual-loading" class="hidden">
                                <div class="border border-gray-100 rounded-xl p-6 text-sm text-gray-500 bg-gray-50">Memuat jawaban individu...</div>
                            </div>
                            <div id="builder-individual-content" class="space-y-6 hidden">
                                <div class="flex items-center justify-between border border-gray-100 rounded-xl p-5">
                                    <div>
                                        <p class="text-sm text-gray-500">Jawaban ke-<span id="builder-response-position">1</span></p>
                                        <p class="text-lg font-semibold text-gray-900" id="builder-response-email">-</p>
                                        <p class="text-sm text-gray-500" id="builder-response-date">-</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm text-gray-500">Skor Total</p>
                                        <p class="text-2xl font-bold text-gray-900" id="builder-response-score">-</p>
                                    </div>
                                </div>

                                <div id="builder-response-answers" class="space-y-4"></div>

                                <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                                    <button id="builder-prev-response" class="px-4 py-2 text-sm font-medium border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-50">Sebelumnya</button>
                                    <button id="builder-next-response" class="px-4 py-2 text-sm font-medium border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-50">Berikutnya</button>
                                </div>
                            </div>
                            <div id="builder-responses-error" class="hidden mt-4 text-sm text-red-600 bg-red-50 border border-red-100 rounded-xl p-4"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Content: Setelan -->
        <div id="tab-settings" class="tab-content hidden">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-6">
                <!-- Form Settings -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Pengaturan Formulir</h3>

                    <div class="space-y-4">
                        <!-- Collect Email -->
                        <div class="flex items-center justify-between">
                            <div>
                                <label class="text-sm font-medium text-gray-900">Kumpulkan alamat email</label>
                                <p class="text-xs text-gray-500 mt-1">Mengumpulkan alamat email responden</p>
                            </div>
                            <label class="relative inline-block w-11 h-6">
                                <input type="checkbox" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-red-500 rounded-full transition-colors duration-200 ease-in-out peer-checked:bg-red-600">
                                    <div class="absolute top-[2px] left-[2px] bg-white rounded-full h-5 w-5 transition-transform duration-200 ease-in-out peer-checked:translate-x-5 shadow-sm"></div>
                                </div>
                            </label>
                        </div>

                        <!-- Limit to One Response -->
                        <div class="flex items-center justify-between">
                            <div>
                                <label class="text-sm font-medium text-gray-900">Batasi ke 1 respons</label>
                                <p class="text-xs text-gray-500 mt-1">Membatasi setiap responden hanya bisa mengisi sekali</p>
                            </div>
                            <label class="relative inline-block w-11 h-6">
                                <input type="checkbox" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-red-500 rounded-full transition-colors duration-200 ease-in-out peer-checked:bg-red-600">
                                    <div class="absolute top-[2px] left-[2px] bg-white rounded-full h-5 w-5 transition-transform duration-200 ease-in-out peer-checked:translate-x-5 shadow-sm"></div>
                                </div>
                            </label>
                        </div>

                        <!-- Show Progress Bar -->
                        <div class="flex items-center justify-between">
                            <div>
                                <label class="text-sm font-medium text-gray-900">Tampilkan progress bar</label>
                                <p class="text-xs text-gray-500 mt-1">Menampilkan progress bar di bagian atas form</p>
                            </div>
                            <label class="relative inline-block w-11 h-6">
                                <input type="checkbox" class="sr-only peer" checked>
                                <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-red-500 rounded-full transition-colors duration-200 ease-in-out peer-checked:bg-red-600">
                                    <div class="absolute top-[2px] left-[2px] bg-white rounded-full h-5 w-5 transition-transform duration-200 ease-in-out peer-checked:translate-x-5 shadow-sm"></div>
                                </div>
                            </label>
                        </div>

                        <!-- Shuffle Question Order -->
                        <div class="flex items-center justify-between">
                            <div>
                                <label class="text-sm font-medium text-gray-900">Acak urutan pertanyaan</label>
                                <p class="text-xs text-gray-500 mt-1">Menampilkan pertanyaan dalam urutan acak</p>
                            </div>
                            <label class="relative inline-block w-11 h-6">
                                <input type="checkbox" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-red-500 rounded-full transition-colors duration-200 ease-in-out peer-checked:bg-red-600">
                                    <div class="absolute top-[2px] left-[2px] bg-white rounded-full h-5 w-5 transition-transform duration-200 ease-in-out peer-checked:translate-x-5 shadow-sm"></div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Form Rules -->
                <div class="border-t border-gray-200 pt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Aturan Form</h3>

                    <div class="space-y-6">
                        <!-- Template Jawaban dengan Skor -->
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <h4 class="text-sm font-medium text-gray-900">Template Jawaban & Skor</h4>
                                    <p class="text-xs text-gray-500 mt-1">Buat template jawaban untuk pertanyaan pilihan ganda beserta skor masing-masing</p>
                                </div>
                                <button id="add-answer-template-btn" class="px-3 py-1.5 text-sm font-medium text-red-600 border border-red-600 rounded-lg hover:bg-red-50 transition-colors">
                                    + Tambah Jawaban
                                </button>
                            </div>

                            <div id="answer-templates-container" class="space-y-3 mt-4">
                                <!-- Answer templates akan ditambahkan di sini -->
                                <div class="text-sm text-gray-500 italic">Belum ada template jawaban. Klik "Tambah Jawaban" untuk menambahkan.</div>
                            </div>
                        </div>

                        <!-- Result Rules berdasarkan Range -->
                        <div class="border-t border-gray-200 pt-6">
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <h4 class="text-sm font-medium text-gray-900">Aturan Hasil Berdasarkan Range Skor</h4>
                                    <p class="text-xs text-gray-500 mt-1">Tentukan hasil yang ditampilkan berdasarkan total skor</p>
                                </div>
                                <button id="add-result-rule-btn" class="px-3 py-1.5 text-sm font-medium text-red-600 border border-red-600 rounded-lg hover:bg-red-50 transition-colors">
                                    + Tambah Aturan
                                </button>
                            </div>

                            <div id="result-rules-container" class="space-y-4 mt-4">
                                <!-- Result rules akan ditambahkan di sini -->
                                <div class="text-sm text-gray-500 italic">Belum ada aturan hasil. Klik "Tambah Aturan" untuk menambahkan.</div>
                            </div>

                            <div class="mt-6 flex items-center justify-between">
                                <p class="text-xs text-gray-500 max-w-md">Simpan aturan agar bisa dipakai ulang pada pertanyaan pilihan ganda.</p>
                                <button id="save-form-rules-btn" hidden class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors">
                                    Simpan Aturan
                                </button>
                            </div>

                            <div id="saved-rules-container" class="mt-4 space-y-2 hidden">
                                <p class="text-xs font-medium text-gray-600 uppercase tracking-wide">Aturan Tersimpan</p>
                                <div id="saved-rules-chips" class="flex flex-wrap gap-2">
                                    <!-- Chips akan ditambahkan melalui JavaScript -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Customization -->
                <div class="border-t border-gray-200 pt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Kustomisasi</h3>

                    <div class="space-y-4">
                        <!-- Form Color -->
                        <div>
                            <label class="text-sm font-medium text-gray-900 mb-2 block">Warna Tema</label>
                            <div class="flex items-center space-x-3">
                                <button type="button" data-theme-color="red" class="w-10 h-10 bg-red-600 rounded-lg border-2 border-red-600 shadow-sm" title="Merah"></button>
                                <button type="button" data-theme-color="blue" class="w-10 h-10 bg-blue-600 rounded-lg border-2 border-transparent hover:border-gray-300" title="Biru"></button>
                                <button type="button" data-theme-color="green" class="w-10 h-10 bg-green-600 rounded-lg border-2 border-transparent hover:border-gray-300" title="Hijau"></button>
                                <button type="button" data-theme-color="purple" class="w-10 h-10 bg-purple-600 rounded-lg border-2 border-transparent hover:border-gray-300" title="Ungu"></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Question Types Menu (akan muncul saat klik add question) -->
<div id="question-types-menu" class="hidden fixed inset-0 z-50 bg-black bg-opacity-50">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <div class="p-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Pilih jenis pertanyaan</h3>
        </div>
        <div class="p-2">
            <button class="question-type-btn w-full text-left px-4 py-3 hover:bg-gray-100 rounded-md transition-colors" data-type="short-answer">
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                    </svg>
                    <span class="text-sm font-medium text-gray-700">Jawaban singkat</span>
                </div>
            </button>
            <button class="question-type-btn w-full text-left px-4 py-3 hover:bg-gray-100 rounded-md transition-colors" data-type="paragraph">
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path>
                    </svg>
                    <span class="text-sm font-medium text-gray-700">Paragraf</span>
                </div>
            </button>
            <button class="question-type-btn w-full text-left px-4 py-3 hover:bg-gray-100 rounded-md transition-colors" data-type="multiple-choice">
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-sm font-medium text-gray-700">Pilihan ganda</span>
                </div>
            </button>
            <button class="question-type-btn w-full text-left px-4 py-3 hover:bg-gray-100 rounded-md transition-colors" data-type="checkbox">
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-sm font-medium text-gray-700">Kotak centang</span>
                </div>
            </button>
            <button class="question-type-btn w-full text-left px-4 py-3 hover:bg-gray-100 rounded-md transition-colors" data-type="dropdown">
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                    <span class="text-sm font-medium text-gray-700">Dropdown</span>
                </div>
            </button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const summaryTab = document.getElementById('builder-summary-tab');
    const individualTab = document.getElementById('builder-individual-tab');
    const summaryPanel = document.getElementById('builder-summary-panel');
    const individualPanel = document.getElementById('builder-individual-panel');

    if (summaryTab && individualTab && summaryPanel && individualPanel) {
        summaryTab.addEventListener('click', () => {
            summaryTab.classList.add('text-red-600', 'border-red-600');
            summaryTab.classList.remove('text-gray-500', 'border-transparent');
            individualTab.classList.remove('text-red-600', 'border-red-600');
            individualTab.classList.add('text-gray-500', 'border-transparent');
            summaryPanel.classList.remove('hidden');
            individualPanel.classList.add('hidden');
        });

        individualTab.addEventListener('click', () => {
            individualTab.classList.add('text-red-600', 'border-red-600');
            individualTab.classList.remove('text-gray-500', 'border-transparent');
            summaryTab.classList.remove('text-red-600', 'border-red-600');
            summaryTab.classList.add('text-gray-500', 'border-transparent');
            individualPanel.classList.remove('hidden');
            summaryPanel.classList.add('hidden');
        });
    }

    const summaryShareBtn = document.getElementById('builder-open-share');
    const headerShareBtn = document.getElementById('share-link-btn');
    if (summaryShareBtn && headerShareBtn) {
        summaryShareBtn.addEventListener('click', () => headerShareBtn.click());
    }
});
</script>
@endpush