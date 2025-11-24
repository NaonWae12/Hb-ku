// Form Builder JavaScript
console.log('Form Builder JS loaded');
let questionCounter = 0;
let sectionCounter = 0;

function getMetaContent(name) {
    return document.querySelector(`meta[name="${name}"]`)?.getAttribute('content') || '';
}

function setMetaContent(name, value) {
    const meta = document.querySelector(`meta[name="${name}"]`);
    if (meta) {
        meta.setAttribute('content', value);
    }
}

function getAnswerTemplatesPlaceholder() {
    return '<div class="answer-templates-placeholder text-sm text-gray-500 italic">Belum ada template jawaban. Klik "Tambah Jawaban" untuk menambahkan.</div>';
}

function getResultRulesPlaceholder() {
    return '<div class="result-rules-placeholder text-sm text-gray-500 italic">Belum ada aturan hasil. Klik "Tambah Aturan" untuk menambahkan.</div>';
}

function resetFormRulesBuilder() {
    const answerTemplatesContainer = document.getElementById('answer-templates-container');
    if (answerTemplatesContainer) {
        answerTemplatesContainer.innerHTML = getAnswerTemplatesPlaceholder();
    }

    const resultRulesContainer = document.getElementById('result-rules-container');
    if (resultRulesContainer) {
        resultRulesContainer.innerHTML = getResultRulesPlaceholder();
    }

    answerTemplateCounter = 0;
    resultRuleCounter = 0;
    updateRuleSaveControlsVisibility();
}

// Fungsi untuk membuat section divider
function createSectionDivider() {
    sectionCounter++;
    const sectionDivider = document.createElement('div');
    sectionDivider.className = 'section-divider bg-white rounded-lg shadow-sm border-2 border-dashed border-gray-300 p-6 my-6';
    sectionDivider.setAttribute('data-section-id', sectionCounter);
    sectionDivider.innerHTML = `
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-medium text-gray-900 section-title">Bagian ${sectionCounter}</h3>
                    <p class="text-sm text-gray-500">Pertanyaan di bawah ini akan ditampilkan di halaman terpisah</p>
                </div>
            </div>
            <button class="delete-section-btn p-2 text-gray-500 hover:bg-gray-100 rounded-full transition-colors" title="Hapus bagian">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
            </button>
        </div>
    `;
    return sectionDivider;
}

// Question templates untuk setiap tipe
const questionTemplates = {
    'short-answer': {
        icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path></svg>`,
        label: 'Jawaban singkat',
        input: `
            <div class="max-w-md mt-4">
                <input 
                    type="text" 
                    placeholder="Jawaban singkat" 
                    class="w-full px-0 py-2 text-sm text-gray-500 border-none border-b border-gray-300 focus:border-red-600 focus:outline-none transition-colors"
                    disabled
                >
            </div>
        `
    },
    'paragraph': {
        icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path></svg>`,
        label: 'Paragraf',
        input: `
            <div class="max-w-md mt-4">
                <textarea 
                    placeholder="Jawaban panjang" 
                    rows="4"
                    class="w-full px-0 py-2 text-sm text-gray-500 border-none border-b border-gray-300 focus:border-red-600 focus:outline-none transition-colors resize-none"
                    disabled
                ></textarea>
            </div>
        `
    },
    'multiple-choice': {
        icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`,
        label: 'Pilihan ganda',
        input: `
            <div class="space-y-2 max-w-md mt-4">
                <div class="option-item flex items-center space-x-2">
                    <input type="radio" disabled class="text-red-600 focus:ring-red-500 mt-1">
                    <input 
                        type="text" 
                        placeholder="Opsi 1" 
                        class="flex-1 px-0 py-1 text-sm text-gray-500 border-none border-b border-transparent focus:border-red-600 focus:outline-none transition-colors option-input"
                    >
                    <button class="remove-option-btn p-1 text-gray-400 hover:text-red-600 transition-colors opacity-0 group-hover:opacity-100" title="Hapus opsi">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="option-item flex items-center space-x-2">
                    <input type="radio" disabled class="text-red-600 focus:ring-red-500 mt-1">
                    <input 
                        type="text" 
                        placeholder="Opsi 2" 
                        class="flex-1 px-0 py-1 text-sm text-gray-500 border-none border-b border-transparent focus:border-red-600 focus:outline-none transition-colors option-input"
                    >
                    <button class="remove-option-btn p-1 text-gray-400 hover:text-red-600 transition-colors opacity-0 group-hover:opacity-100" title="Hapus opsi">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="mt-2 space-y-1">
                    <button class="add-option-btn text-sm text-gray-600 hover:text-red-600 flex items-center space-x-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>Tambahkan opsi</span>
                    </button>
                    <span class="text-sm text-gray-500"> atau </span>
                    <button class="add-other-btn text-sm text-red-600 hover:text-red-700">
                        tambahkan "Lainnya"
                    </button>
                </div>
                <div class="use-saved-rules-wrapper mt-3 hidden">
                    <button type="button" class="use-saved-rule-btn inline-flex items-center px-3 py-1 text-xs font-medium text-red-600 border border-red-200 rounded-full hover:bg-red-50 transition-colors space-x-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Gunakan aturan tersimpan</span>
                    </button>
                </div>
            </div>
        `
    },
    'checkbox': {
        icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`,
        label: 'Kotak centang',
        input: `
            <div class="space-y-2 max-w-md mt-4">
                <div class="option-item flex items-center space-x-2">
                    <input type="checkbox" disabled class="rounded border-gray-300 text-red-600 focus:ring-red-500 mt-1">
                    <input 
                        type="text" 
                        placeholder="Opsi 1" 
                        class="flex-1 px-0 py-1 text-sm text-gray-500 border-none border-b border-transparent focus:border-red-600 focus:outline-none transition-colors option-input"
                    >
                    <button class="remove-option-btn p-1 text-gray-400 hover:text-red-600 transition-colors opacity-0 group-hover:opacity-100" title="Hapus opsi">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="option-item flex items-center space-x-2">
                    <input type="checkbox" disabled class="rounded border-gray-300 text-red-600 focus:ring-red-500 mt-1">
                    <input 
                        type="text" 
                        placeholder="Opsi 2" 
                        class="flex-1 px-0 py-1 text-sm text-gray-500 border-none border-b border-transparent focus:border-red-600 focus:outline-none transition-colors option-input"
                    >
                    <button class="remove-option-btn p-1 text-gray-400 hover:text-red-600 transition-colors opacity-0 group-hover:opacity-100" title="Hapus opsi">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="mt-2 space-y-1">
                    <button class="add-option-btn text-sm text-gray-600 hover:text-red-600 flex items-center space-x-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>Tambahkan opsi</span>
                    </button>
                    <span class="text-sm text-gray-500"> atau </span>
                    <button class="add-other-btn text-sm text-red-600 hover:text-red-700">
                        tambahkan "Lainnya"
                    </button>
                </div>
            </div>
        `
    },
    'dropdown': {
        icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>`,
        label: 'Dropdown',
        input: `
            <div class="space-y-2 max-w-md mt-4">
                <div class="option-item flex items-center space-x-2">
                    <span class="text-sm text-gray-500 w-4">1.</span>
                    <input 
                        type="text" 
                        placeholder="Opsi 1" 
                        class="flex-1 px-0 py-1 text-sm text-gray-500 border-none border-b border-transparent focus:border-red-600 focus:outline-none transition-colors option-input"
                    >
                    <button class="remove-option-btn p-1 text-gray-400 hover:text-red-600 transition-colors opacity-0 group-hover:opacity-100" title="Hapus opsi">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="option-item flex items-center space-x-2">
                    <span class="text-sm text-gray-500 w-4">2.</span>
                    <input 
                        type="text" 
                        placeholder="Opsi 2" 
                        class="flex-1 px-0 py-1 text-sm text-gray-500 border-none border-b border-transparent focus:border-red-600 focus:outline-none transition-colors option-input"
                    >
                    <button class="remove-option-btn p-1 text-gray-400 hover:text-red-600 transition-colors opacity-0 group-hover:opacity-100" title="Hapus opsi">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="mt-2 space-y-1">
                    <button class="add-option-btn text-sm text-gray-600 hover:text-red-600 flex items-center space-x-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>Tambahkan opsi</span>
                    </button>
                    <span class="text-sm text-gray-500"> atau </span>
                    <button class="add-other-btn text-sm text-red-600 hover:text-red-700">
                        tambahkan "Lainnya"
                    </button>
                </div>
            </div>
        `
    }
};

// Fungsi untuk membuat question card
function createQuestionCard(type = 'short-answer') {
    questionCounter++;
    const template = questionTemplates[type];
    
    const questionCard = document.createElement('div');
    questionCard.className = 'question-card bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-all relative group';
    questionCard.setAttribute('data-question-id', questionCounter);
    questionCard.setAttribute('data-question-type', type);
    questionCard.innerHTML = `
        <!-- Drag Handle -->
        <div class="absolute top-2 left-1/2 transform -translate-x-1/2 cursor-move opacity-0 group-hover:opacity-100 transition-opacity">
            <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                <path d="M3 12h18M3 6h18M3 18h18"></path>
            </svg>
        </div>

        <div class="flex items-start space-x-4">
            <div class="flex-1 min-w-0">
                <!-- Question Title Input -->
                <div class="mb-2">
                    <input 
                        type="text" 
                        placeholder="Pertanyaan" 
                        class="w-full text-base font-normal text-gray-900 border-none outline-none focus:ring-0 placeholder-gray-400 pb-2 border-b-2 border-transparent focus:border-red-600 transition-colors question-title"
                    >
                </div>

                <!-- Formatting Toolbar -->
                <div class="formatting-toolbar flex items-center space-x-1 mb-2 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button class="format-btn p-1.5 hover:bg-gray-100 rounded font-bold" title="Bold" data-format="bold">
                        <span class="text-sm">B</span>
                    </button>
                    <button class="format-btn p-1.5 hover:bg-gray-100 rounded italic" title="Italic" data-format="italic">
                        <span class="text-sm">I</span>
                    </button>
                    <button class="format-btn p-1.5 hover:bg-gray-100 rounded underline" title="Underline" data-format="underline">
                        <span class="text-sm">U</span>
                    </button>
                </div>

                <!-- Image Display Area -->
                <div class="question-image-area mb-4 hidden">
                    <div class="relative inline-block">
                        <img src="" alt="Question image" class="max-w-full h-auto rounded-lg border border-gray-200 question-image" style="max-height: 300px;">
                        <button class="remove-image-btn absolute top-2 right-2 p-1.5 bg-red-600 text-white rounded-full hover:bg-red-700 transition-colors" title="Hapus gambar">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Hidden File Input -->
                <input type="file" accept="image/*" class="hidden image-file-input" data-question-id="${questionCounter}">

                <!-- Question Input Area -->
                <div class="question-input-area">
                    ${template.input}
                </div>

                <!-- Bottom Actions -->
                <div class="mt-4 space-y-4 pt-4 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <button class="duplicate-question-btn p-2 text-gray-500 hover:bg-gray-100 rounded-full transition-colors" title="Duplikat">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                            </button>
                            <button class="delete-question-btn p-2 text-gray-500 hover:bg-gray-100 rounded-full transition-colors" title="Hapus">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="flex items-center space-x-4">
                            <label class="flex items-center space-x-2 text-sm text-gray-600 cursor-pointer">
                                <input type="checkbox" class="required-checkbox sr-only">
                                <div class="toggle-switch relative w-11 h-6 bg-gray-300 rounded-full transition-colors duration-200 ease-in-out">
                                    <div class="toggle-switch-handle absolute top-[2px] left-[2px] bg-white rounded-full h-5 w-5 transition-transform duration-200 ease-in-out shadow-sm"></div>
                                </div>
                                <span class="select-none">Wajib diisi</span>
                            </label>
                            <button class="more-options-btn p-2 text-gray-500 hover:bg-gray-100 rounded-full transition-colors" title="Setelan pertanyaan">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="question-advanced-settings hidden bg-gray-50 rounded-lg border border-gray-200 p-4 space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900">Validasi Jawaban</p>
                                <p class="text-xs text-gray-500 mt-1">Gunakan regex sederhana untuk memvalidasi jawaban.</p>
                            </div>
                            <input type="text" class="question-validation-input mt-2 sm:mt-0 sm:max-w-xs px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-red-500" placeholder="contoh: ^[0-9]+$">
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900">Pesan Validasi</p>
                                <p class="text-xs text-gray-500 mt-1">Tampilkan pesan kustom saat jawaban tidak valid.</p>
                            </div>
                            <input type="text" class="question-validation-message mt-2 sm:mt-0 sm:max-w-xs px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-red-500" placeholder="Silakan masukkan angka saja">
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900">Deskripsi Tambahan</p>
                                <p class="text-xs text-gray-500 mt-1">Sampaikan petunjuk tambahan untuk pertanyaan ini.</p>
                            </div>
                            <textarea rows="2" class="question-extra-notes mt-2 sm:mt-0 sm:max-w-xs px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-red-500" placeholder="Contoh: Gunakan format tanggal dd/mm/yyyy"></textarea>
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900">Batas Karakter (opsional)</p>
                                <p class="text-xs text-gray-500 mt-1">Tentukan batas minimal & maksimal untuk jawaban teks.</p>
                            </div>
                            <div class="flex items-center space-x-2 mt-2 sm:mt-0">
                                <input type="number" min="0" class="question-min-length w-24 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-red-500" placeholder="Min">
                                <span class="text-sm text-gray-500">s.d</span>
                                <input type="number" min="0" class="question-max-length w-24 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-red-500" placeholder="Maks">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Sidebar -->
            <div class="shrink-0 flex flex-col items-end space-y-2">
                <button class="add-image-btn p-2 text-gray-500 hover:bg-gray-100 rounded-full transition-colors" title="Tambahkan gambar">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </button>
                <div class="relative">
                    <button class="question-type-dropdown-btn flex items-center space-x-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md border border-gray-300 transition-colors" data-type="${type}">
                        <span class="question-type-icon">${template.icon}</span>
                        <span class="question-type-label">${template.label}</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div class="question-type-dropdown hidden absolute right-0 mt-1 bg-white rounded-lg shadow-lg border border-gray-200 z-10 min-w-[200px]">
                        <div class="py-1">
                            ${Object.keys(questionTemplates).map(key => `
                                <button class="change-question-type-btn w-full text-left px-4 py-2 hover:bg-gray-100 flex items-center space-x-3" data-type="${key}">
                                    <span class="text-gray-500">${questionTemplates[key].icon}</span>
                                    <span class="text-sm text-gray-700">${questionTemplates[key].label}</span>
                                </button>
                            `).join('')}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    return questionCard;
}

// Tab Management
function initTabs() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            // Remove active dari semua tabs
            tabButtons.forEach(btn => {
                btn.classList.remove('active', 'text-red-600', 'border-red-600');
                btn.classList.add('text-gray-600', 'border-transparent');
            });
            
            // Hide semua tab content
            tabContents.forEach(content => {
                content.classList.add('hidden');
            });
            
            // Add active ke tab yang diklik
            this.classList.add('active', 'text-red-600', 'border-red-600');
            this.classList.remove('text-gray-600', 'border-transparent');
            
            // Show target tab content
            const targetContent = document.getElementById(`tab-${targetTab}`);
            if (targetContent) {
                targetContent.classList.remove('hidden');
                
            }
        });
    });
    
    // Initialize toggle switches di tab Setelan
    initSettingsToggles();
}

