<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class VisaRequirementTemplate extends Model
{
    /** @use HasFactory<\Database\Factories\VisaRequirementTemplateFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'region',
        'country_name',
        'visa_type',
        'visa_code',
        'requires_institution_name',
        'label',
        'description',
        'source_url',
        'source_checked_at',
        'processing_time_summary',
        'fee_summary',
        'stay_summary',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'bool',
            'requires_institution_name' => 'bool',
            'source_checked_at' => 'date',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(VisaRequirementItem::class)->orderBy('sort_order');
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function scopeMatching(Builder $query, string $countryName, string $visaType): void
    {
        $query
            ->active()
            ->whereRaw('lower(country_name) = ?', [mb_strtolower(trim($countryName))])
            ->whereRaw('lower(visa_type) = ?', [mb_strtolower(trim($visaType))]);
    }

    public static function findActiveMatch(null|string $countryName, null|string $visaType): ?self
    {
        if (blank($countryName) || blank($visaType)) {
            return null;
        }

        return self::query()
            ->matching($countryName, $visaType)
            ->first();
    }

    public static function requiresInstitutionName(null|string $countryName, null|string $visaType): bool
    {
        return self::findActiveMatch($countryName, $visaType)?->requires_institution_name ?? false;
    }
}
