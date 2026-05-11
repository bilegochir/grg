<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class BusinessSetting extends Model
{
    /** @use HasFactory<\Database\Factories\BusinessSettingFactory> */
    use HasFactory;

    protected $fillable = [
        'business_name',
        'logo_path',
        'contact_email',
        'contact_phone',
        'contact_address',
        'default_locale',
        'sms_provider',
        'sms_sender',
        'multi_branch_enabled',
        'multi_branch_ready',
    ];

    protected function casts(): array
    {
        return [
            'multi_branch_enabled' => 'boolean',
            'multi_branch_ready' => 'boolean',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'business_name' => 'Agency',
            'default_locale' => 'en',
            'sms_provider' => 'log',
            'multi_branch_enabled' => false,
            'multi_branch_ready' => true,
        ]);
    }

    public function logoUrl(): ?string
    {
        if ($this->logo_path === null) {
            return null;
        }

        return Storage::disk('public')->url($this->logo_path);
    }
}
