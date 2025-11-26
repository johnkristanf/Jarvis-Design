<?php

namespace App\Traits;

trait SalesTrait
{
    public function filterSalesReportForChart($sales, $label, $category, $keyValue, $bgColor)
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
                    // Change backgroundColor from slate-900 (#111827) to indigo-700 (#4338CA)
                    'backgroundColor' => $bgColor,
                    'data' => $data,
                ]
            ],
        ];

        return $lineChartData;
    }
}
