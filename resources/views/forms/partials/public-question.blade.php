@php
    $inputKey = "answers.{$question->id}";
    $isCheckbox = $question->type === 'checkbox';
    $fieldName = $isCheckbox ? "answers[{$question->id}][]" : "answers[{$question->id}]";
    $oldValue = old($inputKey);
    $oldArray = $isCheckbox
        ? collect(old($inputKey, []))->map(fn ($value) => (int) $value)->toArray()
        : [];
    $imageAlignment = $question->image_alignment ?? 'center';
    $alignmentClass = match ($imageAlignment) {
        'left' => 'text-left',
        'right' => 'text-right',
        default => 'text-center',
    };
    $imageWidth = $question->image_width ?? 100;
@endphp

<div>
    <div class="flex items-start justify-between mb-3">
        <div>
            <h3 class="text-base font-medium text-gray-900">
                {{ $question->title ?? 'Pertanyaan' }}
                @if($question->is_required)
                    <span class="text-red-600">*</span>
                @endif
            </h3>
            @if($question->description)
                <p class="text-sm text-gray-500 mt-1">{{ $question->description }}</p>
            @endif
        </div>
    </div>

    @if($question->image)
        <div class="mb-4 {{ $alignmentClass }}">
            @php
                // Ensure the image path is properly formatted for asset() helper
                $imagePath = $question->image;
                // If it's already a full URL, use it as-is
                if (str_starts_with($imagePath, 'http://') || str_starts_with($imagePath, 'https://')) {
                    $imageUrl = $imagePath;
                } else {
                    // Otherwise, use asset() to generate the full URL
                    // Remove leading slash if present to ensure proper path
                    $imagePath = ltrim($imagePath, '/');
                    $imageUrl = asset($imagePath);
                }
            @endphp
            <img src="{{ $imageUrl }}" alt="Gambar pertanyaan"
                class="rounded-xl border border-gray-200 object-contain inline-block max-w-full"
                style="width: {{ $imageWidth }}%; max-width: 100%;"
                onerror="this.style.display='none';">
        </div>
    @endif

    @switch($question->type)
        @case('paragraph')
            <textarea name="{{ $fieldName }}" rows="4"
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-red-500"
                {{ $question->is_required ? 'required' : '' }}>{{ $oldValue }}</textarea>
            @break

        @case('multiple-choice')
            <div class="space-y-2">
                @foreach($question->options as $option)
                    <label class="flex items-center space-x-2 text-sm text-gray-700">
                        <input type="radio" name="{{ $fieldName }}" value="{{ $option->id }}"
                            class="text-red-600 focus:ring-red-500"
                            {{ (int) $oldValue === $option->id ? 'checked' : '' }}
                            {{ $question->is_required ? 'required' : '' }}>
                        <span>{{ $option->text }}</span>
                    </label>
                @endforeach
            </div>
            @break

        @case('checkbox')
            <div class="space-y-2">
                @foreach($question->options as $option)
                    <label class="flex items-center space-x-2 text-sm text-gray-700">
                        <input type="checkbox" name="{{ $fieldName }}" value="{{ $option->id }}"
                            class="rounded border-gray-300 text-red-600 focus:ring-red-500"
                            @if(in_array($option->id, $oldArray, true)) checked @endif>
                        <span>{{ $option->text }}</span>
                    </label>
                @endforeach
            </div>
            @break

        @case('dropdown')
            <select name="{{ $fieldName }}"
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-red-500"
                {{ $question->is_required ? 'required' : '' }}>
                <option value="">Pilih jawaban</option>
                @foreach($question->options as $option)
                    <option value="{{ $option->id }}" {{ (int) $oldValue === $option->id ? 'selected' : '' }}>
                        {{ $option->text }}
                    </option>
                @endforeach
            </select>
            @break

        @default
            <input type="text" name="{{ $fieldName }}" value="{{ $oldValue }}"
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-red-500"
                {{ $question->is_required ? 'required' : '' }}>
    @endswitch

    @error("answers.{$question->id}")
        <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
    @enderror
    @error("answers.{$question->id}.*")
        <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
    @enderror
</div>

