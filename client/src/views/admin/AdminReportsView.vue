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
    import { reactive, ref, computed, onMounted, onUnmounted } from 'vue'

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

    // --- REF STATES FOR EXPORTS ---
    const isExporting = ref(false)
    const selectedExportTypes = ref<string[]>(['monthly_sales', 'fabric_used', 'category_sales'])

    const isDropdownOpen = ref(false)

    const toggleDropdown = () => {
        isDropdownOpen.value = !isDropdownOpen.value
    }

    const closeDropdown = (e: Event) => {
        if (!(e.target as HTMLElement).closest('.export-dropdown-container')) {
            isDropdownOpen.value = false
        }
    }

    onMounted(() => {
        document.addEventListener('click', closeDropdown)
    })

    onUnmounted(() => {
        document.removeEventListener('click', closeDropdown)
    })

    const selectedExportTypesText = computed(() => {
        if (selectedExportTypes.value.length === 3) return 'All Reports'
        if (selectedExportTypes.value.length === 0) return 'Select Reports'
        return `${selectedExportTypes.value.length} selected`
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
        maintainAspectRatio: false,
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
    const exportMonthlyReport = async () => {
        try {
            const params = { start_date: dateFilter.start, end_date: dateFilter.end }
            const response = await apiService.get<Blob>('/api/get/reports/monthly-sales', {
                params,
                responseType: 'blob',
            })

            downloadBlobFile(response, 'summary_total_orders_placed_report.pdf')
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

            downloadBlobFile(response, 'sales_per_category_report.pdf')
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

            downloadBlobFile(response, 'fabric_used_report.pdf')
        } catch (error) {
            console.error('Download Report Error: ', error)
        }
    }

    const handleExport = async () => {
        if (selectedExportTypes.value.length === 0) return

        isExporting.value = true
        try {
            if (selectedExportTypes.value.includes('monthly_sales')) {
                await exportMonthlyReport()
            }
            if (selectedExportTypes.value.includes('fabric_used')) {
                await downloadReportPerFabricUsed()
            }
            if (selectedExportTypes.value.includes('category_sales')) {
                await downloadReportPerCategory()
            }
        } finally {
            isExporting.value = false
            isDropdownOpen.value = false
        }
    }

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

    <div
        class="w-full p-4 rounded-md bg-gray-100 dark:bg-gray-900 border-1 border-gray-400 dark:border-gray-700 dark:text-white"
    >
        <h1 class="text-2xl">Reports</h1>
        <p class="text-sm text-gray-400 mt-1 mb-7">
            Overview reports for every transactions, providing an insightful data.
        </p>

        <div class="flex flex-wrap items-end justify-end mb-6 gap-4">
            <div class="flex items-center">
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

            <div class="flex items-end gap-2 export-dropdown-container relative z-20">
                <div class="flex flex-col relative w-48">
                    <label
                        class="mb-2 text-xs text-gray-500 dark:text-gray-400 font-semibold tracking-wide"
                    >
                        Period Report
                    </label>
                    <button
                        @click="toggleDropdown"
                        class="flex items-center justify-between w-full px-4 py-2 text-left bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm focus:(ring-2 ring-indigo-200 border-indigo-400) dark:focus:ring-indigo-500 dark:focus:border-indigo-500 text-base text-gray-700 dark:text-white transition-all duration-150 outline-none"
                    >
                        <span>{{ selectedExportTypesText }}</span>
                        <svg
                            class="w-4 h-4 ml-2"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 9l-7 7-7-7"
                            ></path>
                        </svg>
                    </button>

                    <div
                        v-show="isDropdownOpen"
                        class="absolute left-0 w-full mt-1 top-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg"
                    >
                        <ul class="p-3 space-y-3 text-sm text-gray-700 dark:text-gray-300">
                            <li>
                                <div class="flex items-center">
                                    <input
                                        id="checkbox-item-monthly"
                                        type="checkbox"
                                        value="monthly_sales"
                                        v-model="selectedExportTypes"
                                        class="w-4 h-4 text-blue-600 bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500 focus:ring-2 hover:cursor-pointer"
                                    />
                                    <label
                                        for="checkbox-item-monthly"
                                        class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300 hover:cursor-pointer w-full"
                                    >
                                        Summary Of Total Orders
                                    </label>
                                </div>
                            </li>
                            <li>
                                <div class="flex items-center">
                                    <input
                                        id="checkbox-item-category"
                                        type="checkbox"
                                        value="category_sales"
                                        v-model="selectedExportTypes"
                                        class="w-4 h-4 text-blue-600 bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500 focus:ring-2 hover:cursor-pointer"
                                    />
                                    <label
                                        for="checkbox-item-category"
                                        class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300 hover:cursor-pointer w-full"
                                    >
                                        Sales Per Product Category
                                    </label>
                                </div>
                            </li>

                            <li>
                                <div class="flex items-center">
                                    <input
                                        id="checkbox-item-fabric"
                                        type="checkbox"
                                        value="fabric_used"
                                        v-model="selectedExportTypes"
                                        class="w-4 h-4 text-blue-600 bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500 focus:ring-2 hover:cursor-pointer"
                                    />
                                    <label
                                        for="checkbox-item-fabric"
                                        class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300 hover:cursor-pointer w-full"
                                    >
                                        Total Fabric Used
                                    </label>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
                <button
                    @click="handleExport"
                    :disabled="isExporting || selectedExportTypes.length === 0"
                    :class="[
                        'px-5 py-2 text-base font-medium text-white rounded-lg transition-opacity',
                        isExporting || selectedExportTypes.length === 0
                            ? 'bg-gray-400 cursor-not-allowed'
                            : 'bg-blue-600 hover:cursor-pointer hover:opacity-90',
                    ]"
                >
                    <span v-if="isExporting">Exporting...</span>
                    <span v-else>Export</span>
                </button>
            </div>
        </div>

        <!-- CARD ANALYTICS STATS END -->

        <div class="grid grid-cols-1 sm:grid-cols-1 md:grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
            <div class="h-[300px] rounded-md p-3 bg-gray-200 dark:bg-gray-800">
                <div class="w-full h-full">
                    <Line
                        v-if="monthlySalesReport"
                        id="monthly-sales"
                        :options="lineChartOptions"
                        :data="monthlySalesReport"
                    />
                </div>
            </div>

            <div class="h-[300px] rounded-md p-3 bg-gray-200 dark:bg-gray-800">
                <div class="w-full h-full">
                    <Bar
                        v-if="salePerProductCategory"
                        id="sales-per-category"
                        :options="chartOptions"
                        :data="salePerProductCategory"
                    />
                </div>
            </div>

            <div class="h-[300px] rounded-md p-3 lg:col-span-2 bg-gray-200 dark:bg-gray-800 mt-5">
                <div class="w-full h-full">
                    <Bar
                        v-if="fabricUsed"
                        id="fabric-used"
                        :options="chartOptions"
                        :data="fabricUsed"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
