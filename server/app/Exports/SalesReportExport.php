<?php

namespace App\Exports;

use App\Service\DashboardService;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SalesReportExport implements FromArray, WithHeadings
{
    protected $reportType;

    public function __construct($reportType = 'monthly')
    {
        $this->reportType = $reportType;
    }


    public function array(): array
    {
        $dashboardService = new DashboardService();
        
        if ($this->reportType === 'monthly') {
            $data = $dashboardService->getMonthlySalesReport(isChartFiltered: false);
            Log::info("data monthly: ", [$data]);
        } elseif ($this->reportType === 'perCategory') {
            $data = $dashboardService->getSalesPerProductCategory(isChartFiltered: false);
            Log::info("data perCategory: ", [$data]);
        } else {
            $data = [];
        }

        // Convert collection into plain array
        return collect($data)->map(function ($row) {
            return (array) $row;
        })->toArray();
    }


    public function headings(): array
    {
        if ($this->reportType === 'monthly') {
            return ['Month', 'Month Number', 'Total Sales'];
        }

        if ($this->reportType === 'perCategory') {
            return ['Category', 'Total Sales'];
        }

        return [];
    }


}
