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
    import { useQuery, useQueryClient } from '@tanstack/vue-query'
    import { apiService } from '@/api/axios'
    import type { SalesReport } from '@/types/dashboard'
    import type { CardAnalytics, LatestOrders } from '@/types/order'
    import StatusBadge from '@/components/orders/StatusBadge.vue'
    import { ArrowDownTrayIcon } from '@heroicons/vue/20/solid'
    import { downloadBlobFile } from '@/helper/report'
    import { reactive } from 'vue'

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

    // Set default: start Jan 1 to end Dec 31 of current year
    const currentYear = new Date().getFullYear()
    const defaultStart = `${currentYear}-01-01`
    const defaultEnd = `${currentYear}-12-31`

    const dateFilter = reactive({
        start: defaultStart,
        end: defaultEnd,
    })

    // SALES PER CATEGORY BAR CHART DATA
    const { data: salePerProductCategory } = useQuery({
        queryKey: ['sales-per-category', dateFilter.start, dateFilter.end],
        queryFn: async () => {
            const params = { start_date: dateFilter.start, end_date: dateFilter.end }
            const respData = await apiService.get<SalesReport>('/api/get/sales/category', {
                params,
            })
            return respData
        },
        enabled: !!dateFilter.start && !!dateFilter.end,
    })

    // FABRIC USED BAR CHART DATA
    const { data: fabricUsed } = useQuery({
        queryKey: ['fabric-used', dateFilter.start, dateFilter.end],
        queryFn: async () => {
            const params = { start_date: dateFilter.start, end_date: dateFilter.end }
            const respData = await apiService.get<SalesReport>('/api/get/fabric/used', { params })
            console.log('fabricUsed: ', respData)
            return respData
        },
        enabled: !!dateFilter.start && !!dateFilter.end,
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
        queryKey: ['sales-report', dateFilter.start, dateFilter.end],
        queryFn: async () => {
            const params = { start_date: dateFilter.start, end_date: dateFilter.end }
            const respData = await apiService.get<SalesReport>('/api/get/sales/report', { params })
            return respData
        },
        enabled: !!dateFilter.start && !!dateFilter.end,
    })

    const lineChartOptions: ChartOptions<'line'> = {
        responsive: true,
        maintainAspectRatio: false,
    }

    // LATEST ORDERS
    const { data: latestOrders } = useQuery({
        queryKey: ['latest-orders'],
        queryFn: async () => {
            const respData = await apiService.get<LatestOrders[]>('/api/get/latest/orders')
            return respData
        },
    })

    // DOWNLOAD MONTHLY REPORT
    const downloadMonthlyReport = async () => {
        try {
            const params = { start_date: dateFilter.start, end_date: dateFilter.end }
            const response = await apiService.get<Blob>('/api/get/reports/monthly-sales', {
                params,
                responseType: 'blob',
            })

            downloadBlobFile(response, 'monthly_sales.xlsx')
        } catch (error) {
            console.error('Download Report Error: ', error)
        }
    }

    // DOWNLOAD REPORT PER CATEGORY
    const downloadReportPerCategory = async () => {
        try {
            const params = { start_date: dateFilter.start, end_date: dateFilter.end }
            const response = await apiService.get<Blob>('/api/get/reports/category-sales', {
                params,
                responseType: 'blob',
            })

            downloadBlobFile(response, 'sales_per_category.xlsx')
        } catch (error) {
            console.error('Download Report Error: ', error)
        }
    }

    // DOWNLOAD REPORT PER FABRIC USED
    const downloadReportPerFabricUsed = async () => {
        try {
            const params = { start_date: dateFilter.start, end_date: dateFilter.end }
            const response = await apiService.get<Blob>('/api/get/reports/fabric-used', {
                params,
                responseType: 'blob',
            })

            downloadBlobFile(response, 'fabric_used.xlsx')
        } catch (error) {
            console.error('Download Report Error: ', error)
        }
    }

    const { data: cardAnalytics, refetch: refetchCardAnalytics } = useQuery({
        queryKey: ['card-analytics', dateFilter.start, dateFilter.end],
        queryFn: async () => {
            const params = { start_date: dateFilter.start, end_date: dateFilter.end }
            const respData = await apiService.get<CardAnalytics>('/api/get/card-analytics', {
                params,
            })
            return respData
        },
        enabled: !!dateFilter.start && !!dateFilter.end,
    })

    const queryClient = useQueryClient()

    function onDateChange() {
        // Invalidate all relevant dashboard queries to force reload on date change
        queryClient.invalidateQueries({ queryKey: ['sales-per-category'] })
        queryClient.invalidateQueries({ queryKey: ['fabric-used'] })
        queryClient.invalidateQueries({ queryKey: ['sales-report'] })
        queryClient.invalidateQueries({ queryKey: ['latest-orders'] })
        queryClient.invalidateQueries({ queryKey: ['card-analytics'] })
    }
