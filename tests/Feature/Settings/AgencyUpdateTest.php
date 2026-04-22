<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AgencyUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_agency_settings_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('settings.agency.edit'));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/Agency')
            ->where('auth.agency.name', $user->agency?->name)
        );
    }

    public function test_agency_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->patch(route('settings.agency.update'), [
                'name' => 'North Star Migration',
                'email' => 'hello@northstar.test',
                'phone' => '+1 555 200 4000',
                'website' => 'https://northstar.test',
                'address' => 'Suite 12, 100 Peace Avenue',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('settings.agency.edit'));

        $agency = $user->refresh()->agency;

        $this->assertNotNull($agency);
        $this->assertSame('North Star Migration', $agency->name);
        $this->assertSame('hello@northstar.test', $agency->email);
        $this->assertSame('+1 555 200 4000', $agency->phone);
        $this->assertSame('https://northstar.test', $agency->website);
        $this->assertSame('Suite 12, 100 Peace Avenue', $agency->address);
    }
}