// Initialize toggle switches untuk settings
function initSettingsToggles() {
    const settingsToggles = document.querySelectorAll('#tab-settings input[type="checkbox"]');
    
    settingsToggles.forEach(toggle => {
        const toggleSwitch = toggle.nextElementSibling;
        const toggleHandle = toggleSwitch.querySelector('div');
        
        if (toggleSwitch && toggleHandle) {
            function updateToggleState() {
                if (toggle.checked) {
                    toggleSwitch.classList.remove('bg-gray-300');
                    toggleSwitch.classList.add('bg-red-600');
                    toggleHandle.classList.add('translate-x-5');
                    toggleHandle.classList.remove('translate-x-0');
                } else {
                    toggleSwitch.classList.add('bg-gray-300');
                    toggleSwitch.classList.remove('bg-red-600');
                    toggleHandle.classList.remove('translate-x-5');
                    toggleHandle.classList.add('translate-x-0');
                }
            }
            
            // Initialize state
            updateToggleState();
            
            // Listen for changes
            toggle.addEventListener('change', updateToggleState);
            
            // Allow clicking on the switch itself
            toggleSwitch.addEventListener('click', function(e) {
                e.preventDefault();
                toggle.checked = !toggle.checked;
                updateToggleState();
            });
        }
    });
}

// Form Rules Management
let answerTemplateCounter = 0;
let resultRuleCounter = 0;

// Fungsi untuk membuat answer template card
function createAnswerTemplateCard() {
    answerTemplateCounter++;
    const templateCard = document.createElement('div');
    templateCard.className = 'answer-template-card bg-gray-50 rounded-lg border border-gray-200 p-4';
    templateCard.setAttribute('data-template-id', answerTemplateCounter);
    templateCard.innerHTML = `
        <div class="flex items-center space-x-3">
            <div class="flex-1">
                <label class="text-xs font-medium text-gray-700 mb-1 block">Jawaban</label>
                <input type="text" placeholder="Masukkan jawaban" class="answer-template-text w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none">
            </div>
            <div class="w-32">
                <label class="text-xs font-medium text-gray-700 mb-1 block">Skor</label>
                <input type="number" placeholder="0" class="answer-template-score w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none" min="0" value="0">
            </div>
            <div class="flex items-end">
                <button class="delete-answer-template-btn p-2 text-gray-400 hover:text-red-600 transition-colors" title="Hapus jawaban">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
    `;
    return templateCard;
}

