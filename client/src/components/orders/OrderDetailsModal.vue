<script lang="ts" setup>
    import { formatDate } from '@/helper/designs'
    import { OrderOptions, type Orders, type QrCodePaymentData } from '@/types/order'
    import {
        TransitionRoot,
        TransitionChild,
        Dialog,
        DialogPanel,
        DialogTitle,
    } from '@headlessui/vue'
    import StatusBadge from './StatusBadge.vue'
    import PaymentAttachmentPopOver from './PaymentAttachmentPopOver.vue'
    import PaymentStatusBadge from './PaymentStatusBadge.vue'
    import PaymentAmountApplied from './PaymentAmountApplied.vue'
    import { computed, ref } from 'vue'
    import AddNewButton from '../AddNewButton.vue'
    import AddNewPaymentModal from './AddNewPaymentModal.vue'
    import { usePayments } from '@/composables/usePayments'

    const props = defineProps<{
        orderDetails: Orders
        isOpen: boolean
    }>()

    const showAddNewPaymentModal = ref<boolean>(false)
    const qrCodePaymentData = ref<QrCodePaymentData | null>(null)

    const handleShowNewPaymentModal = () => {
        showAddNewPaymentModal.value = true

        qrCodePaymentData.value = {
            product_name: props.orderDetails.product!.name!,
            total_quantity: props.orderDetails.total_quantity,
            total_price: props.orderDetails.total_price,
            order_id: props.orderDetails.id,
        }
    }

    const handleCloseNewPaymentModal = () => {
        showAddNewPaymentModal.value = false
        qrCodePaymentData.value = null
    }

    const emit = defineEmits(['close'])
    const handleCloseModal = () => emit('close')

    // Payment composable
    const { orderTotalPrice, totalApplied, remainingBalance, hasFullyPaid } = usePayments(
        computed(() => props.orderDetails.order_payments || []),
        props.orderDetails.total_price,
    )
</script>

