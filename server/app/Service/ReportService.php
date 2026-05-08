<?php

namespace App\Service;

use App\Models\OrderLogs;
use App\Models\Orders;
use Barryvdh\DomPDF\Facade\Pdf as PdfFacade;
use DateTime;
use Illuminate\Support\Facades\DB;

class ReportService
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function generateCategorySalesReport(DateTime $startDate, DateTime $endDate)
    {
        $categorySales = $this->dashboardService->getSalesPerProductCategory($startDate, $endDate, false);

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
            'startDate' => $startDate->format('F d, Y'),
            'endDate' => $endDate->format('F d, Y'),
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

    public function generateMonthlySalesReport(DateTime $startDate, DateTime $endDate)
    {
        $monthlySales = $this->dashboardService->getMonthlySalesReport($startDate, $endDate);

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
            'startDate' => $startDate->format('F d, Y'),
            'endDate' => $endDate->format('F d, Y'),
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

    public function generateFabricUsedReport(DateTime $startDate, DateTime $endDate)
    {
        $fabricUsed = OrderLogs::select(
            'material_name',
            DB::raw('MIN(unit) as unit'),
            DB::raw('SUM(total_quantity_used) as total_used')
        )
            ->whereBetween(DB::raw('DATE(created_at)'), [
                $startDate->format('Y-m-d 00:00:00'),
                $endDate->format('Y-m-d 23:59:59'),
            ])
            ->groupBy('material_name')
            ->orderBy('total_used', 'desc')
            ->get();

        // Process the collection into rows
        $rows = [];
        $totalUsed = 0;
        foreach ($fabricUsed as $fabric) {
            $used = (float) $fabric->total_used;
            $rows[] = [
                'fabric' => $fabric->material_name,
                'unit' => $fabric->unit,
                'total_used' => number_format($used, 2),
            ];
            $totalUsed += $used;
        }

        $logoPath = public_path('jarvis-logo-circle.png');

        $data = [
            'startDate' => $startDate->format('F d, Y'),
            'endDate' => $endDate->format('F d, Y'),
            'rows' => $rows,
            'totalUsed' => number_format($totalUsed, 2),
            'logoPath' => $logoPath,
        ];

        $pdf = PdfFacade::loadView('pdf.fabric-used', $data);
        $pdf->setPaper('a4', 'portrait');
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('isRemoteEnabled', true); // Enable to load local images
        $pdf->setOption('defaultFont', 'DejaVu Sans'); // Better Unicode support

        return $pdf;
    }

    public function generateSummaryTotalOrdersReport(DateTime $startDate, DateTime $endDate)
    {
        $orders = Orders::with(['items.product', 'items.sizes'])
            ->withSum('order_payments as total_payment', 'amount_applied')
            ->whereBetween('created_at', [
                $startDate->format('Y-m-d 00:00:00'),
                $endDate->format('Y-m-d 23:59:59'),
            ])
            ->whereIn('status', [Orders::COMPLETED, Orders::CANCELLED])
            ->get();

        $pdf = PdfFacade::loadView('pdf.per-order-monthly-sales', [
            'orders' => $orders,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);

        return $pdf;
    }
}
