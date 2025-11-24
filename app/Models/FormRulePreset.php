<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormRulePreset extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'templates',
        'result_rules',
    ];

    protected $casts = [
        'templates' => 'array',
        'result_rules' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function toBuilderPayload(): array
    {
        return [
            'id' => $this->id,
            'templates' => $this->templates ?? [],
            'result_rules' => $this->result_rules ?? [],
        ];
    }
}

