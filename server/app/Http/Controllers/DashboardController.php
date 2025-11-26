<?php

namespace App\Http\Controllers;

use App\Exports\FabricUsedExport;
use App\Exports\SalesReportExport;
use App\Service\DashboardService;
use DateTime;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

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

        if (!$startDate || !$endDate) {
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

        if (!$startDate || !$endDate) {
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

        if (!$startDate || !$endDate) {
            return response()->json(['error' => 'Start date and end date are required.'], 400);
        }

        $formattedStartDate = new DateTime($startDate);
        $formattedEndDate = new DateTime($endDate);

        return $this->dashboardService->getFabricUsed($formattedStartDate, $formattedEndDate);
    }


    public function downloadMonthlySales()
    {
        $startDate = request()->query('start_date');
        $endDate = request()->query('end_date');

        if (!$startDate || !$endDate) {
            return response()->json(['error' => 'Start date and end date are required.'], 400);
        }

        $formattedStartDate = new DateTime($startDate);
        $formattedEndDate = new DateTime($endDate);

        return Excel::download(new SalesReportExport($formattedStartDate, $formattedEndDate, 'monthly'), 'monthly_sales.xlsx');
    }

    public function downloadCategorySales()
    {
        $startDate = request()->query('start_date');
        $endDate = request()->query('end_date');

        if (!$startDate || !$endDate) {
            return response()->json(['error' => 'Start date and end date are required.'], 400);
        }

        $formattedStartDate = new DateTime($startDate);
        $formattedEndDate = new DateTime($endDate);
        
        return Excel::download(new SalesReportExport($formattedStartDate, $formattedEndDate, 'perCategory'), 'sales_per_category.xlsx');
    }


    public function downloadFabricUsed()
    {
        $startDate = request()->query('start_date');
        $endDate = request()->query('end_date');

        if (!$startDate || !$endDate) {
            return response()->json(['error' => 'Start date and end date are required.'], 400);
        }

        $formattedStartDate = new DateTime($startDate);
        $formattedEndDate = new DateTime($endDate);

        return Excel::download(new FabricUsedExport($formattedStartDate, $formattedEndDate), 'fabric_used.xlsx');
    }


    public function cardAnalytics()
    {
        $startDate = request()->query('start_date');
        $endDate = request()->query('end_date');

        // Validate date format (optional: you can use Laravel validation for stricter checks)
        if (!$startDate || !$endDate) {
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
