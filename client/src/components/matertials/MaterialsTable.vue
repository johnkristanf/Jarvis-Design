<script lang="ts" setup>
    import { ref } from 'vue'
    import AddMaterialsModal from './AddMaterialsModal.vue'
    const isModalOpen = ref(false)
</script>

<template>
    <div class="relative overflow-x-auto">
        <div class="flex justify-end gap-3 pb-3">
            <button @click="isModalOpen = true" type="button" class="w-[20%] px-3 py-2 text-sm font-medium text-center text-white bg-gray-900 rounded-lg hover:opacity-75 hover:cursor-pointer">
                Add New Material
            </button>

            <!-- SEARCH FIELD -->
            <div class="dark:bg-gray-900">
                <label for="table-search" class="sr-only">Search</label>
                <div class="relative mt-1">
                    <div class="absolute inset-y-0 rtl:inset-r-0 start-0 flex items-center ps-3 pointer-events-none">
                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
                        </svg>
                    </div>
                    <input
                        type="text"
                        id="table-search"
                        class="block pt-2 ps-10 text-sm text-gray-900 border border-gray-300 rounded-lg w-80 bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                        placeholder="Search for materials"
                    />
                </div>
            </div>
        </div>

        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400 mt-6">
            <thead class="text-xs text-gray-700 uppercase bg-gray-800 text-white dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3">Material Category</th>
                    <th scope="col" class="px-6 py-3">Material Name</th>
                    <th scope="col" class="px-6 py-3">Unit</th>
                    <th scope="col" class="px-6 py-3">Stock Quantity</th>
                    <th scope="col" class="px-6 py-3">Reorder Level</th>
                    <th scope="col" class="px-6 py-3">Action</th>
                </tr>
            </thead>
            <tbody>
                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 border-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600">
                    <td class="px-6 py-4">Paper & Consumables</td>
                    <td class="px-6 py-4">Sublimation Paper</td>
                    <td class="px-6 py-4">pcs</td>
                    <td class="px-6 py-4">500</td>
                    <td class="px-6 py-4">100</td>
                    <td class="px-6 py-6 flex gap-2">
                        <button class="font-medium text-blue-600 dark:text-blue-500 hover:underline">Edit</button>
                        <button class="font-medium text-red-800 dark:text-blue-500 hover:underline">Delete</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <AddMaterialsModal :open="isModalOpen" @close="isModalOpen = false" />
</template>
