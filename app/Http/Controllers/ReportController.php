<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportFilterRequest;
use App\Services\Budget\BudgetOverviewService;
use App\Services\Report\ReportService;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService,
        private readonly BudgetOverviewService $budgetOverviewService,
    ) {}

    public function index(ReportFilterRequest $request): View
    {
        $startDate = $request->getStartDate()->toDateString();
        $endDate = $request->getEndDate()->toDateString();
        $userId = $request->user()->id;

        $report = $this->reportService->generateReport($startDate, $endDate, $userId);
        $start = $request->getStartDate();
        $end = $request->getEndDate();
        $budgetSummary = $start->isSameMonth($end)
            ? $this->budgetOverviewService->forMonth($request->user(), $start)
            : null;

        return view('report.index', [
            'report' => $report->toArray(),
            'start_date' => $request->getStartDate()->format('Y-m-d'),
            'end_date' => $request->getEndDate()->format('Y-m-d'),
            'budgetSummary' => $budgetSummary,
        ]);
    }
}