</script>

<template>
    <!-- THIS WILL DISPLAY GRIDS FOR EACH GRAPHS AND CHARTS FOR DASHBOARD SPECIFIC LAYOUT -->

    <div class="w-full p-4 rounded-md bg-gray-100 border-1 border-gray-400">
        <h1 class="text-2xl">Dashboard</h1>
        <p class="text-sm text-gray-400 mt-1 mb-7">
            Gives an overview of key metrics, recent activities, and system summaries at a glance.
        </p>

        <div class="flex justify-end mb-6">
            <div class="flex flex-col">
                <label
                    for="startDate"
                    class="mb-2 text-xs text-gray-500 font-semibold tracking-wide"
                >
                    From
                </label>
                <div class="relative">
                    <input
                        id="startDate"
                        type="date"
                        v-model="dateFilter.start"
                        @change="onDateChange"
                        class="block w-38 px-4 py-2 border border-gray-200 rounded-lg shadow-sm bg-gray-50 focus:(ring-2 ring-indigo-200 border-indigo-400) text-base text-gray-700 transition-all duration-150 outline-none"
                    />
                </div>
            </div>
            <span class="mx-2 mt-6 text-gray-400 text-2xl font-light">–</span>
            <div class="flex flex-col">
                <label for="endDate" class="mb-2 text-xs text-gray-500 font-semibold tracking-wide">
                    To
                </label>
                <div class="relative">
                    <input
                        id="endDate"
                        type="date"
                        v-model="dateFilter.end"
                        @change="onDateChange"
                        class="block w-38 px-4 py-2 border border-gray-200 rounded-lg shadow-sm bg-gray-50 focus:(ring-2 ring-indigo-200 border-indigo-400) text-base text-gray-700 transition-all duration-150 outline-none"
                    />
                </div>
            </div>
        </div>

        <!-- CARD ANALYTICS STATS START -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div
                class="bg-white rounded-lg shadow flex flex-col items-center justify-center py-6 px-4 border border-gray-200 min-h-[110px]"
            >
                <span class="text-xs text-gray-500 mb-1">Total Sales</span>
                <span class="text-2xl font-semibold text-indigo-700">
                    <template v-if="cardAnalytics && cardAnalytics.total_sales !== undefined">
                        ₱{{
                            Number(cardAnalytics.total_sales).toLocaleString(undefined, {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2,
                            })
                        }}
                    </template>
                    <template v-else>–</template>
                </span>
            </div>
            <div
                class="bg-white rounded-lg shadow flex flex-col items-center justify-center py-6 px-4 border border-gray-200 min-h-[110px]"
            >
                <span class="text-xs text-gray-500 mb-1">Total Customers</span>
                <span class="text-2xl font-semibold text-teal-600">
                    <template v-if="cardAnalytics && cardAnalytics.total_customers !== undefined">
                        {{ cardAnalytics.total_customers }}
                    </template>
                    <template v-else>–</template>
                </span>
            </div>
            <div
                class="bg-white rounded-lg shadow flex flex-col items-center justify-center py-6 px-4 border border-gray-200 min-h-[110px]"
            >
                <span class="text-xs text-gray-500 mb-1">Pending Orders</span>
                <span class="text-2xl font-semibold text-amber-500">
                    <template
                        v-if="cardAnalytics && cardAnalytics.total_pending_orders !== undefined"
                    >
                        {{ cardAnalytics.total_pending_orders }}
                    </template>
                    <template v-else>–</template>
                </span>
            </div>
            <div
                class="bg-white rounded-lg shadow flex flex-col items-center justify-center py-6 px-4 border border-gray-200 min-h-[110px]"
            >
                <span class="text-xs text-gray-500 mb-1">Completed Orders</span>
                <span class="text-2xl font-semibold text-green-600">
                    <template
                        v-if="cardAnalytics && cardAnalytics.total_completed_orders !== undefined"
                    >
                        {{ cardAnalytics.total_completed_orders }}
                    </template>
                    <template v-else>–</template>
                </span>
            </div>
        </div>
        <!-- CARD ANALYTICS STATS END -->

        <div class="grid grid-cols-1 sm:grid-cols-1 md:grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
            <div class="h-[300px] rounded-md p-3 bg-gray-200">
                <div class="flex justify-end">
                    <button
                        @click="downloadMonthlyReport"
                        class="bg-blue-600 p-2 text-xs text-white rounded-md hover:cursor-pointer hover:opacity-75"
                    >
                        Export
                    </button>
                </div>

                <Line
                    v-if="monthlySalesReport"
                    id="monthly-sales"
                    :options="lineChartOptions"
                    :data="monthlySalesReport"
                />
            </div>

            <div class="h-[300px] rounded-md p-3 bg-gray-200">
                <div class="flex justify-end">
                    <button
                        @click="downloadReportPerCategory"
                        class="bg-blue-600 p-2 text-xs text-white rounded-md hover:cursor-pointer hover:opacity-75"
                    >
                        Export
                    </button>
                </div>

                <Bar
                    v-if="salePerProductCategory"
                    id="sales-per-category"
                    :options="chartOptions"
                    :data="salePerProductCategory"
                />
            </div>

            <div class="h-[300px] rounded-md p-3 lg:col-span-2 bg-gray-200 mt-5">
                <div class="flex justify-end">
                    <button
                        @click="downloadReportPerFabricUsed"
                        class="bg-blue-600 p-2 text-xs text-white rounded-md hover:cursor-pointer hover:opacity-75"
                    >
                        Export
                    </button>
                </div>

                <div class="w-full h-full">
                    <Bar
                        v-if="fabricUsed"
                        id="fabric-used"
                        :options="{
                            ...chartOptions,
                            maintainAspectRatio: false, // important!
                        }"
                        :data="fabricUsed"
                    />
                </div>
            </div>

            <div class="h-[300px] rounded-md p-3 lg:col-span-2">
                <p class="text-gray-700">Latest Orders</p>

                <fwb-table class="w-full h-full mt-3">
                    <fwb-table-head>
                        <fwb-table-head-cell class="text-xs text-white uppercase bg-gray-900">
                            Order No.
                        </fwb-table-head-cell>
                        <fwb-table-head-cell
                            class="px-16 py-3 text-xs text-white uppercase bg-gray-900"
                        >
                            Design
                        </fwb-table-head-cell>
                        <fwb-table-head-cell class="text-xs text-white uppercase bg-gray-900">
                            Name
                        </fwb-table-head-cell>
                        <fwb-table-head-cell class="text-xs text-white uppercase bg-gray-900">
                            Status
                        </fwb-table-head-cell>
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
                                <StatusBadge :status="order.status" />
                            </fwb-table-cell>
                        </fwb-table-row>
                    </fwb-table-body>
                </fwb-table>
            </div>
        </div>
    </div>
</template>
