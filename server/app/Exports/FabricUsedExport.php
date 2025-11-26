<?php

namespace App\Exports;

use App\Models\OrderLogs;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class FabricUsedExport implements FromCollection, WithHeadings
{
    protected $startDate;
    protected $endDate;

    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return OrderLogs::select(
                'material_name',
                DB::raw('SUM(total_quantity_used) as total_used')
            )
            ->whereBetween(DB::raw('DATE(created_at)'), [
                $this->startDate->format('Y-m-d 00:00:00'),
                $this->endDate->format('Y-m-d 23:59:59')
            ])
            ->groupBy('material_name')
            ->orderBy('total_used', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return ['Fabric Name', 'Total Used'];
    }

}
