<?php

namespace App\Http\Controllers;

use App\Service\DashboardService;
use Illuminate\Http\Request;

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

    public function latestOrders()
    {
        return $this->dashboardService->getLatestOrder();
    }
}
