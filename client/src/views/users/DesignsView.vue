<script lang="ts" setup>
    import PreMadeDesignCard from '@/components/designs/PreMadeDesign.vue'
    import { ref } from 'vue'

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
                    <!-- <FIlterDesign /> -->

                    <!-- DESIGN OPTIONS -->
                    <!-- <DesignOptions /> -->
                </div>
            </div>

            <div class="mt-6">
                <!-- BUTTON TOGGLERS BETWEEN UPLOADED DESIGNS AND PRE MADE DESIGNS -->
                <!-- <div
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
                </div> -->

                <!-- UPLOADED DESIGNS -->
                <!-- <div class="w-full" v-if="showUploadedDesignsTableRef">
                    <UploadedDesignsTable />
                </div> -->

                <!-- PRE-MADE DESIGNS -->
                <PreMadeDesignCard :showUploadedDesignsTableRef="showUploadedDesignsTableRef" />
            </div>
        </div>
    </div>
</template>
