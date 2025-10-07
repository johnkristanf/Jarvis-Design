<?php

namespace App\Traits;

trait SalesTrait
{
    public function filterSalesReportForChart($sales, $label, $category, $keyValue)
    {
        $labels = [];
        $data = [];

        foreach ($sales as $sale) {
            $labels[] = trim($sale->$category); // e.g. "January"
            $data[] = (float) $sale->$keyValue;     // e.g. 1500.50
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
