<?php

namespace App\Http\Requests;

use App\Models\VisaCaseDocument;
use Illuminate\Foundation\Http\FormRequest;

class UploadVisaCaseDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var VisaCaseDocument|null $document */
        $document = $this->route('document');

        $rules = ['required', 'file', 'max:'.(($document?->max_file_size_mb ?? 20) * 1024)];

        if (($document?->accepted_file_types ?? []) !== []) {
            $rules[] = 'mimes:'.implode(',', $document->accepted_file_types);
        }

        return [
            'file' => $rules,
        ];
    }
}
