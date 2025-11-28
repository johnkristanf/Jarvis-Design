<script setup>
    import { useQuery } from '@tanstack/vue-query'
    import { apiService } from '@/api/axios'
import { formatDate } from '@/helper/designs'

    const props = defineProps({
        fabricId: {
            type: String,
            required: false,
        },
    })

    const { isPending, data: fabricLogs } = useQuery({
        queryKey: ['fabric-adjust-logs', props.fabricId],
        queryFn: async () => {
            // Pass fabricId as a query parameter
            const respData = await apiService.get(`/api/get/fabric/adjust/logs`, {
                params: { fabric_id: props.fabricId },
            })
            console.log('respData logs: ', respData)
            return respData
        },
        enabled: !!props.fabricId,
    })
</script>

<template>
    <div class="overflow-x-auto sm:rounded-lg">
        <table class="min-w-full border border-gray-300 rounded-md text-sm">
            <thead class="bg-gray-900 text-white">
                <tr>
                    <th class="px-4 py-2 text-left">Date</th>
                    <th class="px-4 py-2 text-left">Fabric</th>
                    <th class="px-4 py-2 text-left">Stock Quantity Adjusted</th>
                    <th class="px-4 py-2 text-left">Action</th>
                </tr>
            </thead>

            <!-- ✅ Data found -->
            <tbody v-if="fabricLogs && fabricLogs.length > 0">
                <tr
                    v-for="(log, index) in fabricLogs"
                    :key="index"
                    class="border-t border-gray-200 hover:bg-gray-50"
                >
                    <td class="px-4 py-2">
                        {{ log.delivery_date ? formatDate(log.delivery_date) : formatDate(log.created_at) }}
                    </td>
                    <td class="px-4 py-2">
                        <template v-if="log.material && log.material.name">
                            {{ log.material.name }}
                        </template>
                        <template v-else>
                            <span class="inline-flex items-center px-2 py-1 rounded bg-red-100 text-red-700 text-xs font-bold border border-red-300">
                                Fabric Deleted
                            </span>
                        </template>
                    </td>
                    <td class="px-4 py-2">{{ log.quantity }}</td>
                    <td class="px-4 py-2">
                        <span
                            class="px-2 py-1 rounded-md text-xs font-semibold"
                            :class="
                                log.action === 'added'
                                    ? 'bg-green-100 text-green-800'
                                    : log.action === 'reduced'
                                      ? 'bg-red-100 text-red-800'
                                      : 'bg-gray-100 text-gray-800'
                            "
                        >
                            {{ log.action ? log.action.toUpperCase() : '' }}
                        </span>
                    </td>
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
