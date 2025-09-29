<script lang="ts" setup>
    import { useQuery } from '@tanstack/vue-query'
    import { apiService } from '@/api/axios'
    import type { OrderLogs } from '@/types/order'

    const { data: orderLogs } = useQuery({
        queryKey: ['order_logs'],
        queryFn: async () => {
            const respData = await apiService.get<OrderLogs[]>('/api/get/order/logs')
            return respData
        },
    })
</script>

<template>
    <div class="overflow-x-auto mt-8 sm:rounded-lg">
        <table class="min-w-full border border-gray-300 rounded-md text-sm">
            <thead class="bg-gray-900 text-white">
                <tr>
                    <th class="px-4 py-2 text-left">User Name</th>
                    <th class="px-4 py-2 text-left">Material Name</th>
                    <th class="px-4 py-2 text-left">Total Quantity Used</th>
                    <th class="px-4 py-2 text-left">Unit</th>
                </tr>
            </thead>

            <!-- ✅ Data found -->
            <tbody v-if="orderLogs && orderLogs.length > 0">
                <tr v-for="(log, index) in orderLogs" :key="index" class="border-t border-gray-200 hover:bg-gray-50">
                    <td class="px-4 py-2">{{ log.users?.name }}</td>
                    <td class="px-4 py-2">{{ log.material_name }}</td>
                    <td class="px-4 py-2">{{ log.total_quantity_used }}</td>
                    <td class="px-4 py-2">{{ log.unit }}</td>
                </tr>
            </tbody>

            <!-- ✅ Not found -->
            <tbody v-else-if="orderLogs && orderLogs.length === 0">
                <tr>
                    <td colspan="4" class="text-center py-4 text-gray-500">No logs found.</td>
                </tr>
            </tbody>

            <!-- ✅ Still loading -->
            <tbody v-else>
                <tr>
                    <td colspan="4" class="text-center py-4 text-gray-500">Loading...</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
