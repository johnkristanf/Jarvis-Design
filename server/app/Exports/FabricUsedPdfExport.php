<?php

namespace App\Exports;

use App\Models\OrderLogs;
use Barryvdh\DomPDF\Facade\Pdf as PdfFacade;
use DateTime;
use Illuminate\Support\Facades\DB;

class FabricUsedPdfExport
{
    protected $startDate;

    protected $endDate;

    public function __construct(DateTime $startDate, DateTime $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function generate()
    {
        $fabricUsed = OrderLogs::select(
            'material_name',
            DB::raw('SUM(total_quantity_used) as total_used')
        )
            ->whereBetween(DB::raw('DATE(created_at)'), [
                $this->startDate->format('Y-m-d 00:00:00'),
                $this->endDate->format('Y-m-d 23:59:59'),
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
                'total_used' => number_format($used, 2),
            ];
            $totalUsed += $used;
        }

        $logoPath = public_path('jarvis-logo-circle.png');

        $data = [
            'startDate' => $this->startDate->format('F d, Y'),
            'endDate' => $this->endDate->format('F d, Y'),
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
}
