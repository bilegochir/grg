<?php

namespace App\Http\Controllers;

use App\Models\CustomReport;
use App\Services\AnalyticsService;
use App\Services\ExportService;
use App\Services\ReportBuilder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AnalyticsController extends Controller
{
    protected AnalyticsService $analytics;
    protected ExportService $export;

    public function __construct(Request $request)
    {
        $this->analytics = new AnalyticsService($request->user());
        $this->export = new ExportService();
    }

    public function getCaseAnalytics(Request $request): JsonResponse
    {
        $from = $request->query('from') ? Carbon::parse($request->query('from')) : now()->subMonths(12);
        $to = $request->query('to') ? Carbon::parse($request->query('to')) : now();

        return response()->json([
            'completion_rate' => $this->analytics->getCaseCompletionRate($from, $to),
            'average_duration' => $this->analytics->getAverageCaseDuration($from, $to),
            'by_stage' => $this->analytics->getCasesByStageWithMetrics($from, $to),
            'by_priority' => $this->analytics->getCasesByPriority(),
            'by_agent' => $this->analytics->getCasesByAgent(),
            'by_country' => $this->analytics->getCasesByCountry(),
        ]);
    }

    public function getFinanceAnalytics(Request $request): JsonResponse
    {
        $from = $request->query('from') ? Carbon::parse($request->query('from')) : now()->startOfMonth();
        $to = $request->query('to') ? Carbon::parse($request->query('to')) : now()->endOfMonth();

        return response()->json([
            'metrics' => $this->analytics->getFinanceMetrics($from, $to),
            'revenue_by_month' => $this->analytics->getRevenueByMonth($from->copy()->subMonths(11), $to),
            'invoice_aging' => $this->analytics->getInvoiceAging(),
        ]);
    }

    public function getStaffAnalytics(Request $request): JsonResponse
    {
        $from = $request->query('from') ? Carbon::parse($request->query('from')) : now()->subMonths(1);
        $to = $request->query('to') ? Carbon::parse($request->query('to')) : now();

        return response()->json([
            'productivity' => $this->analytics->getStaffProductivity($from, $to),
        ]);
    }

    public function getLeadAnalytics(Request $request): JsonResponse
    {
        $from = $request->query('from') ? Carbon::parse($request->query('from')) : now()->subMonths(12);
        $to = $request->query('to') ? Carbon::parse($request->query('to')) : now();

        return response()->json([
            'funnel' => $this->analytics->getLeadConversionFunnel(),
            'conversion_rate' => $this->analytics->getLeadConversionRate($from, $to),
        ]);
    }

    public function buildReport(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:cases,finance,staff,leads',
            'filters' => 'nullable|array',
            'columns' => 'nullable|array',
            'group_by' => 'nullable|array',
            'sort_by' => 'nullable|array',
        ]);

        $builder = new ReportBuilder($request->user());
        $builder->setType($validated['type'])
            ->setFilters($validated['filters'] ?? [])
            ->setColumns($validated['columns'] ?? [])
            ->setGroupBy($validated['group_by'] ?? [])
            ->setSortBy($validated['sort_by'] ?? []);

        $data = $builder->build();

        return response()->json([
            'data' => $data,
            'count' => $data->count(),
        ]);
    }

    public function exportReport(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:cases,finance,staff,leads',
            'format' => 'required|in:csv,json',
            'filters' => 'nullable|array',
            'columns' => 'nullable|array',
            'group_by' => 'nullable|array',
        ]);

        $builder = new ReportBuilder($request->user());
        $builder->setType($validated['type'])
            ->setFilters($validated['filters'] ?? [])
            ->setColumns($validated['columns'] ?? [])
            ->setGroupBy($validated['group_by'] ?? []);

        $data = $builder->build();

        $filename = "{$validated['type']}_report_" . now()->format('Y-m-d_His');

        if ($validated['format'] === 'csv') {
            return $this->export->exportToCsv($data, $filename);
        }

        return $this->export->exportToJson($data, $filename);
    }

    public function listCustomReports(Request $request): JsonResponse
    {
        $reports = CustomReport::where('user_id', $request->user()->id)
            ->orderByDesc('is_favorite')
            ->orderByDesc('updated_at')
            ->get();

        return response()->json($reports);
    }

    public function storeCustomReport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:cases,finance,staff,leads',
            'filters' => 'nullable|array',
            'columns' => 'nullable|array',
            'group_by' => 'nullable|array',
            'sort_by' => 'nullable|array',
        ]);

        $report = CustomReport::create([
            'user_id' => $request->user()->id,
            ...$validated,
        ]);

        return response()->json($report, 201);
    }

    public function updateCustomReport(Request $request, CustomReport $report): JsonResponse
    {
        $this->authorize('update', $report);

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'filters' => 'nullable|array',
            'columns' => 'nullable|array',
            'group_by' => 'nullable|array',
            'sort_by' => 'nullable|array',
            'is_favorite' => 'nullable|boolean',
        ]);

        $report->update($validated);

        return response()->json($report);
    }

    public function deleteCustomReport(Request $request, CustomReport $report): JsonResponse
    {
        $this->authorize('delete', $report);

        $report->delete();

        return response()->json(null, 204);
    }

    public function runCustomReport(Request $request, CustomReport $report)
    {
        $this->authorize('view', $report);

        $builder = ReportBuilder::fromCustomReport($request->user(), $report);
        $data = $builder->build();

        if ($request->query('export')) {
            $format = $request->query('export');
            $filename = "{$report->name}_" . now()->format('Y-m-d_His');

            if ($format === 'csv') {
                return $this->export->exportToCsv($data, $filename);
            }

            return $this->export->exportToJson($data, $filename);
        }

        return response()->json([
            'report' => $report,
            'data' => $data,
            'count' => $data->count(),
        ]);
    }
}
