<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\FormResponse;
use App\Models\ResponseAnswer;
use App\Models\SettingResult;
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
     * Check if a section has been edited (has meaningful content).
     */
    private function isSectionEdited($section): bool
    {
        if (!$section) {
            return false;
        }

        $title = trim($section->title ?? '');
        $description = trim($section->description ?? '');
        $hasImage = !empty($section->image);

        // Check if title is not empty and not a default "Bagian X" pattern
        $hasValidTitle = $title !== '' && !preg_match('/^Bagian\s+\d+$/i', $title);

        return $hasValidTitle || $description !== '' || $hasImage;
    }

    /**
     * Group questions into pages where sections act as page dividers.
     * All questions before a section appear on the same page.
     * The section and its questions appear on the next page.
     * Sections always act as dividers (partition), but only edited sections are displayed.
     */
    private function groupQuestionsIntoPages(Form $form): array
    {
        $allQuestions = $form->questions->sortBy('order')->values();
        $sections = $form->sections->sortBy('order')->values();

        $pages = [];
        $currentPageQuestions = [];
        $currentPageSection = null;

        // Create a map of section_id to section for quick lookup (all sections)
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

                if ($questionSection) {
                    // Section always acts as divider, regardless of whether it's edited
                    if ($currentPageSection && $currentPageSection->id === $questionSectionId) {
                        // Same section as current page - add to current page
                        $currentPageQuestions[] = $question;
                    } else {
                        // Different section - save current page and start new page
                        // Section always acts as divider, but only show if edited
                        if (count($currentPageQuestions) > 0 || $currentPageSection) {
                            $pages[] = [
                                'section' => ($currentPageSection && $this->isSectionEdited($currentPageSection)) ? $currentPageSection : null,
                                'questions' => $currentPageQuestions,
                            ];
                        }

                        // Start new page with this section (always use as divider)
                        $currentPageSection = $questionSection;
                        $currentPageQuestions = [$question];
                    }
                } else {
                    // Section not found, treat question as if it has no section
                    $currentPageQuestions[] = $question;
                }
            }
        }

        // Add the last page
        if (count($currentPageQuestions) > 0 || $currentPageSection) {
            $pages[] = [
                'section' => ($currentPageSection && $this->isSectionEdited($currentPageSection)) ? $currentPageSection : null,
                'questions' => $currentPageQuestions,
            ];
        }

        // Handle section dividers (sections with no questions)
        // These should split questions based on order
        // All sections act as dividers, but only edited ones are displayed
        foreach ($sections as $section) {
            $sectionQuestions = $allQuestions->where('section_id', $section->id);

            // If this is a section divider (no questions assigned to it)
            // Section always acts as divider, regardless of whether it's edited
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
                                        'section' => $this->isSectionEdited($section) ? $section : null,
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
            'resultRules.texts.textSetting',
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
        $finalResultData = null;

        DB::transaction(function () use ($form, $validated, $answers, &$totalScore, &$finalResultData, $request, $allQuestions) {
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

            $resultData = $this->resolveResultText($form, $totalScore);
            $finalResultData = $resultData;

            // Store plain text for backward compatibility
            $resultTextPlain = null;
            if ($resultData && isset($resultData['texts'])) {
                $resultTextPlain = implode("\n\n", array_column($resultData['texts'], 'result_text'));
            }

            $formResponse->update([
                'total_score' => $totalScore,
                'result_text' => $resultTextPlain,
            ]);
        });

        return redirect()
            ->route('forms.public.show', $form)
            ->with('status', 'Terima kasih! Jawaban Anda telah disimpan.')
            ->with('result_data', $finalResultData);
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

    private function resolveResultText(Form $form, int $totalScore): ?array
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

        // Get all texts from matching rule (ordered by order)
        $allRuleTexts = $matchingRule->texts->sortBy('order')->values();

        if ($allRuleTexts->isEmpty()) {
            return null;
        }

        $ruleGroupId = $matchingRule->rule_group_id;

        // Load setting_results for this rule_group_id (if exists)
        $settingResults = null;
        $textAlignment = 'center';
        $imageAlignment = 'center';

        if ($ruleGroupId) {
            $settingResults = SettingResult::where('form_id', $form->id)
                ->where('rule_group_id', $ruleGroupId)
                ->with('resultRuleText')
                ->orderBy('order')
                ->get()
                ->keyBy('result_rule_text_id');

            // Get alignment from first setting if exists
            if ($settingResults->isNotEmpty()) {
                $firstSetting = $settingResults->first();
                $textAlignment = $firstSetting->text_alignment ?? 'center';
                $imageAlignment = $firstSetting->image_alignment ?? 'center';
            }
        }

        // Build texts array: get all texts from rule, merge with settings if available
        $texts = $allRuleTexts->map(function ($ruleText) use ($settingResults) {
            $setting = $settingResults ? $settingResults->get($ruleText->id) : null;

            return [
                'title' => $setting ? $setting->title : null,
                'image' => $setting ? $setting->image : null,
                'image_url' => $setting && $setting->image ? asset($setting->image) : null,
                'result_text' => $ruleText->result_text ?? '',
            ];
        })->filter(function ($text) {
            return !empty($text['result_text']);
        })->values()->toArray();

        if (empty($texts)) {
            return null;
        }

        return [
            'text_alignment' => $textAlignment,
            'image_alignment' => $imageAlignment,
            'texts' => $texts,
        ];
    }
}
