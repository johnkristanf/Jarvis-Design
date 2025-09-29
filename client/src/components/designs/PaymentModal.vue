<script lang="ts" setup>
    import { apiService } from '@/api/axios'
    import type { OrderTypes, PlaceOrderData } from '@/types/order'
    import { type DesignAttribute, type ProceedPaymentData } from '@/types/payment'
    import { TransitionRoot, TransitionChild, Dialog, DialogPanel, DialogTitle } from '@headlessui/vue'
    import { useMutation, useQueryClient } from '@tanstack/vue-query'
    import { ref } from 'vue'

    import Toast from 'primevue/toast'
    import { useToast } from 'primevue/usetoast'

    import Loader from '../Loader.vue'

    const props = defineProps<{
        orderType: OrderTypes
        paymentData: ProceedPaymentData
        attributeData: DesignAttribute
        isOpen: boolean
    }>()

    // DYNAMIC QRCODE GENERATION RESPONSE
    // const paymentResponseRef = ref<ProceedPaymentResponseData>({
    //     code_id: '',
    //     amount: -1,
    //     business_name: '',
    //     qrcode_img_src: '',
    // })

    // STATE FOR PLACE ORDER MUTATING
    const isPlacingOrder = ref<boolean>(false)
    const fileInput = ref<HTMLInputElement | null>(null)

    // MODAL EMITS
    const emit = defineEmits(['close'])
    const handleCloseModal = () => emit('close')

    const queryClient = useQueryClient()
    const toast = useToast()

    // const handleGeneratePaymentQrCode = async () => {
    //     const designID = props.attributeData.design_id
    //     const totalPrice = props.attributeData.quantity * props.paymentData.price
    //     const orderOption = props.paymentData.order_option
    //     const orderType = props.orderType

    //     const quantity = props.attributeData.quantity
    //     const color = props.attributeData.color
    //     const size = props.attributeData.size

    //     console.log('totalPrice: ', totalPrice)
    //     console.log('orderType: ', orderType)
    //     console.log('quantity: ', quantity)
    //     console.log('color: ', color)
    //     console.log('size: ', size)

    //     const response = await generateQrCode(
    //         designID,
    //         totalPrice,
    //         orderOption,
    //         orderType,
    //         quantity,
    //         color,
    //         size,
    //     )
    //     console.log('response qrcode: ', response)

    //     if (response && paymentResponseRef.value) {
    //         paymentResponseRef.value = response
    //     }
    // }

    // UNCOMMENT THIS LATER IF PANEL OR ADVISOR ASK FOR DYNAMIC QRCODE PAYMENT PROCESS
    // onMounted(() => {
    //     handleGeneratePaymentQrCode()
    // })

    // PLACE ORDER MUTATION
    const orderMutation = useMutation({
        mutationFn: async (data: PlaceOrderData) => {
            const respData = await apiService.post('/api/place/order', data)
            return respData
        },
        onSuccess: (response) => {
            console.log('response place order: ', response)

            isPlacingOrder.value = false
            toast.add({
                severity: 'success',
                summary: 'Placed Order Successfully',
                life: 2000,
            })

            queryClient.invalidateQueries({ queryKey: ['orders'] })

            setTimeout(() => {
                handleCloseModal()
            }, 2500)
        },

        onError: (error) => {
            console.error('Error placing order:', error)
            isPlacingOrder.value = false

            toast.add({
                severity: 'error',
                summary: 'Unexpected Error, please try again',
            })
        },

        onMutate: () => {
            isPlacingOrder.value = true
        },
    })

    const handlePlaceOrder = () => {
        const designID = props.attributeData.design_id
        const totalPrice = props.attributeData.quantity * props.paymentData.price
        const orderOption = props.paymentData.order_option
        const orderType = props.orderType

        const quantity = props.attributeData.quantity
        const color = props.attributeData.color
        const size = props.attributeData.size

        const data: PlaceOrderData = {
            order_type: orderType,
            design_id: designID,
            total_price: totalPrice,
            order_option: orderOption,
            quantity: quantity,
            color_id: color,
            size_id: size,
        }

        orderMutation.mutate(data)
    }
</script>

<template>
    <TransitionRoot appear :show="isOpen" as="template">
        <Dialog as="div" @close="handleCloseModal" class="relative z-10">
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
                            class="w-full max-w-md transform overflow-hidden rounded-2xl bg-white p-6 mb-8 text-left align-middle shadow-xl transition-all"
                        >
                            <DialogTitle as="h3" class="text-lg font-medium leading-6 text-gray-900">Scan Gcash QR Code</DialogTitle>

                            <div class="mt-2">
                                <p class="text-sm text-gray-500">Item Name: {{ paymentData.name }}</p>
                                <p class="text-sm text-gray-500">Quantity: {{ attributeData.quantity }}</p>
                                <p class="text-sm text-gray-500">
                                    Total Price: ₱{{
                                        new Intl.NumberFormat('en-PH', {
                                            minimumFractionDigits: 2,
                                        }).format(attributeData.quantity * paymentData.price)
                                    }}
                                </p>

                                <p class="text-sm text-center my-5">
                                    Note:
                                    <br />
                                    Please ensure you pay the exact amount. Orders with incorrect payments will not be processed.
                                </p>
                            </div>

                            <!-- <div class="mt-2" v-if="paymentResponseRef.qrcode_img_src != ''">
                                <img
                                    :src="paymentResponseRef.qrcode_img_src"
                                    alt="Generated QR CODE"
                                />
                            </div> -->

                            <div class="mt-5 flex flex-col items-center justify-center">
                                <img src="/jarvis-gcash-qr.webp" alt="Generated QR CODE" width="300" />

                                <h1 class="text-gray-500">Name: JA**N S.</h1>
                            </div>
                            <!-- <div class="w-full flex flex-col justify-center items-center">
                                <h1>Generating QR Code...</h1>
                                <ProgressSpinner :pt="{ root: { style: { width: '40px' } } }" />
                            </div> -->

                            <!-- PAYMENT CONFIRMATION SCREENSHOT UPLOAD -->
                            <div class="mt-10">
                                <div class="flex items-center justify-between">
                                    <p>Screenshot of Payment Confirmation</p>
                                </div>

                                <div class="mt-4 border-2 border-dashed border-gray-300 rounded-md p-6 flex flex-col items-center justify-center">
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
                                        <p class="text-sm text-gray-600">Drag and drop files to here to upload</p>
                                        <p class="text-xs text-gray-500 mt-1">or</p>
                                    </div>

                                    <input ref="fileInput" type="file" accept="image/*" class="hidden" />

                                    <button
                                        type="button"
                                        class="mt-4 px-4 py-2 bg-white border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none"
                                        @click="fileInput?.click()"
                                    >
                                        Select File
                                    </button>
                                </div>
                            </div>

                            <div class="mt-4 flex justify-end">
                                <button
                                    type="button"
                                    @click="handlePlaceOrder"
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

    <Toast />

    <Loader v-if="isPlacingOrder" msg="Placing Order..." />
</template>
