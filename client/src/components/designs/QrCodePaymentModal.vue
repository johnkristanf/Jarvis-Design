<script lang="ts" setup>
    import type { QrCodePaymentData } from '@/types/order'
    import {
        TransitionRoot,
        TransitionChild,
        Dialog,
        DialogPanel,
        DialogTitle,
    } from '@headlessui/vue'

    const props = defineProps<{
        paymentData: QrCodePaymentData | null
    }>()

    // MODAL EMITS
    const emit = defineEmits(['close', 'place_order'])
    const handleCloseModal = () => emit('close')
    const handleTriggerPlaceOrder = () => emit('place_order')
</script>

<template>
    <TransitionRoot appear :show="true" as="template">
        <Dialog as="div" @close="handleCloseModal" class="relative z-[9999]">
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
                            class="w-[800px] max-w-5xl transform overflow-hidden rounded-2xl bg-white p-6 mb-8 text-left align-middle shadow-xl transition-all"
                        >
                            <DialogTitle
                                as="h3"
                                class="text-lg font-medium leading-6 text-gray-900"
                            >
                                Scan Gcash QR Code
                            </DialogTitle>

                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    Product Name: {{ props.paymentData?.product_name }}
                                </p>
                                <p class="text-sm text-gray-500">
                                    Quantity: {{ props.paymentData?.total_quantity }}
                                </p>
                                <p class="text-sm text-gray-500">
                                    Total Price: ₱{{ props.paymentData?.total_price }}
                                </p>
                            </div>

                            <p class="text-sm text-center my-5">
                                <strong>Note:</strong>
                                <br />
                                A minimum payment of
                                <strong>50% of the total amount</strong>
                                is required. Orders with payments below this threshold will not be
                                approved or processed.
                            </p>

                            <div class="flex items-center justify-center gap-5 h-full">
                                <div class="mt-5 flex flex-col items-center justify-center">
                                    <img
                                        src="/jarvis-gcash-qr.webp"
                                        alt="Generated QR CODE"
                                        width="300"
                                    />

                                    <h1 class="text-gray-500">Name: JA**N S.</h1>
                                </div>

                                <!-- PAYMENT CONFIRMATION SCREENSHOT UPLOAD -->
                                <div
                                    class="mt-4 border-2 border-dashed border-gray-300 rounded-md p-6 flex flex-col items-center justify-center"
                                >
                                    <div class="flex items-center justify-center">
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="w-12 h-12 text-gray-400"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
                                            />
                                        </svg>
                                    </div>

                                    <div class="mt-4 text-center">
                                        <p class="text-sm text-gray-600">
                                            Screenshot of Payment Confirmation
                                        </p>
                                        <p class="text-xs text-gray-500 mt-1">or</p>
                                    </div>

                                    <input
                                        ref="fileInput"
                                        type="file"
                                        accept="image/*"
                                        class="hidden"
                                    />

                                    <button
                                        type="button"
                                        class="mt-4 px-4 py-2 bg-white border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none"
                                    >
                                        Select File
                                    </button>
                                </div>
                            </div>

                            <div class="mt-4 flex justify-end gap-3">
                                <button
                                    type="button"
                                    @click="handleCloseModal"
                                    class="inline-flex justify-center rounded-md bg-black px-4 py-2 text-sm font-medium text-white hover:opacity-75 hover:cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="button"
                                    @click="handleTriggerPlaceOrder"
                                    class="inline-flex justify-center rounded-md border border-transparent bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:opacity-75 hover:cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2"
                                >
                                    Place Order
                                </button>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>
