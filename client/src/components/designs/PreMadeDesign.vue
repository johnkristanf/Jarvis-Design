<script lang="ts" setup>
    import { ref, watch } from 'vue'
    import PreviewDesignModal from './PreviewDesignModal.vue'
    import type { Designs, GroupedDesignsResponse, Product } from '@/types/design'
    import { useQuery, useQueryClient } from '@tanstack/vue-query'
    import { apiService } from '@/api/axios'
    import Loader from '../Loader.vue'
    import { useDesignFilterStore } from '@/stores/filter'
    import { FwbCard } from 'flowbite-vue'
    import OrderProductModal from './OrderProductModal.vue'
    import GenerateAIDesignsModal from './GenerateAIDesignsModal.vue'

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
    const showAIDesignModal = ref<boolean>(false)

    // CATEGORY EXPANSION TRACKER
    const expandedCategory = ref<number | null>(null)

    // HANDLE DESIGN SELECTION FOR MODAL
    const handleSelectDesign = (
        designs: Designs[],
        categoryName: string | number,
        tagName: string | number,
    ) => {
        openDesignModal.value = true
        selectedDesigns.value = designs
        selectedCategory.value = categoryName
        selectedTag.value = tagName
    }

    // ORDER RELATED
    const showOrderModal = ref<boolean>(false)
    const selectedProductRef = ref<Product>()
    const selectedCategoryRef = ref<string>()
    const fixedPriceAmmountRef = ref<number>()

    const openOrderDetailsModal = (categoryName: string, selectedProduct: Product) => {
        selectedProductRef.value = selectedProduct
        selectedCategoryRef.value = categoryName
        showOrderModal.value = true
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

            return respData
        },
    })

    // REFRESH DESIGNS ON FILTER CHANGE
    const queryClient = useQueryClient()

    watch(
        () => [filterStore.selectedSort.tag, filterStore.selectedCategories.slice()],
        () => {
            queryClient.invalidateQueries({ queryKey: ['designs'] })
        },
        { deep: true },
    )

    const handleOpenAIDesigns = () => {
        showAIDesignModal.value = true
        showOrderModal.value = false
    }
</script>

<template>
    <div
        v-if="!showUploadedDesignsTableRef && designs"
        class="space-y-6 grid grid-cols-3 sm:grid-col-4"
    >
        <div v-for="category in designs" :key="category.id">
            <!-- CATEGORY CARD -->
            <fwb-card @click="toggleCategory(category.id)" class="cursor-pointer">
                <div class="p-5">
                    <h5 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ category.name }}
                    </h5>
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        {{
                            category.is_fixed_priced
                                ? `₱${category.fixed_price}`
                                : 'Variable Pricing'
                        }}
                    </p>
                </div>
            </fwb-card>

            <!-- PRODUCT CARDS -->
            <div
                v-if="expandedCategory === category.id && category.products.length > 0"
                class="mt-4 flex flex-col"
            >
                <fwb-card v-for="product in category.products" :key="product.id">
                    <div
                        class="p-4 w-full bg-gray-900 hover:cursor-pointer hover:opacity-75"
                        @click="openOrderDetailsModal(category.name, product)"
                    >
                        <h5 class="text-lg font-medium text-white dark:text-white">
                            {{ product.name }}
                            {{ product.fabric_type ? `(${product.fabric_type.name})` : '' }}
                        </h5>
                        <p class="text-sm text-white dark:text-gray-400">
                            ₱{{ product.unit_price }}
                        </p>
                    </div>
                </fwb-card>
            </div>

            <!-- NO PRODUCTS MESSAGE -->
            <div
                v-else-if="expandedCategory === category.id && category.products.length === 0"
                class="text-sm text-gray-500 dark:text-gray-400 mt-2 px-4"
            >
                No products in this category.
            </div>
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

    <GenerateAIDesignsModal v-if="showAIDesignModal" @close="showAIDesignModal = false" />

    <OrderProductModal
        v-if="showOrderModal && selectedCategoryRef && selectedProductRef"
        :categoryName="selectedCategoryRef"
        :product="selectedProductRef"
        @close="showOrderModal = false"
        @openAIDesigns="handleOpenAIDesigns"
    />
</template>
