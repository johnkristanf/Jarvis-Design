<script lang="ts" setup>
    import MaterialsTable from '@/components/matertials/MaterialsTable.vue'
</script>

<template>
    <div class="w-full p-4 rounded-md bg-gray-100 border-1 border-gray-400">
        <h1 class="text-2xl">Business Materials</h1>
        <p class="text-sm text-gray-400 mt-1 mb-7">
            Manages available printing materials, including types, stock levels, and details used in
            design orders.
        </p>

        <MaterialsTable />
    </div>
</template>
