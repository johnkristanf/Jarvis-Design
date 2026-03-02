<script lang="ts" setup>
    import AddProductModal from '@/components/designs/AddProductModal.vue'
    import ProductsTable from '@/components/designs/ProductsTable.vue'
    import { PlusIcon } from '@heroicons/vue/20/solid'
    import { reactive } from 'vue'

    const refToggles = reactive({
        show_uploaded_designs_table: false,
        show_update_design_modal: false,
        show_add_product_modal: false,
    })

    // SELECTING TO UPDATE UPLOADED DESIGN STATUS
    // const selectedDesign = ref<UploadedDesign>()

    // const openUpdateModal = (design: UploadedDesign) => {
    //     console.log('selected design: ', design)

    //     selectedDesign.value = design
    //     refToggles.show_update_design_modal = true
    // }
</script>

<template>
    <div
        class="w-full p-4 rounded-md bg-gray-100 dark:bg-gray-900 border-1 border-gray-400 dark:border-gray-700 dark:text-white mb-12"
    >
        <h1 class="text-2xl">Business Products</h1>
        <p class="text-sm text-gray-400 dark:text-gray-400 mt-1">
            Displays business products offered
        </p>

        <div class="flex justify-end my-4">
            <button
                v-if="!refToggles.show_uploaded_designs_table"
                type="button"
                @click="refToggles.show_add_product_modal = true"
                class="w-48 py-2 text-sm font-medium text-center inline-flex items-center justify-center gap-2 text-white bg-gray-900 hover:cursor-pointer rounded-lg hover:bg-gray-800 focus:ring-4 focus:outline-none focus:ring-gray-300 class:bg-gray-600 class:hover:bg-gray-700 class:focus:ring-gray-800"
            >
                <PlusIcon class="size-6" />
                Add Product
            </button>
        </div>

        <ProductsTable v-if="!refToggles.show_uploaded_designs_table" />
    </div>

    <AddProductModal
        v-if="refToggles.show_add_product_modal"
        @close="refToggles.show_add_product_modal = false"
    />
</template>
