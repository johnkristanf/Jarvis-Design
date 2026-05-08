<?php

namespace App\Service;

use App\Models\OrderPayment;
use App\Models\Orders;
use App\Traits\HandleAttachments;
use App\Traits\SalesTrait;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardService
{
    use HandleAttachments, SalesTrait;

    public function getMonthlySalesReport($startDate, $endDate, $isChartFiltered = true)
    {
        // Accrual basis: recognize revenue when the order is COMPLETED (earned),
        // grouped by the month the order was completed, summing orders.total_price.
        $query = DB::table('orders')
            ->select(
                DB::raw("TO_CHAR(created_at, 'Month') as month_name"),
                DB::raw('EXTRACT(MONTH FROM created_at) as month_number'),
                DB::raw('SUM(total_price) as total_sales')
            )
            ->where('status', Orders::COMPLETED);

        if ($startDate && $endDate) {
            $query->whereBetween(DB::raw('DATE(created_at)'), [
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d'),
            ]);
        }

        $monthlySales = $query
            ->groupBy('month_name', 'month_number')
            ->orderBy('month_number', 'asc')
            ->get();


        if ($isChartFiltered) {
            return $this->filterSalesReportForChart(
                sales: $monthlySales,
                label: 'Monthly Sales Report',
                category: 'month_name',
                keyValue: 'total_sales',
                bgColor: '#4338CA'
            );
        }

        return $monthlySales;
    }


    public function getSalesPerProductCategory($startDate, $endDate, $isChartFiltered = true)
    {
        // Find all orders within the date range
        $orderIds = DB::table('orders')
            ->whereBetween('created_at', [
                $startDate->format('Y-m-d 00:00:00'),
                $endDate->format('Y-m-d 23:59:59'),
            ])
            ->whereIn('status', [Orders::COMPLETED])
            ->pluck('id')
            ->toArray();

        // For those orders, determine if any payment (regardless of whether it's the latest) is FULLY_PAID
        $fullyPaidOrderIds = DB::table('order_payments')
            ->whereIn('order_id', $orderIds)
            ->where('status', OrderPayment::FULLY_PAID)
            ->distinct()
            ->pluck('order_id')
            ->toArray();

        // order_items contain the individual products now
        $salesPerProductCategory = DB::table('order_items')
            ->select(
                'design_categories.name as category_name',
                DB::raw('SUM(order_items.total_price) as total_sales')
            )
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('design_categories', 'products.category_id', '=', 'design_categories.id')
            ->whereIn('orders.id', $fullyPaidOrderIds)
            ->groupBy('design_categories.name')
            ->orderByDesc('total_sales')
            ->get();

        if ($isChartFiltered) {
            return $this->filterSalesReportForChart(
                sales: $salesPerProductCategory,
                label: 'Sales Per Product Category',
                category: 'category_name',
                keyValue: 'total_sales',
                bgColor: '#0D9488'

            );
        }

        return $salesPerProductCategory;
    }

    public function getFabricUsed($startDate, $endDate)
    {
        $fabricUsed = DB::table('order_logs')
            ->select(
                'material_name',
                DB::raw('SUM(total_quantity_used) as total_fabric_used')
            )
            ->whereBetween(DB::raw('DATE(created_at)'), [
                $startDate->format('Y-m-d 00:00:00'),
                $endDate->format('Y-m-d 23:59:59'),
            ])
            ->groupBy('material_name')
            ->orderByDesc('total_fabric_used')
            ->get();

        return $this->filterSalesReportForChart(
            sales: $fabricUsed,
            label: 'Total Fabric Used',
            category: 'material_name',
            keyValue: 'total_fabric_used',
            bgColor: '#F59E42'
        );
    }

    public function getLatestOrder()
    {
        $orders = Orders::with(['items.product:id,name']) // eagerly load items instead
            ->select([
                'id',
                'order_number',
                'status',
            ]) // own_design_url etc are now in items
            ->orderBy('id', 'desc')
            ->limit(3)
            ->get();

        // transformOrderDesignToS3Temp needs to be mindful of items. Since it might not be updated, we'll return orders
        // and fix transformOrderDesignToS3Temp later if it throws.
        return $this->transformOrderDesignToS3Temp($orders);
    }

    public function getTotalSalesWithRange($startDate, $endDate)
    {
        $totalSales = DB::table('orders')
            ->where('status', Orders::COMPLETED)
            ->whereBetween(DB::raw('DATE(created_at)'), [
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d'),
            ])
            ->sum('total_price');

        return $totalSales;
    }

    public function getTotalCustomersWithRange($startDate, $endDate)
    {
        // Find all order IDs within the date range
        $orderIds = DB::table('orders')
            ->whereBetween('created_at', [
                $startDate->format('Y-m-d 00:00:00'),
                $endDate->format('Y-m-d 23:59:59'),
            ])
            
            ->pluck('id')
            ->toArray();

        // Get distinct customer IDs from those orders
        $totalCustomers = DB::table('orders')
            ->whereIn('id', $orderIds)
            ->distinct('user_id')
            ->count('user_id');

        return $totalCustomers;
    }

    public function countPendingOrdersWithRange($startDate, $endDate)
    {
        $statuses = [
            Orders::PENDING,
            Orders::FOR_DELIVERY,
            Orders::FOR_PICKUP,
            Orders::IN_PROGRESS,
        ];

        return Orders::whereIn('status', $statuses)
            ->whereBetween('created_at', [
                $startDate->format('Y-m-d 00:00:00'),
                $endDate->format('Y-m-d 23:59:59'),
            ])
            ->count();
    }

    public function countCompletedOrdersWithRange($startDate, $endDate)
    {
        return Orders::where('status', Orders::COMPLETED)
            ->whereBetween('created_at', [
                $startDate->format('Y-m-d 00:00:00'),
                $endDate->format('Y-m-d 23:59:59'),
            ])
            ->count();
    }
}
