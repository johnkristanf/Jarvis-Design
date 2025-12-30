<?php

namespace App\Service;

use App\Models\OrderPayment;
use App\Models\Orders;
use App\Traits\HandleAttachments;
use App\Traits\SalesTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CopyDashboardService
{
    use HandleAttachments, SalesTrait;

    public function getMonthlySalesReport($startDate, $endDate, $isChartFiltered = true)
    {

        // Find all orders within the date range
        $orderIds = DB::table('orders')
            ->whereBetween('created_at', [
                $startDate->format('Y-m-d 00:00:00'),
                $endDate->format('Y-m-d 23:59:59'),
            ])
            ->pluck('id')
            ->toArray();

        // For those orders, determine if any payment (regardless of whether it's the latest) is FULLY_PAID
        $fullyPaidOrderIds = DB::table('order_payments')
            ->whereIn('order_id', $orderIds)
            ->where('status', OrderPayment::FULLY_PAID)
            ->distinct()
            ->pluck('order_id')
            ->toArray();


        $query = DB::table('order_payments')
            ->select(
                DB::raw("TO_CHAR(updated_at, 'Month') as month_name"),
                DB::raw('EXTRACT(MONTH FROM updated_at) as month_number'),
                DB::raw('SUM(amount_applied) as total_sales')
            )
            ->whereIn('order_id', $fullyPaidOrderIds);

        // Remove status = fully_paid so we sum all historical payments for those orders

        // Apply date range filter if both startDate and endDate are provided
        if ($startDate && $endDate) {
            // Expecting $startDate and $endDate to be \DateTime or string compatible for whereBetween
            $query->whereBetween(DB::raw('DATE(updated_at)'), [$startDate, $endDate]);
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
            ->pluck('id')
            ->toArray();

        // For those orders, determine if any payment (regardless of whether it's the latest) is FULLY_PAID
        $fullyPaidOrderIds = DB::table('order_payments')
            ->whereIn('order_id', $orderIds)
            ->where('status', OrderPayment::FULLY_PAID)
            ->distinct()
            ->pluck('order_id')
            ->toArray();

        $salesPerProductCategory = DB::table('orders')
            ->select(
                'design_categories.name as category_name',
                DB::raw('SUM(order_payments.amount_applied) as total_sales')
            )
            ->leftJoin('products', 'orders.product_id', '=', 'products.id')
            ->leftJoin('design_categories', 'products.category_id', '=', 'design_categories.id')
            ->leftJoin('order_payments', 'orders.id', '=', 'order_payments.order_id')
            ->whereIn('orders.id', $fullyPaidOrderIds)
            ->whereBetween('order_payments.updated_at', [
                $startDate->format('Y-m-d 00:00:00'),
                $endDate->format('Y-m-d 23:59:59'),
            ])
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
        $orders = Orders::with(['product:id,name'])
            ->select([
                'id',
                'order_number',
                'own_design_url',
                'business_design_url',
                'status',
                'product_id',
            ])
            ->limit(3)
            ->get();

        return $this->transformOrderDesignToS3Temp($orders);
    }

    public function getTotalSalesWithRange($startDate, $endDate)
    {
        // Find all orders within the date range
        $orderIds = DB::table('orders')
            ->whereBetween('created_at', [
                $startDate->format('Y-m-d 00:00:00'),
                $endDate->format('Y-m-d 23:59:59'),
            ])
            ->pluck('id')
            ->toArray();

        // For those orders, determine if any payment (regardless of whether it's the latest) is FULLY_PAID
        $fullyPaidOrderIds = DB::table('order_payments')
            ->whereIn('order_id', $orderIds)
            ->where('status', OrderPayment::FULLY_PAID)
            ->distinct()
            ->pluck('order_id')
            ->toArray();

        Log::info("fullyPaidOrderIds: ", [$fullyPaidOrderIds]);

        // Sum all amount_applied for all payments of those fully paid orders
        $sales = DB::table('order_payments')
            ->whereIn('order_id', $fullyPaidOrderIds)
            ->sum('amount_applied');

        return $sales;
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
