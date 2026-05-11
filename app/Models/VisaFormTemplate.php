<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class VisaFormTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'visa_type_id',
        'name',
        'description',
        'file_path',
        'original_filename',
        'field_mapping',
        'is_active',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'field_mapping' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function visaType(): BelongsTo
    {
        return $this->belongsTo(VisaType::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function fileUrl(): ?string
    {
        return $this->file_path ? Storage::disk('public')->url($this->file_path) : null;
    }

    /**
     * Available CRM field sources that can be mapped to PDF fields.
     * Format: dotted path understood by the PDF generation service.
     */
    public static function availableFields(): array
    {
        return [
            'applicant' => [
                'applicant.first_name'         => 'First Name',
                'applicant.last_name'          => 'Last Name',
                'applicant.email'              => 'Email Address',
                'applicant.phone'              => 'Phone Number',
                'applicant.date_of_birth'      => 'Date of Birth',
                'applicant.nationality'        => 'Nationality',
                'applicant.passport_number'    => 'Passport Number',
                'applicant.passport_expiry'    => 'Passport Expiry',
                'applicant.address'            => 'Address',
            ],
            'case' => [
                'case.reference_code'          => 'Case Reference',
                'case.visa_type'               => 'Visa Type Name',
                'case.country'                 => 'Destination Country',
                'case.expected_submission_at'  => 'Submission Date',
                'case.expected_decision_at'    => 'Expected Decision Date',
            ],
            'business' => [
                'business.name'               => 'Agency Name',
                'business.email'              => 'Agency Email',
                'business.phone'              => 'Agency Phone',
                'business.address'            => 'Agency Address',
            ],
        ];
    }
}
