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
            </div>

            <div class="mt-6">
                <PreMadeDesignCard :showUploadedDesignsTableRef="showUploadedDesignsTableRef" />
            </div>
        </div>
    </div>
</template>
