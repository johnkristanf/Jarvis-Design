<script lang="ts" setup>
    import { onMounted, ref } from 'vue'
    import {
        TransitionRoot,
        TransitionChild,
        Dialog,
        DialogPanel,
        DialogTitle,
    } from '@headlessui/vue'
    import { useMutation, useQueryClient } from '@tanstack/vue-query'
    import { apiService } from '@/api/axios'
    import { useToast } from 'primevue'

    const emit = defineEmits(['confirmCancel'])
    const handleCancelOrder = () => emit('confirmCancel')
</script>

<template>
    <!-- <div class="flex items-center justify-center">
        <slot :openModal="openModal">
            <button @click="openModal" class="text-red-600 hover:underline">Delete</button>
        </slot>
    </div> -->

    <TransitionRoot appear show as="template">
        <Dialog as="div" class="relative z-[9999]">
            <!-- Backdrop -->
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

            <!-- Dialog Content -->
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
                            class="w-full max-w-md transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all"
                        >
                            <DialogTitle
                                as="h3"
                                class="text-lg font-medium leading-6 text-gray-900"
                            >
                                Confirm Order Cancellation
                            </DialogTitle>

                            <div class="mt-2">
                                <p class="text-sm text-gray-600">
                                    Are you sure you want to cancel this order?
                                </p>
                            </div>

                            <div class="mt-4 flex justify-end space-x-3">
                                <!-- Cancel Button -->
                                <button
                                    type="button"
                                    class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100"
                                >
                                    Cancel
                                </button>

                                <!-- Delete Button -->
                                <button
                                    type="button"
                                    class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50"
                                    @click="handleCancelOrder"
                                >
                                    Proceed
                                </button>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>
