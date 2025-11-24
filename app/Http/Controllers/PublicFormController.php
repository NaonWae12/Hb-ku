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
                $query->orderBy('order')
                    ->with(['options' => function ($optionQuery) {
                        $optionQuery->orderBy('order')
                            ->with('answerTemplate');
                    }]);
            },
        ]);

        // Group questions into pages based on sections as dividers
        $pages = $this->groupQuestionsIntoPages($form);

        return view('forms.public', [
            'form' => $form,
            'pages' => $pages,
            'totalPages' => count($pages),
            'shareUrl' => route('forms.public.show', $form),
        ]);
    }

    /**
     * Group questions into pages where sections act as page dividers.
     * All questions before a section appear on the same page.
     * The section and its questions appear on the next page.
     */
    private function groupQuestionsIntoPages(Form $form): array
    {
        $allQuestions = $form->questions->sortBy('order')->values();
        $sections = $form->sections->sortBy('order')->values();
        
        $pages = [];
        $currentPageQuestions = [];
        $currentPageSection = null;
        
        // Create a map of section_id to section for quick lookup
        $sectionMap = $sections->keyBy('id');
        
        // Process each question in order
        foreach ($allQuestions as $question) {
            $questionSectionId = $question->section_id;
            
            if ($questionSectionId === null) {
                // Question has no section - add to current page
                $currentPageQuestions[] = $question;
            } else {
                // Question belongs to a section
                $questionSection = $sectionMap->get($questionSectionId);
                
                if ($currentPageSection && $currentPageSection->id === $questionSectionId) {
                    // Same section as current page - add to current page
                    $currentPageQuestions[] = $question;
                } else {
                    // Different section - save current page and start new page
                    if (count($currentPageQuestions) > 0 || $currentPageSection) {
                        $pages[] = [
                            'section' => $currentPageSection,
                            'questions' => $currentPageQuestions,
                        ];
                    }
                    
                    // Start new page with this section
                    $currentPageSection = $questionSection;
                    $currentPageQuestions = [$question];
                }
            }
        }
        
        // Add the last page
        if (count($currentPageQuestions) > 0 || $currentPageSection) {
            $pages[] = [
                'section' => $currentPageSection,
                'questions' => $currentPageQuestions,
            ];
        }
        
        // Handle section dividers (sections with no questions)
        // These should split questions based on order
        foreach ($sections as $section) {
            $sectionQuestions = $allQuestions->where('section_id', $section->id);
            
            // If this is a section divider (no questions assigned to it)
            if ($sectionQuestions->isEmpty()) {
                // Find questions that should be split by this section divider
                // Questions with order < section.order and no section_id go before
                // Questions with order > section.order and no section_id go after
                $questionsBefore = $allQuestions
                    ->where('order', '<', $section->order)
                    ->whereNull('section_id')
                    ->values();
                
                $questionsAfter = $allQuestions
                    ->where('order', '>', $section->order)
                    ->whereNull('section_id')
                    ->values();
                
                // If we have both before and after, we need to split
                if ($questionsBefore->isNotEmpty() && $questionsAfter->isNotEmpty()) {
                    // Rebuild pages to account for this divider
                    $newPages = [];
                    $splitDone = false;
                    
                    foreach ($pages as $page) {
                        if (!$splitDone && $page['section'] === null) {
                            // Check if this page contains questions that should be split
                            $pageQuestionIds = collect($page['questions'])->pluck('id')->toArray();
                            $beforeIds = $questionsBefore->pluck('id')->toArray();
                            $afterIds = $questionsAfter->pluck('id')->toArray();
                            
                            $hasBefore = count(array_intersect($pageQuestionIds, $beforeIds)) > 0;
                            $hasAfter = count(array_intersect($pageQuestionIds, $afterIds)) > 0;
                            
                            if ($hasBefore && $hasAfter) {
                                // Split this page
                                $beforeQuestions = collect($page['questions'])
                                    ->where('order', '<', $section->order)
                                    ->values()
                                    ->toArray();
                                
                                $afterQuestions = collect($page['questions'])
                                    ->where('order', '>', $section->order)
                                    ->values()
                                    ->toArray();
                                
                                if (count($beforeQuestions) > 0) {
                                    $newPages[] = [
                                        'section' => null,
                                        'questions' => $beforeQuestions,
                                    ];
                                }
                                
                                if (count($afterQuestions) > 0) {
                                    $newPages[] = [
                                        'section' => $section,
                                        'questions' => $afterQuestions,
                                    ];
                                }
                                
                                $splitDone = true;
                            } else {
                                $newPages[] = $page;
                            }
                        } else {
                            $newPages[] = $page;
                        }
                    }
                    
                    if ($splitDone) {
                        $pages = $newPages;
                    }
                }
            }
        }
        
        // If no pages were created, create one with all questions
        if (empty($pages)) {
            $pages[] = [
                'section' => null,
                'questions' => $allQuestions->toArray(),
            ];
        }
        
        return $pages;
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

