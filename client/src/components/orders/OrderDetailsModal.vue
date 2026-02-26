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
    import ReuploadPaymentModal from './ReuploadPaymentModal.vue'
    import { usePayments } from '@/composables/usePayments'

    const props = defineProps<{
        orderDetails: Orders
        isOpen: boolean
    }>()

    const showAddNewPaymentModal = ref<boolean>(false)
    const showReuploadPaymentModal = ref<boolean>(false)
    const qrCodePaymentData = ref<QrCodePaymentData | null>(null)
    const activePaymentIdForReupload = ref<number | null>(null)

    const handleShowNewPaymentModal = () => {
        showAddNewPaymentModal.value = true

        const totalQty =
            props.orderDetails.items?.reduce((sum, item) => sum + (item.total_quantity || 0), 0) ||
            0
        qrCodePaymentData.value = {
            product_name: props.orderDetails.items?.[0]?.product?.name || 'Order Items',
            total_quantity: totalQty,
            total_price: props.orderDetails.total_price,
            order_id: props.orderDetails.id,
        }
    }

    const handleCloseNewPaymentModal = () => {
        showAddNewPaymentModal.value = false
        qrCodePaymentData.value = null
    }

    const handleShowReuploadPaymentModal = (paymentId: number) => {
        activePaymentIdForReupload.value = paymentId

        const totalQty =
            props.orderDetails.items?.reduce((sum, item) => sum + (item.total_quantity || 0), 0) ||
            0
        qrCodePaymentData.value = {
            product_name: props.orderDetails.items?.[0]?.product?.name || 'Order Items',
            total_quantity: totalQty,
            total_price: props.orderDetails.total_price,
            order_id: props.orderDetails.id,
        }
        showReuploadPaymentModal.value = true
    }

    const handleCloseReuploadPaymentModal = () => {
        showReuploadPaymentModal.value = false
        activePaymentIdForReupload.value = null
        qrCodePaymentData.value = null
    }

    const emit = defineEmits(['close'])
    const handleCloseOrderDetailsModal = () => emit('close')

    // Payment composable
    const { hasFullyPaid } = usePayments(computed(() => props.orderDetails.order_payments || []))

    console.log('orderDetails:', props.orderDetails)
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
                <div class="fixed inset-0 bg-black/25 dark:bg-black/60" />
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
                            class="relative w-full max-w-3xl transform overflow-hidden bg-white dark:bg-gray-900 shadow-2xl transition-all"
                        >
                            <!-- Header -->
                            <div
                                class="bg-gray-900 text-white p-6 border-b border-gray-200 dark:bg-gray-800 dark:border-gray-700"
                            >
                                <div class="flex items-center justify-between">
                                    <DialogTitle as="h2" class="text-xl font-bold">
                                        Order Details
                                    </DialogTitle>
                                    <button
                                        @click="handleCloseOrderDetailsModal"
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
                            <div class="p-6 max-h-96 overflow-y-auto dark:bg-gray-900">
                                <!-- Order Header Info -->
                                <div
                                    class="grid grid-cols-2 gap-4 mb-6 pb-6 border-b border-gray-200 dark:border-gray-700"
                                >
                                    <div>
                                        <h3
                                            class="text-sm font-medium text-gray-600 tracking-wide dark:text-gray-400"
                                        >
                                            Order Number
                                        </h3>
                                        <p
                                            class="text-lg font-bold text-black mt-1 dark:text-white"
                                        >
                                            {{ orderDetails.order_number }}
                                        </p>
                                    </div>
                                    <div>
                                        <h3
                                            class="text-sm font-medium text-gray-600 tracking-wide dark:text-gray-400"
                                        >
                                            Status
                                        </h3>
                                        <div class="mt-1">
                                            <StatusBadge :status="orderDetails.status" />
                                        </div>
                                    </div>
                                    <div>
                                        <h3
                                            class="text-sm font-medium text-gray-600 tracking-wide dark:text-gray-400"
                                        >
                                            Date Ordered
                                        </h3>
                                        <p class="text-sm text-black mt-1 dark:text-white">
                                            {{ formatDate(orderDetails.created_at) }}
                                        </p>
                                    </div>

                                    <div>
                                        <h3
                                            class="text-sm font-medium text-gray-600 tracking-wide dark:text-gray-400"
                                        >
                                            {{
                                                orderDetails.order_option === OrderOptions.DELIVERY
                                                    ? 'Delivery Date'
                                                    : 'Pick-up Date'
                                            }}
                                        </h3>
                                        <p class="text-sm text-black mt-1 dark:text-white">
                                            {{
                                                orderDetails.delivery_date
                                                    ? formatDate(orderDetails.delivery_date)
                                                    : 'N/A'
                                            }}
                                        </p>
                                    </div>

                                    <div>
                                        <h3
                                            class="text-sm font-medium text-gray-600 tracking-wide dark:text-gray-400"
                                        >
                                            Expected Delivery Days
                                        </h3>
                                        <p class="text-sm text-green-600 mt-1 dark:text-green-400">
                                            {{
                                                (orderDetails.items?.reduce(
                                                    (sum, item) => sum + (item.total_quantity || 0),
                                                    0,
                                                ) || 0) < 100
                                                    ? '5-7 business days'
                                                    : '13-15 business days'
                                            }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Customer Info -->
                                <div
                                    class="mb-6 pb-6 border-b border-gray-200 dark:border-gray-700"
                                >
                                    <h3
                                        class="text-lg font-semibold text-black mb-3 border-l-4 border-black pl-3 dark:text-white dark:border-white"
                                    >
                                        Customer Information
                                    </h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                                Name
                                            </p>
                                            <p class="font-medium text-black dark:text-white">
                                                {{ orderDetails.user?.name }}
                                            </p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                                Phone Number
                                            </p>
                                            <p class="font-medium text-black dark:text-white">
                                                +63 {{ orderDetails.phone_number }}
                                            </p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                                Address
                                            </p>
                                            <p class="font-medium text-black dark:text-white">
                                                {{ orderDetails.address }}
                                            </p>
                                        </div>

                                        <div>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                                Email
                                            </p>
                                            <p class="font-medium text-black dark:text-white">
                                                {{ orderDetails.user?.email }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Product Info (Iterating over items) -->
                                <div
                                    class="mb-6 pb-6 border-b border-gray-200 dark:border-gray-700"
                                >
                                    <h3
                                        class="text-lg font-semibold text-black mb-3 border-l-4 border-black pl-3 dark:text-white dark:border-white"
                                    >
                                        Product Details
                                    </h3>

                                    <div class="space-y-6">
                                        <div
                                            v-for="item in orderDetails.items"
                                            :key="item.id"
                                            class="flex gap-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg"
                                        >
                                            <!-- Product Image -->
                                            <div v-if="item.temp_url" class="flex-shrink-0">
                                                <img
                                                    :src="item.temp_url"
                                                    :alt="`Design ${item.id}`"
                                                    class="w-24 h-24 object-cover border-2 border-gray-300 dark:border-gray-700 rounded-md"
                                                />
                                            </div>

                                            <!-- Product Details -->
                                            <div class="flex-1">
                                                <h4
                                                    class="font-bold text-lg text-black dark:text-white mb-2"
                                                >
                                                    {{ item.product?.name }}
                                                    <span class="text-sm font-normal text-gray-500">
                                                        (x{{ item.total_quantity || 1 }})
                                                    </span>
                                                </h4>
                                                <div class="grid grid-cols-2 gap-4">
                                                    <div>
                                                        <p
                                                            class="text-sm text-gray-600 dark:text-gray-400"
                                                        >
                                                            Color
                                                        </p>
                                                        <div class="flex items-center gap-2">
                                                            <p
                                                                class="font-medium text-black dark:text-white"
                                                            >
                                                                {{ item.color }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Sizes -->
                                                <div
                                                    v-if="item.sizes && item.sizes.length > 0"
                                                    class="mt-4"
                                                >
                                                    <p
                                                        class="text-sm text-gray-600 mb-2 dark:text-gray-400"
                                                    >
                                                        Sizes & Quantities
                                                    </p>
                                                    <div class="flex flex-wrap gap-2">
                                                        <div
                                                            v-for="size in item.sizes"
                                                            :key="size.id"
                                                            class="bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 px-3 py-1 text-sm rounded-md shadow-sm"
                                                        >
                                                            <span
                                                                class="font-medium dark:text-white"
                                                            >
                                                                {{ size.name }}
                                                            </span>
                                                            <span
                                                                v-if="size.pivot"
                                                                class="text-gray-600 ml-1 dark:text-gray-400"
                                                            >
                                                                ({{ size.pivot.quantity || 'N/A' }})
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-4 grid grid-cols-2 gap-4">
                                        <div>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                                Option
                                            </p>
                                            <p class="font-medium text-black dark:text-white">
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
                                            class="text-lg font-semibold text-black mb-3 border-l-4 border-black pl-3 dark:text-white dark:border-white"
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
                                                class="mt-4 text-green-600 font-semibold text-sm flex items-center gap-2 dark:text-green-400"
                                            >
                                                <svg
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    class="w-5 h-5 text-green-600 dark:text-green-400"
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
                                            class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 p-4 hover:border-gray-300 dark:hover:border-gray-600 transition-colors duration-200"
                                        >
                                            <!-- Payment Header -->
                                            <div
                                                class="flex items-center justify-between mb-3 pb-3 border-b border-gray-200 dark:border-gray-700"
                                            >
                                                <div class="flex items-center gap-3">
                                                    <div
                                                        class="w-10 h-10 bg-gray-900 dark:bg-gray-700 rounded-full flex items-center justify-center flex-shrink-0"
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
                                                        <p
                                                            class="font-semibold text-black text-sm dark:text-white"
                                                        >
                                                            {{ payment.payment_number }}
                                                        </p>
                                                        <p
                                                            class="text-xs text-gray-600 dark:text-gray-400"
                                                        >
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

                                                    <button
                                                        v-if="payment.status === 'declined'"
                                                        @click="
                                                            handleShowReuploadPaymentModal(
                                                                payment.id,
                                                            )
                                                        "
                                                        class="text-xs bg-green-800 text-white px-3 py-1.5 rounded-lg font-medium hover:opacity-75 hover:cursor-pointer transition-colors"
                                                    >
                                                        Re-upload payment
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Payment Amount -->
                                            <PaymentAmountApplied
                                                :amount="payment.amount_applied"
                                                :status="payment.status"
                                                :remarks="payment.remarks"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <!-- No Payments State -->
                                <div v-else class="mb-6">
                                    <h3
                                        class="text-lg font-semibold text-black mb-3 border-l-4 border-black pl-3 dark:text-white dark:border-white"
                                    >
                                        Payment History
                                    </h3>
                                    <div
                                        class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-8 text-center"
                                    >
                                        <svg
                                            class="w-12 h-12 text-gray-400 mx-auto mb-3 dark:text-gray-600"
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
                                        <p class="text-gray-600 text-sm dark:text-gray-400">
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
                                class="absolute bottom-0 w-full border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-6 py-4 flex-shrink-0"
                            >
                                <div class="grid grid-cols-3 gap-4">
                                    <div>
                                        <p class="text-sm text-gray-900 dark:text-gray-100 mb-1">
                                            Order Total Price
                                        </p>
                                        <p class="text-xl font-bold text-gray-900 dark:text-white">
                                            ₱{{ props.orderDetails.total_price.toLocaleString() }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-900 dark:text-gray-100 mb-1">
                                            Total Paid Amount
                                        </p>
                                        <p
                                            class="text-xl font-bold text-green-600 dark:text-green-400"
                                        >
                                            ₱{{ props.orderDetails.total_paid }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-900 dark:text-gray-100 mb-1">
                                            Remaining Balance
                                        </p>
                                        <p
                                            class="text-xl font-bold text-amber-600 dark:text-amber-400"
                                        >
                                            ₱{{ props.orderDetails.balance }}
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

    <ReuploadPaymentModal
        v-if="showReuploadPaymentModal && qrCodePaymentData && activePaymentIdForReupload"
        :paymentData="qrCodePaymentData"
        :paymentId="activePaymentIdForReupload"
        @closeModal="handleCloseReuploadPaymentModal"
    />
</template>
