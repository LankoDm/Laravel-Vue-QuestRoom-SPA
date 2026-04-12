<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class DashboardController extends Controller
{
    protected DashboardService $dashboardService;

    /**
     * Inject the DashboardService.
     */
    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Retrieve aggregated statistics for the dashboard UI.
     */
    public function stats(): JsonResponse
    {
        $stats = $this->dashboardService->getStats();
        return response()->json($stats);
    }

    /**
     * Generate and download a PDF analytics report.
     */
    public function downloadPdfReport(): Response
    {
        $data = $this->dashboardService->getPdfReportData();

        $pdf = Pdf::loadView('pdf.admin_report', $data);

        return $pdf->download('report.pdf');
    }
}