<template>
    <TransitionRoot appear :show="isOpen" as="template">
        <Dialog as="div" class="relative z-[999]">
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
                <div class="flex min-h-full items-center justify-center p-4">
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
                            class="relative w-full max-w-3xl transform overflow-hidden bg-white shadow-2xl transition-all"
                        >
                            <!-- Header -->
                            <div class="bg-gray-900 text-white p-6 border-b border-gray-200">
                                <div class="flex items-center justify-between">
                                    <DialogTitle as="h2" class="text-xl font-bold">
                                        Order Details
                                    </DialogTitle>
                                    <button
                                        @click="handleCloseModal"
                                        class="text-white hover:text-gray-300 transition-colors"
                                    >
                                        <svg
                                            class="w-6 h-6"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12"
                                            />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Content -->
                            <div class="p-6 max-h-96 overflow-y-auto">
                                <!-- Order Header Info -->
                                <div
                                    class="grid grid-cols-2 gap-4 mb-6 pb-6 border-b border-gray-200"
                                >
                                    <div>
                                        <h3 class="text-sm font-medium text-gray-600 tracking-wide">
                                            Order Number
                                        </h3>
                                        <p class="text-lg font-bold text-black mt-1">
                                            {{ orderDetails.order_number }}
                                        </p>
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-medium text-gray-600 tracking-wide">
                                            Status
                                        </h3>
                                        <div class="mt-1">
                                            <StatusBadge :status="orderDetails.status" />
                                        </div>
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-medium text-gray-600 tracking-wide">
                                            Created
                                        </h3>
                                        <p class="text-sm text-black mt-1">
                                            {{ formatDate(orderDetails.created_at) }}
                                        </p>
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-medium text-gray-600 tracking-wide">
                                            {{
                                                orderDetails.order_option === OrderOptions.DELIVERY
                                                    ? 'Delivery Date'
                                                    : 'Pick-up Date'
                                            }}
                                        </h3>
                                        <p class="text-sm text-black mt-1">
                                            {{
                                                orderDetails.delivery_date
                                                    ? formatDate(orderDetails.delivery_date)
                                                    : 'N/A'
                                            }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Customer Info -->
                                <div class="mb-6 pb-6 border-b border-gray-200">
                                    <h3
                                        class="text-lg font-semibold text-black mb-3 border-l-4 border-black pl-3"
                                    >
                                        Customer Information
                                    </h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <p class="text-sm text-gray-600">Name</p>
                                            <p class="font-medium text-black">
                                                {{ orderDetails.user?.name }}
                                            </p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-600">Phone Number</p>
                                            <p class="font-medium text-black">
                                                +63 {{ orderDetails.phone_number }}
                                            </p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-600">Address</p>
                                            <p class="font-medium text-black">
                                                {{ orderDetails.address }}
                                            </p>
                                        </div>

                                        <div>
                                            <p class="text-sm text-gray-600">Email</p>
                                            <p class="font-medium text-black">
                                                {{ orderDetails.user?.email }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Product Info -->
                                <div class="mb-6 pb-6 border-b border-gray-200">
                                    <h3
                                        class="text-lg font-semibold text-black mb-3 border-l-4 border-black pl-3"
                                    >
                                        Product Details
                                    </h3>
                                    <div class="flex gap-4">
                                        <!-- Product Image -->
                                        <div
                                            v-if="orderDetails.image_path || orderDetails.temp_url"
                                            class="flex-shrink-0"
                                        >
                                            <img
                                                :src="
                                                    orderDetails.temp_url || orderDetails.image_path
                                                "
                                                :alt="`Design ${orderDetails.design_id}`"
                                                class="w-20 h-20 object-cover border-2 border-gray-300"
                                            />
                                        </div>

                                        <!-- Product Details -->
                                        <div class="flex-1">
                                            <div class="grid grid-cols-2 gap-4">
                                                <div>
                                                    <p class="text-sm text-gray-600">Color</p>
                                                    <div class="flex items-center gap-2">
                                                        <p class="font-medium text-black">
                                                            {{ orderDetails.color }}
                                                        </p>
                                                    </div>
                                                </div>
                                                <div>
                                                    <p class="text-sm text-gray-600">Option</p>
                                                    <p class="font-medium text-black">
                                                        {{
                                                            orderDetails.order_option ===
                                                            OrderOptions.DELIVERY
                                                                ? 'Delivery'
                                                                : 'Pick-up'
                                                        }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Sizes -->
                                    <div
                                        v-if="orderDetails.sizes && orderDetails.sizes.length > 0"
                                        class="mt-4"
                                    >
                                        <p class="text-sm text-gray-600 mb-2">Sizes & Quantities</p>
                                        <div class="flex flex-wrap gap-2">
                                            <div
                                                v-for="size in orderDetails.sizes"
                                                :key="size.id"
                                                class="bg-gray-100 border border-gray-300 px-3 py-1 text-sm"
                                            >
                                                <span class="font-medium">{{ size.name }}</span>
                                                <span v-if="size.pivot" class="text-gray-600 ml-1">
                                                    ({{ size.pivot.quantity || 'N/A' }})
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Payments Section -->
                                <div
                                    v-if="
                                        orderDetails.order_payments &&
                                        orderDetails.order_payments.length > 0
                                    "
                                    class="mb-20"
                                >
                                    <div class="flex items-center justify-between mb-3">
                                        <h3
                                            class="text-lg font-semibold text-black mb-3 border-l-4 border-black pl-3"
                                        >
                                            Payment History
                                        </h3>
                                        <div>
                                            <!-- Show Add button if not fully paid -->
                                            <AddNewButton
                                                v-if="!hasFullyPaid"
                                                message="Add Payment"
                                                @action="handleShowNewPaymentModal"
                                            />

                                            <!-- Show fully paid message if at least one payment is fully paid -->
                                            <p
                                                v-else
                                                class="mt-4 text-green-600 font-semibold text-sm flex items-center gap-2"
                                            >
                                                <svg
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    class="w-5 h-5 text-green-600"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M5 13l4 4L19 7"
                                                    />
                                                </svg>
                                                This order is fully paid
                                            </p>
                                        </div>
                                    </div>
                                    <div class="space-y-4">
                                        <div
                                            v-for="payment in orderDetails.order_payments"
                                            :key="payment.id"
                                            class="bg-white border-2 border-gray-200 p-4 hover:border-gray-300 transition-colors duration-200"
                                        >
                                            <!-- Payment Header -->
                                            <div
                                                class="flex items-center justify-between mb-3 pb-3 border-b border-gray-200"
                                            >
                                                <div class="flex items-center gap-3">
                                                    <div
                                                        class="w-10 h-10 bg-gray-900 rounded-full flex items-center justify-center flex-shrink-0"
                                                    >
                                                        <svg
                                                            class="w-5 h-5 text-white"
                                                            fill="none"
                                                            stroke="currentColor"
                                                            viewBox="0 0 24 24"
                                                        >
                                                            <path
                                                                stroke-linecap="round"
                                                                stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"
                                                            />
                                                        </svg>
                                                    </div>
                                                    <div>
                                                        <p class="font-semibold text-black text-sm">
                                                            {{ payment.payment_number }}
                                                        </p>
                                                        <p class="text-xs text-gray-600">
                                                            {{ payment.payment_methods.name }}
                                                        </p>
                                                    </div>
                                                </div>

                                                <div class="flex items-center gap-2">
                                                    <PaymentStatusBadge :status="payment.status" />
                                                    <PaymentAttachmentPopOver
                                                        :paymentAttachmentURL="
                                                            payment.payment_attachments.temp_url
                                                        "
                                                    />
                                                </div>
                                            </div>

                                            <!-- Payment Amount -->
                                            <PaymentAmountApplied
                                                :amount="payment.amount_applied"
                                                :status="payment.status"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <!-- No Payments State -->
                                <div v-else class="mb-6">
                                    <h3
                                        class="text-lg font-semibold text-black mb-3 border-l-4 border-black pl-3"
                                    >
                                        Payment History
                                    </h3>
                                    <div class="bg-gray-50 border border-gray-200 p-8 text-center">
                                        <svg
                                            class="w-12 h-12 text-gray-400 mx-auto mb-3"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"
                                            />
                                        </svg>
                                        <p class="text-gray-600 text-sm">
                                            No payments recorded yet
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Payment Total Summary -->
                            <div
                                v-if="
                                    orderDetails.order_payments &&
                                    orderDetails.order_payments.length > 0
                                "
                                class="absolute bottom-0 w-full border-t border-gray-200 bg-gray-50 px-6 py-4 flex-shrink-0"
                            >
                                <div class="grid grid-cols-3 gap-4">
                                    <div>
                                        <p class="text-sm text-gray-900 mb-1">Order Total Price</p>
                                        <p class="text-xl font-bold text-gray-900">
                                            ₱{{ orderTotalPrice.toLocaleString() }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-900 mb-1">Total Paid Amount</p>
                                        <p class="text-xl font-bold text-green-600">
                                            ₱{{ totalApplied.toLocaleString() }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-900 mb-1">Remaining Balance</p>
                                        <p class="text-xl font-bold text-amber-600">
                                            ₱{{ remainingBalance.toLocaleString() }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>

    <AddNewPaymentModal
        v-if="showAddNewPaymentModal && qrCodePaymentData"
        :paymentData="qrCodePaymentData"
        @closeModal="handleCloseNewPaymentModal"
    />
</template>
