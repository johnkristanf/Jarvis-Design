<script lang="ts" setup>
    import UploadDesignModal from '@/components/designs/UploadDesignModal.vue'
    import GenerateAIDesignsModal from '@/components/designs/GenerateAIDesignsModal.vue'

    import { DocumentArrowUpIcon, WrenchScrewdriverIcon } from '@heroicons/vue/20/solid'
    import { Popover, PopoverButton, PopoverPanel } from '@headlessui/vue'
    import { ChevronDownIcon } from '@heroicons/vue/20/solid'
    import { ref } from 'vue'
    import { useAuthStore } from '@/stores/user'

    const authStore = useAuthStore()

    // START A DESIGN MODAL REF TOGGLERS
    const openUploadDesignModal = ref(false)
    const openAIDesignGenerateModal = ref(false)

    const handleOpenUploadModal = () => (openUploadDesignModal.value = true)
    const handleOpenAIDesignModal = () => (openAIDesignGenerateModal.value = true)
</script>

<template>
    <!-- SORT DROPDOWN -->
    <Popover class="relative">
        <PopoverButton
            class="flex items-center w-48 gap-2 px-4 py-2 rounded-md text-sm hover:cursor-pointer hover:opacity-75"
            v-if="authStore.currentUser && authStore.isAuthenticated"
        >
            Design Options
            <ChevronDownIcon class="w-5 h-5" />
        </PopoverButton>

        <transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="translate-y-1 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="translate-y-1 opacity-0"
        >
            <PopoverPanel
                class="absolute right-0 z-10 mt-2 w-60 bg-white p-4 rounded-lg shadow-lg ring-1 ring-black/5"
            >
                <button
                    v-if="authStore.currentUser && authStore.isAuthenticated"
                    @click="handleOpenUploadModal"
                    class="w-full mb-3 flex items-center justify-center gap-1 font-medium bg-gray-900 p-3 text-white w-[40%] rounded-md text-base focus:outline-none hover:bg-gray-700 cursor-pointer sm:text-sm/6"
                >
                    <DocumentArrowUpIcon class="size-5" />
                    Upload Design
                </button>

                <button
                    v-if="authStore.currentUser && authStore.isAuthenticated"
                    @click="handleOpenAIDesignModal"
                    class="w-full flex items-center justify-center gap-1 font-medium bg-gray-900 p-3 text-white w-[40%] rounded-md text-base focus:outline-none hover:bg-gray-700 cursor-pointer sm:text-sm/6"
                >
                    <WrenchScrewdriverIcon class="size-5" />
                    Generate AI Designs
                </button>
            </PopoverPanel>
        </transition>
    </Popover>

    <UploadDesignModal
        v-if="openUploadDesignModal"
        :isOpen="openUploadDesignModal"
        @close="openUploadDesignModal = false"
    />

    <GenerateAIDesignsModal
        v-if="openAIDesignGenerateModal"
        :isOpen="openAIDesignGenerateModal"
        @close="openAIDesignGenerateModal = false"
    />
</template>
