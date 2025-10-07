<?php

namespace App\Http\Controllers;

use App\Exports\FabricUsedExport;
use App\Exports\SalesReportExport;
use App\Service\DashboardService;
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
        return $this->dashboardService->getMonthlySalesReport();
    }

    public function salesPerProductCategory()
    {
        return $this->dashboardService->getSalesPerProductCategory();
    }


    public function fabricUsed()
    {
        return $this->dashboardService->getFabricUsed();
    }


    public function downloadMonthlySales()
    {
        return Excel::download(new SalesReportExport('monthly'), 'monthly_sales.xlsx');
    }

    public function downloadCategorySales()
    {
        return Excel::download(new SalesReportExport('perCategory'), 'sales_per_category.xlsx');
    }


    public function downloadFabricUsed()
    {
        return Excel::download(new FabricUsedExport(), 'fabric_used.xlsx');
    }

    public function latestOrders()
    {
        return $this->dashboardService->getLatestOrder();
    }
}