// Fungsi untuk membuat result rule card
function createResultRuleCard() {
    resultRuleCounter++;
    const ruleCard = document.createElement('div');
    ruleCard.className = 'result-rule-card bg-gray-50 rounded-lg border border-gray-200 p-4';
    ruleCard.setAttribute('data-rule-id', resultRuleCounter);
    ruleCard.innerHTML = `
        <div class="flex items-start justify-between mb-3">
            <h5 class="text-sm font-medium text-gray-900">Aturan ${resultRuleCounter}</h5>
            <button class="delete-result-rule-btn p-1 text-gray-400 hover:text-red-600 transition-colors" title="Hapus aturan">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="space-y-3">
            <div>
                <label class="text-xs font-medium text-gray-700 mb-1 block">Kondisi Skor</label>
                <select class="rule-condition-type w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none">
                    <option value="range">Range (Min - Max)</option>
                    <option value="equal">Sama dengan (=)</option>
                    <option value="greater">Lebih dari (>)</option>
                    <option value="less">Kurang dari (<)</option>
                </select>
            </div>
            <div id="rule-condition-inputs-${resultRuleCounter}" class="space-y-2">
                <div class="rule-range-inputs flex items-center space-x-2">
                    <input type="number" placeholder="Min" class="rule-min-score w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none" min="0">
                    <span class="text-sm text-gray-500">sampai</span>
                    <input type="number" placeholder="Max" class="rule-max-score w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none" min="0">
                </div>
                <div class="rule-single-inputs hidden">
                    <input type="number" placeholder="Nilai" class="rule-single-score w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none" min="0">
                </div>
            </div>
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="text-xs font-medium text-gray-700">Hasil Teks</label>
                    <button class="add-result-text-btn px-2 py-1 text-xs font-medium text-red-600 border border-red-600 rounded hover:bg-red-50 transition-colors" data-rule-id="${resultRuleCounter}">
                        + Tambah Teks
                    </button>
                </div>
                <div class="rule-result-texts space-y-2" data-rule-id="${resultRuleCounter}">
                    <div class="flex items-start space-x-2">
                        <textarea placeholder="Masukkan teks hasil yang akan ditampilkan" rows="2" class="rule-result-text flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none resize-none"></textarea>
                        <button class="delete-result-text-btn p-2 text-gray-400 hover:text-red-600 transition-colors shrink-0" title="Hapus teks">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

    const conditionSelect = ruleCard.querySelector('.rule-condition-type');
    const rangeInputs = ruleCard.querySelector('.rule-range-inputs');
    const singleInput = ruleCard.querySelector('.rule-single-inputs');

    if (conditionSelect) {
        conditionSelect.addEventListener('change', function () {
            const conditionType = this.value;
            const singleScoreInput = singleInput.querySelector('.rule-single-score');

            if (conditionType === 'range') {
                rangeInputs.classList.remove('hidden');
                singleInput.classList.add('hidden');
            } else {
                rangeInputs.classList.add('hidden');
                singleInput.classList.remove('hidden');

                if (singleScoreInput) {
                    if (conditionType === 'equal') {
                        singleScoreInput.placeholder = 'Nilai (sama dengan)';
                    } else if (conditionType === 'greater') {
                        singleScoreInput.placeholder = 'Nilai (lebih dari)';
                    } else if (conditionType === 'less') {
                        singleScoreInput.placeholder = 'Nilai (kurang dari)';
                    }
                }
            }
        });
    }

    const addResultTextBtn = ruleCard.querySelector('.add-result-text-btn');
    const resultTextsContainer = ruleCard.querySelector('.rule-result-texts');

    if (addResultTextBtn && resultTextsContainer) {
        addResultTextBtn.addEventListener('click', function () {
            const textItem = document.createElement('div');
            textItem.className = 'flex items-start space-x-2';
            textItem.innerHTML = `
                <textarea placeholder="Masukkan teks hasil yang akan ditampilkan" rows="2" class="rule-result-text flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:border-red-600 focus:ring-1 focus:ring-red-600 outline-none resize-none"></textarea>
                <button class="delete-result-text-btn p-2 text-gray-400 hover:text-red-600 transition-colors shrink-0" title="Hapus teks">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            `;
            resultTextsContainer.appendChild(textItem);

            const deleteBtn = textItem.querySelector('.delete-result-text-btn');
            if (deleteBtn) {
                deleteBtn.addEventListener('click', function () {
                    textItem.remove();
                    const remaining = resultTextsContainer.querySelectorAll('.rule-result-text');
                    if (!remaining.length) {
                        addResultTextBtn.click();
                    }
                });
            }
        });
    }

    const deleteTextBtns = ruleCard.querySelectorAll('.delete-result-text-btn');
    deleteTextBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const textItem = this.closest('.flex.items-start.space-x-2');
            if (textItem) {
                textItem.remove();
                const remaining = ruleCard.querySelectorAll('.rule-result-text');
                if (!remaining.length) {
                    addResultTextBtn.click();
                }
            }
        });
    });

    return ruleCard;
}

let savedRulesState = [];

function hasFormTemplates() {
    return document.querySelectorAll('.answer-template-card').length > 0;
}

function updateRuleSaveControlsVisibility() {
    const saveBtn = document.getElementById('save-form-rules-btn');
    const savedRulesContainer = document.getElementById('saved-rules-container');
    const hasTemplates = hasFormTemplates();
    if (saveBtn) {
        saveBtn.hidden = !hasTemplates;
        saveBtn.disabled = !hasTemplates;
    }
    if (savedRulesContainer && !savedRulesState.length) {
        savedRulesContainer.classList.add('hidden');
    }
}

function normalizeSavedRule(rule) {
    if (!rule) {
        return { templates: [], result_rules: [] };
    }

    const normalized = {
        id: rule.id ?? Date.now(),
        templates: Array.isArray(rule.templates) ? rule.templates.slice() : Array.isArray(rule.answer_templates) ? rule.answer_templates.slice() : [],
        result_rules: Array.isArray(rule.result_rules) ? rule.result_rules.slice() : [],
    };

    if (!normalized.result_rules.length && rule.condition_type) {
        normalized.result_rules.push({
            condition_type: rule.condition_type,
            min_score: rule.min_score ?? null,
            max_score: rule.max_score ?? null,
            single_score: rule.single_score ?? null,
            texts: Array.isArray(rule.texts) ? rule.texts.slice() : [],
        });
    }

    if (!normalized.templates.length && rule.answer_template) {
        normalized.templates.push(rule.answer_template);
    }

    return normalized;
}

function loadSavedRules() {
    return savedRulesState.map(normalizeSavedRule);
}

function saveRulesToState(rules) {
    savedRulesState = Array.isArray(rules) ? rules.map(normalizeSavedRule) : [];
}

function renderSavedRulesChips() {
    const container = document.getElementById('saved-rules-container');
    const chipsWrapper = document.getElementById('saved-rules-chips');
    if (!container || !chipsWrapper) {
        return;
    }

    const savedRules = loadSavedRules();
    chipsWrapper.innerHTML = '';

    if (!savedRules.length) {
        container.classList.add('hidden');
        updateRuleSaveControlsVisibility();
        return;
    }

    container.classList.remove('hidden');

    savedRules.forEach((rawRule, index) => {
        const rule = normalizeSavedRule(rawRule);
        const templates = Array.isArray(rule.templates) ? rule.templates : [];
        const resultRules = Array.isArray(rule.result_rules) ? rule.result_rules : [];

        const chip = document.createElement('div');
        chip.className = 'saved-rule-chip inline-flex items-center space-x-2 px-3 py-1 text-xs font-medium text-red-700 bg-red-50 border border-red-200 rounded-full';
        chip.setAttribute('data-rule-index', index);

        const descriptionParts = [];
        if (templates.length) {
            descriptionParts.push(`${templates.length} opsi`);
            const preview = templates.slice(0, 2).map(t => t.answer_text || t.text || '').filter(Boolean);
            if (preview.length) {
                descriptionParts.push(preview.join(', '));
            }
        } else {
            descriptionParts.push('0 opsi');
        }

        if (resultRules.length) {
            descriptionParts.push(`${resultRules.length} hasil`);
        }

        chip.innerHTML = `
            <span class="truncate max-w-[200px]">${descriptionParts.join(' • ')}</span>
            <button type="button" class="remove-saved-rule-btn text-red-500 hover:text-red-700">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        `;

        chipsWrapper.appendChild(chip);

        const removeBtn = chip.querySelector('.remove-saved-rule-btn');
        if (removeBtn) {
            removeBtn.addEventListener('click', function () {
                const index = parseInt(chip.getAttribute('data-rule-index'), 10);
                const current = loadSavedRules();
                current.splice(index, 1);
                saveRulesToState(current);
                renderSavedRulesChips();
                updateUseRuleButtonsVisibility();
            });
        }
    });

    updateUseRuleButtonsVisibility();
    updateRuleSaveControlsVisibility();
}

function updateUseRuleButtonsVisibility() {
    const savedRules = loadSavedRules();
    const useRuleWrappers = document.querySelectorAll('.use-saved-rules-wrapper');
    useRuleWrappers.forEach(wrapper => {
        const card = wrapper.closest('.question-card');
        const button = wrapper.querySelector('.use-saved-rule-btn');
        if (!card || !button) {
            return;
        }

        if (savedRules.length && card.getAttribute('data-question-type') === 'multiple-choice') {
            wrapper.classList.remove('hidden');
        } else {
            wrapper.classList.add('hidden');
        }

        if (card.querySelector('.saved-rule-payload')) {
            button.classList.remove('text-red-600', 'border', 'border-red-200');
            button.classList.add('bg-red-600', 'text-white');
        } else {
            button.classList.remove('bg-red-600', 'text-white');
            button.classList.add('text-red-600', 'border', 'border-red-200');
        }
    });

    attachSavedRuleButtons();
}

function gatherRulesFromSettings() {
    const templates = [];
    const templateCards = document.querySelectorAll('.answer-template-card');
    templateCards.forEach((card) => {
        const answerText = card.querySelector('.answer-template-text')?.value?.trim();
        const scoreValue = card.querySelector('.answer-template-score')?.value;
        if (answerText) {
            templates.push({
                answer_text: answerText,
                score: Number(scoreValue ?? 0) || 0,
            });
        }
    });

    if (!templates.length) {
        return null;
    }

    const resultRules = [];
    const ruleCards = document.querySelectorAll('.result-rule-card');
    ruleCards.forEach((card) => {
        const conditionType = card.querySelector('.rule-condition-type')?.value || 'range';
        const ruleData = {
            condition_type: conditionType,
            min_score: null,
            max_score: null,
            single_score: null,
            texts: [],
        };

        if (conditionType === 'range') {
            ruleData.min_score = parseInt(card.querySelector('.rule-min-score')?.value || '0', 10);
            ruleData.max_score = parseInt(card.querySelector('.rule-max-score')?.value || '0', 10);
        } else {
            ruleData.single_score = parseInt(card.querySelector('.rule-single-score')?.value || '0', 10);
        }

        const textareas = card.querySelectorAll('.rule-result-text');
        textareas.forEach((textarea) => {
            if (textarea.value && textarea.value.trim() !== '') {
                ruleData.texts.push(textarea.value.trim());
            }
        });

        if (ruleData.texts.length) {
            resultRules.push(ruleData);
        }
    });

    return normalizeSavedRule({
        id: Date.now(),
        templates,
        result_rules: resultRules,
    });
}

function applySavedRuleToQuestion(questionCard, savedRule) {
    if (!questionCard || !savedRule) {
        return;
    }

    const rule = normalizeSavedRule(savedRule);
    const metaKey = 'savedRuleApplied';
    const templates = Array.isArray(rule.templates) ? rule.templates : [];

    if (questionCard.getAttribute('data-question-type') === 'multiple-choice' && templates.length) {
        const addOptionBtn = questionCard.querySelector('.add-option-btn');
        const container = questionCard.querySelector('.question-input-area > div');
        if (container && addOptionBtn) {
            let optionItems = Array.from(container.querySelectorAll('.option-item'));
            while (optionItems.length < templates.length) {
                addOptionBtn.click();
                optionItems = Array.from(container.querySelectorAll('.option-item'));
            }

            optionItems.forEach((item, idx) => {
                const input = item.querySelector('.option-input');
                if (!input) {
                    return;
                }
                if (idx < templates.length) {
                    input.value = templates[idx].answer_text || templates[idx].text || '';
                } else {
                    item.remove();
                }
            });
        }
    }

    let ruleBadgesContainer = questionCard.querySelector('.saved-rule-badges');
    if (!ruleBadgesContainer) {
        ruleBadgesContainer = document.createElement('div');
        ruleBadgesContainer.className = 'saved-rule-badges flex flex-wrap gap-2 mt-2';
        const wrapper = questionCard.querySelector('.use-saved-rules-wrapper');
        if (wrapper) {
            wrapper.insertAdjacentElement('afterend', ruleBadgesContainer);
        } else {
            questionCard.appendChild(ruleBadgesContainer);
        }
    }
    ruleBadgesContainer.innerHTML = '';

    const badge = document.createElement('span');
    badge.className = 'inline-flex items-center px-3 py-1 text-xs font-medium text-red-600 bg-red-50 border border-red-200 rounded-full space-x-2';
    const parts = [];
    const resultRules = Array.isArray(rule.result_rules) ? rule.result_rules : [];
    if (templates.length) {
        parts.push(`${templates.length} opsi`);
    }
    if (resultRules.length) {
        parts.push(`${resultRules.length} hasil`);
    }
    badge.innerHTML = `
        <span>Aturan: ${parts.join(' • ')}</span>
        <button type="button" class="remove-applied-rule text-red-500 hover:text-red-700">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    `;
    ruleBadgesContainer.appendChild(badge);

    questionCard.setAttribute(metaKey, 'true');

    const ruleInput = document.createElement('input');
    ruleInput.type = 'hidden';
    ruleInput.className = 'saved-rule-payload';
    ruleInput.value = JSON.stringify(rule);

    const existingPayload = questionCard.querySelector('.saved-rule-payload');
    if (existingPayload) {
        existingPayload.remove();
    }

    questionCard.appendChild(ruleInput);

    const useRuleBtn = questionCard.querySelector('.use-saved-rule-btn');
    if (useRuleBtn) {
        useRuleBtn.classList.remove('text-red-600', 'border', 'border-red-200');
        useRuleBtn.classList.add('bg-red-600', 'text-white');
    }

    const removeAppliedBtn = badge.querySelector('.remove-applied-rule');
    if (removeAppliedBtn) {
        removeAppliedBtn.addEventListener('click', () => {
            const payload = questionCard.querySelector('.saved-rule-payload');
            if (payload) {
                payload.remove();
            }
            badge.remove();
            questionCard.removeAttribute(metaKey);
            if (useRuleBtn) {
                useRuleBtn.classList.remove('bg-red-600', 'text-white');
                useRuleBtn.classList.add('text-red-600', 'border', 'border-red-200');
            }
            updateUseRuleButtonsVisibility();
        });
    }

    updateUseRuleButtonsVisibility();
}

function attachSavedRuleButtons() {
    const savedRules = loadSavedRules();
    const questionCards = document.querySelectorAll('.question-card');

    questionCards.forEach((card) => {
        const type = card.getAttribute('data-question-type');
        const wrapper = card.querySelector('.use-saved-rules-wrapper');
        if (!wrapper) {
            return;
        }

        if (type === 'multiple-choice' && savedRules.length) {
            wrapper.classList.remove('hidden');
        } else {
            wrapper.classList.add('hidden');
            return;
        }

        let button = wrapper.querySelector('.use-saved-rule-btn');
        if (button && !button.hasAttribute('data-listener')) {
            button.setAttribute('data-listener', 'true');
            button.addEventListener('click', function () {
                const bundles = loadSavedRules();
                if (!bundles.length) {
                    return;
                }

                const payload = card.querySelector('.saved-rule-payload');
                if (payload) {
                    payload.remove();
                    const badgeContainer = card.querySelector('.saved-rule-badges');
                    if (badgeContainer) {
                        badgeContainer.remove();
                    }
                    card.removeAttribute('savedRuleApplied');
                    updateUseRuleButtonsVisibility();
                    return;
                }

                const menu = document.createElement('div');
                menu.className = 'absolute z-50 mt-2 w-64 bg-white border border-gray-200 rounded-lg shadow-lg p-2 space-y-1';
                bundles.forEach((bundle, index) => {
                    const option = document.createElement('button');
                    option.type = 'button';
                    option.className = 'w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md';
                    const normalized = normalizeSavedRule(bundle);
                    const parts = [];
                    if (normalized.templates?.length) {
                        parts.push(`${normalized.templates.length} opsi`);
                    }
                    if (normalized.result_rules?.length) {
                        parts.push(`${normalized.result_rules.length} hasil`);
                    }
                    option.textContent = parts.join(' • ') || `Aturan ${index + 1}`;
                    option.addEventListener('click', () => {
                        applySavedRuleToQuestion(card, normalized);
                        menu.remove();
                    });
                    menu.appendChild(option);
                });

                const existingMenu = wrapper.querySelector('.saved-rules-menu');
                if (existingMenu) {
                    existingMenu.remove();
                }
                menu.classList.add('saved-rules-menu');
                wrapper.style.position = 'relative';
                wrapper.appendChild(menu);

                setTimeout(() => {
                    document.addEventListener('click', function handleClickOutside(e) {
                        if (!menu.contains(e.target)) {
                            menu.remove();
                            document.removeEventListener('click', handleClickOutside);
                        }
                    });
                }, 0);
            });
        }
    });
}

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    console.log('Form Builder DOMContentLoaded fired');
    // Initialize tabs
    initTabs();
    
    const rootElement = document.getElementById('form-builder-root');
    let initialData = null;
    let formMode = 'create';
    let formId = getMetaContent('form-id');
    let saveFormUrl = getMetaContent('save-form-url') || '/forms';
    let saveFormMethod = (getMetaContent('save-form-method') || 'POST').toUpperCase();
    const shareLinkBtn = document.getElementById('share-link-btn');
    let shareLinkUrl = rootElement?.getAttribute('data-share-url') || '';
    const rulePresetUrl = getMetaContent('rule-preset-url') || '';

    if (rootElement) {
        const initialAttr = rootElement.getAttribute('data-initial');
        if (initialAttr) {
            try {
                initialData = JSON.parse(initialAttr);
            } catch (error) {
                console.error('Failed to parse initial form data:', error);
            }
        }

        formMode = rootElement.getAttribute('data-mode') || 'create';

        const savedRulesAttr = rootElement.getAttribute('data-saved-rules');
        if (savedRulesAttr) {
            try {
                const parsedSavedRules = JSON.parse(savedRulesAttr) || [];
                saveRulesToState(parsedSavedRules);
            } catch (error) {
                console.error('Failed to parse saved rules data:', error);
            }
        }
    }

    const updateShareButtonVisibility = () => {
        if (!shareLinkBtn) {
            return;
        }

        if (shareLinkUrl) {
            shareLinkBtn.classList.remove('hidden');
            shareLinkBtn.disabled = false;
        } else {
            shareLinkBtn.classList.add('hidden');
            shareLinkBtn.disabled = true;
        }
    };

    updateShareButtonVisibility();

    if (shareLinkBtn) {
        shareLinkBtn.addEventListener('click', function() {
            if (shareLinkUrl) {
                openShareModal(shareLinkUrl);
            }
        });
    }

    const themeColorButtons = document.querySelectorAll('[data-theme-color]');
    themeColorButtons.forEach((button) => {
        button.addEventListener('click', function() {
            const color = this.getAttribute('data-theme-color');
            updateThemeColorSelection(color);
        });
    });
    
    if (formMode === 'edit') {
        const saveBtnLabel = document.querySelector('#save-form-btn span');
        if (saveBtnLabel) {
            saveBtnLabel.textContent = 'Update Form';
        }
    }
    
    if (initialData) {
        if (initialData.saved_rules) {
            saveRulesToState(initialData.saved_rules);
        }
        populateFormBuilder(initialData);
    } else {
        updateThemeColorSelection('red');
    }
    
    updateRuleSaveControlsVisibility();
    
    // Initialize answer templates
    const addAnswerTemplateBtn = document.getElementById('add-answer-template-btn');
    const answerTemplatesContainer = document.getElementById('answer-templates-container');
    
    if (addAnswerTemplateBtn && answerTemplatesContainer) {
        addAnswerTemplateBtn.addEventListener('click', function() {
            if (answerTemplatesContainer.querySelector('.answer-templates-placeholder')) {
                answerTemplatesContainer.innerHTML = '';
            }
            const templateCard = createAnswerTemplateCard();
            answerTemplatesContainer.appendChild(templateCard);
            updateRuleSaveControlsVisibility();
            
            const deleteBtn = templateCard.querySelector('.delete-answer-template-btn');
            if (deleteBtn) {
                deleteBtn.addEventListener('click', function() {
                    templateCard.remove();
                    if (answerTemplatesContainer.children.length === 0) {
                        answerTemplatesContainer.innerHTML = getAnswerTemplatesPlaceholder();
                    }
                    updateRuleSaveControlsVisibility();
                });
            }
        });
    }
    
    const saveRulesBtn = document.getElementById('save-form-rules-btn');
    if (saveRulesBtn) {
        saveRulesBtn.addEventListener('click', async function() {
            const bundle = gatherRulesFromSettings();
            if (!bundle) {
                alert('Tambahkan terlebih dahulu Template Jawaban & Skor untuk menyimpan aturan.');
                return;
            }

            if (!rulePresetUrl) {
                alert('Endpoint penyimpanan aturan tidak tersedia.');
                return;
            }

            saveRulesBtn.disabled = true;
            saveRulesBtn.textContent = 'Menyimpan...';

            try {
                const response = await fetch(rulePresetUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': getMetaContent('csrf-token'),
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        templates: bundle.templates ?? [],
                        result_rules: bundle.result_rules ?? [],
                    }),
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Gagal menyimpan aturan.');
                }

                saveRulesToState(data.presets || []);
                renderSavedRulesChips();
                updateUseRuleButtonsVisibility();
                resetFormRulesBuilder();
                alert(data.message || 'Aturan berhasil disimpan.');
            } catch (error) {
                console.error(error);
                alert(error.message || 'Terjadi kesalahan saat menyimpan aturan.');
            } finally {
                saveRulesBtn.disabled = false;
                saveRulesBtn.textContent = 'Simpan Aturan';
            }
        });
    }

    renderSavedRulesChips();
    updateUseRuleButtonsVisibility();
    
    // Initialize result rules
    const addResultRuleBtn = document.getElementById('add-result-rule-btn');
    const resultRulesContainer = document.getElementById('result-rules-container');
    
    if (addResultRuleBtn && resultRulesContainer) {
        addResultRuleBtn.addEventListener('click', function() {
            if (resultRulesContainer.querySelector('.result-rules-placeholder')) {
                resultRulesContainer.innerHTML = '';
            }
            const ruleCard = createResultRuleCard();
            resultRulesContainer.appendChild(ruleCard);
            
            const deleteBtn = ruleCard.querySelector('.delete-result-rule-btn');
            if (deleteBtn) {
                deleteBtn.addEventListener('click', function() {
                    ruleCard.remove();
                    if (resultRulesContainer.children.length === 0) {
                        resultRulesContainer.innerHTML = getResultRulesPlaceholder();
                    }
                    renderSavedRulesChips();
                    updateUseRuleButtonsVisibility();
                });
            }
        });
    }
    
    const addQuestionBtn = document.getElementById('add-question-btn');
    const questionTypesMenu = document.getElementById('question-types-menu');
    const questionsContainer = document.getElementById('questions-container');
    const questionTypeButtons = document.querySelectorAll('.question-type-btn');
    
    // Langsung tambahkan pertanyaan default saat klik tombol
    if (addQuestionBtn) {
        addQuestionBtn.addEventListener('click', function() {
            // Default type: short-answer (Jawaban singkat)
            const questionCard = createQuestionCard('short-answer');
            questionsContainer.appendChild(questionCard);
            updateQuestionNumbers();
            attachQuestionCardEvents(questionCard);
            
            // Focus ke input pertanyaan
            setTimeout(() => {
                const titleInput = questionCard.querySelector('.question-title');
                if (titleInput) {
                    titleInput.focus();
                }
            }, 100);
        });
    }
    
    // Tambahkan section divider
    const addSectionBtn = document.getElementById('add-section-btn');
    if (addSectionBtn) {
        addSectionBtn.addEventListener('click', function() {
            const sectionDivider = createSectionDivider();
            questionsContainer.appendChild(sectionDivider);
            
            // Attach delete event
            const deleteBtn = sectionDivider.querySelector('.delete-section-btn');
            if (deleteBtn) {
                deleteBtn.addEventListener('click', function() {
                    sectionDivider.remove();
                    updateSectionNumbers();
                });
            }
        });
    }
    
    // Tutup menu saat klik di luar (untuk modal yang mungkin masih digunakan di tempat lain)
    if (questionTypesMenu) {
        questionTypesMenu.addEventListener('click', function(e) {
            if (e.target === questionTypesMenu) {
                questionTypesMenu.classList.add('hidden');
                questionTypesMenu.classList.remove('flex', 'items-center', 'justify-center');
            }
        });
    }
    
    // Handle pemilihan jenis pertanyaan dari modal (jika masih digunakan)
    questionTypeButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const type = this.getAttribute('data-type');
            const questionCard = createQuestionCard(type);
            questionsContainer.appendChild(questionCard);
            questionTypesMenu.classList.add('hidden');
            questionTypesMenu.classList.remove('flex', 'items-center', 'justify-center');
            updateQuestionNumbers();
            attachQuestionCardEvents(questionCard);
            
        });
    });
    
    // Attach events untuk question cards yang sudah ada
    document.querySelectorAll('.question-card').forEach(card => {
        attachQuestionCardEvents(card);
    });
    
    // Save Form Button
    const saveFormBtn = document.getElementById('save-form-btn');
    if (saveFormBtn) {
        saveFormBtn.addEventListener('click', function() {
            const originalButtonHtml = saveFormBtn.getAttribute('data-original-html') || saveFormBtn.innerHTML;
            saveFormBtn.setAttribute('data-original-html', originalButtonHtml);

            saveFormBtn.disabled = true;
            saveFormBtn.innerHTML = `
                <svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                <span class="text-sm font-medium">Menyimpan...</span>
            `;
            
            const formData = collectFormData();
            const csrfToken = getMetaContent('csrf-token');

            const savedRules = loadSavedRules();
            formData.saved_rules = savedRules;
            
            fetch(saveFormUrl, {
                method: saveFormMethod,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(formData)
            })
            .then(async (response) => {
                const data = await response.json().catch(() => ({}));
                if (!response.ok || !data.success) {
                    const message = data && data.message ? data.message : 'Gagal menyimpan form.';
                    throw new Error(message);
                }
                return data;
            })
            .then(data => {
                if (data.form_id) {
                    formId = data.form_id;
                    setMetaContent('form-id', formId);
                    if (rootElement) {
                        rootElement.setAttribute('data-form-id', formId);
                    }
                }

                if (data.update_url) {
                    saveFormUrl = data.update_url;
                    setMetaContent('save-form-url', saveFormUrl);
                }

                if (data.save_method) {
                    saveFormMethod = data.save_method.toUpperCase();
                    setMetaContent('save-form-method', saveFormMethod);
                } else if (saveFormMethod !== 'PUT') {
                    saveFormMethod = 'PUT';
                    setMetaContent('save-form-method', 'PUT');
                }

                if (data.share_url) {
                    shareLinkUrl = data.share_url;
                    if (rootElement) {
                        rootElement.setAttribute('data-share-url', shareLinkUrl);
                    }
                    updateShareButtonVisibility();
                }

                formMode = 'edit';
                const saveBtnLabel = saveFormBtn.querySelector('span');
                if (saveBtnLabel) {
                    saveBtnLabel.textContent = 'Update Form';
                }

                showSuccessDialog(data.message || 'Form berhasil disimpan!');
            })
            .catch(error => {
                console.error('Error:', error);
                alert(error.message || 'Terjadi kesalahan saat menyimpan form. Silakan coba lagi.');
            })
            .finally(() => {
                saveFormBtn.disabled = false;
                saveFormBtn.innerHTML = saveFormBtn.getAttribute('data-original-html') || originalButtonHtml;
            });
        });
    }
});

// Fungsi untuk attach events ke question card
function attachQuestionCardEvents(card) {
    // Delete question
    const deleteBtn = card.querySelector('.delete-question-btn');
    if (deleteBtn && !deleteBtn.hasAttribute('data-listener')) {
        deleteBtn.setAttribute('data-listener', 'true');
        deleteBtn.addEventListener('click', function() {
            card.remove();
            updateQuestionNumbers();
        });
    }
    
    // Duplicate question
    const duplicateBtn = card.querySelector('.duplicate-question-btn');
    if (duplicateBtn && !duplicateBtn.hasAttribute('data-listener')) {
        duplicateBtn.setAttribute('data-listener', 'true');
        duplicateBtn.addEventListener('click', function() {
            const type = card.getAttribute('data-question-type');
            const newCard = createQuestionCard(type);
            const title = card.querySelector('.question-title').value;
            if (title) {
                newCard.querySelector('.question-title').value = title;
            }
            card.parentNode.insertBefore(newCard, card.nextSibling);
            updateQuestionNumbers();
            attachQuestionCardEvents(newCard);
        });
    }
    
    // More options panel
    const moreOptionsBtn = card.querySelector('.more-options-btn');
    const advancedSettings = card.querySelector('.question-advanced-settings');
    if (moreOptionsBtn && advancedSettings) {
        moreOptionsBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            advancedSettings.classList.toggle('hidden');
        });
    }

    // Question type dropdown
    const typeDropdownBtn = card.querySelector('.question-type-dropdown-btn');
    const typeDropdown = card.querySelector('.question-type-dropdown');
    if (typeDropdownBtn && typeDropdown) {
        typeDropdownBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            typeDropdown.classList.toggle('hidden');
        });
        
        // Close dropdown saat klik di luar
        document.addEventListener('click', function(e) {
            if (!typeDropdownBtn.contains(e.target) && !typeDropdown.contains(e.target)) {
                typeDropdown.classList.add('hidden');
            }
        });
        
        // Change question type
        const changeTypeBtns = card.querySelectorAll('.change-question-type-btn');
        changeTypeBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const newType = this.getAttribute('data-type');
                const template = questionTemplates[newType];
                const inputArea = card.querySelector('.question-input-area');
                inputArea.innerHTML = template.input;
                
                // Update icon and label
                const iconEl = card.querySelector('.question-type-icon');
                const labelEl = card.querySelector('.question-type-label');
                if (iconEl) iconEl.innerHTML = template.icon;
                if (labelEl) labelEl.textContent = template.label;
                
                card.setAttribute('data-question-type', newType);
                typeDropdown.classList.add('hidden');
                
                attachOptionEvents(card);
                
                if (newType !== 'multiple-choice') {
                    const wrapper = card.querySelector('.use-saved-rules-wrapper');
                    if (wrapper) {
                        wrapper.classList.add('hidden');
                    }
                    const payload = card.querySelector('.saved-rule-payload');
                    if (payload) {
                        payload.remove();
                    }
                    const badgeContainer = card.querySelector('.saved-rule-badges');
                    if (badgeContainer) {
                        badgeContainer.remove();
                    }
                    card.removeAttribute('savedRuleApplied');
                }

                updateUseRuleButtonsVisibility();
                attachSavedRuleButtons();
            });
        });
    }
    
    // Attach option events
    attachOptionEvents(card);
    
    // Active state highlight saat focus pada input pertanyaan
    const questionTitle = card.querySelector('.question-title');
    if (questionTitle) {
        questionTitle.addEventListener('focus', function() {
            // Remove active dari semua cards
            document.querySelectorAll('.question-card').forEach(c => {
                c.classList.remove('ring-2', 'ring-red-600', 'border-red-600');
                c.classList.add('border-gray-200');
            });
            // Add active ke card ini
            card.classList.add('ring-2', 'ring-red-600', 'border-red-600');
            card.classList.remove('border-gray-200');
        });
    }
    
    // Click pada card juga trigger highlight
    card.addEventListener('click', function(e) {
        // Jangan trigger jika klik pada button atau input yang sudah ada handler
        if (e.target.closest('button') || e.target.closest('input[type="file"]') || e.target.closest('.question-type-dropdown')) {
            return;
        }
        // Focus ke input pertanyaan
        if (questionTitle) {
            questionTitle.focus();
        }
    });
    
    // Required checkbox toggle - ensure it's clickable
    const requiredCheckbox = card.querySelector('.required-checkbox');
    const toggleSwitch = card.querySelector('.toggle-switch');
    const toggleHandle = card.querySelector('.toggle-switch-handle');
    
    if (requiredCheckbox && toggleSwitch && toggleHandle) {
        function updateToggleState() {
            toggleSwitch.classList.toggle('bg-red-600', requiredCheckbox.checked);
            toggleSwitch.classList.toggle('bg-gray-300', !requiredCheckbox.checked);
            toggleHandle.classList.toggle('translate-x-5', requiredCheckbox.checked);
        }

        updateToggleState();

        requiredCheckbox.addEventListener('change', function () {
            updateToggleState();
        });

        [toggleSwitch, toggleHandle].forEach((element) => {
            element.addEventListener('click', function (event) {
                event.preventDefault();
                requiredCheckbox.checked = !requiredCheckbox.checked;
                updateToggleState();
            });
        });
    }
    
    // Add image button
    const addImageBtn = card.querySelector('.add-image-btn');
    const imageFileInput = card.querySelector('.image-file-input');
    const imageArea = card.querySelector('.question-image-area');
    const questionImage = card.querySelector('.question-image');
    const removeImageBtn = card.querySelector('.remove-image-btn');
    
    if (addImageBtn && imageFileInput) {
        addImageBtn.addEventListener('click', function() {
            imageFileInput.click();
        });
    }
    
    if (imageFileInput) {
        imageFileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (questionImage) {
                        questionImage.src = e.target.result;
                    }
                    if (imageArea) {
                        imageArea.classList.remove('hidden');
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }
    
    if (questionImage) {
        questionImage.addEventListener('error', function() {
            questionImage.removeAttribute('src');
            if (imageArea) {
                imageArea.classList.add('hidden');
            }
        });
    }
    
    if (removeImageBtn) {
        removeImageBtn.addEventListener('click', function() {
            if (imageArea) {
                imageArea.classList.add('hidden');
            }
            if (questionImage) {
                questionImage.src = '';
            }
            if (imageFileInput) {
                imageFileInput.value = '';
            }
        });
    }

    const useRuleWrapper = card.querySelector('.use-saved-rules-wrapper');
    const questionTypeContainer = card.querySelector('.question-type-dropdown')?.parentElement;
    if (useRuleWrapper && questionTypeContainer) {
        questionTypeContainer.insertAdjacentElement('afterend', useRuleWrapper);
    }

    updateUseRuleButtonsVisibility();
    attachSavedRuleButtons();
}

// Fungsi untuk attach events ke opsi
function attachOptionEvents(card) {
    // Remove option buttons
    const removeOptionBtns = card.querySelectorAll('.remove-option-btn');
    removeOptionBtns.forEach(btn => {
        if (!btn.hasAttribute('data-listener')) {
            btn.setAttribute('data-listener', 'true');
            btn.addEventListener('click', function() {
                const optionItem = this.closest('.option-item');
                if (optionItem) {
                    optionItem.remove();
                }
            });
        }
    });
    
    // Show remove button on hover untuk option items
    const optionItems = card.querySelectorAll('.option-item');
    optionItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            const removeBtn = this.querySelector('.remove-option-btn');
            if (removeBtn) {
                removeBtn.classList.remove('opacity-0');
                removeBtn.classList.add('opacity-100');
            }
        });
        item.addEventListener('mouseleave', function() {
            const removeBtn = this.querySelector('.remove-option-btn');
            if (removeBtn) {
                removeBtn.classList.add('opacity-0');
                removeBtn.classList.remove('opacity-100');
            }
        });
    });
    
    // Add option button
    const addOptionBtns = card.querySelectorAll('.add-option-btn');
    addOptionBtns.forEach(btn => {
        if (!btn.hasAttribute('data-listener')) {
            btn.setAttribute('data-listener', 'true');
            btn.addEventListener('click', function() {
                const container = card.querySelector('.question-input-area > div');
                const addSection = btn.closest('.mt-2.space-y-1');
                const optionCount = container ? container.querySelectorAll('.option-item').length + 1 : 1;
                const type = card.getAttribute('data-question-type');
                let newOption;
                
                if (type === 'multiple-choice') {
                    newOption = `
                        <div class="option-item flex items-center space-x-2">
                            <input type="radio" disabled class="text-red-600 focus:ring-red-500 mt-1">
                            <input 
                                type="text" 
                                placeholder="Opsi ${optionCount}" 
                                class="flex-1 px-0 py-1 text-sm text-gray-500 border-none border-b border-transparent focus:border-red-600 focus:outline-none transition-colors option-input"
                            >
                            <button class="remove-option-btn p-1 text-gray-400 hover:text-red-600 transition-colors opacity-0 group-hover:opacity-100" title="Hapus opsi">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    `;
                } else if (type === 'checkbox') {
                    newOption = `
                        <div class="option-item flex items-center space-x-2">
                            <input type="checkbox" disabled class="rounded border-gray-300 text-red-600 focus:ring-red-500 mt-1">
                            <input 
                                type="text" 
                                placeholder="Opsi ${optionCount}" 
                                class="flex-1 px-0 py-1 text-sm text-gray-500 border-none border-b border-transparent focus:border-red-600 focus:outline-none transition-colors option-input"
                            >
                            <button class="remove-option-btn p-1 text-gray-400 hover:text-red-600 transition-colors opacity-0 group-hover:opacity-100" title="Hapus opsi">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    `;
                } else if (type === 'dropdown') {
                    newOption = `
                        <div class="option-item flex items-center space-x-2">
                            <span class="text-sm text-gray-500 w-4">${optionCount}.</span>
                            <input 
                                type="text" 
                                placeholder="Opsi ${optionCount}" 
                                class="flex-1 px-0 py-1 text-sm text-gray-500 border-none border-b border-transparent focus:border-red-600 focus:outline-none transition-colors option-input"
                            >
                            <button class="remove-option-btn p-1 text-gray-400 hover:text-red-600 transition-colors opacity-0 group-hover:opacity-100" title="Hapus opsi">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    `;
                }
                
                if (newOption && container) {
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = newOption;
                    const newOptionEl = tempDiv.firstElementChild;
                    if (addSection && newOptionEl) {
                        container.insertBefore(newOptionEl, addSection);
                        attachOptionEvents(card);
                        updateQuestionNumbers();
                    }
                }
            });
        }
    });
    
    // Add "Lainnya" button
    const addOtherBtns = card.querySelectorAll('.add-other-btn');
    addOtherBtns.forEach(btn => {
        if (!btn.hasAttribute('data-listener')) {
            btn.setAttribute('data-listener', 'true');
            btn.addEventListener('click', function() {
                const inputArea = card.querySelector('.question-input-area > div');
                const optionCount = inputArea.querySelectorAll('.option-item').length + 1;
                const type = card.getAttribute('data-question-type');
                let newOption;
                
                if (type === 'multiple-choice') {
                    newOption = `
                        <div class="option-item flex items-center space-x-2">
                            <input type="radio" disabled class="text-red-600 focus:ring-red-500 mt-1">
                            <input 
                                type="text" 
                                value="Lainnya" 
                                class="flex-1 px-0 py-1 text-sm text-gray-500 border-none border-b border-transparent focus:border-red-600 focus:outline-none transition-colors option-input"
                            >
                            <button class="remove-option-btn p-1 text-gray-400 hover:text-red-600 transition-colors opacity-0 group-hover:opacity-100" title="Hapus opsi">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    `;
                } else if (type === 'checkbox') {
                    newOption = `
                        <div class="option-item flex items-center space-x-2">
                            <input type="checkbox" disabled class="rounded border-gray-300 text-red-600 focus:ring-red-500 mt-1">
                            <input 
                                type="text" 
                                value="Lainnya" 
                                class="flex-1 px-0 py-1 text-sm text-gray-500 border-none border-b border-transparent focus:border-red-600 focus:outline-none transition-colors option-input"
                            >
                            <button class="remove-option-btn p-1 text-gray-400 hover:text-red-600 transition-colors opacity-0 group-hover:opacity-100" title="Hapus opsi">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    `;
                }
                
                if (newOption) {
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = newOption;
                    const addBtnContainer = btn.parentElement;
                    addBtnContainer.insertBefore(tempDiv.firstElementChild, btn);
                    attachOptionEvents(card);
                }
            });
        }
    });
}

// Update nomor pertanyaan
function updateQuestionNumbers() {
    const questionCards = document.querySelectorAll('.question-card');
    questionCards.forEach((card, index) => {
        // No need to update numbers since we removed the number display
    });
}

// Update nomor section
function updateSectionNumbers() {
    const sections = document.querySelectorAll('.section-divider');
    sections.forEach((section, index) => {
        const titleEl = section.querySelector('.section-title');
        if (titleEl) {
            titleEl.textContent = `Bagian ${index + 1}`;
        }
    });
}

// Fungsi untuk mengumpulkan data form
function collectFormData() {
    const formData = {
        title: document.getElementById('form-title')?.value || 'Formulir tanpa judul',
        description: document.getElementById('form-description')?.value || '',
        theme_color: (() => {
            const selectedThemeButton = document.querySelector('[data-theme-color][data-selected="true"]');
            return selectedThemeButton ? selectedThemeButton.getAttribute('data-theme-color') : 'red';
        })(),
        collect_email: document.querySelectorAll('#tab-settings input[type="checkbox"]')[0]?.checked || false,
        limit_one_response: document.querySelectorAll('#tab-settings input[type="checkbox"]')[1]?.checked || false,
        show_progress_bar: document.querySelectorAll('#tab-settings input[type="checkbox"]')[2]?.checked || false,
        shuffle_questions: document.querySelectorAll('#tab-settings input[type="checkbox"]')[3]?.checked || false,
        sections: [],
        questions: [],
        answer_templates: [],
        result_rules: []
    };

    // Collect sections
    const sections = document.querySelectorAll('.section-divider');
    sections.forEach((section, index) => {
        const titleEl = section.querySelector('.section-title');
        const descEl = section.querySelector('.section-description');
        formData.sections.push({
            title: titleEl?.textContent || null,
            description: descEl?.textContent || null
        });
    });

    // Collect answer templates
    const answerTemplates = document.querySelectorAll('.answer-template-card');
    answerTemplates.forEach((template) => {
        const answerText = template.querySelector('.answer-template-text')?.value;
        const score = template.querySelector('.answer-template-score')?.value || 0;
        if (answerText) {
            formData.answer_templates.push({
                answer_text: answerText,
                score: parseInt(score) || 0
            });
        }
    });

    // Collect result rules
    const resultRules = document.querySelectorAll('.result-rule-card');
    resultRules.forEach((ruleCard) => {
        const conditionType = ruleCard.querySelector('.rule-condition-type')?.value || 'range';
        const ruleData = {
            condition_type: conditionType,
            texts: []
        };

        if (conditionType === 'range') {
            ruleData.min_score = parseInt(ruleCard.querySelector('.rule-min-score')?.value) || null;
            ruleData.max_score = parseInt(ruleCard.querySelector('.rule-max-score')?.value) || null;
        } else {
            ruleData.single_score = parseInt(ruleCard.querySelector('.rule-single-score')?.value) || null;
        }

        // Collect result texts
        const textAreas = ruleCard.querySelectorAll('.rule-result-text');
        textAreas.forEach((textarea) => {
            if (textarea.value.trim()) {
                ruleData.texts.push(textarea.value.trim());
            }
        });

        if (ruleData.texts.length > 0) {
            formData.result_rules.push(ruleData);
        }
    });

    // Collect questions from questions container
    const questionsContainer = document.getElementById('questions-container');
    if (!questionsContainer) {
        return formData;
    }
    
    const questions = questionsContainer.querySelectorAll('.question-card, .section-divider');
    let currentSectionIndex = -1;
    
    questions.forEach((questionCard) => {
        // Check if this is a section divider
        if (questionCard.classList.contains('section-divider')) {
            currentSectionIndex++;
            return;
        }

        const extraSettingsPayload = {
            validation: questionCard.querySelector('.question-validation-input')?.value?.trim() || null,
            validation_message: questionCard.querySelector('.question-validation-message')?.value?.trim() || null,
            extra_notes: questionCard.querySelector('.question-extra-notes')?.value?.trim() || null,
            min_length: questionCard.querySelector('.question-min-length')?.value || null,
            max_length: questionCard.querySelector('.question-max-length')?.value || null,
        };

        if (extraSettingsPayload.min_length !== null && extraSettingsPayload.min_length !== '') {
            extraSettingsPayload.min_length = parseInt(extraSettingsPayload.min_length, 10);
        } else {
            extraSettingsPayload.min_length = null;
        }

        if (extraSettingsPayload.max_length !== null && extraSettingsPayload.max_length !== '') {
            extraSettingsPayload.max_length = parseInt(extraSettingsPayload.max_length, 10);
        } else {
            extraSettingsPayload.max_length = null;
        }

        const questionData = {
            type: questionCard.getAttribute('data-question-type') || 'short-answer',
            title: questionCard.querySelector('.question-title')?.value || '',
            description: questionCard.querySelector('.question-description')?.value || '',
            is_required: questionCard.querySelector('.required-checkbox')?.checked || false,
            options: [],
            extra_settings: extraSettingsPayload,
        };

        const extraValues = Object.values(questionData.extra_settings);
        if (extraValues.every(value => value === null || value === '')) {
            delete questionData.extra_settings;
        }

        if (currentSectionIndex >= 0) {
            questionData.section_id = currentSectionIndex;
        }

        const imageArea = questionCard.querySelector('.question-image-area');
        const questionImageEl = questionCard.querySelector('.question-image');
        if (imageArea && questionImageEl && !imageArea.classList.contains('hidden')) {
            const src = questionImageEl.getAttribute('src');
            if (src && src.trim() !== '') {
                questionData.image = src;
            }
        }

        if (['multiple-choice', 'checkbox', 'dropdown'].includes(questionData.type)) {
            const optionItems = questionCard.querySelectorAll('.option-item');
            optionItems.forEach((optionItem) => {
                const optionInput = optionItem.querySelector('.option-input');
                const optionText = optionInput ? optionInput.value : '';
                if (optionText && optionText.trim() !== '') {
                    questionData.options.push({
                        text: optionText.trim(),
                        answer_template_id: null
                    });
                }
            });
        }

        if (!questionData.image) {
            delete questionData.image;
        }

        const savedRulePayload = questionCard.querySelector('.saved-rule-payload');
        if (savedRulePayload && savedRulePayload.value) {
            try {
                questionData.saved_rule = JSON.parse(savedRulePayload.value);
            } catch (error) {
                console.error('Failed to parse saved rule payload:', error);
            }
        }

        if (questionData.title) {
            formData.questions.push(questionData);
        }
    });

    return formData;
}

// Fungsi untuk menampilkan dialog sukses
function showSuccessDialog(message) {
    // Create modal overlay
    const modal = document.createElement('div');
    modal.id = 'success-modal';
    modal.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50';
    modal.innerHTML = `
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
            <div class="flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mx-auto mb-4">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 text-center mb-2">Berhasil!</h3>
            <p class="text-sm text-gray-600 text-center mb-6">${message}</p>
            <div class="flex justify-center">
                <button id="close-success-modal" class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">
                    Tutup
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Close modal handlers
    const closeBtn = modal.querySelector('#close-success-modal');
    closeBtn.addEventListener('click', () => {
        modal.remove();
    });
    
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.remove();
        }
    });
    
    // Auto close after 3 seconds
    setTimeout(() => {
        if (document.getElementById('success-modal')) {
            modal.remove();
        }
    }, 3000);
}

