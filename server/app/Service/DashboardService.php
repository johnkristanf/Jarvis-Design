<?php

namespace App\Service;

use App\Models\OrderPayment;
use App\Models\Orders;
use App\Traits\HandleAttachments;
use App\Traits\SalesTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardService
{
    use SalesTrait, HandleAttachments;

    public function getMonthlySalesReport($isChartFiltered = true)
    {
        $monthlySales = DB::table('order_payments')
            ->select(
                DB::raw("TO_CHAR(updated_at, 'Month') as month_name"),
                DB::raw("EXTRACT(MONTH FROM updated_at) as month_number"),
                DB::raw("SUM(amount_applied) as total_sales")
            )
            ->where('status', OrderPayment::FULLY_PAID)
            ->groupBy('month_name', 'month_number')
            ->orderBy('month_number', 'asc')
            ->get();
            
        if($isChartFiltered){
            return $this->filterSalesReportForChart(
                sales: $monthlySales,
                label: 'Monthly Sales Report',
                category: 'month_name',
                keyValue: 'total_sales'
            );
        }

        return $monthlySales;
        
    }


    public function getSalesPerProductCategory($isChartFiltered = true)
    {
        $salesPerProductCategory = DB::table('orders')
            ->select(
                'design_categories.name as category_name',
                DB::raw('SUM(order_payments.amount_applied) as total_sales')
            )
            ->leftJoin('products', 'orders.product_id', '=', 'products.id')
            ->leftJoin('design_categories', 'products.category_id', '=', 'design_categories.id')
            ->leftJoin('order_payments', 'orders.id', '=', 'order_payments.order_id')

            ->where('order_payments.status', '=', OrderPayment::FULLY_PAID)
            ->groupBy('design_categories.name')
            ->orderByDesc('total_sales')
            ->get();


        Log::info("salesPerProductCategory: ", [$salesPerProductCategory]);
       
        if($isChartFiltered){
            return $this->filterSalesReportForChart(
                sales: $salesPerProductCategory,
                label: 'Sales Per Product',
                category: 'category_name',
                keyValue: 'total_sales'
            );
        }

        return $salesPerProductCategory;
    }

    public function getFabricUsed()
    {
        $fabricUsed = DB::table('order_logs')
            ->select(
                'material_name',
                DB::raw('SUM(total_quantity_used) as total_fabric_used')
            )
            ->groupBy('material_name')
            ->orderByDesc('total_fabric_used')
            ->get();

        Log::info("fabricUsed: ", [$fabricUsed]);

        return $this->filterSalesReportForChart(
            sales: $fabricUsed,
            label: 'Total Fabric Used',
            category: 'material_name',
            keyValue: 'total_fabric_used'
        );
    }

    public function getLatestOrder()
    {
        $orders = Orders::with(['product:id,name'])
            ->select([
                'id',
                'order_number',
                'own_design_url',
                'business_design_url',
                'status',
                'product_id'
            ])
            ->limit(3)
            ->get();

        return $this->transformOrderDesignToS3Temp($orders);
    }
}
