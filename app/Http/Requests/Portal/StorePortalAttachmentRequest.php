<?php

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class StorePortalAttachmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'attachment' => [
                'required',
                File::types(['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx'])
                    ->max(10 * 1024),
            ],
        ];
    }
}
