<script lang="ts" setup>
    import {
        Chart as ChartJS,
        CategoryScale,
        BarElement,
        LinearScale,
        PointElement,
        LineElement,
        Title,
        Tooltip,
        Legend,
    } from 'chart.js'

    import {
        FwbTable,
        FwbTableBody,
        FwbTableCell,
        FwbTableHead,
        FwbTableHeadCell,
        FwbTableRow,
    } from 'flowbite-vue'

    import { Bar, Line } from 'vue-chartjs'
    import type { ChartOptions } from 'chart.js'
    import { useQuery } from '@tanstack/vue-query'
    import { apiService } from '@/api/axios'
    import type { SalesReport } from '@/types/dashboard'
    import { type LatestOrders } from '@/types/order'
import StatusBadge from '@/components/orders/StatusBadge.vue'

    ChartJS.register(
        CategoryScale,
        LinearScale,
        PointElement,
        LineElement,
        Title,
        Tooltip,
        Legend,
        BarElement,
    )

    // SALES PER CATEGORY BAR CHART DATA
    const { data: salePerProductCategory } = useQuery({
        queryKey: ['sales-per-category'],
        queryFn: async () => {
            const respData = await apiService.get<SalesReport>('/api/get/sales/category')
            return respData
        },
    })

    const chartOptions: ChartOptions<'bar'> = {
        responsive: true,
        plugins: {
            legend: {
                display: true,
                position: 'top',
            },
        },
        scales: {
            y: {
                beginAtZero: true,
            },
        },
    }

    // SALES REPORT LINE CHART DATA
    const { data: monthlySalesReport } = useQuery({
        queryKey: ['sales-report'],
        queryFn: async () => {
            const respData = await apiService.get<SalesReport>('/api/get/sales/report')
            return respData
        },
    })

    const lineChartOptions = {
        responsive: true,
        maintainAspectRatio: false,
    }

    // LATEST ORDERS
    const { data: latestOrders } = useQuery({
        queryKey: ['latest-orders'],
        queryFn: async () => {
            const respData = await apiService.get<LatestOrders[]>('/api/get/latest/orders')
            console.log('respData 33434: ', respData)
            return respData
        },
    })
</script>

<template>
    <!-- THIS WILL DISPLAY GRIDS FOR EACH GRAPHS AND CHARTS FOR DASHBOARD SPECIFIC LAYOUT -->

    <div class="w-full p-4 rounded-md bg-gray-100 border-1 border-gray-400">
        <h1 class="text-2xl">Dashboard</h1>
        <p class="text-sm text-gray-400 mt-1 mb-7">
            Gives an overview of key metrics, recent activities, and system summaries at a glance.
        </p>

        <div class="grid grid-cols-1 sm:grid-cols-1 md:grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
            <div class="h-[300px] rounded-md p-3">
                <Line
                    v-if="monthlySalesReport"
                    id="my-chart-id"
                    :options="lineChartOptions"
                    :data="monthlySalesReport"
                />
            </div>

            <div class="h-[300px] rounded-md p-3">
                <Bar
                    v-if="salePerProductCategory"
                    id="my-chart-id"
                    :options="chartOptions"
                    :data="salePerProductCategory"
                />
            </div>

            <div class="h-[300px] rounded-md p-3 bg-gray-100 lg:col-span-2">
                <p class="text-gray-700">Latest Orders</p>

                <fwb-table class="w-full h-full mt-3">
                    <fwb-table-head>
                        <fwb-table-head-cell>Order No.</fwb-table-head-cell>
                        <fwb-table-head-cell class="px-16 py-3">Design</fwb-table-head-cell>
                        <fwb-table-head-cell>Name</fwb-table-head-cell>
                        <fwb-table-head-cell>Status</fwb-table-head-cell>
                    </fwb-table-head>

                    <fwb-table-body>
                        <fwb-table-row v-for="order in latestOrders" :key="order.id">
                            <fwb-table-cell>{{ order.order_number }}</fwb-table-cell>
                            <fwb-table-cell>
                                <img
                                    :src="order.temp_url"
                                    class="w-16 h-16 object-cover rounded-md border"
                                    alt="Design Image"
                                />
                            </fwb-table-cell>
                            <fwb-table-cell>{{ order.product.name }}</fwb-table-cell>
                            <fwb-table-cell>
                               <StatusBadge :status="order.status"/>
                            </fwb-table-cell>
                        </fwb-table-row>
                    </fwb-table-body>
                </fwb-table>
            </div>
        </div>
    </div>
</template>
