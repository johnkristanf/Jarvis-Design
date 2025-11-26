<?php

namespace App\Exports;

use App\Service\DashboardService;
use Barryvdh\DomPDF\Facade\Pdf as PdfFacade;
use DateTime;

class MonthlySalesPdfExport
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
        $monthlySales = $this->dashboardService->getMonthlySalesReport($this->startDate, $this->endDate);

        // Extract labels and data from the chart format
        $labels = $monthlySales['labels'] ?? [];
        $amounts = $monthlySales['datasets'][0]['data'] ?? [];

        // Combine labels and amounts into rows
        $rows = [];
        $totalAmount = 0;
        for ($i = 0; $i < count($labels); $i++) {
            $amount = $amounts[$i] ?? 0;
            $rows[] = [
                'month' => $labels[$i],
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

        $pdf = PdfFacade::loadView('pdf.monthly-sales', $data);
        $pdf->setPaper('a4', 'portrait');
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('isRemoteEnabled', true); // Enable to load local images
        $pdf->setOption('defaultFont', 'DejaVu Sans'); // Better Unicode support

        return $pdf;
    }
}
