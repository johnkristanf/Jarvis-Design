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

        // Find all orders within the date range
        $orderIds = DB::table('orders')
            ->whereBetween('created_at', [
                $startDate->format('Y-m-d 00:00:00'),
                $endDate->format('Y-m-d 23:59:59'),
            ])
            ->whereIn('status', [Orders::COMPLETED, Orders::CANCELLED])
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
                label: 'Summary of Total Orders Placed',
                category: 'month_name',
                keyValue: 'total_sales',
                bgColor: '#4338CA'
            );
        }

        return $monthlySales;
    }

    public function getPerOrderSalesPDFReport($startDate, $endDate)
    {
        $orders = Orders::with(['items.product', 'items.sizes'])
            ->withSum('order_payments as total_payment', 'amount_applied')
            ->whereBetween('created_at', [
                $startDate->format('Y-m-d 00:00:00'),
                $endDate->format('Y-m-d 23:59:59'),
            ])
            ->whereIn('status', [Orders::COMPLETED, Orders::CANCELLED])
            ->get();

        $pdf = Pdf::loadView('pdf.per-order-monthly-sales', [
            'orders' => $orders,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);

        return $pdf;
    }

    public function getSalesPerProductCategory($startDate, $endDate, $isChartFiltered = true)
    {
        // Find all orders within the date range
        $orderIds = DB::table('orders')
            ->whereBetween('created_at', [
                $startDate->format('Y-m-d 00:00:00'),
                $endDate->format('Y-m-d 23:59:59'),
            ])
            ->whereIn('status', [Orders::COMPLETED, Orders::CANCELLED])

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
        // Find all orders within the date range
        $orderIds = DB::table('orders')
            ->whereBetween('created_at', [
                $startDate->format('Y-m-d 00:00:00'),
                $endDate->format('Y-m-d 23:59:59'),
            ])
            ->whereIn('status', [Orders::COMPLETED, Orders::CANCELLED])

            ->pluck('id')
            ->toArray();

        // For those orders, determine if any payment (regardless of whether it's the latest) is FULLY_PAID
        $fullyPaidOrderIds = DB::table('order_payments')
            ->whereIn('order_id', $orderIds)
            ->where('status', OrderPayment::FULLY_PAID)
            ->distinct()
            ->pluck('order_id')
            ->toArray();

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
