<script lang="ts" setup>
    import PreviewDesignModal from '@/components/designs/PreviewDesignModal.vue'
    import Loader from '@/components/Loader.vue'
    import type { Designs } from '@/types/design'
    import { ArrowLeftIcon, ArrowRightIcon } from '@heroicons/vue/20/solid'
    import { ref } from 'vue'
    import UploadedDesignsTable from '@/components/designs/UploadedDesignsTable.vue'
    import { useAuthStore } from '@/stores/user'
    import FIlterDesign from '@/components/designs/FIlterDesign.vue'
    import DesignOptions from '@/components/designs/DesignOptions.vue'
    import { useQuery } from '@tanstack/vue-query'
    import { apiService } from '@/api/axios'
    import PreMadeDesignCard from '@/components/designs/PreMadeDesign.vue'

    // USER AUTH STATE
    const authStore = useAuthStore()

    // AI DESIGNS GENERATION REF'S
    const imageUrls = ref([])
    const isLoadingMutation = ref(false)
    const loaderMsg = ref<string>('')

    // REF FOR SHOWING UPLOADED DESIGNS OR PRE MADE DESIGNS
    const showUploadedDesignsTableRef = ref<boolean>(false)
</script>

<template>
    <div class="bg-white">
        <div class="mx-auto px-4 pt-10 pb-18 sm:px-6 sm:py-24 lg:max-w-7xl lg:px-8">
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-bold tracking-tight text-gray-900">Explore our designs</h2>

                <div class="w-[60%] flex justify-end gap-3">
                    <!-- FILTERS -->
                    <FIlterDesign />

                    <DesignOptions />
                </div>
            </div>

            <div class="mt-6">
                <!-- BUTTON TOGGLERS BETWEEN UPLOADED DESIGNS AND PRE MADE DESIGNS -->
                <div
                    v-if="authStore.currentUser && authStore.isAuthenticated"
                    class="mt-12 mb-5 w-full flex justify-end"
                >
                    <h1
                        v-if="!showUploadedDesignsTableRef"
                        class="text-gray-600 hover:cursor-pointer hover:opacity-75 flex items-center"
                        @click="showUploadedDesignsTableRef = true"
                    >
                        View Uploaded Designs
                        <ArrowRightIcon class="size-6" />
                    </h1>

                    <h1
                        v-else
                        class="text-gray-600 hover:cursor-pointer hover:opacity-75 flex items-center"
                        @click="showUploadedDesignsTableRef = false"
                    >
                        <ArrowLeftIcon class="size-6" />
                        View Made Designs
                    </h1>
                </div>

                <!-- UPLOADED DESIGNS -->
                <div class="w-full" v-if="showUploadedDesignsTableRef">
                    <UploadedDesignsTable />
                </div>

                <!-- PRE-MADE DESIGNS -->
                <PreMadeDesignCard 
                    :showUploadedDesignsTableRef="showUploadedDesignsTableRef"
                />
            </div>
        </div>
    </div>

   
</template>
