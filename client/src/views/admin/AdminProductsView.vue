<script lang="ts" setup>
    import AddProductModal from '@/components/designs/AddProductModal.vue'
    import ProductsTable from '@/components/designs/ProductsTable.vue'
    import UpdateUploadedDesignsModal from '@/components/designs/UpdateUploadedDesignsModal.vue'
    import UploadedDesignsTable from '@/components/designs/UploadedDesignsTable.vue'
    import type { UploadedDesign } from '@/types/design'
    import { ArrowLeftIcon, ArrowRightIcon, PlusIcon } from '@heroicons/vue/20/solid'
    import { reactive, ref } from 'vue'

    const refToggles = reactive({
        show_uploaded_designs_table: false,
        show_update_design_modal: false,
        show_add_product_modal: false,
    })

    // SELECTING TO UPDATE UPLOADED DESIGN STATUS
    const selectedDesign = ref<UploadedDesign>()

    const openUpdateModal = (design: UploadedDesign) => {
        console.log('selected design: ', design)

        selectedDesign.value = design
        refToggles.show_update_design_modal = true
    }
</script>

<template>
    <div class="w-full p-4 rounded-md bg-gray-100 border-1 border-gray-400 mb-12">
        <h1 class="text-2xl">Business Products</h1>
        <p class="text-sm text-gray-400 mt-1">Displays business products offered</p>

        <div class="flex items-center justify-between">
            <!-- CUSTOMER UPLOADED AND PRE MADE DESIGNS BUTTON TOGGLERS -->
            <!-- <div class="mt-12 mb-5 w-full flex justify-end">
                <h1
                    v-if="!refToggles.show_uploaded_designs_table"
                    class="text-gray-600 hover:cursor-pointer hover:opacity-75 flex items-center"
                    @click="refToggles.show_uploaded_designs_table = true"
                >
                    View Uploaded Designs
                    <ArrowRightIcon class="size-6" />
                </h1>

                <h1
                    v-else
                    class="text-gray-600 hover:cursor-pointer hover:opacity-75 flex items-center"
                    @click="refToggles.show_uploaded_designs_table = false"
                >
                    <ArrowLeftIcon class="size-6" />
                    View Made Designs
                </h1>
            </div> -->
        </div>

        <!-- DESIGN NOTE -->
        <!-- <h1 class="text-sm text-gray-400 mt-6 mb-3">
            Note: Materials used must be attach after the design creation in order the automated
            deduction work
        </h1> -->

        <div class="flex justify-end my-4">
            <button
                v-if="!refToggles.show_uploaded_designs_table"
                type="button"
                @click="refToggles.show_add_product_modal = true"
                class="w-48 py-2 text-sm font-medium text-center inline-flex items-center justify-center gap-2 text-white bg-gray-900 hover:cursor-pointer rounded-lg hover:bg-gray-800 focus:ring-4 focus:outline-none focus:ring-gray-300 dark:bg-gray-600 dark:hover:bg-gray-700 dark:focus:ring-gray-800"
            >
                <PlusIcon class="size-6" />
                Add Product
            </button>
        </div>

        <!-- CUSTOMER UPLOADED DESIGNS TABLE -->
        <UploadedDesignsTable
            v-if="refToggles.show_uploaded_designs_table"
            @openUpdateModal="openUpdateModal"
        />

        <!-- PRE-MADE DESIGNS -->
        <ProductsTable v-if="!refToggles.show_uploaded_designs_table"></ProductsTable>
    </div>

    <AddProductModal
        v-if="refToggles.show_add_product_modal"
        @close="refToggles.show_add_product_modal = false"
    />

    <!-- UPDATE CUSTOMER UPLOADED DESIGNS MODAL -->
    <UpdateUploadedDesignsModal
        v-if="selectedDesign && refToggles.show_update_design_modal"
        :design="selectedDesign"
        @close="refToggles.show_update_design_modal = false"
    />
</template>
