<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisaCaseDocumentVersion extends Model
{
    /** @use HasFactory<\Database\Factories\VisaCaseDocumentVersionFactory> */
    use HasFactory;

    protected $fillable = [
        'visa_case_document_id',
        'uploaded_by_user_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
        'version_number',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(VisaCaseDocument::class, 'visa_case_document_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }
}
