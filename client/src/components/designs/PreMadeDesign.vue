<script lang="ts" setup>
    import { ref, watch } from 'vue'
    import PreviewDesignModal from './PreviewDesignModal.vue'
    import type {
        BusinessProductDesign,
        Designs,
        GroupedDesignsResponse,
        Product,
    } from '@/types/design'
    import { useQuery, useQueryClient } from '@tanstack/vue-query'
    import { apiService } from '@/api/axios'
    import Loader from '../Loader.vue'
    import { useDesignFilterStore } from '@/stores/filter'
    import { FwbCard } from 'flowbite-vue'
    import OrderProductModal from './OrderProductModal.vue'
    import DesignOptionModal from './DesignOptionModal.vue'

    // FILTER SELECT STORE
    const filterStore = useDesignFilterStore()

    defineProps<{
        showUploadedDesignsTableRef: boolean
    }>()

    // PREVIEW PREMADE DESIGN REF
    const selectedDesigns = ref<Designs[]>()
    const selectedCategory = ref<string | number>()
    const selectedTag = ref<string | number>()
    const openDesignModal = ref(false)
    const businessProductDesign = ref<BusinessProductDesign[]>([])

    // CATEGORY EXPANSION TRACKER
    const expandedCategory = ref<number | null>(null)

    // ORDER RELATED
    const showOrderModal = ref<boolean>(false)
    const showDesignOptionModal = ref<boolean>(false)
    const selectedProductRef = ref<Product>()
    const selectedCategoryRef = ref<string>()
    // const fixedPriceAmmountRef = ref<number>()

    const openOrderDetailsModal = (categoryName: string, selectedProduct: Product) => {
        selectedProductRef.value = selectedProduct
        selectedCategoryRef.value = categoryName
        showOrderModal.value = true
    }

    const openDesignOptionModal = (selectedProduct: Product) => {
        selectedProductRef.value = selectedProduct
        showDesignOptionModal.value = true

        console.log('selectedProductRef.value: ', selectedProductRef.value)
        console.log('showDesignOptionModal.value: ', showDesignOptionModal.value)
    }

    // HANDLE CATEGORY EXPANSION
    const toggleCategory = (categoryId: number) => {
        expandedCategory.value = expandedCategory.value === categoryId ? null : categoryId
    }

    // FETCH DESIGN CATEGORIES
    const { data: designs, isLoading } = useQuery({
        queryKey: ['designs'],
        queryFn: async () => {
            const sortTag = filterStore.selectedSort.tag
            const categoryIds = filterStore.selectedCategories.join(',')

            const respData = await apiService.get<GroupedDesignsResponse>(
                `/api/get/pre_made/designs/${sortTag}/${categoryIds}`,
            )

            console.log('DESIGNS NI: ', respData)
            return respData
        },
    })

    // FETCH UPLOADED BUSINESS DESIGNS
    const fetchBusinessDesigns = async (product_id: number) => {
        // isLoadingBusinessDesigns.value = true
        const designs = await apiService.get<BusinessProductDesign[]>(
            `/api/get/bussiness_designs/${product_id}`,
        )
        console.log('designs: ', designs)

        businessProductDesign.value = designs
        // isLoadingBusinessDesigns.value = false
    }

    // REFRESH DESIGNS ON FILTER CHANGE
    const queryClient = useQueryClient()

    watch(
        () => [filterStore.selectedSort.tag, filterStore.selectedCategories.slice()],
        () => {
            queryClient.invalidateQueries({ queryKey: ['designs'] })
        },
        { deep: true },
    )

    // Pre-select the 1st category in designs
    watch(
        () => designs?.value,
        (newDesigns) => {
            if (
                Array.isArray(newDesigns) &&
                newDesigns.length > 0 &&
                expandedCategory.value === null
            ) {
                expandedCategory.value = newDesigns[0].id
            }
        },
    )

    // // Provide a static images array for testing, to be shown under each product card
    // const staticImages = [
    //     'https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=facearea&w=400&q=80',
    //     'https://images.unsplash.com/photo-1519125323398-675f0ddb6308?auto=format&fit=facearea&w=400&q=80',
    //     'https://images.unsplash.com/photo-1465101046530-73398c7f28ca?auto=format&fit=facearea&w=400&q=80',
    // ]
