<script lang="ts" setup>
    import { apiService } from '@/api/axios'
    import type { QrCodePaymentData } from '@/types/order'
    import { TransitionRoot, TransitionChild, Dialog, DialogPanel, DialogTitle } from '@headlessui/vue'
    import { useMutation, useQueryClient } from '@tanstack/vue-query'
    import { ref } from 'vue'
    import { useToast } from 'primevue/usetoast'

    const props = defineProps<{
        paymentData: QrCodePaymentData | null
    }>()

    // MODAL EMITS
    const emit = defineEmits(['closeModal'])
    const handleCloseModal = () => emit('closeModal')

    const queryClient = useQueryClient()
    const toast = useToast()

    const file = ref<File | null>(null)
    const fileInput = ref<HTMLInputElement | null>(null)
    const previewUrl = ref<string | null>(null)

    const triggerFileSelect = () => {
        fileInput.value?.click()
    }

    const handleFileChange = (e: Event) => {
        const target = e.target as HTMLInputElement
        if (target.files && target.files[0]) {
            const selectedFile = target.files[0]

            // ✅ Ensure it's an image
            if (!selectedFile.type.startsWith('image/')) {
                toast.add({
                    severity: 'warn',
                    summary: 'Only image files are allowed (jpg, png, etc.)',
                    life: 1500,
                })
                target.value = '' // reset input
                return
            }

            file.value = selectedFile
            previewUrl.value = URL.createObjectURL(selectedFile)
        }
    }

    const clearFile = () => {
        file.value = null
        previewUrl.value = null
        if (fileInput.value) fileInput.value.value = '' // reset input
    }

    // ADD NEW PAYMENT MUTATION
    const paymentMutation = useMutation({
        mutationFn: async (data: FormData) => {
            const respData = await apiService.post('/api/add/payment', data)
            return respData
        },
        onSuccess: (response) => {
            console.log('response add payment: ', response)
            toast.add({
                severity: 'success',
                summary: 'Payment Added Successfully',
                life: 1500,
            })

            setTimeout(() => {
                queryClient.invalidateQueries({ queryKey: ['orders'] })
                handleCloseModal()
            }, 1500)
        },

        onError: (error) => {
            console.error('Error adding new payment:', error)

            toast.add({
                severity: 'error',
                summary: 'Adding new payment failed, please try again.',
                life: 1500,
            })
        },
    })

    const handleAddPayment = () => {
        if (props.paymentData?.order_id && file.value) {
            const formData = new FormData()
            formData.append('order_id', String(props.paymentData.order_id))
            formData.append('payment_attachment', file.value)

            paymentMutation.mutate(formData)
        }
    }
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
                            <DialogTitle as="h3" class="text-lg font-medium leading-6 text-gray-900">Scan Gcash QR Code 213123123</DialogTitle>

                            <div class="mt-2">
                                <p class="text-sm text-gray-500">Product Name: {{ paymentData?.product_name }}</p>
                                <p class="text-sm text-gray-500">Quantity: {{ paymentData?.total_quantity }}</p>
                                <p class="text-sm text-gray-500">Total Price: ₱{{ paymentData?.total_price }}</p>
                            </div>

                            <p class="text-sm text-center my-5">
                                <strong class="text-lg">Note:</strong>
                                <br />
                                A minimum payment of
                                <strong>50% of the total amount</strong>
                                is required. Orders with payments below this threshold will not be approved or processed.
                            </p>

                            <div class="flex items-center justify-center gap-5 h-full">
                                <div class="mt-5 flex flex-col items-center justify-center">
                                    <img src="/jarvis-gcash-qr.webp" alt="Generated QR CODE" width="300" />

                                    <h1 class="text-gray-500">Name: JA**N S.</h1>
                                </div>

                                <!-- PAYMENT CONFIRMATION SCREENSHOT UPLOAD -->
                                <div
                                    class="mt-4 border-2 border-dashed border-gray-300 rounded-md p-6 flex flex-col items-center justify-center relative w-[280px] h-[200px]"
                                >
                                    <!-- If preview exists -->
                                    <div v-if="previewUrl" class="relative w-full h-full">
                                        <img :src="previewUrl" alt="Payment Preview" class="w-full h-full object-cover rounded-md" />
                                        <!-- Clear button -->
                                        <button
                                            @click="clearFile"
                                            class="absolute top-[-12px] right-0 text-red-800 text-xl rounded-md p-1 hover:cursor-pointer"
                                        >
                                            ✕
                                        </button>
                                    </div>

                                    <!-- If no file yet -->
                                    <div v-else class="flex flex-col items-center justify-center text-center">
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
                                        <p class="text-sm text-gray-600 mt-2">Screenshot of Payment Confirmation</p>

                                        <input ref="fileInput" type="file" accept="image/*" class="hidden" @change="handleFileChange" />

                                        <button
                                            type="button"
                                            @click="triggerFileSelect"
                                            class="mt-3 px-4 py-2 bg-white border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none"
                                        >
                                            Select File
                                        </button>
                                    </div>
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
                                    :disabled="!file"
                                    @click="handleAddPayment"
                                    :class="[
                                        'inline-flex justify-center rounded-md border border-transparent px-4 py-2 text-sm font-medium text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2',
                                        !file ? 'bg-gray-400 hover:cursor-not-allowed' : 'bg-gray-900 hover:opacity-75 hover:cursor-pointer ',
                                    ]"
                                >
                                    Add Payment
                                </button>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>
