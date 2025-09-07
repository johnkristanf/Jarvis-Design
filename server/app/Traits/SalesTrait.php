<?php

namespace App\Traits;

trait SalesTrait
{
    public function filterSalesReportForChart($sales, $label, $category)
    {
        $labels = [];
        $data = [];

        foreach ($sales as $sale) {
            $labels[] = trim($sale->$category); // e.g. "January"
            $data[] = (float) $sale->total_sales;     // e.g. 1500.50
        }

        $lineChartData = [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => $label,
                    'backgroundColor' => '#111827',
                    'data' => $data,
                ]
            ],
        ];

        return $lineChartData;
    }
}
