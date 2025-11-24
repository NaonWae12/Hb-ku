<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $form->title }} - hb-ku Form</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
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
                <h1 class="text-2xl font-semibold text-gray-900 mb-2">{{ $form->title }}</h1>
                @if($form->description)
                    <p class="text-sm text-gray-600">{{ $form->description }}</p>
                @endif
                <div class="mt-4 text-xs text-gray-500">
                    Bagikan tautan ini: <span class="font-medium text-gray-700">{{ $shareUrl }}</span>
                </div>
            </div>
        </div>

        @if (session('status'))
            <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl p-4 mb-6">
                <p class="font-medium">{{ session('status') }}</p>
                @if (session('result_text'))
                    <p class="text-sm mt-2 whitespace-pre-line">{{ session('result_text') }}</p>
                @endif
            </div>
        @endif

        <form method="POST" action="{{ route('forms.public.submit', $form) }}" class="space-y-6">
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

            @if($unsectionedQuestions->count())
                @foreach($unsectionedQuestions as $question)
                    <div class="bg-white rounded-2xl shadow border border-gray-100 p-6">
                        @include('forms.partials.public-question', ['question' => $question])
                    </div>
                @endforeach
            @endif

            @foreach($sections as $section)
                <div class="bg-white rounded-2xl shadow border border-gray-100 p-6">
                    <div class="mb-4">
                        <h2 class="text-lg font-semibold text-gray-900">{{ $section->title ?? 'Bagian' }}</h2>
                        @if($section->description)
                            <p class="text-sm text-gray-600 mt-1">{{ $section->description }}</p>
                        @endif
                    </div>

                    <div class="space-y-6">
                        @foreach($section->questions as $question)
                            <div class="border border-gray-100 rounded-xl p-4">
                                @include('forms.partials.public-question', ['question' => $question])
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div class="flex justify-end">
                <button type="submit"
                    class="inline-flex items-center px-6 py-3 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg shadow transition">
                    Kirim Jawaban
                </button>
            </div>
        </form>
    </div>
</body>
</html>

