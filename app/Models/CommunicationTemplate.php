<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommunicationTemplate extends Model
{
    /** @use HasFactory<\Database\Factories\CommunicationTemplateFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'key',
        'channel',
        'locale',
        'subject',
        'body',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
