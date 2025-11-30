<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormTextFormatting extends Model
{
    protected $table = 'form_text_formatting';

    protected $fillable = [
        'form_id',
        'question_id',
        'section_id',
        'result_rule_text_id',
        'element_type',
        'text_align',
        'font_family',
        'font_size',
        'font_weight',
        'font_style',
        'text_decoration',
    ];

    protected $casts = [
        'font_size' => 'integer',
    ];

    /**
     * Get the form that owns the formatting.
     */
    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    /**
     * Get the question that owns the formatting.
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * Get the section that owns the formatting.
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * Get the result rule text that owns the formatting.
     */
    public function resultRuleText(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ResultRuleText::class);
    }
}
