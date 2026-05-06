<?php

namespace App\Http\Controllers;


use App\Exports\FabricUsedPdfExport;
use App\Exports\MonthlySalesPdfExport;
use App\Service\DashboardService;
use DateTime;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function monthlySalesReport()
    {
        $startDate = request()->query('start_date');
        $endDate = request()->query('end_date');

        if (! $startDate || ! $endDate) {
            return response()->json(['error' => 'Start date and end date are required.'], 400);
        }

        $formattedStartDate = new DateTime($startDate);
        $formattedEndDate = new DateTime($endDate);

        return $this->dashboardService->getMonthlySalesReport($formattedStartDate, $formattedEndDate);
    }


    public function salesPerProductCategory()
    {

        $startDate = request()->query('start_date');
        $endDate = request()->query('end_date');

        if (! $startDate || ! $endDate) {
            return response()->json(['error' => 'Start date and end date are required.'], 400);
        }

        $formattedStartDate = new DateTime($startDate);
        $formattedEndDate = new DateTime($endDate);

        return $this->dashboardService->getSalesPerProductCategory($formattedStartDate, $formattedEndDate);
    }

    public function fabricUsed()
    {
        $startDate = request()->query('start_date');
        $endDate = request()->query('end_date');

        if (! $startDate || ! $endDate) {
            return response()->json(['error' => 'Start date and end date are required.'], 400);
        }

        $formattedStartDate = new DateTime($startDate);
        $formattedEndDate = new DateTime($endDate);

        return $this->dashboardService->getFabricUsed($formattedStartDate, $formattedEndDate);
    }

    public function exportMonthlySales()
    {
        $startDate = request()->query('start_date');
        $endDate = request()->query('end_date');

        if (! $startDate || ! $endDate) {
            return response()->json(['error' => 'Start date and end date are required.'], 400);
        }

        $formattedStartDate = new DateTime($startDate);
        $formattedEndDate = new DateTime($endDate);

        try {
            $pdf = $this->dashboardService->getPerOrderSalesPDFReport($formattedStartDate, $formattedEndDate);

            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->output();
            }, 'monthly_sales_report.pdf', [
                'Content-Type' => 'application/pdf',
            ]);
        } catch (\Exception $e) {
            Log::error('PDF Generation Error: ', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return response()->json(['error' => 'Failed to generate PDF report: '.$e->getMessage()], 500);
        }
    }

    public function downloadMonthlySalesReport()
    {
        $startDate = request()->query('start_date');
        $endDate = request()->query('end_date');

        if (! $startDate || ! $endDate) {
            return response()->json(['error' => 'Start date and end date are required.'], 400);
        }

        $formattedStartDate = new DateTime($startDate);
        $formattedEndDate = new DateTime($endDate);

        try {
            // Generate PDF report
            $pdfExport = new MonthlySalesPdfExport($formattedStartDate, $formattedEndDate);
            $pdf = $pdfExport->generate();

            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->output();
            }, 'monthly_sales_report.pdf', [
                'Content-Type' => 'application/pdf',
            ]);
        } catch (\Exception $e) {
            Log::error('PDF Generation Error: ', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return response()->json(['error' => 'Failed to generate PDF report: '.$e->getMessage()], 500);
        }
    }

    public function downloadFabricUsed()
    {
        $startDate = request()->query('start_date');
        $endDate = request()->query('end_date');

        if (! $startDate || ! $endDate) {
            return response()->json(['error' => 'Start date and end date are required.'], 400);
        }

        $formattedStartDate = new DateTime($startDate);
        $formattedEndDate = new DateTime($endDate);

        try {
            // Generate PDF report
            $pdfExport = new FabricUsedPdfExport($formattedStartDate, $formattedEndDate);
            $pdf = $pdfExport->generate();

            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->output();
            }, 'fabric_used_report.pdf', [
                'Content-Type' => 'application/pdf',
            ]);
        } catch (\Exception $e) {
            Log::error('PDF Generation Error: ', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return response()->json(['error' => 'Failed to generate PDF report: '.$e->getMessage()], 500);
        }
    }

    public function cardAnalytics()
    {
        $startDate = request()->query('start_date');
        $endDate = request()->query('end_date');

        // Validate date format (optional: you can use Laravel validation for stricter checks)
        if (! $startDate || ! $endDate) {
            return response()->json(['error' => 'Start date and end date are required.'], 400);
        }

        $formattedStartDate = new DateTime($startDate);
        $formattedEndDate = new DateTime($endDate);

        // Pass the dates to the service method (we assume you may want to use filter on created_at)
        $totalSales = $this->dashboardService->getTotalSalesWithRange($formattedStartDate, $formattedEndDate);
        $totalCustomers = $this->dashboardService->getTotalCustomersWithRange($formattedStartDate, $formattedEndDate);

        $totalPendingOrders = $this->dashboardService->countPendingOrdersWithRange($formattedStartDate, $formattedEndDate);
        $totalCompletedOrders = $this->dashboardService->countCompletedOrdersWithRange($formattedStartDate, $formattedEndDate);

        return response()->json([
            'total_sales' => $totalSales,
            'total_customers' => $totalCustomers,
            'total_pending_orders' => $totalPendingOrders,
            'total_completed_orders' => $totalCompletedOrders,
        ]);
    }

    public function latestOrders()
    {
        return $this->dashboardService->getLatestOrder();
    }
}
