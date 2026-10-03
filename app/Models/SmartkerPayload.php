<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmartkerPayload extends Model
{
    protected $table = 'smartker_payloads';

    protected $guarded = ['id'];

    protected $casts = [
        'attributes'  => 'array',
        'live'        => 'boolean',
        'sent'        => 'boolean',
        'http_status' => 'integer',
        'file_count'  => 'integer',
    ];

    public function comprobacion(): BelongsTo
    {
        return $this->belongsTo(Comprobacion::class);
    }
}
