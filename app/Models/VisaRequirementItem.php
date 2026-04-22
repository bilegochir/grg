<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class VisaRequirementItem extends Model
{
    /** @use HasFactory<\Database\Factories\VisaRequirementItemFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'visa_requirement_template_id',
        'category',
        'label',
        'help_text',
        'is_required',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_required' => 'bool',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(VisaRequirementTemplate::class, 'visa_requirement_template_id');
    }

    public function caseRequirements(): HasMany
    {
        return $this->hasMany(VisaCaseRequirement::class);
    }
}
