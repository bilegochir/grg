<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphedByMany;

class Tag extends Model
{
    /** @use HasFactory<\Database\Factories\TagFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'color',
    ];

    public function leads(): MorphedByMany
    {
        return $this->morphedByMany(Lead::class, 'taggable')->withTimestamps();
    }

    public function applicants(): MorphedByMany
    {
        return $this->morphedByMany(Applicant::class, 'taggable')->withTimestamps();
    }
}
