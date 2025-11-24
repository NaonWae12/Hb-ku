<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionOption extends Model
{
    protected $fillable = [
        'question_id',
        'answer_template_id',
        'text',
        'order',
    ];

    // Relationships
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function answerTemplate(): BelongsTo
    {
        return $this->belongsTo(AnswerTemplate::class);
    }
}