</script>

<template>
    <!-- Added dark mode support using dark:bg-* and dark:text-* classes, as seen in LoginView.vue -->
    <div
        v-if="!showUploadedDesignsTableRef && designs"
        class="w-full bg-white dark:bg-gray-900 transition-colors duration-200 rounded-xl p-3 md:p-6"
    >
        <!-- CATEGORY FILTERS -->
        <div class="flex items-center gap-8 mb-6">
            <div v-for="category in designs" :key="category.id">
                <h1
                    @click="toggleCategory(category.id)"
                    class="text-xs font-bold text-gray-500 dark:text-gray-100 hover:opacity-75 hover:cursor-pointer"
                    :class="{
                        'text-blue-600 dark:text-blue-400': expandedCategory === category.id,
                    }"
                >
                    {{ category.name }}
                </h1>
            </div>
        </div>

        <!-- PRODUCT CARDS DISPLAY AREA (SEPARATE FROM FILTERS) -->
        <div v-if="expandedCategory !== null">
            <template v-for="category in designs" :key="category.id">
                <div v-if="expandedCategory === category.id">
                    <!-- PRODUCT CARDS -->
                    <div
                        v-if="category.products.length > 0"
                        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"
                    >
                        <fwb-card
                            v-for="product in category.products"
                            :key="product.id"
                            class="hover:cursor-pointer hover:opacity-75 bg-white dark:bg-gray-800 transition-colors duration-200 border border-gray-200 dark:border-gray-700"
                            @click="openDesignOptionModal(product)"
                        >
                            <div class="flex gap-2 p-2 justify-center">
                                <!-- FOR LOOP BUSINESS DESIGNS HERE -->

                                <!-- Loop over product designs and show images -->
                                <img
                                    v-if="product.designs && product.designs.length"
                                    :key="product.designs[0].id"
                                    :src="product.designs[0].temp_url"
                                    alt="Product Design"
                                    class="object-cover rounded shadow border border-gray-200 dark:border-gray-700 max-h-44 w-full bg-white dark:bg-gray-900"
                                />
                            </div>

                            <div class="p-4 w-full flex items-center justify-between">
                                <div class="flex flex-col">
                                    <h5
                                        class="text-md font-medium text-gray-900 dark:text-gray-100 transition-colors duration-200"
                                    >
                                        {{ product.name }}
                                        <!-- <span v-if="product.fabric_type">({{ product.fabric_type.name }})</span> -->
                                    </h5>
                                    <p
                                        class="text-sm text-gray-700 dark:text-gray-400 transition-colors duration-200"
                                    >
                                        ₱{{ product.unit_price }}
                                    </p>
                                </div>
                            </div>
                        </fwb-card>
                    </div>

                    <!-- NO PRODUCTS MESSAGE -->
                    <div
                        v-else
                        class="text-sm text-gray-500 dark:text-gray-400 px-4 py-8 text-center transition-colors duration-200"
                    >
                        No products in this category.
                    </div>
                </div>
            </template>
        </div>
    </div>

    <Loader v-if="isLoading" msg="Loading Designs..." />

    <PreviewDesignModal
        v-if="selectedDesigns && selectedCategory && selectedTag"
        :design="selectedDesigns"
        :category="selectedCategory"
        :tag="selectedTag"
        :isOpen="openDesignModal"
        @close="openDesignModal = false"
    />
    <!-- 
    <OrderProductModal
        v-if="showOrderModal && selectedCategoryRef && selectedProductRef"
        :categoryName="selectedCategoryRef"
        :product="selectedProductRef"
        @close="showOrderModal = false"
    /> -->

    <DesignOptionModal
        v-if="showDesignOptionModal && selectedProductRef"
        :product="selectedProductRef"
        @close="showDesignOptionModal = false"
    />
</template>
