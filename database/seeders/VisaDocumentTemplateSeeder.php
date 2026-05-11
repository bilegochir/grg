<?php

namespace Database\Seeders;

use App\Models\DocumentTemplate;
use App\Models\VisaType;
use Illuminate\Database\Seeder;

class VisaDocumentTemplateSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->catalog() as $entry) {
            $visaType = VisaType::query()
                ->whereHas('country', fn ($query) => $query->where('slug', $entry['country']))
                ->where('official_subclass', $entry['subclass'])
                ->first();

            if ($visaType === null) {
                continue;
            }

            foreach ($entry['templates'] as $index => $template) {
                DocumentTemplate::query()->updateOrCreate(
                    [
                        'visa_type_id' => $visaType->id,
                        'slug' => $template['slug'],
                    ],
                    [
                        'name' => $template['name'],
                        'description' => $template['description'],
                        'category' => $template['category'],
                        'client_instructions' => $template['client_instructions'],
                        'agent_guidance' => $template['agent_guidance'],
                        'sample_hint' => $template['sample_hint'],
                        'accepted_file_types' => $template['accepted_file_types'],
                        'max_files' => $template['max_files'],
                        'max_file_size_mb' => $template['max_file_size_mb'],
                        'due_days' => $template['due_days'],
                        'is_repeatable' => $template['is_repeatable'],
                        'position' => $template['position'] ?? $index + 1,
                        'is_required' => $template['is_required'],
                        'tracks_expiry' => $template['tracks_expiry'],
                    ],
                );
            }
        }
    }

    private function catalog(): array
    {
        return [
            $this->visa('australia', '600', [
                $this->doc('passport-copy', 'Passport copy', 'Identity', 'Color copy of the bio page and any pages with active visas or travel stamps relevant to the trip.', 'Check passport validity, identity details, and any prior travel patterns relevant to visitor intent.', 'One clear PDF with all relevant pages.', true, true),
                $this->doc('travel-itinerary', 'Travel itinerary', 'Travel', 'Provide intended travel dates, flights if booked, and accommodation or host information.', 'Use this to test the coherence of the travel purpose and timeline.', 'Flight hold, hotel booking, or invitation letter.', true),
                $this->doc('financial-evidence', 'Financial evidence', 'Finance', 'Upload recent bank statements or sponsor funding proof showing you can cover the visit.', 'Review balance trends and whether the funds genuinely support the stated trip.', 'Recent 3 to 6 months of statements.', true),
                $this->doc('employment-or-ties', 'Home country ties', 'Background', 'Show employment, study, business, or family ties demonstrating your intention to leave Australia after the visit.', 'Temporary stay credibility is often strongest when ties are well documented.', 'Employer letter, school letter, or business registration.', true),
            ]),
            $this->visa('australia', '500', [
                $this->doc('passport-copy', 'Passport copy', 'Identity', 'Upload the passport bio page and any previous visa pages relevant to your study history.', 'Check passport validity and name consistency against enrolment records.', 'Color PDF preferred.', true, true),
                $this->doc('coe', 'Confirmation of Enrolment', 'Study', 'Provide the official Confirmation of Enrolment issued by the Australian institution.', 'Must match provider, course, and dates used in the visa strategy.', 'Single PDF from the provider portal.', true),
                $this->doc('financial-capacity', 'Financial capacity evidence', 'Finance', 'Upload bank statements, loan letters, scholarship letters, or sponsor documents showing tuition and living cost coverage.', 'Check coverage, document freshness, and whether sponsor evidence is internally consistent.', 'Combine related finance evidence into one file where possible.', true),
                $this->doc('genuine-student', 'Genuine student statement', 'Background', 'Provide a personal statement explaining study plans, education history, and why Australia and this course make sense.', 'This should align with academic background and future plans.', 'Signed PDF is fine.', true),
                $this->doc('oshc', 'Health insurance (OSHC)', 'Compliance', 'Upload Overseas Student Health Cover evidence for the relevant study period.', 'Check dates line up with course duration and arrival buffer.', 'Certificate or policy schedule.', true),
            ]),
            $this->visa('australia', '482', [
                $this->doc('passport-copy', 'Passport copy', 'Identity', 'Upload the passport bio page and relevant prior visa history.', 'Confirm identity and passport validity beyond the intended work period.', 'Color PDF preferred.', true, true),
                $this->doc('nomination-and-sponsorship', 'Sponsorship and nomination records', 'Employment', 'Provide sponsor and nomination documents or reference details used for the work case.', 'Verify the sponsoring entity and job details are consistent across all filings.', 'Upload approval or filing records.', true),
                $this->doc('employment-contract', 'Employment contract', 'Employment', 'Upload the signed employment contract or formal offer showing role, salary, and conditions.', 'Salary and job title should align with the nominated occupation and stream.', 'Signed PDF preferred.', true),
                $this->doc('skills-evidence', 'Skills and experience evidence', 'Qualifications', 'Provide CV, reference letters, qualifications, licences, and any skills assessment documents if applicable.', 'Check the evidence covers the claimed experience window without unexplained gaps.', 'Chronological ordering helps.', true),
                $this->doc('police-clearance', 'Police clearance', 'Compliance', 'Upload required police certificates for relevant countries of residence.', 'Check issue dates and whether translation is needed.', 'Original plus translation if applicable.', false, false, true),
            ]),
            $this->visa('australia', '820', [
                $this->doc('identity-documents', 'Identity documents', 'Identity', 'Upload passports, birth certificates, and any name change records for both partners if relevant.', 'Names and dates should align across all relationship evidence.', 'Merge by person if helpful.', true, true),
                $this->doc('relationship-evidence', 'Relationship evidence', 'Relationship', 'Provide evidence of the genuine and continuing relationship, such as shared finances, cohabitation, and social recognition.', 'Aim for balanced evidence across financial, household, social, and commitment factors.', 'Group by evidence type.', true),
                $this->doc('sponsor-documents', 'Sponsor documents', 'Relationship', 'Upload sponsor status documents, declarations, and any required sponsorship forms.', 'Check sponsor eligibility and identity early.', 'Signed PDFs preferred.', true),
                $this->doc('police-clearance', 'Police clearance', 'Compliance', 'Upload police certificates for the applicant and sponsor where required.', 'Confirm validity window and translation needs.', 'Original plus translation if needed.', false, false, true),
                $this->doc('medicals', 'Medical examination records', 'Compliance', 'Upload health examination references or resulting documents where requested.', 'Track whether the applicant has completed the correct panel process.', 'Use the official reference sheet if issued.', false),
            ]),
            $this->visa('south-korea', 'D-2', [
                $this->doc('passport-copy', 'Passport copy', 'Identity', 'Upload the passport bio page and any prior Korean visa pages.', 'Check identity details and passport validity across the study period.', 'Color PDF preferred.', true, true),
                $this->doc('admission-letter', 'Admission documents', 'Study', 'Provide the university admission letter and any visa issuance confirmation from the institution.', 'School name and course dates should match the planned stay.', 'Single PDF from the institution.', true),
                $this->doc('financial-proof', 'Financial proof', 'Finance', 'Upload bank statements or sponsor support evidence showing funds for tuition and living costs.', 'Review the balance level and whether the funds are credible for the full first year.', 'Recent official statements.', true),
                $this->doc('academic-records', 'Academic records', 'Qualifications', 'Provide transcripts, diplomas, and other academic background records used for admission.', 'Use consistent translations where required.', 'Certified copies if applicable.', true),
            ]),
            $this->visa('south-korea', 'E-7', [
                $this->doc('passport-copy', 'Passport copy', 'Identity', 'Upload the passport bio page and relevant immigration history.', 'Check passport validity and identity consistency.', 'Color PDF preferred.', true, true),
                $this->doc('employment-contract', 'Employment contract', 'Employment', 'Provide the Korean employment contract or appointment letter.', 'Role title and duties should support the E-7 eligibility case.', 'Signed PDF preferred.', true),
                $this->doc('employer-support', 'Employer support documents', 'Employment', 'Upload sponsor company documents and any visa issuance support materials.', 'Confirm the company details line up with the filing record.', 'Merge company evidence into one file.', true),
                $this->doc('qualifications', 'Qualifications and experience', 'Qualifications', 'Provide CV, degrees, licences, and reference letters proving suitability for the specialty role.', 'Check chronology and occupation relevance.', 'Combine into one ordered PDF.', true),
            ]),
            $this->visa('south-korea', 'F-6', [
                $this->doc('identity-documents', 'Identity documents', 'Identity', 'Upload passport, civil status, and any name change records relevant to the family case.', 'Names should match marriage and sponsor records exactly.', 'Color scans preferred.', true, true),
                $this->doc('marriage-evidence', 'Marriage and relationship evidence', 'Relationship', 'Provide marriage records and supporting evidence that the relationship is genuine.', 'Flag weak documentary continuity early.', 'Include certified translations if needed.', true),
                $this->doc('sponsor-documents', 'Sponsor documents', 'Relationship', 'Upload the Korean spouse or sponsor identity and residence records plus support documents.', 'Check sponsor status and current residence details.', 'Single merged PDF is ideal.', true),
                $this->doc('financial-support', 'Financial support evidence', 'Finance', 'Provide income, tax, or savings evidence showing household support capacity where required.', 'Review whether the household financial documents are current and complete.', 'Latest official records preferred.', true),
            ]),
            $this->visa('ireland', 'D Study', [
                $this->doc('passport-copy', 'Passport copy', 'Identity', 'Upload the passport bio page and any relevant prior Irish or UK travel history.', 'Check identity and validity across the intended study period.', 'Color PDF preferred.', true, true),
                $this->doc('enrolment-letter', 'Enrolment letter', 'Study', 'Provide the official letter of enrolment from the Irish institution.', 'Course name, duration, and provider should match the visa plan.', 'Single PDF from the institution.', true),
                $this->doc('fees-and-funds', 'Fees and funds evidence', 'Finance', 'Upload fee payment receipts and financial evidence for living costs.', 'Check whether the funds meet the route expectation and are accessible.', 'Recent bank or sponsor documents.', true),
                $this->doc('medical-insurance', 'Private medical insurance', 'Compliance', 'Provide required private medical insurance evidence for the study stay.', 'Dates should align with arrival and registration timing.', 'Policy schedule or official letter.', true),
            ]),
            $this->visa('ireland', 'D Employment', [
                $this->doc('passport-copy', 'Passport copy', 'Identity', 'Upload the passport bio page and any prior residence permissions relevant to the application.', 'Check passport validity and identity consistency.', 'Color PDF preferred.', true, true),
                $this->doc('employment-permit', 'Employment permit', 'Employment', 'Provide the issued Irish employment permit or equivalent permission record.', 'This is a core eligibility document for the route.', 'Official PDF only.', true),
                $this->doc('employment-contract', 'Employment contract', 'Employment', 'Upload the signed employment contract or formal offer letter.', 'Confirm salary, role, and employer match the permit.', 'Signed PDF preferred.', true),
                $this->doc('employer-documents', 'Employer support documents', 'Employment', 'Upload supporting company documents or sponsor letters used for the case.', 'Use these to reconcile the permit, role, and host entity.', 'Merge into one file.', true),
            ]),
            $this->visa('ireland', 'D Join Family', [
                $this->doc('identity-documents', 'Identity documents', 'Identity', 'Upload passport and civil identity documents for the applicant and sponsor where relevant.', 'Names and civil status details must be consistent.', 'Color scans preferred.', true, true),
                $this->doc('relationship-evidence', 'Relationship evidence', 'Relationship', 'Provide civil records and supporting evidence of the qualifying family relationship.', 'Check whether the relationship proof is sufficient for the exact sponsorship basis.', 'Include certified translations if needed.', true),
                $this->doc('sponsor-status', 'Sponsor status documents', 'Relationship', 'Upload the sponsor’s Irish status and residence evidence.', 'Sponsor eligibility should be clear before deeper review.', 'Residence card, passport, or Irish status evidence.', true),
                $this->doc('financial-support', 'Financial support evidence', 'Finance', 'Provide income or financial records supporting sponsorship where relevant.', 'Review against route-specific sponsor expectations.', 'Recent statements or employment records.', true),
            ]),
            $this->visa('united-kingdom', 'Standard Visitor', [
                $this->doc('passport-copy', 'Passport copy', 'Identity', 'Upload the passport bio page and relevant travel history pages.', 'Check identity details and prior UK travel patterns.', 'Color PDF preferred.', true, true),
                $this->doc('trip-details', 'Trip details', 'Travel', 'Provide travel dates, accommodation details, and the purpose of the visit.', 'Use this to test the coherence of the visit plan.', 'Flight hold, hotel, or host invitation.', true),
                $this->doc('financial-evidence', 'Financial evidence', 'Finance', 'Upload bank statements or sponsor funding proof for the trip.', 'Assess whether the funds are sufficient and consistent with the planned visit.', 'Recent official statements.', true),
                $this->doc('home-country-ties', 'Home country ties', 'Background', 'Provide employment, business, study, or family tie evidence showing you will leave the UK after the visit.', 'This is often key for visitor credibility.', 'Employer letter or equivalent proof.', true),
            ]),
            $this->visa('united-kingdom', 'Student', [
                $this->doc('passport-copy', 'Passport copy', 'Identity', 'Upload the passport bio page and relevant prior UK immigration pages.', 'Check identity consistency across CAS and financial documents.', 'Color PDF preferred.', true, true),
                $this->doc('cas', 'CAS and offer documents', 'Study', 'Provide the Confirmation of Acceptance for Studies and any linked offer records.', 'Course, sponsor, and dates must match the application strategy.', 'Single merged PDF.', true),
                $this->doc('financial-evidence', 'Financial evidence', 'Finance', 'Upload statements or sponsor evidence showing tuition and maintenance funds.', 'Check the held period and whether the source is acceptable.', 'Recent statements only.', true),
                $this->doc('english-evidence', 'English language evidence', 'Qualifications', 'Provide test results or other accepted proof of English where needed.', 'Make sure the evidence matches the route and sponsor basis.', 'Official report only.', true),
            ]),
            $this->visa('united-kingdom', 'Skilled Worker', [
                $this->doc('passport-copy', 'Passport copy', 'Identity', 'Upload the passport bio page and relevant prior UK immigration history.', 'Check identity, validity, and prior status continuity.', 'Color PDF preferred.', true, true),
                $this->doc('certificate-of-sponsorship', 'Certificate of sponsorship', 'Employment', 'Provide the CoS reference details and any related sponsor paperwork.', 'Check sponsor, occupation code, and salary carefully.', 'PDF or official confirmation record.', true),
                $this->doc('job-offer', 'Job offer and contract', 'Employment', 'Upload the job offer or contract showing role, salary, and start date.', 'Cross-check against CoS and route salary rules.', 'Signed PDF preferred.', true),
                $this->doc('english-evidence', 'English language evidence', 'Qualifications', 'Provide accepted proof of English language ability where required.', 'Confirm the route exemption or evidence basis.', 'Official result or reference document.', true),
            ]),
            $this->visa('united-states', 'B-1 / B-2', [
                $this->doc('passport-copy', 'Passport copy', 'Identity', 'Upload the passport bio page and prior U.S. travel history if relevant.', 'Check identity details and previous compliance patterns.', 'Color PDF preferred.', true, true),
                $this->doc('ds160-and-appointment', 'DS-160 and appointment record', 'Application', 'Provide the DS-160 confirmation and visa interview appointment details.', 'Use this to verify the exact post and application track.', 'Confirmation pages only.', true),
                $this->doc('purpose-and-itinerary', 'Purpose of trip and itinerary', 'Travel', 'Upload a short explanation of the visit plus travel or host details.', 'The purpose should fit B visitor rules and match the interview prep.', 'Invitation or itinerary summary.', true),
                $this->doc('financial-and-ties', 'Financial evidence and home ties', 'Finance', 'Provide recent financial evidence and documents showing strong ties outside the United States.', 'These documents help support nonimmigrant intent.', 'Recent statements and employer/school proof.', true),
            ]),
            $this->visa('united-states', 'F-1', [
                $this->doc('passport-copy', 'Passport copy', 'Identity', 'Upload the passport bio page and any previous U.S. visa pages.', 'Check identity and previous status history.', 'Color PDF preferred.', true, true),
                $this->doc('i20', 'Form I-20', 'Study', 'Provide the issued Form I-20 from the U.S. school.', 'School, SEVIS details, and program dates should match the filing plan.', 'Signed PDF preferred.', true),
                $this->doc('sevis-and-ds160', 'SEVIS and DS-160 records', 'Application', 'Upload SEVIS payment proof and the DS-160 confirmation page.', 'These should align with the same school and applicant identity.', 'Confirmation pages only.', true),
                $this->doc('financial-evidence', 'Financial evidence', 'Finance', 'Provide statements or sponsor support proving ability to fund tuition and living costs.', 'Check coverage for at least the first academic year.', 'Recent official statements.', true),
            ]),
            $this->visa('united-states', 'H-1B', [
                $this->doc('passport-copy', 'Passport copy', 'Identity', 'Upload the passport bio page and relevant prior U.S. status history.', 'Check passport validity and any prior petition-based status alignment.', 'Color PDF preferred.', true, true),
                $this->doc('petition-package', 'Petition and approval records', 'Employment', 'Provide the approved petition notice and key support documents used in the work case.', 'Role, employer, and classification should be consistent across all records.', 'Merge into one ordered PDF.', true),
                $this->doc('employment-letter', 'Employment letter', 'Employment', 'Upload the current employer letter or contract confirming position and terms.', 'Review against the petition and consular application.', 'Signed PDF preferred.', true),
                $this->doc('qualifications', 'Qualifications evidence', 'Qualifications', 'Provide degrees, transcripts, evaluations, licences, and CV relevant to the specialty occupation.', 'Make sure the degree path supports the offered role.', 'Combine into one file.', true),
            ]),
            $this->visa('united-states', 'CR1 / IR1', [
                $this->doc('civil-documents', 'Civil documents', 'Identity', 'Upload passports, birth certificates, marriage certificate, and any prior divorce or name change records.', 'Civil records should be complete before interview preparation.', 'Scan all official records clearly.', true, true),
                $this->doc('petition-and-nvc', 'Petition and NVC records', 'Application', 'Provide I-130 approval, NVC notices, and immigrant visa case records.', 'Use these to confirm current case stage and interview readiness.', 'Single merged PDF is helpful.', true),
                $this->doc('relationship-evidence', 'Relationship evidence', 'Relationship', 'Upload proof the marriage is genuine and continuing.', 'Focus on high-quality evidence rather than volume alone.', 'Group by timeline or evidence type.', true),
                $this->doc('affidavit-of-support', 'Financial sponsorship evidence', 'Finance', 'Provide the affidavit of support and supporting income or tax evidence.', 'Check sponsor eligibility and whether any joint sponsor is needed.', 'Most recent official tax records preferred.', true),
            ]),
        ];
    }

    private function visa(string $country, string $subclass, array $templates): array
    {
        return [
            'country' => $country,
            'subclass' => $subclass,
            'templates' => $templates,
        ];
    }

    private function doc(
        string $slug,
        string $name,
        string $category,
        string $clientInstructions,
        string $agentGuidance,
        string $sampleHint,
        bool $isRequired,
        bool $tracksExpiry = false,
        bool $isRepeatable = false,
    ): array {
        return [
            'slug' => $slug,
            'name' => $name,
            'description' => $clientInstructions,
            'category' => $category,
            'client_instructions' => $clientInstructions,
            'agent_guidance' => $agentGuidance,
            'sample_hint' => $sampleHint,
            'accepted_file_types' => ['pdf', 'jpg', 'jpeg', 'png'],
            'max_files' => $isRepeatable ? 5 : 1,
            'max_file_size_mb' => 15,
            'due_days' => 5,
            'is_repeatable' => $isRepeatable,
            'is_required' => $isRequired,
            'tracks_expiry' => $tracksExpiry,
        ];
    }
}