function openShareModal(link) {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 px-4';
    modal.innerHTML = `
        <div class="bg-white rounded-lg shadow-xl max-w-lg w-full p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Bagikan Formulir</h3>
            <p class="text-sm text-gray-600 mb-4">Salin tautan berikut dan bagikan kepada responden Anda.</p>
            <div class="flex items-center space-x-2">
                <input type="text" readonly class="share-link-input flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none" value="">
                <button type="button" data-share-copy class="px-3 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg">Salin</button>
            </div>
            <p class="text-xs text-gray-500 mt-2">Tautan ini akan membawa responden ke halaman pengisian form.</p>
            <div class="flex justify-end mt-6">
                <button type="button" data-share-close class="px-4 py-2 text-sm font-medium text-gray-700 hover:text-red-600">Tutup</button>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    const input = modal.querySelector('.share-link-input');
    if (input) {
        input.value = link;
        input.focus();
        input.select();
    }

    const closeModal = () => {
        modal.remove();
    };

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });

    const closeBtn = modal.querySelector('[data-share-close]');
    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }

    const copyBtn = modal.querySelector('[data-share-copy]');
    if (copyBtn) {
        copyBtn.addEventListener('click', async () => {
            try {
                if (navigator.clipboard?.writeText) {
                    await navigator.clipboard.writeText(link);
                } else if (input) {
                    input.select();
                    document.execCommand('copy');
                }
                copyBtn.textContent = 'Disalin!';
                copyBtn.classList.remove('bg-red-600');
                copyBtn.classList.add('bg-green-600');
                setTimeout(() => {
                    copyBtn.textContent = 'Salin';
                    copyBtn.classList.remove('bg-green-600');
                    copyBtn.classList.add('bg-red-600');
                }, 1500);
            } catch (error) {
                alert('Gagal menyalin tautan. Silakan salin secara manual.');
            }
        });
    }
}

function updateThemeColorSelection(color) {
    const buttons = document.querySelectorAll('[data-theme-color]');
    if (!buttons.length) {
        return;
    }

    const borderClasses = {
        red: 'border-red-600',
        blue: 'border-blue-600',
        green: 'border-green-600',
        purple: 'border-purple-600',
    };

    const ringClasses = {
        red: 'ring-red-600',
        blue: 'ring-blue-600',
        green: 'ring-green-600',
        purple: 'ring-purple-600',
    };

    buttons.forEach((button) => {
        const buttonColor = button.getAttribute('data-theme-color');
        button.classList.remove(
            'ring-2',
            'ring-offset-2',
            'ring-red-600',
            'ring-blue-600',
            'ring-green-600',
            'ring-purple-600',
            'border-red-600',
            'border-blue-600',
            'border-green-600',
            'border-purple-600'
        );
        button.classList.add('border-transparent');
        button.removeAttribute('data-selected');

        if (buttonColor === color) {
            const borderClass = borderClasses[buttonColor] || 'border-red-600';
            const ringClass = ringClasses[buttonColor] || 'ring-red-600';

            button.classList.remove('border-transparent');
            button.classList.add(borderClass, 'ring-2', 'ring-offset-2', ringClass);
            button.setAttribute('data-selected', 'true');
        }
    });
}

function populateFormBuilder(data) {
    if (!data) {
        return;
    }

    const titleInput = document.getElementById('form-title');
    if (titleInput) {
        titleInput.value = data.title || '';
    }

    const descriptionInput = document.getElementById('form-description');
    if (descriptionInput) {
        descriptionInput.value = data.description || '';
    }

    updateThemeColorSelection(data.theme_color || 'red');

    const settingsCheckboxes = document.querySelectorAll('#tab-settings input[type="checkbox"]');
    const settingsValues = [
        Boolean(data.collect_email),
        Boolean(data.limit_one_response),
        Boolean(data.show_progress_bar),
        Boolean(data.shuffle_questions),
    ];

    settingsCheckboxes.forEach((checkbox, index) => {
        const value = settingsValues[index] ?? false;
        checkbox.checked = value;
        checkbox.dispatchEvent(new Event('change'));
    });

    const answerTemplatesContainer = document.getElementById('answer-templates-container');
    if (answerTemplatesContainer) {
        answerTemplatesContainer.innerHTML = '';
        const templates = Array.isArray(data.answer_templates) ? data.answer_templates : [];

        if (templates.length === 0) {
            answerTemplatesContainer.innerHTML = getAnswerTemplatesPlaceholder();
            updateRuleSaveControlsVisibility();
        } else {
            templates.forEach((template) => {
                const templateCard = createAnswerTemplateCard();
                answerTemplatesContainer.appendChild(templateCard);

                const textInput = templateCard.querySelector('.answer-template-text');
                const scoreInput = templateCard.querySelector('.answer-template-score');
                if (textInput) {
                    textInput.value = template.answer_text || '';
                }
                if (scoreInput) {
                    scoreInput.value = template.score ?? 0;
                }

                const deleteBtn = templateCard.querySelector('.delete-answer-template-btn');
                if (deleteBtn) {
                    deleteBtn.addEventListener('click', function () {
                        templateCard.remove();
                        if (answerTemplatesContainer.children.length === 0) {
                            answerTemplatesContainer.innerHTML = getAnswerTemplatesPlaceholder();
                        }
                        updateRuleSaveControlsVisibility();
                    });
                }
            });
            updateRuleSaveControlsVisibility();
        }
    }

    const resultRulesContainer = document.getElementById('result-rules-container');
    if (resultRulesContainer) {
        resultRulesContainer.innerHTML = '';
        const rules = Array.isArray(data.result_rules) ? data.result_rules : [];

        if (rules.length === 0) {
            resultRulesContainer.innerHTML = getResultRulesPlaceholder();
        } else {
            rules.forEach((rule) => {
                const ruleCard = createResultRuleCard();
                resultRulesContainer.appendChild(ruleCard);

                const conditionSelect = ruleCard.querySelector('.rule-condition-type');
                if (conditionSelect) {
                    conditionSelect.value = rule.condition_type || 'range';
                    conditionSelect.dispatchEvent(new Event('change'));
                }

                if ((rule.condition_type || 'range') === 'range') {
                    const minInput = ruleCard.querySelector('.rule-min-score');
                    const maxInput = ruleCard.querySelector('.rule-max-score');
                    if (minInput) {
                        minInput.value = rule.min_score ?? '';
                    }
                    if (maxInput) {
                        maxInput.value = rule.max_score ?? '';
                    }
                } else {
                    const singleScoreInput = ruleCard.querySelector('.rule-single-score');
                    if (singleScoreInput) {
                        singleScoreInput.value = rule.single_score ?? '';
                    }
                }

                const resultTextsContainer = ruleCard.querySelector('.rule-result-texts');
                const addResultTextBtn = ruleCard.querySelector('.add-result-text-btn');
                const texts = Array.isArray(rule.texts) && rule.texts.length ? rule.texts : [''];

                texts.forEach((text, idx) => {
                    if (idx === 0) {
                        const textarea = resultTextsContainer.querySelector('.rule-result-text');
                        if (textarea) {
                            textarea.value = text || '';
                        }
                    } else if (addResultTextBtn) {
                        addResultTextBtn.click();
                        const textareas = resultTextsContainer.querySelectorAll('.rule-result-text');
                        const textarea = textareas[textareas.length - 1];
                        if (textarea) {
                            textarea.value = text || '';
                        }
                    }
                });

                const deleteRuleBtn = ruleCard.querySelector('.delete-result-rule-btn');
                if (deleteRuleBtn) {
                    deleteRuleBtn.addEventListener('click', function () {
                        ruleCard.remove();
                        if (resultRulesContainer.children.length === 0) {
                            resultRulesContainer.innerHTML = getResultRulesPlaceholder();
                        }
                    });
                }
            });
        }
    }

    const questionsContainer = document.getElementById('questions-container');
    if (!questionsContainer) {
        return;
    }

    questionsContainer.innerHTML = '';

    const sections = Array.isArray(data.sections) ? data.sections : [];
    const questions = Array.isArray(data.questions) ? data.questions : [];
    let currentSectionIndex = -1;

    questions.forEach((questionData) => {
        if (
            questionData &&
            questionData.section_id !== undefined &&
            questionData.section_id !== null &&
            questionData.section_id !== currentSectionIndex
        ) {
            currentSectionIndex = questionData.section_id;
            const sectionDivider = createSectionDivider();
            questionsContainer.appendChild(sectionDivider);

            const sectionInfo = sections[currentSectionIndex] || null;
            const sectionTitleEl = sectionDivider.querySelector('.section-title');
            if (sectionTitleEl) {
                sectionTitleEl.textContent = sectionInfo && sectionInfo.title
                    ? sectionInfo.title
                    : `Bagian ${currentSectionIndex + 1}`;
            }

            const deleteBtn = sectionDivider.querySelector('.delete-section-btn');
            if (deleteBtn) {
                deleteBtn.addEventListener('click', function () {
                    sectionDivider.remove();
                    updateSectionNumbers();
                });
            }
        }

        const questionType = questionData.type || 'short-answer';
        const questionCard = createQuestionCard(questionType);
        questionsContainer.appendChild(questionCard);

        const titleInput = questionCard.querySelector('.question-title');
        if (titleInput) {
            titleInput.value = questionData.title || '';
        }

        const requiredCheckbox = questionCard.querySelector('.required-checkbox');
        if (requiredCheckbox) {
            requiredCheckbox.checked = Boolean(questionData.is_required);
        }

        const questionImage = questionCard.querySelector('.question-image');
        const imageArea = questionCard.querySelector('.question-image-area');
        if (questionData.image && questionImage && imageArea) {
            questionImage.src = questionData.image;
            imageArea.classList.remove('hidden');
        } else if (questionImage && imageArea) {
            questionImage.removeAttribute('src');
            imageArea.classList.add('hidden');
        }

        const extraSettings = questionData.extra_settings || {};
        const validationInput = questionCard.querySelector('.question-validation-input');
        const validationMessageInput = questionCard.querySelector('.question-validation-message');
        const extraNotesInput = questionCard.querySelector('.question-extra-notes');
        const minLengthInput = questionCard.querySelector('.question-min-length');
        const maxLengthInput = questionCard.querySelector('.question-max-length');
        const advancedSettingsPanel = questionCard.querySelector('.question-advanced-settings');

        const hasExtraSettings = [
            extraSettings.validation,
            extraSettings.validation_message,
            extraSettings.extra_notes,
            extraSettings.min_length,
            extraSettings.max_length,
        ].some(value => value !== null && value !== undefined && value !== '');

        if (validationInput && extraSettings.validation) {
            validationInput.value = extraSettings.validation;
        }
        if (validationMessageInput && extraSettings.validation_message) {
            validationMessageInput.value = extraSettings.validation_message;
        }
        if (extraNotesInput && extraSettings.extra_notes) {
            extraNotesInput.value = extraSettings.extra_notes;
        }
        if (minLengthInput && extraSettings.min_length !== null && extraSettings.min_length !== undefined) {
            minLengthInput.value = extraSettings.min_length;
        }
        if (maxLengthInput && extraSettings.max_length !== null && extraSettings.max_length !== undefined) {
            maxLengthInput.value = extraSettings.max_length;
        }
        if (hasExtraSettings && advancedSettingsPanel) {
            advancedSettingsPanel.classList.remove('hidden');
        }

        if (['multiple-choice', 'checkbox', 'dropdown'].includes(questionType)) {
            const inputArea = questionCard.querySelector('.question-input-area');
            const options = Array.isArray(questionData.options) ? questionData.options : [];

            if (inputArea) {
                let optionItems = Array.from(inputArea.querySelectorAll('.option-item'));
                const addOptionBtn = questionCard.querySelector('.add-option-btn');

                while (options.length > optionItems.length && addOptionBtn) {
                    addOptionBtn.click();
                    optionItems = Array.from(inputArea.querySelectorAll('.option-item'));
                }

                optionItems.forEach((item, idx) => {
                    const input = item.querySelector('.option-input');
                    if (!input) {
                        return;
                    }

                    if (idx < options.length) {
                        input.value = options[idx].text || '';
                    } else if (options.length === 0) {
                        if (idx > 1) {
                            item.remove();
                        } else {
                            input.value = '';
                        }
                    } else {
                        item.remove();
                    }
                });
            }
        }

        if (questionData.saved_rule) {
            applySavedRuleToQuestion(questionCard, questionData.saved_rule);
        }

        attachQuestionCardEvents(questionCard);

        if (requiredCheckbox) {
            requiredCheckbox.dispatchEvent(new Event('change'));
        }
    });

    if (questions.length === 0) {
        const defaultQuestion = createQuestionCard('short-answer');
        questionsContainer.appendChild(defaultQuestion);
        attachQuestionCardEvents(defaultQuestion);
    }

    updateSectionNumbers();
    updateQuestionNumbers();
}
