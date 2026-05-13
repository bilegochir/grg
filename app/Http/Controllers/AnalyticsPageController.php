<?php

namespace App\Http\Controllers;

use App\Services\AnalyticsService;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;

class AnalyticsPageController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Analytics/Index');
    }

    public function cases(Request $request): Response
    {
        $analytics = new AnalyticsService($request->user());

        return Inertia::render('Analytics/Cases', [
            'completionRate' => $analytics->getCaseCompletionRate(),
            'averageDuration' => $analytics->getAverageCaseDuration(),
            'byStage' => $analytics->getCasesByStageWithMetrics(),
            'byPriority' => $analytics->getCasesByPriority(),
            'byAgent' => $analytics->getCasesByAgent(),
            'byCountry' => $analytics->getCasesByCountry(),
        ]);
    }

    public function finance(Request $request): Response
    {
        $analytics = new AnalyticsService($request->user());

        return Inertia::render('Analytics/Finance', [
            'metrics' => $analytics->getFinanceMetrics(),
            'revenueByMonth' => $analytics->getRevenueByMonth(),
            'invoiceAging' => $analytics->getInvoiceAging(),
        ]);
    }

    public function staff(Request $request): Response
    {
        $analytics = new AnalyticsService($request->user());

        return Inertia::render('Analytics/Staff', [
            'productivity' => $analytics->getStaffProductivity(),
        ]);
    }

    public function leads(Request $request): Response
    {
        $analytics = new AnalyticsService($request->user());

        return Inertia::render('Analytics/Leads', [
            'funnel' => $analytics->getLeadConversionFunnel(),
            'conversionRate' => $analytics->getLeadConversionRate(),
        ]);
    }

    public function customReports(Request $request): Response
    {
        return Inertia::render('Analytics/CustomReports');
    }
}
