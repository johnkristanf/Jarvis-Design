<?php

namespace App\Http\Controllers;

use App\Exports\SalesReportExport;
use App\Service\DashboardService;
use Illuminate\Http\Request;
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


    public function downloadMonthlySales()
    {
        return Excel::download(new SalesReportExport('monthly'), 'monthly_sales.xlsx');
    }

    public function downloadCategorySales()
    {
        return Excel::download(new SalesReportExport('perCategory'), 'sales_per_category.xlsx');
    }

    public function latestOrders()
    {
        return $this->dashboardService->getLatestOrder();
    }
}
