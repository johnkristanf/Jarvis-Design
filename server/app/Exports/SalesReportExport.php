<?php

namespace App\Exports;

use App\Service\DashboardService;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SalesReportExport implements FromArray, WithHeadings
{
    protected $reportType;
    protected $startDate;
    protected $endDate;

    public function __construct($startDate , $endDate, $reportType = 'monthly' )
    {
        $this->reportType = $reportType;

        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }


    public function array(): array
    {
        $dashboardService = new DashboardService();
        
        if ($this->reportType === 'monthly') {
            $data = $dashboardService->getMonthlySalesReport($this->startDate, $this->endDate, false);
        } elseif ($this->reportType === 'perCategory') {
            $data = $dashboardService->getSalesPerProductCategory($this->startDate, $this->endDate, false);
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
