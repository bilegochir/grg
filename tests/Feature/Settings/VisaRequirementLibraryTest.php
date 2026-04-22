<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use App\Models\VisaRequirementItem;
use App\Models\VisaRequirementTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisaRequirementLibraryTest extends TestCase
{
    use RefreshDatabase;

    public function test_requirement_library_page_is_displayed(): void
    {
        $user = User::factory()->create();
        $template = VisaRequirementTemplate::factory()->create([
            'region' => 'Australia',
            'country_name' => 'Australia',
            'visa_type' => 'Student visa',
            'visa_code' => '500',
            'label' => 'Australia student visa checklist',
        ]);

        VisaRequirementItem::factory()->create([
            'visa_requirement_template_id' => $template->id,
            'label' => 'Passport',
            'sort_order' => 1,
        ]);
        VisaRequirementItem::factory()->create([
            'visa_requirement_template_id' => $template->id,
            'label' => 'Confirmation of enrolment',
            'sort_order' => 2,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/settings/visa-requirements');

        $response->assertOk();
        $response->assertSee('Australia');
        $response->assertSee('Student visa');
        $response->assertSee('500');
        $response->assertSee('Passport');
    }

    public function test_users_can_create_requirement_templates(): void
    {
        $user = User::factory()->create();

        $longRequirementLabel = 'Passport biodata page, previous travel history pages, and any page that includes a visa, stamp, or annotation';
        $longRequirementHelpText = 'Check that the passport is readable, signed where needed, and valid for the required period, then confirm all prior travel pages are included for review.';

        $response = $this
            ->actingAs($user)
            ->post('/settings/visa-requirements', [
                'region' => 'Australia',
                'country_name' => 'Australia',
                'visa_type' => 'Visitor visa',
                'visa_code' => '600',
                'requires_institution_name' => true,
                'description' => 'Starter checklist for short-stay visitors.',
                'source_url' => 'https://example.com/visitor',
                'source_checked_at' => now()->toDateString(),
                'processing_time_summary' => 'Check official processing portal.',
                'fee_summary' => 'Varies by stream.',
                'stay_summary' => 'Depends on grant conditions.',
                'requirements' => [
                    ['category' => 'Identity', 'label' => $longRequirementLabel, 'help_text' => $longRequirementHelpText, 'is_required' => true],
                    ['category' => 'Travel', 'label' => 'Travel itinerary', 'help_text' => 'Flight and hotel plan', 'is_required' => true],
                    ['category' => 'Financial', 'label' => 'Proof of funds', 'help_text' => null, 'is_required' => true],
                ],
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/settings/visa-requirements');

        $template = VisaRequirementTemplate::query()
            ->where('country_name', 'Australia')
            ->where('visa_type', 'Visitor visa')
            ->first();

        $this->assertNotNull($template);
        $this->assertSame('Australia', $template->region);
        $this->assertSame('600', $template->visa_code);
        $this->assertTrue($template->requires_institution_name);
        $this->assertSame('Starter checklist for short-stay visitors.', $template->description);
        $this->assertSame('https://example.com/visitor', $template->source_url);
        $this->assertSame(3, $template->items()->count());
        $this->assertSame($longRequirementHelpText, $template->items()->orderBy('sort_order')->first()?->help_text);
        $this->assertSame(
            [$longRequirementLabel, 'Travel itinerary', 'Proof of funds'],
            $template->items()->orderBy('sort_order')->pluck('label')->all(),
        );
    }

    public function test_users_can_mark_a_template_as_reviewed(): void
    {
        $user = User::factory()->create();
        $template = VisaRequirementTemplate::factory()->create([
            'source_checked_at' => now()->subMonth()->toDateString(),
        ]);

        $this->actingAs($user)
            ->patch(route('settings.visa-requirements.review', $template))
            ->assertRedirect('/settings/visa-requirements');

        $this->assertSame(now()->toDateString(), $template->fresh()->source_checked_at?->toDateString());
    }
}
