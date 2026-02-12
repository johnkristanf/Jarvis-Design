<script lang="ts" setup>
    import GenerateAIDesigns from '@/components/designs/GenerateAIDesigns.vue'
    import PreMadeDesignCard from '@/components/designs/PreMadeDesign.vue'
    import { ref } from 'vue'
    import {
        TransitionRoot,
        TransitionChild,
        Dialog,
        DialogPanel,
        DialogTitle,
    } from '@headlessui/vue'
    import { useFetchAuthenticatedUser } from '@/composables/useFetchAuthenticatedUser'

    // REF FOR SHOWING UPLOADED DESIGNS OR PRE MADE DESIGNS
    const showUploadedDesignsTableRef = ref<boolean>(false)
    const showAIDesignModal = ref<boolean>(false)

    const { authStore } = useFetchAuthenticatedUser()
</script>

<template>
    <div class="bg-white dark:bg-gray-900 transition-colors duration-200 h-screen">
        <div class="mx-auto px-4 pt-10 pb-18 sm:px-6 sm:py-24 lg:max-w-7xl lg:px-8">
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100 transition-colors duration-200">
                    Explore our designs
                </h2>
                <button
                    v-if="authStore.currentUser"
                    @click="showAIDesignModal = true"
                    class="rounded-md bg-gray-900 dark:bg-blue-600 text-white p-2 text-sm hover:cursor-pointer hover:opacity-75 transition-colors duration-200"
                >
                    Explore AI Generated Designs
                </button>
            </div>

            <div class="mt-6">
                <PreMadeDesignCard :showUploadedDesignsTableRef="showUploadedDesignsTableRef" />
            </div>
        </div>
    </div>

    <!-- AI GENERATE MODAL -->
    <TransitionRoot appear :show="showAIDesignModal" as="template">
        <Dialog as="div" @close="showAIDesignModal = false" class="relative z-10">
            <TransitionChild
                as="template"
                enter="duration-300 ease-out"
                enter-from="opacity-0"
                enter-to="opacity-100"
                leave="duration-200 ease-in"
                leave-from="opacity-100"
                leave-to="opacity-0"
            >
                <div class="fixed inset-0 bg-black/25" />
            </TransitionChild>

            <div class="fixed inset-0 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4 text-center">
                    <TransitionChild
                        as="template"
                        enter="duration-300 ease-out"
                        enter-from="opacity-0 scale-95"
                        enter-to="opacity-100 scale-100"
                        leave="duration-200 ease-in"
                        leave-from="opacity-100 scale-100"
                        leave-to="opacity-0 scale-95"
                    >
                        <DialogPanel
                            class="w-full max-w-6xl transform overflow-hidden rounded-2xl bg-white mb-8 text-left align-middle shadow-xl transition-all"
                        >
                            <DialogTitle
                                as="h3"
                                class="text-lg font-medium leading-6 text-gray-900"
                            ></DialogTitle>

                            <GenerateAIDesigns @close="showAIDesignModal = false" />
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>
