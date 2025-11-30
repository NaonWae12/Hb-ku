<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ strip_tags($form->title) }} - hb-ku Form</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .form-page {
            display: none;
        }

        .form-page.active {
            display: block;
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen">
    <div class="max-w-3xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
        <div class="mb-6 text-center">
            <a href="{{ route('login') }}" class="inline-flex items-center space-x-2 text-red-600 font-semibold">
                <span class="w-10 h-10 bg-red-600 text-white rounded-full flex items-center justify-center font-bold">Hb</span>
                <span>Hb-ku</span>
            </a>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 mb-6">
            <div class="p-8">
                <h1 class="text-2xl font-semibold text-gray-900 mb-2">{{ strip_tags($form->title) }}</h1>
                @if($form->description)
                    <p class="text-sm text-gray-600">{{ strip_tags($form->description) }}</p>
                @endif
                <div class="mt-4 text-xs text-gray-500">
                    Bagikan tautan ini: <span class="font-medium text-gray-700">{{ $shareUrl }}</span>
                </div>
            </div>
        </div>

        @if (session('status'))
            <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl p-4 mb-6">
                <p class="font-medium">{{ session('status') }}</p>
        </div>
        @endif

        @if (session('result_data'))
        @php
        $resultData = session('result_data');
        $textAlignment = $resultData['text_alignment'] ?? 'center';
        $imageAlignment = $resultData['image_alignment'] ?? 'center';
        $texts = $resultData['texts'] ?? [];

        $textAlignClass = match($textAlignment) {
        'left' => 'text-left',
        'right' => 'text-right',
        default => 'text-center',
        };

        $imageAlignClass = match($imageAlignment) {
        'left' => 'text-left',
        'right' => 'text-right',
        default => 'text-center',
        };
        @endphp

        @if (!empty($texts))
        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Hasil</h2>

            <div class="space-y-6">
                @foreach($texts as $textItem)
                <div class="border-b border-gray-100 pb-6 last:border-b-0 last:pb-0">
                    @if (!empty($textItem['title']))
                    <h3 class="text-lg font-medium text-gray-900 mb-3">{{ $textItem['title'] }}</h3>
                    @endif

                    @if (!empty($textItem['image_url']))
                    <div class="mb-3 {{ $imageAlignClass }}">
                        <img src="{{ $textItem['image_url'] }}" alt="{{ $textItem['title'] ?? 'Result image' }}"
                            class="inline-block max-w-full h-auto rounded-lg border border-gray-200"
                            style="max-height: 400px;">
                    </div>
                    @endif

                    @if (!empty($textItem['result_text']))
                    <div class="{{ $textAlignClass }}">
                        <p class="text-sm text-gray-700 whitespace-pre-line">{{ $textItem['result_text'] }}</p>
                    </div>
                @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif
        @endif

        @if (!session('result_data'))
        <form method="POST" action="{{ route('forms.public.submit', $form) }}" id="public-form" class="space-y-6">
            @csrf

            @if($form->collect_email)
                <div class="bg-white rounded-2xl shadow border border-gray-100 p-6">
                    <label for="email" class="block text-sm font-medium text-gray-900 mb-1">
                        Alamat Email @if($form->collect_email)<span class="text-red-600">*</span>@endif
                    </label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}"
                        class="w-full px-3 py-2 text-sm border rounded-lg focus:ring-red-500 focus:border-red-500 @error('email') border-red-500 @enderror"
                        placeholder="nama@contoh.com" {{ $form->collect_email ? 'required' : '' }}>
                    @error('email')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            @foreach($pages as $pageIndex => $page)
                <div class="form-page {{ $pageIndex === 0 ? 'active' : '' }}" data-page="{{ $pageIndex }}">
                    @if($page['section'])
                        @php
                            $section = $page['section'];
                            $title = trim($section->title ?? '');
                            $description = trim($section->description ?? '');
                            $hasImage = !empty($section->image);
                            // Check if section has been edited (not default "Bagian X" pattern)
                            $hasValidTitle = $title !== '' && !preg_match('/^Bagian\s+\d+$/i', $title);
                            $isEdited = $hasValidTitle || $description !== '' || $hasImage;
                        @endphp
                        @if($isEdited)
                            @php
                                $imageAlignment = $section->image_alignment ?? 'center';
                                $alignmentClass = match ($imageAlignment) {
                                    'left' => 'text-left',
                                    'right' => 'text-right',
                                    default => 'text-center',
                                };
                                $imageWrapMode = $section->image_wrap_mode ?? 'fixed';
                                $imageStyle = $imageWrapMode === 'fit' 
                                    ? 'width: 100%; max-width: 100%; height: auto; object-fit: cover;' 
                                    : 'max-width: 100%; height: auto;';
                            @endphp
                            <div class="bg-white rounded-2xl shadow border border-gray-100 p-6 mb-6">
                                @if($hasValidTitle || $description !== '')
                                    <div class="mb-4">
                                        @if($hasValidTitle)
                                            <h2 class="text-lg font-semibold text-gray-900">{{ $title }}</h2>
                                        @endif
                                        @if($description !== '')
                                            <p class="text-sm text-gray-600 mt-1">{{ $description }}</p>
                                        @endif
                                    </div>
                                @endif
                                
                                @if($section->image)
                                    <div class="{{ $hasValidTitle || $description !== '' ? 'mt-4' : 'mb-4' }} {{ $alignmentClass }}">
                                        @php
                                            $imageUrl = $section->image;
                                            if (!str_starts_with($imageUrl, 'http://') && !str_starts_with($imageUrl, 'https://')) {
                                                $imageUrl = asset($imageUrl);
                                            }
                        $finalImageStyle = $imageStyle ?? 'max-width: 100%; height: auto;';
                        $styleAttr = $finalImageStyle ? ' style="' . htmlspecialchars($finalImageStyle, ENT_QUOTES, 'UTF-8') . '"' : '';
                                        @endphp
                                        <img src="{{ $imageUrl }}" alt="Gambar bagian"
                            class="rounded-xl border border-gray-200 object-contain inline-block" {!! $styleAttr !!}
                                            onerror="this.style.display='none';">
                                    </div>
                                @endif
                            </div>
                        @endif
                    @endif

                    @foreach($page['questions'] as $question)
                        <div class="bg-white rounded-2xl shadow border border-gray-100 p-6">
                            @include('forms.partials.public-question', ['question' => $question])
                        </div>
                    @endforeach
                </div>
            @endforeach

            <div class="flex justify-between items-center mt-6">
                <button type="button" id="prev-page-btn" class="items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-lg shadow transition hidden">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    Sebelumnya
                </button>
                <div class="flex-1 text-center">
                    <span class="text-sm text-gray-600">
                        Halaman <span id="current-page">1</span> dari <span id="total-pages">{{ $totalPages }}</span>
                    </span>
                </div>
                <button type="button" id="next-page-btn" class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg shadow transition">
                    Selanjutnya
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>
                <button type="submit" id="submit-btn" class="items-center px-6 py-3 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg shadow transition hidden">
                    Kirim Jawaban
                </button>
            </div>
        </form>
        @endif
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('public-form');
            if (!form) {
                return;
            }

            const pages = document.querySelectorAll('.form-page');
            const prevBtn = document.getElementById('prev-page-btn');
            const nextBtn = document.getElementById('next-page-btn');
            const submitBtn = document.getElementById('submit-btn');
            const currentPageSpan = document.getElementById('current-page');
            const totalPagesSpan = document.getElementById('total-pages');

            if (!pages.length || !prevBtn || !nextBtn || !submitBtn || !currentPageSpan || !totalPagesSpan) {
                return;
            }
            
            let currentPage = 0;
            const totalPages = pages.length;
            
            function updatePageDisplay() {
                // Hide all pages
                pages.forEach((page, index) => {
                    page.classList.remove('active');
                    if (index === currentPage) {
                        page.classList.add('active');
                    }
                });
                
                // Update page number
                currentPageSpan.textContent = currentPage + 1;
                
                // Show/hide navigation buttons
                if (currentPage === 0) {
                    prevBtn.classList.add('hidden');
                    prevBtn.classList.remove('inline-flex');
                } else {
                    prevBtn.classList.remove('hidden');
                    prevBtn.classList.add('inline-flex');
                }
                
                if (currentPage === totalPages - 1) {
                    nextBtn.classList.add('hidden');
                    nextBtn.classList.remove('inline-flex');
                    submitBtn.classList.remove('hidden');
                    submitBtn.classList.add('inline-flex');
                } else {
                    nextBtn.classList.remove('hidden');
                    nextBtn.classList.add('inline-flex');
                    submitBtn.classList.add('hidden');
                    submitBtn.classList.remove('inline-flex');
                }
            }
            
            prevBtn.addEventListener('click', function() {
                if (currentPage > 0) {
                    currentPage--;
                    updatePageDisplay();
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                }
            });
            
            nextBtn.addEventListener('click', function() {
                // Validate current page before proceeding
                const currentPageElement = pages[currentPage];
                const requiredFields = currentPageElement.querySelectorAll('[required]');
                let isValid = true;
                
                requiredFields.forEach(field => {
                    if (!field.value || (field.type === 'checkbox' && !field.checked)) {
                        isValid = false;
                        field.classList.add('border-red-500');
                    } else {
                        field.classList.remove('border-red-500');
                    }
                });
                
                if (isValid && currentPage < totalPages - 1) {
                    currentPage++;
                    updatePageDisplay();
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                } else if (!isValid) {
                    alert('Silakan lengkapi semua field yang wajib diisi.');
                }
            });
            
            // Initialize
            updatePageDisplay();
        });
    </script>
</body>

</html>