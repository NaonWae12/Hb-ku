<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormHeader extends Model
{
    protected $fillable = [
        'form_id',
        'image_path',
        'image_mode',
        'source',
    ];

    protected $casts = [
        'image_mode' => 'string',
        'source' => 'string',
    ];

    /**
     * Get the form that owns the header.
     */
    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }
}
