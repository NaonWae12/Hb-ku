<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResultRuleText extends Model
{
    protected $fillable = [
        'result_rule_id',
        'result_text',
        'order',
        'rule_group_id',
    ];

    // Relationships
    public function resultRule(): BelongsTo
    {
        return $this->belongsTo(ResultRule::class);
    }
}
