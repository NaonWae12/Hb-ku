<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResultTextSetting extends Model
{
    protected $fillable = [
        'result_rule_text_id',
        'title',
        'image',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    // Relationships
    public function resultRuleText(): BelongsTo
    {
        return $this->belongsTo(ResultRuleText::class);
    }
}
