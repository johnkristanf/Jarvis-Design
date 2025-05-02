<script lang="ts" setup>
    import { ref, watch } from 'vue'
    import PreviewDesignModal from './PreviewDesignModal.vue'
    import type { Designs } from '@/types/design'
    import { useQuery, useQueryClient } from '@tanstack/vue-query'
    import { apiService } from '@/api/axios'
    import Loader from '../Loader.vue'
    import { useDesignFilterStore } from '@/stores/filter'

    // FILTER SELECT STORE
    const filterStore = useDesignFilterStore()

    defineProps<{
        showUploadedDesignsTableRef: boolean
    }>()

    // PREVIEW PREMADE DESIGN REF
    const selectedDesign = ref<Designs>()
    const openDesignModal = ref(false)

    // PREVIEW SELECTED DESIGN MODAL TOGGLER
    const handleSelectDesign = (design: Designs) => {
        openDesignModal.value = true
        selectedDesign.value = design
    }

    // USE QUERY FOR FETCHING DESIGN CATEGORIES
    const { data: designs, isLoading } = useQuery({
        queryKey: ['designs'],
        queryFn: async () => {
            const sortTag = filterStore.selectedSort.tag
            const categoryIds = filterStore.selectedCategories.join(',')

            const respData = await apiService.get<Designs[]>(
                `/api/get/pre_made/designs/${sortTag}/${categoryIds}`,
            )

            console.log("respData pre made des: ", respData);
            
            return respData
        },
    })

    // DYNAMIC REFETCHING UPON FILTER CHANGES
    const queryClient = useQueryClient();

    watch(
        () => [filterStore.selectedSort.tag, filterStore.selectedCategories.slice()],
        () => {
            queryClient.invalidateQueries({ queryKey: ['designs'] })
        },
        { deep: true },
    )
</script>

<template>
    <div
        v-if="!showUploadedDesignsTableRef && designs"
        class="grid grid-cols-1 sm:grid-cols-3 gap-12"
    >
        <div
            v-for="design in designs"
            :key="design.id"
            class="group relative"
            @click="handleSelectDesign(design)"
        >
            <img
                :src="design.image_path"
                class="aspect-square w-full rounded-md bg-gray-200 object-cover group-hover:opacity-75 lg:aspect-auto lg:h-80"
            />

            <div class="mt-4 flex justify-between">
                <div>
                    <h3 class="text-sm text-gray-700">
                        <h1>
                            <span aria-hidden="true" class="absolute inset-0" />
                            {{ design.name }}
                        </h1>
                    </h3>
                </div>

                <p class="text-sm font-medium text-gray-900">₱ {{ design.price }}</p>
            </div>
        </div>
    </div>

    <div v-if="(!isLoading && !designs) || (designs && designs.length == 0)">
        <p>No designs available.</p>
    </div>

    <div v-if="isLoading ">
        <Loader msg="Loading Designs..." />
    </div>

    <PreviewDesignModal
        v-if="selectedDesign"
        :design="selectedDesign"
        :isOpen="openDesignModal"
        @close="openDesignModal = false"
    />
</template>
