<?php

namespace App\Http\Controllers;

use App\Models\FormRulePreset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FormRulePresetController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'templates' => ['required', 'array', 'min:1'],
            'templates.*.answer_text' => ['required', 'string'],
            'templates.*.score' => ['nullable', 'numeric'],
            'result_rules' => ['nullable', 'array'],
            'result_rules.*.condition_type' => ['required_with:result_rules', 'string', 'in:range,equal,greater,less'],
            'result_rules.*.min_score' => ['nullable', 'numeric'],
            'result_rules.*.max_score' => ['nullable', 'numeric'],
            'result_rules.*.single_score' => ['nullable', 'numeric'],
            'result_rules.*.texts' => ['nullable', 'array'],
            'result_rules.*.texts.*' => ['nullable', 'string'],
        ]);

        $preset = FormRulePreset::create([
            'user_id' => $user->id,
            'templates' => collect($data['templates'])
                ->map(fn ($template) => [
                    'answer_text' => $template['answer_text'],
                    'score' => isset($template['score']) ? (float) $template['score'] : 0,
                ])->values(),
            'result_rules' => collect($data['result_rules'] ?? [])
                ->map(function ($rule) {
                    return [
                        'condition_type' => $rule['condition_type'] ?? 'range',
                        'min_score' => $rule['min_score'] ?? null,
                        'max_score' => $rule['max_score'] ?? null,
                        'single_score' => $rule['single_score'] ?? null,
                        'texts' => array_values(array_filter($rule['texts'] ?? [])),
                    ];
                })->values(),
        ]);

        $presets = $user->formRulePresets()
            ->latest()
            ->get()
            ->map
            ->toBuilderPayload()
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'Aturan berhasil disimpan.',
            'presets' => $presets,
        ]);
    }

    public function destroy(FormRulePreset $formRulePreset): JsonResponse
    {
        $user = auth()->user();

        // Ensure the preset belongs to the authenticated user
        if ($formRulePreset->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        $formRulePreset->delete();

        // Return updated list of presets
        $presets = $user->formRulePresets()
            ->latest()
            ->get()
            ->map
            ->toBuilderPayload()
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'Aturan berhasil dihapus.',
            'presets' => $presets,
        ]);
    }
}

