<?php

namespace App\Service;

use App\Models\Orders;
use App\Traits\HandleAttachments;
use App\Traits\SalesTrait;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    use SalesTrait, HandleAttachments;

    public function getMonthlySalesReport()
    {
        $monthlySales = DB::table('orders')
            ->select(
                DB::raw("TO_CHAR(delivery_date, 'Month') as month_name"),
                DB::raw("EXTRACT(MONTH FROM delivery_date) as month_number"),
                DB::raw("SUM(total_price) as total_sales")
            )
            ->whereNotNull('delivery_date')
            ->where('status', Orders::COMPLETED)

            ->groupBy('month_name', 'month_number')
            ->orderBy('month_number', 'asc')
            ->get();

        return $this->filterSalesReportForChart(
            sales: $monthlySales,
            label: 'Monthly Sales Report',
            category: 'month_name'
        );
    }


    public function getSalesPerProductCategory()
    {
        $salesPerProductCategory = DB::table('orders')
            ->select(
                'design_categories.name as category_name',
                DB::raw('SUM(orders.total_price) as total_sales')
            )
            ->leftJoin('products', 'orders.product_id', '=', 'products.id')
            ->leftJoin('design_categories', 'products.category_id', '=', 'design_categories.id')

            ->whereNotNull('orders.delivery_date')
            ->where('orders.status', '=', Orders::COMPLETED)

            ->groupBy('design_categories.name')
            ->orderByDesc('total_sales')
            ->get();


        return $this->filterSalesReportForChart(
            sales: $salesPerProductCategory,
            label: 'Sales Per Product',
            category: 'category_name'
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
