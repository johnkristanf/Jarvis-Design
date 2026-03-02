<script lang="ts" setup>
    import { apiService } from '@/api/axios'
    import { useQuery } from '@tanstack/vue-query'
    import Loader from '../Loader.vue'
    import AttachMaterialsModal from './AttachMaterialsModal.vue'
    import { reactive, ref, watch } from 'vue'
    import { OrderTypes } from '@/types/order'
    import type { Products } from '@/types/product'
    import AddDesignModal from './AddDesignModal.vue'
    import PaginationControls from '../PaginationControls.vue'
    import type { PaginatedResponse } from '@/types/pagination'
    import type { BusinessProductDesign } from '@/types/design'
    import DeleteDialog from '../DeleteDialog.vue'

    const modals = reactive({
        show_attach_materials: false,
        show_add_design: false,
    })

    // SELETED PRODUCT DETAILS
    const selectedProductCategory = ref<string>()
    const selectedProductID = ref<number>()
    const selectedProductName = ref<string>()
    const selectedDesignImages = ref<BusinessProductDesign[]>()

    // PAGINATION REFS
    const currentPage = ref(1)

    // GET ALL PRE - MADE DESIGNS DATA QUERY
    const {
        isPending,
        isError,
        data: products,
        error,
    } = useQuery({
        queryKey: ['products', currentPage],
        queryFn: async () => {
            const respData = await apiService.get<PaginatedResponse<Products>>(
                `/api/get/all/products?page=${currentPage.value}`,
            )
            return respData
        },
    })

    const handleAddDesignOnProduct = (
        product_id: number,
        product_name: string,
        product_category: string,
        design_images: BusinessProductDesign[],
    ) => {
        selectedProductCategory.value = product_category
        selectedProductID.value = product_id
        selectedProductName.value = product_name
        selectedDesignImages.value = design_images
        modals.show_add_design = true
    }
</script>

<template>
    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
            <thead
                class="text-xs text-white uppercase bg-gray-900 dark:bg-gray-700 dark:text-gray-400"
            >
                <tr>
                    <th scope="col" class="px-8 py-3">Category</th>
                    <th scope="col" class="px-6 py-3">Product Name</th>
                    <th scope="col" class="px-6 py-3">Price</th>
                    <th scope="col" class="px-6 py-3">Action</th>
                </tr>
            </thead>
            <tbody v-if="products">
                <tr
                    v-for="product in products.data"
                    :key="product.id"
                    class="bg-white border-b dark:bg-gray-800 dark:border-gray-700"
                >
                    <!-- Product Category -->
                    <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                        {{ product.design_category.name }}
                    </td>

                    <!-- Product Name -->
                    <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                        {{ product.name }}
                    </td>

                    <!-- Unit Price -->
                    <td class="px-6 py-4">₱ {{ product.unit_price }}</td>

                    <!-- Actions -->
                    <td class="font-medium px-6 py-6 flex items-center gap-3">
                        <button
                            @click="
                                handleAddDesignOnProduct(
                                    product.id,
                                    product.name,
                                    product.design_category.name,
                                    product.design_images,
                                )
                            "
                            class="flex items-center gap-1 px-2 py-1 bg-indigo-600 text-white rounded text-xs hover:cursor-pointer hover:opacity-75 transition"
                            title="Upload Design"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-3 w-3"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 16v2a2 2 0 002 2h8a2 2 0 002-2v-2M7 10l5-5m0 0l5 5m-5-5v12"
                                />
                            </svg>
                            <span>Upload Design</span>
                        </button>

                        <!-- <button class="text-blue-600 hover:underline">Edit</button> -->
                        <DeleteDialog
                            :selectedID="product.id"
                            endpoint_url="/api/delete/product"
                            query_key="products"
                            success_message="Product Deleted Successfully"
                        />
                    </td>
                </tr>

                <PaginationControls
                    :currentPage="products.current_page"
                    :lastPage="products.last_page"
                    @changePage="currentPage = $event"
                />
            </tbody>
        </table>

        <!-- ATTACH MATERIALS MODAL -->
        <AttachMaterialsModal
            v-if="modals.show_attach_materials && selectedProductID"
            :selectedProductID="selectedProductID"
            :designType="OrderTypes.PRE_MADE"
            @close="modals.show_attach_materials = false"
        />

        <AddDesignModal
            v-if="modals.show_add_design && selectedProductID"
            :selectedProductCategory="selectedProductCategory"
            :selectedProductID="selectedProductID"
            :selectedProductName="selectedProductName"
            :designImages="selectedDesignImages"
            @close="modals.show_add_design = false"
        />

        <!-- LOADER -->
        <Loader v-if="isPending" msg="Loading Products..." />

        <!-- ERROR -->
        <div v-if="isError" class="text-red-500 p-4">
            Error loading designs: {{ error?.message }}
        </div>
    </div>
</template>
