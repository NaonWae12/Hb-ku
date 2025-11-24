<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\FormResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FormController extends Controller
{
    /**
     * Menampilkan halaman dashboard
     */
    public function index()
    {
        $userId = auth()->id();

        $formsQuery = Form::withCount(['questions', 'responses'])
            ->where('user_id', $userId)
            ->orderByDesc('created_at');

        $formsCollection = $formsQuery->get();
        $formIds = $formsCollection->pluck('id');

        $totalResponses = $formIds->isNotEmpty()
            ? FormResponse::whereIn('form_id', $formIds)->count()
            : 0;

        $activeThisMonth = $formIds->isNotEmpty()
            ? FormResponse::whereIn('form_id', $formIds)
                ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                ->distinct()
                ->count('form_id')
            : 0;

        $stats = [
            'total_forms' => $formsCollection->count(),
            'total_responses' => $totalResponses,
            'active_this_month' => $activeThisMonth,
        ];

        $forms = $formsCollection->map(function ($form) {
            return [
                'id' => $form->id,
                'title' => $form->title,
                'description' => $form->description ?? '',
                'questions_count' => $form->questions_count ?? 0,
                'responses_count' => $form->responses_count ?? 0,
                'created_at' => optional($form->created_at)->toDateString() ?? Carbon::now()->toDateString(),
            ];
        })->toArray();

        return view('dashboard', compact('stats', 'forms'));
    }

    /**
     * Menampilkan halaman pembuatan form baru
     */
    public function create()
    {
        $savedRules = auth()->check()
            ? auth()->user()->formRulePresets()->latest()->get()->map->toBuilderPayload()->values()->toArray()
            : [];

        $responseStats = $this->responseStats();

        return view('forms.create', [
            'formData' => null,
            'formMode' => 'create',
            'saveFormUrl' => route('forms.store'),
            'saveFormMethod' => 'POST',
            'formId' => null,
            'shareUrl' => null,
            'savedRules' => $savedRules,
            'responsesStats' => $responseStats,
        ]);
    }

    /**
     * Menyimpan form baru ke database
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'theme_color' => 'nullable|string|in:red,blue,green,purple',
            'collect_email' => 'boolean',
            'limit_one_response' => 'boolean',
            'show_progress_bar' => 'boolean',
            'shuffle_questions' => 'boolean',
        ]);

        try {
            DB::beginTransaction();

            $form = Form::create([
                'user_id' => auth()->id(),
                'title' => $request->title,
                'description' => $request->description,
                'slug' => Str::slug($request->title) . '-' . time(),
                'theme_color' => $request->theme_color ?? 'red',
                'collect_email' => $request->boolean('collect_email'),
                'limit_one_response' => $request->boolean('limit_one_response'),
                'show_progress_bar' => $request->boolean('show_progress_bar'),
                'shuffle_questions' => $request->boolean('shuffle_questions'),
                'is_active' => true,
            ]);

            $this->syncFormRelations($form, [
                'sections' => $request->input('sections', []),
                'answer_templates' => $request->input('answer_templates', []),
                'result_rules' => $request->input('result_rules', []),
                'questions' => $request->input('questions', []),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Form berhasil disimpan!',
                'form_id' => $form->id,
                'slug' => $form->slug,
                'share_url' => route('forms.public.show', $form),
                'edit_url' => route('forms.edit', $form),
                'update_url' => route('forms.update', $form),
                'save_method' => 'PUT',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan form: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Menampilkan halaman edit form.
     */
    public function edit(Form $form)
    {
        if ($form->user_id !== auth()->id()) {
            abort(403);
        }

        $form->load([
            'sections' => function ($query) {
                $query->orderBy('order');
            },
            'questions' => function ($query) {
                $query->with(['options' => function ($optionQuery) {
                    $optionQuery->orderBy('order');
                }])->orderBy('order');
            },
            'answerTemplates' => function ($query) {
                $query->orderBy('order');
            },
            'resultRules' => function ($query) {
                $query->with(['texts' => function ($textQuery) {
                    $textQuery->orderBy('order');
                }])->orderBy('order');
            },
        ]);

        $formData = $this->prepareFormBuilderData($form);

        $savedRules = auth()->check()
            ? auth()->user()->formRulePresets()->latest()->get()->map->toBuilderPayload()->values()->toArray()
            : [];

        $responseStats = $this->responseStats($form, count($formData['questions'] ?? []));

        return view('forms.create', [
            'formData' => $formData,
            'formMode' => 'edit',
            'saveFormUrl' => route('forms.update', $form),
            'saveFormMethod' => 'PUT',
            'formId' => $form->id,
            'shareUrl' => route('forms.public.show', $form),
            'savedRules' => $savedRules,
            'responsesStats' => $responseStats,
        ]);
    }

    /**
     * Memperbarui form yang sudah ada.
     */
    public function update(Request $request, Form $form)
    {
        if ($form->user_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'theme_color' => 'nullable|string|in:red,blue,green,purple',
            'collect_email' => 'boolean',
            'limit_one_response' => 'boolean',
            'show_progress_bar' => 'boolean',
            'shuffle_questions' => 'boolean',
        ]);

        try {
            DB::beginTransaction();

            $form->update([
                'title' => $request->title,
                'description' => $request->description,
                'theme_color' => $request->theme_color ?? 'red',
                'collect_email' => $request->boolean('collect_email'),
                'limit_one_response' => $request->boolean('limit_one_response'),
                'show_progress_bar' => $request->boolean('show_progress_bar'),
                'shuffle_questions' => $request->boolean('shuffle_questions'),
            ]);

            // Hapus relasi lama sebelum sinkronisasi ulang
            $form->questions()->delete();
            $form->sections()->delete();
            $form->answerTemplates()->delete();
            $form->resultRules()->delete();

            $this->syncFormRelations($form, [
                'sections' => $request->input('sections', []),
                'answer_templates' => $request->input('answer_templates', []),
                'result_rules' => $request->input('result_rules', []),
                'questions' => $request->input('questions', []),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Form berhasil diperbarui!',
                'form_id' => $form->id,
                'slug' => $form->slug,
                'share_url' => route('forms.public.show', $form),
                'edit_url' => route('forms.edit', $form),
                'update_url' => route('forms.update', $form),
                'save_method' => 'PUT',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memperbarui form: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Menghapus form beserta relasi-relasinya.
     */
    public function destroy(Form $form)
    {
        if ($form->user_id !== auth()->id()) {
            abort(403);
        }

        try {
            DB::beginTransaction();
            $form->delete();
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Form berhasil dihapus.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus form: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Menampilkan halaman ringkasan dan detail jawaban.
     */
    public function responses(Form $form)
    {
        if ($form->user_id !== auth()->id()) {
            abort(403);
        }

        $responseData = $this->buildResponseData($form);

        return view('forms.responses', [
            'form' => $form,
            'totalResponses' => $responseData['totalResponses'],
            'questionSummaries' => collect($responseData['questionSummaries']),
            'individualResponses' => collect($responseData['individualResponses']),
        ]);
    }

    /**
     * Memberikan data jawaban untuk tab builder secara asinkron.
     */
    public function responsesData(Form $form)
    {
        if ($form->user_id !== auth()->id()) {
            abort(403);
        }

        $data = $this->buildResponseData($form);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    private function buildResponseData(Form $form): array
    {
        $form->loadMissing([
            'questions' => function ($query) {
                $query->with(['options' => function ($optionQuery) {
                    $optionQuery->orderBy('order');
                }])->orderBy('order');
            },
        ]);

        $responses = $form->responses()
            ->with(['answers.question', 'answers.questionOption'])
            ->latest()
            ->get();

        $questionSummaries = $form->questions->map(function ($question) use ($responses) {
            $answers = $responses->flatMap(function ($response) use ($question) {
                return $response->answers->where('question_id', $question->id)->values();
            });

            $total = $answers->count();
            $chart = null;
            $textAnswers = null;

            if (in_array($question->type, ['multiple-choice', 'dropdown', 'checkbox'])) {
                $labels = [];
                $counts = [];
                foreach ($question->options as $option) {
                    $labels[] = $option->text ?? 'Opsi';
                    $counts[] = $answers->where('question_option_id', $option->id)->count();
                }

                $chartType = $question->type === 'checkbox' ? 'bar' : 'pie';
                $chart = [
                    'type' => $chartType,
                    'labels' => $labels,
                    'values' => $counts,
                ];
            } else {
                $textAnswers = $answers->pluck('answer_text')->filter()->values()->take(50)->toArray();
            }

            return [
                'id' => $question->id,
                'title' => $question->title ?? 'Pertanyaan',
                'type' => $question->type,
                'total' => $total,
                'chart' => $chart,
                'text_answers' => $textAnswers,
            ];
        })->values()->toArray();

        $individualResponses = $responses->map(function ($response) {
            return [
                'id' => $response->id,
                'submitted_at' => optional($response->created_at)->format('d M Y H:i'),
                'email' => $response->email,
                'total_score' => $response->total_score,
                'result_text' => $response->result_text,
                'answers' => $response->answers->map(function ($answer) {
                    return [
                        'question' => $answer->question->title ?? 'Pertanyaan',
                        'value' => $answer->questionOption->text ?? $answer->answer_text,
                    ];
                })->values()->toArray(),
            ];
        })->values()->toArray();

        $latestResponse = $responses->first();

        return [
            'totalResponses' => $responses->count(),
            'latestResponseAt' => $latestResponse && $latestResponse->created_at
                ? $latestResponse->created_at->format('d M Y H:i')
                : null,
            'questionSummaries' => $questionSummaries,
            'individualResponses' => $individualResponses,
        ];
    }

    private function responseStats(?Form $form = null, int $questionCount = 0): array
    {
        if (!$form || !$form->exists) {
            return [
                'total_responses' => 0,
                'question_count' => $questionCount,
                'latest_response_at' => null,
            ];
        }

        $totalResponses = $form->responses()->count();
        $latest = $form->responses()->latest('created_at')->value('created_at');

        return [
            'total_responses' => $totalResponses,
            'question_count' => $questionCount,
            'latest_response_at' => $latest ? $latest->format('d M Y H:i') : null,
        ];
    }

    private function makeTemplateLookupKey(array $templateData): ?string
    {
        $answerText = trim($templateData['answer_text'] ?? $templateData['text'] ?? '');
        if ($answerText === '') {
            return null;
        }

        $score = (int) ($templateData['score'] ?? 0);

        return Str::lower($answerText) . '|' . $score;
    }

    private function processQuestionImage(?string $imageValue, Form $form): ?string
    {
        if ($imageValue === null || $imageValue === '') {
            return null;
        }

        // If it's base64 data, process it
        if (str_starts_with($imageValue, 'data:image')) {
            return $this->storeBase64Image($imageValue, $form);
        }

        // If it's already a storage path (starts with "storage/"), return as-is
        if (str_starts_with($imageValue, 'storage/')) {
            return $imageValue;
        }

        // If it's a full URL, extract the storage path
        if (str_starts_with($imageValue, 'http://') || str_starts_with($imageValue, 'https://')) {
            $path = parse_url($imageValue, PHP_URL_PATH);
            if ($path && str_starts_with($path, '/storage/')) {
                return ltrim($path, '/');
            }
        }

        // Otherwise, assume it's a storage path
        return $imageValue;
    }

    private function storeBase64Image(string $base64, Form $form): string
    {
        if (!preg_match('/^data:image\/(\w+);base64,/', $base64, $type)) {
            throw new \RuntimeException('Invalid image data.');
        }

        $extension = strtolower($type[1]);
        $extension = $extension === 'jpeg' ? 'jpg' : $extension;
        if (!in_array($extension, ['jpg', 'png'], true)) {
            $extension = 'jpg';
        }

        $base64 = substr($base64, strpos($base64, ',') + 1);
        $imageData = base64_decode($base64);

        if ($imageData === false) {
            throw new \RuntimeException('Failed to decode image.');
        }

        $image = \imagecreatefromstring($imageData);
        if ($image === false) {
            throw new \RuntimeException('Invalid image contents.');
        }

        $image = $this->resizeImageResource($image);

        ob_start();
        if ($extension === 'png') {
            \imagepng($image, null, 8);
        } else {
            \imagejpeg($image, null, 85);
            $extension = 'jpg';
        }
        $contents = ob_get_clean();
        \imagedestroy($image);

        if ($contents === false) {
            throw new \RuntimeException('Unable to prepare image for storage.');
        }

        $directory = "question-images/{$form->id}";
        $filename = Str::random(40) . '.' . $extension;
        
        // Ensure directory exists
        Storage::disk('public')->makeDirectory($directory);
        
        // Store the file
        Storage::disk('public')->put("{$directory}/{$filename}", $contents);

        // Return path relative to public directory (for asset() helper)
        return "storage/{$directory}/{$filename}";
    }

    /**
     * Resize GD image resource while keeping aspect ratio.
     *
     * @param  \GdImage  $image
     */
    private function resizeImageResource($image, int $maxWidth = 1600)
    {
        $width = \imagesx($image);
        $height = \imagesy($image);

        if ($width <= $maxWidth) {
            return $image;
        }

        $ratio = $maxWidth / $width;
        $newWidth = $maxWidth;
        $newHeight = (int) round($height * $ratio);

        $resampled = \imagecreatetruecolor($newWidth, $newHeight);
        \imagealphablending($resampled, false);
        \imagesavealpha($resampled, true);
        \imagecopyresampled($resampled, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        \imagedestroy($image);

        return $resampled;
    }

    /**
     * Sinkronisasi relasi form berdasarkan data dari request.
     */
    private function syncFormRelations(Form $form, array $payload): void
    {
        $sectionIdMap = [];
        $sections = $payload['sections'] ?? [];

        foreach ($sections as $index => $sectionData) {
            if (!is_array($sectionData)) {
                continue;
            }

            $section = $form->sections()->create([
                'title' => $sectionData['title'] ?? null,
                'description' => $sectionData['description'] ?? null,
                'order' => $index,
            ]);

            $sectionIdMap[$index] = $section->id;
        }

        $answerTemplateIdMap = [];
        $answerTemplates = $payload['answer_templates'] ?? [];
        $answerTemplateLookup = [];

        foreach ($answerTemplates as $index => $templateData) {
            if (!is_array($templateData)) {
                continue;
            }

            $answerText = trim($templateData['answer_text'] ?? '');
            if ($answerText === '') {
                continue;
            }

            $template = $form->answerTemplates()->create([
                'answer_text' => $answerText,
                'score' => $templateData['score'] ?? 0,
                'order' => $index,
            ]);

            $answerTemplateIdMap[$index] = $template->id;
            $templateKey = $this->makeTemplateLookupKey($templateData);
            if ($templateKey) {
                $answerTemplateLookup[$templateKey] = $template->id;
            }
        }

        $nextTemplateOrder = count($answerTemplateLookup);

        $resultRules = $payload['result_rules'] ?? [];

        foreach ($resultRules as $index => $ruleData) {
            if (!is_array($ruleData)) {
                continue;
            }

            $rule = $form->resultRules()->create([
                'condition_type' => $ruleData['condition_type'] ?? 'range',
                'min_score' => $ruleData['min_score'] ?? null,
                'max_score' => $ruleData['max_score'] ?? null,
                'single_score' => $ruleData['single_score'] ?? null,
                'order' => $index,
            ]);

            foreach ($ruleData['texts'] ?? [] as $textIndex => $text) {
                $textValue = trim($text ?? '');
                if ($textValue === '') {
                    continue;
                }

                $rule->texts()->create([
                    'result_text' => $textValue,
                    'order' => $textIndex,
                ]);
            }
        }

        $questions = $payload['questions'] ?? [];

        foreach ($questions as $index => $questionData) {
            if (!is_array($questionData)) {
                continue;
            }

            $title = trim($questionData['title'] ?? '');
            $sectionId = null;
            if (isset($questionData['section_id']) && array_key_exists($questionData['section_id'], $sectionIdMap)) {
                $sectionId = $sectionIdMap[$questionData['section_id']];
            }

            $question = $form->questions()->create([
                'section_id' => $sectionId,
                'type' => $questionData['type'] ?? 'short-answer',
                'title' => $title !== '' ? $title : 'Pertanyaan tanpa judul',
                'description' => $questionData['description'] ?? null,
                'image' => $this->processQuestionImage($questionData['image'] ?? null, $form),
                'image_alignment' => $questionData['image_alignment'] ?? 'center',
                'image_width' => isset($questionData['image_width'])
                    ? (int) $questionData['image_width']
                    : null,
                'is_required' => (bool) ($questionData['is_required'] ?? false),
                'order' => $index,
            ]);

            $savedRuleTemplateIds = [];
            if (!empty($questionData['saved_rule']['templates']) && is_array($questionData['saved_rule']['templates'])) {
                foreach ($questionData['saved_rule']['templates'] as $templateIndex => $templateData) {
                    $templateKey = $this->makeTemplateLookupKey($templateData);
                    if (!$templateKey) {
                        continue;
                    }

                    if (!isset($answerTemplateLookup[$templateKey])) {
                        $template = $form->answerTemplates()->create([
                            'answer_text' => $templateData['answer_text'] ?? $templateData['text'] ?? 'Jawaban',
                            'score' => $templateData['score'] ?? 0,
                            'order' => $nextTemplateOrder++,
                        ]);

                        $answerTemplateLookup[$templateKey] = $template->id;
                    }

                    $savedRuleTemplateIds[$templateIndex] = $answerTemplateLookup[$templateKey];
                }
            }

            foreach ($questionData['options'] ?? [] as $optIndex => $optionData) {
                if (!is_array($optionData)) {
                    continue;
                }

                $optionText = trim($optionData['text'] ?? '');
                if ($optionText === '') {
                    continue;
                }

                $answerTemplateId = null;
                $templateIndexReference = $optionData['answer_template_index'] ?? $optionData['answer_template_id'] ?? null;
                if ($templateIndexReference !== null && array_key_exists($templateIndexReference, $answerTemplateIdMap)) {
                    $answerTemplateId = $answerTemplateIdMap[$templateIndexReference];
                } elseif (array_key_exists($optIndex, $savedRuleTemplateIds)) {
                    $answerTemplateId = $savedRuleTemplateIds[$optIndex];
                }

                $question->options()->create([
                    'answer_template_id' => $answerTemplateId,
                    'text' => $optionText,
                    'order' => $optIndex,
                ]);
            }
        }
    }

    /**
     * Menyusun data awal untuk form builder ketika mode edit.
     */
    private function prepareFormBuilderData(Form $form): array
    {
        $sections = $form->sections->sortBy('order')->values();
        $sectionIndexMap = [];

        $sectionsData = $sections->map(function ($section, $index) use (&$sectionIndexMap) {
            $sectionIndexMap[$section->id] = $index;

            return [
                'title' => $section->title,
                'description' => $section->description,
            ];
        })->toArray();

        $answerTemplatesCollection = $form->answerTemplates
            ->sortBy('order')
            ->values();

        $answerTemplateIndexMap = [];
        $answerTemplatesData = $answerTemplatesCollection
            ->map(function ($template, $index) use (&$answerTemplateIndexMap) {
                $answerTemplateIndexMap[$template->id] = $index;

                return [
                    'answer_text' => $template->answer_text,
                    'score' => $template->score,
                ];
            })->toArray();

        $questionsData = $form->questions
            ->sortBy('order')
            ->values()
            ->map(function ($question) use ($sectionIndexMap, $answerTemplateIndexMap) {
                $questionData = [
                    'type' => $question->type,
                    'title' => $question->title,
                    'description' => $question->description,
                    'image' => $question->image,
                    'image_alignment' => $question->image_alignment ?? 'center',
                    'image_width' => $question->image_width ?? 100,
                    'image_url' => $question->image ? asset($question->image) : null,
                    'is_required' => (bool) $question->is_required,
                    'options' => [],
                ];

                if ($question->section_id && isset($sectionIndexMap[$question->section_id])) {
                    $questionData['section_id'] = $sectionIndexMap[$question->section_id];
                }

                $questionData['options'] = $question->options
                    ->sortBy('order')
                    ->values()
                    ->map(function ($option) use ($answerTemplateIndexMap) {
                        return [
                            'text' => $option->text,
                            'answer_template_index' => $option->answer_template_id !== null && isset($answerTemplateIndexMap[$option->answer_template_id])
                                ? $answerTemplateIndexMap[$option->answer_template_id]
                                : null,
                        ];
                    })->toArray();

                return $questionData;
            })->toArray();

        $resultRulesData = $form->resultRules
            ->sortBy('order')
            ->values()
            ->map(function ($rule) {
                return [
                    'condition_type' => $rule->condition_type,
                    'min_score' => $rule->min_score,
                    'max_score' => $rule->max_score,
                    'single_score' => $rule->single_score,
                    'texts' => $rule->texts
                        ->sortBy('order')
                        ->pluck('result_text')
                        ->toArray(),
                ];
            })->toArray();

        return [
            'id' => $form->id,
            'slug' => $form->slug,
            'title' => $form->title,
            'description' => $form->description,
            'theme_color' => $form->theme_color,
            'collect_email' => (bool) $form->collect_email,
            'limit_one_response' => (bool) $form->limit_one_response,
            'show_progress_bar' => (bool) $form->show_progress_bar,
            'shuffle_questions' => (bool) $form->shuffle_questions,
            'share_url' => route('forms.public.show', $form),
            'sections' => $sectionsData,
            'questions' => $questionsData,
            'answer_templates' => $answerTemplatesData,
            'result_rules' => $resultRulesData,
        ];
    }
}

