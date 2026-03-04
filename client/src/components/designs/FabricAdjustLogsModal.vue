<script setup>
    import { ref } from 'vue'
    import {
        TransitionRoot,
        TransitionChild,
        Dialog,
        DialogPanel,
        DialogTitle,
    } from '@headlessui/vue'

    import OrderLogsTable from '../orders/OrderLogsTable.vue'
    import FabricAdjustTable from '../orders/FabricAdjustTable.vue'

    const props = defineProps({
        fabricId: String,
        fabricName: String,
        fabricQuantity: String,
    })

    // Modal open logic
    const isOpen = ref(false)

    function openModal() {
        isOpen.value = true
    }

    function closeModal() {
        isOpen.value = false
    }
</script>

<template>
    <div class="flex items-center justify-center">
        <button
            class="flex items-center gap-1 px-2 py-1 bg-indigo-600 text-white rounded hover:cursor-pointer hover:opacity-75 transition"
            @click="openModal"
            title="View"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-4 w-4"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                />
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                />
            </svg>
            <span class="text-xs">View</span>
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
                <div class="fixed inset-0 bg-black/25" />
            </TransitionChild>

            <div class="fixed inset-0 overflow-y-auto mt-12">
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
                            class="w-[1000px] max-w-3xl transform overflow-hidden rounded-2xl bg-white dark:bg-gray-900 dark:text-white border dark:border-gray-700 p-6 text-left align-middle shadow-xl transition-all"
                        >
                            <DialogTitle
                                as="h3"
                                class="text-lg font-semibold leading-6 text-gray-900 dark:text-white"
                            >
                                <div class="flex justify-between">
                                    <p>{{ fabricName }}</p>
                                    <p class="!text-sm">Total Quantity: {{ fabricQuantity }}</p>
                                </div>
                            </DialogTitle>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500 dark:text-gray-300">
                                    View the complete logs of all fabric stock additions and
                                    reductions.
                                </p>
                            </div>

                            <!-- FABRIC ORDER LOGS -->
                            <h1 class="mb-2 mt-5">Transaction Logs</h1>
                            <OrderLogsTable />

                            <!-- FABRIC ADJUST LOG TABLE -->
                            <h1 class="mb-2 mt-5">Fabric Adjust Logs</h1>
                            <FabricAdjustTable :fabricId="props.fabricId" />
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>
