<script lang="ts" setup>
    import DataTable from 'primevue/datatable'
    import Column from 'primevue/column'
    import { useQuery } from '@tanstack/vue-query'
    import { apiService } from '@/api/axios'
    import type { OrderLogs } from '@/types/order'

    const {
        isPending,
        isError,
        data: orderLogs,
        error,
    } = useQuery({
        queryKey: ['order_logs'],
        queryFn: async () => {
            const respData = await apiService.get<OrderLogs[]>('/api/get/order/logs')
            console.log('respData order logs: ', respData)
            return respData
        },
    })
</script>

<template>
    <DataTable
        :value="orderLogs"
        tableStyle="min-width: 10rem"
        :lazy="true"
        class="custom-table"
        :pt="{
            thead: { class: 'my-custom-header' }, // Change from header to thead
            headerCell: { class: 'my-custom-header-cell' } // Add this for the cells
        }"
    >
        <Column field="users.name" header="User Name" />
        <Column field="material_name" header="Material Name" />
        <Column field="total_quantity_used" header="Total Quantity Used" />
        <Column field="unit" header="Unit" />
    </DataTable>
</template>

<style>
    .custom-table {
        margin-top: 2rem;
    }

    /* More specific selectors to ensure they override PrimeVue's styles */
    .my-custom-header {
        background-color: #3b82f6 !important; /* Blue background with !important */
        color: white !important; /* White text with !important */
    }
    
    .my-custom-header-cell {
        background-color: #3b82f6 !important;
        color: white !important;
    }
</style>
