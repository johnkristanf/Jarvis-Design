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
    import { ArrowDownTrayIcon } from '@heroicons/vue/20/solid'
    import { downloadBlobFile } from '@/helper/report'

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

    // FABRIC USED BAR CHART DATA
    const { data: fabricUsed } = useQuery({
        queryKey: ['fabric-used'],
        queryFn: async () => {
            const respData = await apiService.get<SalesReport>('/api/get/fabric/used')
            console.log('fabricUsed: ', respData)

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
            const response = await apiService.get<Blob>('/api/get/reports/monthly-sales', {
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
            const response = await apiService.get<Blob>('/api/get/reports/category-sales', {
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
            const response = await apiService.get<Blob>('/api/get/reports/fabric-used', {
                responseType: 'blob',
            })

            downloadBlobFile(response, 'fabric_used.xlsx')
        } catch (error) {
            console.error('Download Report Error: ', error)
        }
    }
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
                <div class="flex justify-end">
                    <button @click="downloadMonthlyReport">
                        <ArrowDownTrayIcon class="size-6 hover:cursor-pointer hover:opacity-75" />
                    </button>
                </div>

                <Line
                    v-if="monthlySalesReport"
                    id="monthly-sales"
                    :options="lineChartOptions"
                    :data="monthlySalesReport"
                />
            </div>

            <div class="h-[300px] rounded-md p-3">
                <div class="flex justify-end">
                    <button @click="downloadReportPerCategory">
                        <ArrowDownTrayIcon class="size-6 hover:cursor-pointer hover:opacity-75" />
                    </button>
                </div>

                <Bar
                    v-if="salePerProductCategory"
                    id="sales-per-category"
                    :options="chartOptions"
                    :data="salePerProductCategory"
                />
            </div>

            <div class="h-[300px] rounded-md p-3 lg:col-span-2">
                <div class="flex justify-end">
                    <button @click="downloadReportPerFabricUsed">
                        <ArrowDownTrayIcon class="size-6 hover:cursor-pointer hover:opacity-75" />
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
