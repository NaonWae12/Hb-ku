<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResultSetting extends Model
{
    protected $fillable = [
        'form_id',
        'result_rule_id',
        'title',
        'image',
        'image_alignment',
        'result_text',
        'text_alignment',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    // Relationships
    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function resultRule(): BelongsTo
    {
        return $this->belongsTo(ResultRule::class);
    }
}
