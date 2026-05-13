<?php

namespace Tests\Feature;

use App\Models\CustomReport;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\User;
use App\Models\VisaCase;
use App\Models\VisaCaseTask;
use App\Services\AnalyticsService;
use App\Services\ReportBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_analytics_service_calculates_case_completion_rate()
    {
        VisaCase::factory(10)->create(['assigned_to_user_id' => $this->user->id]);
        VisaCase::factory(5)->create(['assigned_to_user_id' => $this->user->id, 'closed_at' => now()]);

        $service = new AnalyticsService($this->user);
        $rate = $service->getCaseCompletionRate();

        $this->assertArrayHasKey('total', $rate);
        $this->assertArrayHasKey('closed', $rate);
        $this->assertArrayHasKey('rate', $rate);
    }

    public function test_analytics_service_calculates_average_case_duration()
    {
        VisaCase::factory()
            ->create([
                'assigned_to_user_id' => $this->user->id,
                'created_at' => now()->subDays(10),
                'closed_at' => now()
            ]);

        $service = new AnalyticsService($this->user);
        $duration = $service->getAverageCaseDuration();

        $this->assertArrayHasKey('average', $duration);
        $this->assertArrayHasKey('min', $duration);
        $this->assertArrayHasKey('max', $duration);
    }

    public function test_analytics_service_groups_cases_by_priority()
    {
        $service = new AnalyticsService($this->user);
        $priorities = $service->getCasesByPriority();

        $this->assertIsCollection($priorities);
        $this->assertTrue($priorities->count() > 0);
    }

    public function test_analytics_service_calculates_finance_metrics()
    {
        Invoice::factory(5)->create([
            'total' => 1000,
            'balance_due' => 500,
        ]);

        $service = new AnalyticsService($this->user);
        $metrics = $service->getFinanceMetrics();

        $this->assertArrayHasKey('total_invoiced', $metrics);
        $this->assertArrayHasKey('outstanding', $metrics);
        $this->assertArrayHasKey('invoice_count', $metrics);
    }

    public function test_analytics_service_calculates_invoice_aging()
    {
        Invoice::factory()->create([
            'balance_due' => 500,
            'due_at' => now()->subDays(5)
        ]);

        $service = new AnalyticsService($this->user);
        $aging = $service->getInvoiceAging();

        $this->assertArrayHasKey('overdue', $aging);
        $this->assertArrayHasKey('due_30', $aging);
        $this->assertArrayHasKey('due_60', $aging);
    }

    public function test_analytics_service_calculates_lead_conversion_rate()
    {
        Lead::factory(20)->create();
        Lead::factory(5)->create(['status' => 'converted']);

        $service = new AnalyticsService($this->user);
        $rate = $service->getLeadConversionRate();

        $this->assertArrayHasKey('total_leads', $rate);
        $this->assertArrayHasKey('converted', $rate);
        $this->assertArrayHasKey('conversion_rate', $rate);
        $this->assertTrue($rate['conversion_rate'] > 0);
    }

    public function test_report_builder_builds_case_report()
    {
        VisaCase::factory(5)->create(['assigned_to_user_id' => $this->user->id]);

        $builder = new ReportBuilder($this->user);
        $builder->setType('cases');
        $data = $builder->build();

        $this->assertIsCollection($data);
    }

    public function test_report_builder_builds_finance_report()
    {
        Invoice::factory(5)->create();

        $builder = new ReportBuilder($this->user);
        $builder->setType('finance');
        $data = $builder->build();

        $this->assertIsCollection($data);
    }

    public function test_report_builder_builds_staff_report()
    {
        $builder = new ReportBuilder($this->user);
        $builder->setType('staff');
        $data = $builder->build();

        $this->assertIsCollection($data);
    }

    public function test_report_builder_builds_leads_report()
    {
        Lead::factory(10)->create();

        $builder = new ReportBuilder($this->user);
        $builder->setType('leads');
        $data = $builder->build();

        $this->assertIsCollection($data);
    }

    public function test_custom_report_can_be_created()
    {
        $report = CustomReport::create([
            'user_id' => $this->user->id,
            'name' => 'Test Report',
            'description' => 'A test report',
            'type' => 'cases',
            'filters' => ['date_range' => ['from' => now()->subMonths(1)]],
            'columns' => ['name', 'total_cases'],
        ]);

        $this->assertDatabaseHas('custom_reports', [
            'user_id' => $this->user->id,
            'name' => 'Test Report',
        ]);
    }

    public function test_custom_report_can_be_retrieved()
    {
        $report = CustomReport::create([
            'user_id' => $this->user->id,
            'name' => 'Test Report',
            'type' => 'cases',
        ]);

        $retrieved = CustomReport::find($report->id);
        $this->assertEquals('Test Report', $retrieved->name);
    }

    public function test_custom_report_can_be_updated()
    {
        $report = CustomReport::create([
            'user_id' => $this->user->id,
            'name' => 'Test Report',
            'type' => 'cases',
        ]);

        $report->update(['is_favorite' => true]);

        $this->assertTrue($report->fresh()->is_favorite);
    }

    public function test_custom_report_can_be_deleted()
    {
        $report = CustomReport::create([
            'user_id' => $this->user->id,
            'name' => 'Test Report',
            'type' => 'cases',
        ]);

        $report->delete();

        $this->assertDatabaseMissing('custom_reports', ['id' => $report->id]);
    }

    public function test_api_endpoint_returns_case_analytics()
    {
        $this->actingAs($this->user);

        $response = $this->getJson('/api/analytics/cases');

        $response->assertOk();
        $response->assertJsonStructure([
            'completion_rate',
            'average_duration',
            'by_stage',
            'by_priority',
            'by_agent',
            'by_country',
        ]);
    }

    public function test_api_endpoint_returns_finance_analytics()
    {
        $this->actingAs($this->user);

        $response = $this->getJson('/api/analytics/finance');

        $response->assertOk();
        $response->assertJsonStructure([
            'metrics',
            'revenue_by_month',
            'invoice_aging',
        ]);
    }

    public function test_api_endpoint_builds_custom_report()
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/analytics/build-report', [
            'type' => 'cases',
            'filters' => [],
            'columns' => [],
            'group_by' => [],
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['data', 'count']);
    }

    public function test_api_endpoint_exports_report_csv()
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/analytics/export-report', [
            'type' => 'cases',
            'format' => 'csv',
        ]);

        $response->assertOk();
    }

    public function test_api_endpoint_creates_custom_report()
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/analytics/reports', [
            'name' => 'My Report',
            'description' => 'Test report',
            'type' => 'cases',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('custom_reports', [
            'user_id' => $this->user->id,
            'name' => 'My Report',
        ]);
    }

    public function test_api_endpoint_lists_custom_reports()
    {
        $this->actingAs($this->user);

        CustomReport::factory(3)->create(['user_id' => $this->user->id]);

        $response = $this->getJson('/api/analytics/reports');

        $response->assertOk();
        $response->assertJsonCount(3);
    }

    public function test_api_endpoint_deletes_custom_report()
    {
        $this->actingAs($this->user);

        $report = CustomReport::create([
            'user_id' => $this->user->id,
            'name' => 'Test Report',
            'type' => 'cases',
        ]);

        $response = $this->deleteJson("/api/analytics/reports/{$report->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('custom_reports', ['id' => $report->id]);
    }

    public function test_analytics_pages_are_accessible()
    {
        $this->actingAs($this->user);

        $this->get('/analytics')->assertOk();
        $this->get('/analytics/cases')->assertOk();
        $this->get('/analytics/finance')->assertOk();
        $this->get('/analytics/staff')->assertOk();
        $this->get('/analytics/leads')->assertOk();
        $this->get('/analytics/custom-reports')->assertOk();
    }

    public function test_user_cannot_delete_others_custom_report()
    {
        $other = User::factory()->create();
        $report = CustomReport::create([
            'user_id' => $other->id,
            'name' => 'Test Report',
            'type' => 'cases',
        ]);

        $this->actingAs($this->user);
        $response = $this->deleteJson("/api/analytics/reports/{$report->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('custom_reports', ['id' => $report->id]);
    }
}
