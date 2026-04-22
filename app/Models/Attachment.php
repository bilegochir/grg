<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Attachment extends Model
{
    /** @use HasFactory<\Database\Factories\AttachmentFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'agency_id',
        'uploaded_by_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getHumanSizeAttribute(): string
    {
        $size = (int) $this->size;

        if ($size < 1024) {
            return "{$size} B";
        }

        if ($size < 1024 * 1024) {
            return number_format($size / 1024, 1).' KB';
        }

        return number_format($size / (1024 * 1024), 1).' MB';
    }
}
