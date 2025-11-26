<?php

namespace App\Exports;

use App\Service\DashboardService;
use Barryvdh\DomPDF\Facade\Pdf as PdfFacade;
use DateTime;

class CategorySalesPdfExport
{
    protected $startDate;

    protected $endDate;

    protected $dashboardService;

    public function __construct(DateTime $startDate, DateTime $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->dashboardService = new DashboardService;
    }

    public function generate()
    {
        $categorySales = $this->dashboardService->getSalesPerProductCategory($this->startDate, $this->endDate, false);

        // Process the collection into rows
        $rows = [];
        $totalAmount = 0;
        foreach ($categorySales as $sale) {
            $amount = (float) $sale->total_sales;
            $rows[] = [
                'category' => $sale->category_name,
                'amount' => number_format($amount, 2),
            ];
            $totalAmount += $amount;
        }

        $logoPath = public_path('jarvis-logo-circle.png');

        $data = [
            'startDate' => $this->startDate->format('F d, Y'),
            'endDate' => $this->endDate->format('F d, Y'),
            'rows' => $rows,
            'totalAmount' => number_format($totalAmount, 2),
            'pesoSign' => html_entity_decode('&#8369;', ENT_QUOTES | ENT_HTML5, 'UTF-8'), // Peso sign
            'logoPath' => $logoPath,
        ];

        $pdf = PdfFacade::loadView('pdf.category-sales', $data);
        $pdf->setPaper('a4', 'portrait');
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('isRemoteEnabled', true); // Enable to load local images
        $pdf->setOption('defaultFont', 'DejaVu Sans'); // Better Unicode support

        return $pdf;
    }
}
