<?php

namespace App\Http\Requests;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Client;
use App\Models\User;
use App\Models\VisaCase;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreTaskRequest extends FormRequest
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

        return [
            'client_id' => [
                'nullable',
                Rule::exists(Client::class, 'id')->where(
                    fn (Builder $query): Builder => $query->where('agency_id', $agencyId),
                ),
            ],
            'visa_case_id' => [
                'nullable',
                Rule::exists(VisaCase::class, 'id')->where(
                    fn (Builder $query): Builder => $query->where('agency_id', $agencyId),
                ),
            ],
            'assigned_user_id' => [
                'nullable',
                Rule::exists(User::class, 'id')->where(
                    fn (Builder $query): Builder => $query->where('agency_id', $agencyId),
                ),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::enum(TaskStatus::class)],
            'priority' => ['required', Rule::enum(TaskPriority::class)],
            'due_at' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $clientId = $this->integer('client_id');
                $visaCaseId = $this->integer('visa_case_id');

                if ($clientId === 0 || $visaCaseId === 0) {
                    return;
                }

                $visaCase = VisaCase::query()->find($visaCaseId);

                if ($visaCase !== null && $visaCase->client_id !== $clientId) {
                    $validator->errors()->add('visa_case_id', 'The selected case does not belong to the selected client.');
                }
            },
        ];
    }
}
