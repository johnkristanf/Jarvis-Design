<?php

namespace App\Exports;

use App\Models\OrderLogs;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class FabricUsedExport implements FromCollection, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return OrderLogs::select(
                'material_name',
                DB::raw('SUM(total_quantity_used) as total_used')
            )
            ->groupBy('material_name')
            ->orderBy('total_used', 'desc')
            ->get();
    }


    public function headings(): array
    {
        return ['Fabric Name', 'Total Used'];
    }

}
