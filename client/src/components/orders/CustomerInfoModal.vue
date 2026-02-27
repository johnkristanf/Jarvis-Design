<script setup lang="ts">
    import { ref } from 'vue'
    import {
        TransitionRoot,
        TransitionChild,
        Dialog,
        DialogPanel,
        DialogTitle,
    } from '@headlessui/vue'
    import { EyeIcon } from '@heroicons/vue/20/solid'

    const props = defineProps({
        customerName: {
            type: String,
            default: 'N/A',
        },
        email: {
            type: String,
            default: 'N/A',
        },
        phoneNumber: {
            type: String,
            default: 'N/A',
        },
        address: {
            type: String,
            default: 'N/A',
        },
    })

    const isOpen = ref(false)

    function closeModal() {
        isOpen.value = false
    }
    function openModal() {
        isOpen.value = true
    }
</script>

<template>
    <div class="flex items-center justify-start ml-2">
        <button
            type="button"
            @click="openModal"
            class="flex items-center gap-2 rounded-md bg-gray-900 hover:cursor-pointer px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-500"
        >
            <EyeIcon class="size-5" />
            View
        </button>
    </div>

    <TransitionRoot appear :show="isOpen" as="template">
        <Dialog as="div" @close="closeModal" class="relative z-10">
            <TransitionChild
                as="template"
                enter="duration-300 ease-out"
                enter-from="opacity-0"
                enter-to="opacity-100"
                leave="duration-200 ease-in"
                leave-from="opacity-100"
                leave-to="opacity-0"
            >
                <div class="fixed inset-0 bg-black/50" />
            </TransitionChild>

            <div class="fixed inset-0 overflow-y-auto z-[999999]">
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
                            class="w-full max-w-md transform overflow-hidden rounded-2xl bg-white text-left align-middle shadow-xl transition-all"
                        >
                            <!-- Header -->
                            <div class="bg-gray-900 px-6 py-4">
                                <DialogTitle as="h3" class="text-lg font-semibold text-white">
                                    Customer Information
                                </DialogTitle>
                            </div>

                            <!-- Content -->
                            <div class="px-6 py-4 space-y-4">
                                <!-- Name -->
                                <div
                                    class="flex items-center justify-between py-3 border-b border-gray-200 gap-4"
                                >
                                    <span class="text-sm font-medium text-gray-900 w-1/3">
                                        Name
                                    </span>
                                    <span
                                        class="text-sm text-gray-600 text-right w-2/3 truncate"
                                        :title="customerName"
                                    >
                                        {{ customerName || 'N/A' }}
                                    </span>
                                </div>

                                <!-- Email -->
                                <div
                                    class="flex items-center justify-between py-3 border-b border-gray-200 gap-4"
                                >
                                    <span class="text-sm font-medium text-gray-900 w-1/3">
                                        Email
                                    </span>
                                    <span
                                        class="text-sm text-gray-600 text-right w-2/3 truncate"
                                        :title="email"
                                    >
                                        {{ email || 'N/A' }}
                                    </span>
                                </div>

                                <!-- Phone Number -->
                                <div
                                    class="flex items-center justify-between py-3 border-b border-gray-200 gap-4"
                                >
                                    <span class="text-sm font-medium text-gray-900 w-1/3">
                                        Phone No.
                                    </span>
                                    <span
                                        class="text-sm text-gray-600 text-right w-2/3 truncate"
                                        :title="phoneNumber"
                                    >
                                        {{ phoneNumber || 'N/A' }}
                                    </span>
                                </div>

                                <!-- Address -->
                                <div class="flex flex-col py-3 border-b border-gray-200 gap-2">
                                    <span class="text-sm font-medium text-gray-900">Address</span>
                                    <span class="text-sm text-gray-600 leading-relaxed">
                                        {{ address || 'N/A' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Footer -->
                            <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3">
                                <button
                                    type="button"
                                    class="inline-flex justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-500 focus-visible:ring-offset-2"
                                    @click="closeModal"
                                >
                                    Close
                                </button>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>
