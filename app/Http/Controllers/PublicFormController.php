<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\FormResponse;
use App\Models\ResponseAnswer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PublicFormController extends Controller
{
    public function show(Form $form): View
    {
        abort_unless($form->is_active, 404);

        $form->load([
            'sections' => function ($query) {
                $query->orderBy('order')
                    ->with(['questions' => function ($questionQuery) {
                        $questionQuery->orderBy('order')
                            ->with(['options' => function ($optionQuery) {
                                $optionQuery->orderBy('order')
                                    ->with('answerTemplate');
                            }]);
                    }]);
            },
            'questions' => function ($query) {
                $query->whereNull('section_id')
                    ->orderBy('order')
                    ->with(['options' => function ($optionQuery) {
                        $optionQuery->orderBy('order')
                            ->with('answerTemplate');
                    }]);
            },
        ]);

        return view('forms.public', [
            'form' => $form,
            'sections' => $form->sections,
            'unsectionedQuestions' => $form->questions,
            'shareUrl' => route('forms.public.show', $form),
        ]);
    }

    public function submit(Request $request, Form $form): RedirectResponse
    {
        abort_unless($form->is_active, 404);

        $form->load([
            'sections.questions.options.answerTemplate',
            'questions.options.answerTemplate',
            'resultRules.texts',
        ]);

        $allQuestions = collect($form->questions)
            ->concat($form->sections->flatMap->questions)
            ->unique('id')
            ->values();

        $rules = [];
        $messages = [];

        $rules['email'] = $form->collect_email ? ['required', 'email'] : ['nullable', 'email'];

        foreach ($allQuestions as $question) {
            $this->buildQuestionValidation($question->id, $question->is_required, $question->type, $rules, $messages);
        }

        $validated = $request->validate($rules, $messages);

        if ($form->limit_one_response && $form->collect_email && $validated['email']) {
            $exists = FormResponse::where('form_id', $form->id)
                ->where('email', $validated['email'])
                ->exists();

            if ($exists) {
                return back()
                    ->withInput()
                    ->withErrors(['email' => 'Anda sudah mengisi formulir ini.']);
            }
        }

        $answers = $validated['answers'] ?? [];
        $totalScore = 0;
        $finalResultText = null;

        DB::transaction(function () use ($form, $validated, $answers, &$totalScore, &$finalResultText, $request, $allQuestions) {
            $formResponse = FormResponse::create([
                'form_id' => $form->id,
                'email' => $validated['email'] ?? null,
                'total_score' => 0,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            foreach ($allQuestions as $question) {
                $answerValue = $answers[$question->id] ?? null;

                if ($answerValue === null || $answerValue === '') {
                    continue;
                }

                $options = $question->options->keyBy('id');

                if ($question->type === 'checkbox') {
                    $selectedOptions = is_array($answerValue) ? $answerValue : [$answerValue];
                    foreach ($selectedOptions as $optionId) {
                        $option = $options->get((int) $optionId);
                        if (! $option) {
                            continue;
                        }

                        $score = optional($option->answerTemplate)->score ?? 0;
                        $totalScore += $score;

                        ResponseAnswer::create([
                            'form_response_id' => $formResponse->id,
                            'question_id' => $question->id,
                            'question_option_id' => $option->id,
                            'answer_text' => $option->text,
                            'score' => $score,
                        ]);
                    }
                } elseif (in_array($question->type, ['multiple-choice', 'dropdown'], true)) {
                    $option = $options->get((int) $answerValue);
                    if (! $option) {
                        continue;
                    }

                    $score = optional($option->answerTemplate)->score ?? 0;
                    $totalScore += $score;

                    ResponseAnswer::create([
                        'form_response_id' => $formResponse->id,
                        'question_id' => $question->id,
                        'question_option_id' => $option->id,
                        'answer_text' => $option->text,
                        'score' => $score,
                    ]);
                } else {
                    $textAnswer = is_array($answerValue) ? implode(', ', $answerValue) : $answerValue;
                    ResponseAnswer::create([
                        'form_response_id' => $formResponse->id,
                        'question_id' => $question->id,
                        'answer_text' => $textAnswer,
                        'score' => 0,
                    ]);
                }
            }

            $resultText = $this->resolveResultText($form, $totalScore);
            $finalResultText = $resultText;

            $formResponse->update([
                'total_score' => $totalScore,
                'result_text' => $resultText,
            ]);
        });

        return redirect()
            ->route('forms.public.show', $form)
            ->with('status', 'Terima kasih! Jawaban Anda telah disimpan.')
            ->with('result_text', $finalResultText);
    }

    private function buildQuestionValidation(int $questionId, bool $isRequired, string $type, array &$rules, array &$messages): void
    {
        $key = "answers.{$questionId}";

        if ($type === 'checkbox') {
            $rules[$key] = $isRequired ? ['required', 'array', 'min:1'] : ['nullable', 'array'];
            $rules["{$key}.*"] = ['nullable'];
        } else {
            $rules[$key] = $isRequired ? ['required'] : ['nullable'];
        }

        if ($isRequired) {
            $messages["{$key}.required"] = 'Pertanyaan wajib diisi.';
            if ($type === 'checkbox') {
                $messages["{$key}.min"] = 'Pilih minimal satu jawaban.';
            }
        }
    }

    private function resolveResultText(Form $form, int $totalScore): ?string
    {
        $matchingRule = $form->resultRules
            ->sortBy('order')
            ->first(function ($rule) use ($totalScore) {
                return match ($rule->condition_type) {
                    'range' => ($rule->min_score === null || $totalScore >= $rule->min_score)
                        && ($rule->max_score === null || $totalScore <= $rule->max_score),
                    'equal' => $rule->single_score !== null && $totalScore === $rule->single_score,
                    'greater' => $rule->single_score !== null && $totalScore > $rule->single_score,
                    'less' => $rule->single_score !== null && $totalScore < $rule->single_score,
                    default => false,
                };
            });

        if (! $matchingRule) {
            return null;
        }

        $texts = $matchingRule->texts->pluck('result_text')->filter()->toArray();

        return $texts ? implode("\n\n", $texts) : null;
    }
}

