<script lang="ts" setup>
    import { Popover, PopoverButton, PopoverPanel } from '@headlessui/vue'
    import { ref } from 'vue'

    const props = defineProps<{
        paymentAttachmentURL: string
    }>()

    const imageError = ref(false)

    const handleImageError = () => {
        imageError.value = true
    }

    const getFileName = (url: string) => {
        return (
            url
                .split('/')
                .pop()
                ?.replace(/^[^_]*_/, '') || 'attachment'
        )
    }

    const openImageInNewTab = () => {
        window.open(props.paymentAttachmentURL, '_blank')
    }
</script>

<template>
    <Popover class="relative">
        <PopoverButton
            class="flex items-center gap-1 px-3 py-1 text-xs hover:cursor-pointer bg-gray-900/10 hover:bg-gray-900/20 text-gray-700 rounded-md transition-colors duration-200 backdrop-blur-sm border border-gray-200/50"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"
                />
            </svg>
            Attachment
        </PopoverButton>

        <transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="translate-y-1 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="translate-y-1 opacity-0"
        >
            <PopoverPanel class="absolute right-0 z-50 mt-3 w-80 sm:w-96 transform">
                <div class="bg-white rounded-xl shadow-xl border border-gray-200 overflow-hidden">
                    <!-- Header -->
                    <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-900">Payment Attachment</h3>
                            <button
                                @click="openImageInNewTab"
                                class="text-gray-500 hover:text-gray-700 p-1 rounded transition-colors"
                                title="Open in new tab"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Image Container -->
                    <div class="relative">
                        <!-- Error State -->
                        <div v-if="imageError" class="w-full h-64 bg-gray-50 flex items-center justify-center">
                            <div class="text-center text-gray-500">
                                <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                                <p class="text-sm font-medium">Failed to load image</p>
                                <p class="text-xs text-gray-400 mt-1">The attachment could not be displayed</p>
                            </div>
                        </div>

                        <!-- Image -->
                        <img
                            v-else
                            :src="paymentAttachmentURL"
                            :alt="getFileName(paymentAttachmentURL)"
                            class="w-full h-auto max-h-64 object-contain bg-gray-50"
                            @error="handleImageError"
                        />

                    </div>

                    <!-- Footer Actions -->
                    <div class="px-4 py-3 bg-gray-50 border-t border-gray-200">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-500">Payment Receipt</span>
                            <div class="flex gap-2">
                                <button
                                    @click="openImageInNewTab"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:ring-2 focus:ring-gray-900 focus:ring-offset-2 transition-colors"
                                >
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
                                        />
                                    </svg>
                                    Open Full Size
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Arrow pointer -->
                <div class="absolute -top-2 right-6">
                    <div class="w-4 h-4 bg-white border-l border-t border-gray-200 transform rotate-45"></div>
                </div>
            </PopoverPanel>
        </transition>
    </Popover>
</template>
