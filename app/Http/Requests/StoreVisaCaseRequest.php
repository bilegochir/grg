<?php

namespace App\Http\Requests;

use App\Enums\VisaCaseStatus;
use App\Models\Client;
use App\Models\User;
use App\Models\VisaRequirementTemplate;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVisaCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        $agencyId = $this->user()?->agency_id;
        $destinationCountry = trim((string) $this->input('destination_country'));

        return [
            'client_id' => [
                'required',
                Rule::exists(Client::class, 'id')->where(
                    fn (Builder $query): Builder => $query->where('agency_id', $agencyId),
                ),
            ],
            'assigned_user_id' => [
                'nullable',
                Rule::exists(User::class, 'id')->where(
                    fn (Builder $query): Builder => $query->where('agency_id', $agencyId),
                ),
            ],
            'visa_type' => [
                'required',
                'string',
                'max:255',
                Rule::exists(VisaRequirementTemplate::class, 'visa_type')->where(
                    fn (Builder $query): Builder => $query
                        ->where('is_active', true)
                        ->where('country_name', $destinationCountry),
                ),
            ],
            'destination_country' => [
                'required',
                'string',
                'max:255',
                Rule::exists(VisaRequirementTemplate::class, 'country_name')->where(
                    fn (Builder $query): Builder => $query->where('is_active', true),
                ),
            ],
            'institution_name' => [
                Rule::requiredIf($this->requiresInstitutionName()),
                'nullable',
                'string',
                'max:255',
            ],
            'status' => ['required', Rule::enum(VisaCaseStatus::class)],
            'submitted_at' => ['nullable', 'date'],
            'decision_at' => ['nullable', 'date', 'after_or_equal:submitted_at'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'visa_type' => trim((string) $this->input('visa_type')),
            'destination_country' => trim((string) $this->input('destination_country')),
            'institution_name' => trim((string) $this->input('institution_name')),
        ]);
    }

    private function requiresInstitutionName(): bool
    {
        return VisaRequirementTemplate::requiresInstitutionName(
            $this->input('destination_country'),
            $this->input('visa_type'),
        );
    }
}
