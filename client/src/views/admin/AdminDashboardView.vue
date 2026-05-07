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
    import { downloadBlobFile } from '@/helper/report'
    import { reactive, ref } from 'vue'

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

    // --- REF STATES FOR DOWNLOAD REQUEST TYPES ---
    const isMonthlyReportDownloading = ref(false)
    const isCategoryReportDownloading = ref(false)
    const isFabricUsedReportDownloading = ref(false)


    // LATEST ORDERS
    const { data: latestOrders } = useQuery({
        queryKey: ['latest-orders'],
        queryFn: async () => {
            const respData = await apiService.get<LatestOrders[]>('/api/get/latest/orders')
            return respData
        },
    })

    const { data: cardAnalytics } = useQuery({
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
        queryClient.invalidateQueries({ queryKey: ['latest-orders'] })
        queryClient.invalidateQueries({ queryKey: ['card-analytics'] })
    }
</script>

<template>
    <!-- THIS WILL DISPLAY GRIDS FOR EACH GRAPHS AND CHARTS FOR DASHBOARD SPECIFIC LAYOUT -->

    <div
        class="w-full p-4 rounded-md bg-gray-100 dark:bg-gray-900 border-1 border-gray-400 dark:border-gray-700 dark:text-white"
    >
        <h1 class="text-2xl">Dashboard</h1>
        <p class="text-sm text-gray-400 mt-1 mb-7">
            Provide an overview of order metrics at glance
        </p>

        <div class="flex justify-end mb-6">
            <div class="flex flex-col">
                <label
                    for="startDate"
                    class="mb-2 text-xs text-gray-500 dark:text-gray-400 font-semibold tracking-wide"
                >
                    From
                </label>
                <div class="relative">
                    <input
                        id="startDate"
                        type="date"
                        v-model="dateFilter.start"
                        @change="onDateChange"
                        class="block w-38 px-4 py-2 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm bg-gray-50 dark:bg-gray-800 focus:(ring-2 ring-indigo-200 border-indigo-400) dark:focus:ring-indigo-500 dark:focus:border-indigo-500 text-base text-gray-700 dark:text-white transition-all duration-150 outline-none"
                    />
                </div>
            </div>
            <span class="mx-2 mt-6 text-gray-400 text-2xl font-light">–</span>
            <div class="flex flex-col">
                <label
                    for="endDate"
                    class="mb-2 text-xs text-gray-500 dark:text-gray-400 font-semibold tracking-wide"
                >
                    To
                </label>
                <div class="relative">
                    <input
                        id="endDate"
                        type="date"
                        v-model="dateFilter.end"
                        @change="onDateChange"
                        class="block w-38 px-4 py-2 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm bg-gray-50 dark:bg-gray-800 focus:(ring-2 ring-indigo-200 border-indigo-400) dark:focus:ring-indigo-500 dark:focus:border-indigo-500 text-base text-gray-700 dark:text-white transition-all duration-150 outline-none"
                    />
                </div>
            </div>
        </div>

        <!-- CARD ANALYTICS STATS START -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div
                class="bg-white dark:bg-gray-800 rounded-lg shadow flex flex-col items-center justify-center py-6 px-4 border border-gray-200 dark:border-gray-700 min-h-[110px]"
            >
                <span class="text-xs text-gray-500 dark:text-gray-400 mb-1">Total Sales</span>
                <span class="text-2xl font-semibold text-indigo-700 dark:text-indigo-400">
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
                class="bg-white dark:bg-gray-800 rounded-lg shadow flex flex-col items-center justify-center py-6 px-4 border border-gray-200 dark:border-gray-700 min-h-[110px]"
            >
                <span class="text-xs text-gray-500 dark:text-gray-400 mb-1">Total Customers</span>
                <span class="text-2xl font-semibold text-teal-600 dark:text-teal-400">
                    <template v-if="cardAnalytics && cardAnalytics.total_customers !== undefined">
                        {{ cardAnalytics.total_customers }}
                    </template>
                    <template v-else>–</template>
                </span>
            </div>
            <div
                class="bg-white dark:bg-gray-800 rounded-lg shadow flex flex-col items-center justify-center py-6 px-4 border border-gray-200 dark:border-gray-700 min-h-[110px]"
            >
                <span class="text-xs text-gray-500 dark:text-gray-400 mb-1">Pending Orders</span>
                <span class="text-2xl font-semibold text-amber-500 dark:text-amber-400">
                    <template
                        v-if="cardAnalytics && cardAnalytics.total_pending_orders !== undefined"
                    >
                        {{ cardAnalytics.total_pending_orders }}
                    </template>
                    <template v-else>–</template>
                </span>
            </div>
            <div
                class="bg-white dark:bg-gray-800 rounded-lg shadow flex flex-col items-center justify-center py-6 px-4 border border-gray-200 dark:border-gray-700 min-h-[110px]"
            >
                <span class="text-xs text-gray-500 dark:text-gray-400 mb-1">Completed Orders</span>
                <span class="text-2xl font-semibold text-green-600 dark:text-green-400">
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
          
            <div class="h-[300px] rounded-md p-3 lg:col-span-2">
                <p class="text-gray-700 dark:text-gray-300">Latest Orders</p>

                <fwb-table class="w-full h-full mt-3">
                    <fwb-table-head>
                        <fwb-table-head-cell class="text-xs text-white uppercase bg-gray-900">
                            Order No.
                        </fwb-table-head-cell>
                        <fwb-table-head-cell class="text-xs text-white uppercase bg-gray-900">
                            Product Name
                        </fwb-table-head-cell>
                        <fwb-table-head-cell
                            class="px-16 py-3 text-xs text-white uppercase bg-gray-900"
                        >
                            Design
                        </fwb-table-head-cell>
                        <fwb-table-head-cell class="text-xs text-white uppercase bg-gray-900">
                            Status
                        </fwb-table-head-cell>
                    </fwb-table-head>

                    <fwb-table-body>
                        <fwb-table-row v-for="order in latestOrders" :key="order.id">
                            <fwb-table-cell>{{ order.order_number }}</fwb-table-cell>
                            <fwb-table-cell>
                                <div class="flex flex-col gap-2">
                                    <div v-for="item in order.items" :key="'name-' + item.id">
                                        {{ item.product?.name }}
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            (x{{ item.total_quantity || 1 }})
                                        </span>
                                    </div>
                                </div>
                            </fwb-table-cell>
                            <fwb-table-cell>
                                <div class="flex flex-wrap gap-2">
                                    <template v-for="item in order.items" :key="'img-' + item.id">
                                        <img
                                            v-if="item.temp_url"
                                            :src="item.temp_url"
                                            class="w-16 h-16 object-cover rounded-md border dark:border-gray-700"
                                            alt="Design Image"
                                        />
                                    </template>
                                </div>
                            </fwb-table-cell>
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
