<?php

namespace App\Http\Controllers;

use App\Models\VisaCase;
use App\Models\VisaFormTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class VisaFormTemplateController extends Controller
{
    /**
     * Store a new form template with uploaded PDF.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'visa_type_id'   => ['required', 'exists:visa_types,id'],
            'name'           => ['required', 'string', 'max:255'],
            'description'    => ['nullable', 'string', 'max:500'],
            'pdf'            => ['required', 'file', 'mimes:pdf', 'max:10240'],
            'field_mapping'  => ['nullable', 'array'],
        ]);

        $path = $request->file('pdf')->store('form-templates', 'public');

        VisaFormTemplate::create([
            'visa_type_id'      => $data['visa_type_id'],
            'name'              => $data['name'],
            'description'       => $data['description'] ?? null,
            'file_path'         => $path,
            'original_filename' => $request->file('pdf')->getClientOriginalName(),
            'field_mapping'     => $data['field_mapping'] ?? null,
            'created_by_user_id' => $request->user()->id,
        ]);

        return back()->with('success', 'Form template uploaded.');
    }

    /**
     * Update field mappings for a form template.
     */
    public function update(Request $request, VisaFormTemplate $formTemplate): RedirectResponse
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'description'   => ['nullable', 'string', 'max:500'],
            'field_mapping' => ['nullable', 'array'],
            'is_active'     => ['boolean'],
        ]);

        $formTemplate->update($data);

        return back()->with('success', 'Form template updated.');
    }

    /**
     * Delete a form template and its stored PDF.
     */
    public function destroy(VisaFormTemplate $formTemplate): RedirectResponse
    {
        Storage::disk('public')->delete($formTemplate->file_path);
        $formTemplate->delete();

        return back()->with('success', 'Form template deleted.');
    }

    /**
     * Generate a filled PDF for a given case and download it.
     */
    public function generate(Request $request, VisaCase $case, VisaFormTemplate $formTemplate): Response
    {
        // Load all data the mapping may reference
        $case->load(['applicant', 'visaType', 'country']);
        $business = \App\Models\BusinessSetting::current();

        $dataMap = [
            'applicant.first_name'        => $case->applicant->first_name ?? '',
            'applicant.last_name'         => $case->applicant->last_name ?? '',
            'applicant.email'             => $case->applicant->email ?? '',
            'applicant.phone'             => $case->applicant->phone ?? '',
            'applicant.date_of_birth'     => $case->applicant->date_of_birth?->format('Y-m-d') ?? '',
            'applicant.nationality'       => $case->applicant->nationality ?? '',
            'applicant.passport_number'   => $case->applicant->passport_number ?? '',
            'applicant.passport_expiry'   => $case->applicant->passport_expiry?->format('Y-m-d') ?? '',
            'applicant.address'           => $case->applicant->address ?? '',
            'case.reference_code'         => $case->reference_code ?? '',
            'case.visa_type'              => $case->visaType?->name ?? '',
            'case.country'                => $case->country?->name ?? '',
            'case.expected_submission_at' => $case->expected_submission_at?->format('Y-m-d') ?? '',
            'case.expected_decision_at'   => $case->expected_decision_at?->format('Y-m-d') ?? '',
            'business.name'               => $business->business_name ?? '',
            'business.email'              => $business->contact_email ?? '',
            'business.phone'              => $business->contact_phone ?? '',
            'business.address'            => $business->contact_address ?? '',
        ];

        $fieldMapping = $formTemplate->field_mapping ?? [];
        $pdfPath = Storage::disk('public')->path($formTemplate->file_path);

        // Build filled data array: PDF field name → value
        $fillData = [];
        foreach ($fieldMapping as $pdfField => $crmPath) {
            $fillData[$pdfField] = $dataMap[$crmPath] ?? '';
        }

        // Use pdftk if available, otherwise return the blank PDF with a warning header
        if (($pdftkPath = $this->pdftkPath()) && count($fillData)) {
            $fdfContent = $this->buildFdf($fillData);
            $fdfPath = tempnam(sys_get_temp_dir(), 'fdf_') . '.fdf';
            $outputPath = tempnam(sys_get_temp_dir(), 'pdf_') . '.pdf';

            file_put_contents($fdfPath, $fdfContent);

            exec("{$pdftkPath} " . escapeshellarg($pdfPath) . " fill_form " . escapeshellarg($fdfPath) . " output " . escapeshellarg($outputPath) . " flatten");

            $pdfContent = file_get_contents($outputPath);

            @unlink($fdfPath);
            @unlink($outputPath);
        } else {
            // Fallback: return the unfilled template
            $pdfContent = file_get_contents($pdfPath);
        }

        $filename = $case->reference_code . '_' . str($formTemplate->name)->slug() . '.pdf';

        return response($pdfContent, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function pdftkPath(): ?string
    {
        foreach (['/usr/bin/pdftk', '/usr/local/bin/pdftk', 'pdftk'] as $path) {
            if (@is_executable($path) || trim(shell_exec("which {$path} 2>/dev/null")) !== '') {
                return $path;
            }
        }
        return null;
    }

    private function buildFdf(array $fields): string
    {
        $fdf = "%FDF-1.2\n%âãÏÓ\n1 0 obj\n<< /FDF << /Fields [\n";
        foreach ($fields as $name => $value) {
            $escaped = addslashes($value);
            $fdf .= "<< /T ({$name}) /V ({$escaped}) >>\n";
        }
        $fdf .= "] >> >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n";
        return $fdf;
    }
}
