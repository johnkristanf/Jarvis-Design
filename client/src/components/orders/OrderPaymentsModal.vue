<!-- eslint-disable @typescript-eslint/no-explicit-any -->
<script lang="ts" setup>
    import { apiService } from '@/api/axios'
    import { formatDateWithTime } from '@/helper/order'
    import type { Payment, UpdatePaymentPayload } from '@/types/payment'
    import { Dialog, DialogPanel } from '@headlessui/vue'
    import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
    import { computed, ref, watch } from 'vue'
    import PaymentAttachmentPopOver from './PaymentAttachmentPopOver.vue'
    import PaymentStatusBadge from './PaymentStatusBadge.vue'
    import { useToast } from 'primevue/usetoast'
    import { usePayments } from '@/composables/usePayments'

    const props = defineProps<{
        orderData: {
            order_id: number
            order_number: string
        }
    }>()

    // MODAL CLOSING EMITS
    const emit = defineEmits(['close'])
    const handleCloseModal = () => emit('close')

    const queryClient = useQueryClient()
    const editingPayments = ref<{ [key: number]: number }>({})
    const updatingPayments = ref<Set<number>>(new Set())
    const toast = useToast()

    // FETCH ALL PAYMENTS BY ORDER ID
    const {
        data: payments,
        error,
        isLoading,
    } = useQuery({
        queryKey: ['payments_by_order', props.orderData.order_id],
        queryFn: async () => {
            const respData = await apiService.get<Payment[]>(
                `/api/get/payments/${props.orderData.order_id}`,
            )
            return respData
        },
    })

    // UPDATE PAYMENT MUTATION
    const updatePaymentMutation = useMutation({
        mutationFn: async ({ id, amount }: UpdatePaymentPayload) => {
            return await apiService.patch(`/api/update/payment/${id}`, {
                amount_applied: amount,
            })
        },
        onSuccess: (_, variables) => {
            toast.add({
                severity: 'success',
                summary: 'Payment Applied Successfully',
                life: 1500,
            })

            // Remove from editing state
            delete editingPayments.value[variables.id]
            updatingPayments.value.delete(variables.id)

            // Invalidate and refetch
            queryClient.invalidateQueries({
                queryKey: ['payments_by_order', props.orderData.order_id],
            })
        },
        onError: (error, variables) => {
            console.error('Failed to update payment:', error)
            updatingPayments.value.delete(variables.id)
        },
    })

    // Watch for data changes
    watch(
        payments,
        (newData) => {
            if (newData) {
                console.log('payments: ', newData)
            }
        },
        { immediate: true },
    )

    const startEditing = (paymentId: number, currentAmount: number) => {
        editingPayments.value[paymentId] = currentAmount
    }

    const cancelEditing = (paymentId: number) => {
        delete editingPayments.value[paymentId]
    }

    const savePayment = async (paymentId: number) => {
        const newAmount = editingPayments.value[paymentId]

        updatingPayments.value.add(paymentId)
        updatePaymentMutation.mutate({ id: paymentId, amount: newAmount })
    }

    const isEditing = (paymentId: number) => paymentId in editingPayments.value
    const isUpdating = (paymentId: number) => updatingPayments.value.has(paymentId)

    // Payment composable
    const { orderTotalPrice, totalApplied, remainingBalance, hasFullyPaid } = usePayments(
        computed(() => payments.value || []),
        null,
    )
</script>

<template>
    <Dialog
        :open="true"
        @close="handleCloseModal"
        class="fixed inset-0 z-[999] flex items-center justify-center bg-gray-900/70"
    >
        <DialogPanel class="w-full max-w-4xl mx-4">
            <div class="bg-white max-h-[90vh] overflow-hidden rounded-2xl shadow-2xl">
                <!-- Header -->
                <div class="bg-gray-900 text-white px-6 py-4 flex items-center justify-between">
                    <div>
                        <h1 class="text-xl font-bold">Payment Management</h1>
                        <p class="text-gray-300 text-sm">
                            Order # {{ props.orderData.order_number }}
                        </p>
                    </div>
                    <button
                        @click="handleCloseModal"
                        class="text-gray-300 hover:text-white p-2 rounded-lg hover:bg-gray-800 transition-colors"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>

                <!-- Content -->
                <div class="p-6">
                    <!-- Loading State -->
                    <div v-if="isLoading" class="flex items-center justify-center py-12">
                        <div class="text-center">
                            <div
                                class="animate-spin rounded-full h-12 w-12 border-b-2 border-gray-900 mx-auto mb-4"
                            ></div>
                            <p class="text-gray-600">Loading payments...</p>
                        </div>
                    </div>

                    <!-- Error State -->
                    <div v-else-if="error" class="text-center py-12">
                        <div class="text-red-500 mb-4">
                            <svg
                                class="w-16 h-16 mx-auto"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">
                            Failed to load payments
                        </h3>
                        <p class="text-gray-600">There was an error loading the payment data.</p>
                    </div>

                    <!-- Payment Cards -->
                    <div v-else-if="payments && payments.length > 0" class="space-y-4">
                        <!-- Payment List -->
                        <div class="max-h-96 overflow-y-auto space-y-4">
                            <div
                                v-for="payment in payments"
                                :key="payment.id"
                                class="bg-white border border-gray-200 rounded-xl p-6 hover:shadow-md transition-shadow duration-200 relative"
                            >
                                <!-- Payment Header -->
                                <div class="flex items-center justify-between my-6">
                                    <div class="flex items-center space-x-3">
                                        <div
                                            class="w-10 h-10 bg-gray-900 rounded-full flex items-center justify-center"
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
                                            <h4 class="font-semibold text-gray-900">
                                                {{ payment.payment_number }}
                                            </h4>
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

                                <!-- Payment Details -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Amount Applied
                                        </label>
                                        <div
                                            v-if="!isEditing(payment.id)"
                                            class="flex items-center justify-between bg-gray-50 rounded-lg p-3"
                                        >
                                            <span class="text-xl font-bold text-gray-900">
                                                ${{ payment.amount_applied }}
                                            </span>
                                            <button
                                                v-if="!hasFullyPaid"
                                                @click="
                                                    startEditing(payment.id, payment.amount_applied)
                                                "
                                                :disabled="isUpdating(payment.id)"
                                                class="text-gray-600 hover:text-gray-900 p-1 rounded transition-colors disabled:opacity-50"
                                            >
                                                <svg
                                                    class="w-4 h-4"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                                    />
                                                </svg>
                                            </button>
                                        </div>
                                        <div v-else class="flex space-x-2">
                                            <div class="relative flex-1">
                                                <span
                                                    class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500"
                                                >
                                                    $
                                                </span>
                                                <input
                                                    v-model="editingPayments[payment.id]"
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent"
                                                    @keydown.enter="savePayment(payment.id)"
                                                    @keydown.escape="cancelEditing(payment.id)"
                                                />
                                            </div>
                                            <button
                                                @click="savePayment(payment.id)"
                                                :disabled="isUpdating(payment.id)"
                                                class="px-3 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 focus:ring-2 focus:ring-gray-900 focus:ring-offset-2 disabled:opacity-50 transition-colors"
                                            >
                                                <svg
                                                    v-if="isUpdating(payment.id)"
                                                    class="animate-spin h-4 w-4"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <circle
                                                        class="opacity-25"
                                                        cx="12"
                                                        cy="12"
                                                        r="10"
                                                        stroke="currentColor"
                                                        stroke-width="4"
                                                    ></circle>
                                                    <path
                                                        class="opacity-75"
                                                        fill="currentColor"
                                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                                    ></path>
                                                </svg>
                                                <svg
                                                    v-else
                                                    class="w-4 h-4"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M5 13l4 4L19 7"
                                                    />
                                                </svg>
                                            </button>
                                            <button
                                                @click="cancelEditing(payment.id)"
                                                :disabled="isUpdating(payment.id)"
                                                class="px-3 py-2 text-gray-600 hover:text-gray-900 border border-gray-300 rounded-lg hover:bg-gray-50 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 disabled:opacity-50 transition-colors"
                                            >
                                                <svg
                                                    class="w-4 h-4"
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

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Payment Method
                                        </label>
                                        <div class="bg-gray-50 rounded-lg p-3">
                                            <span class="text-gray-900">
                                                {{ payment.payment_methods.name }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Timestamps -->
                                <div
                                    class="flex items-center justify-between text-sm text-gray-500 pt-4 border-t border-gray-200"
                                >
                                    <span>
                                        Paid At: {{ formatDateWithTime(payment.created_at) }}
                                    </span>
                                    <span>
                                        Updated At: {{ formatDateWithTime(payment.updated_at) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- No Payments Found -->
                    <div v-else class="text-center py-12">
                        <div class="text-gray-400 mb-4">
                            <svg
                                class="w-16 h-16 mx-auto"
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
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No payments found</h3>
                        <p class="text-gray-600">
                            There are no payments associated with this order.
                        </p>
                    </div>

                    <!-- Payment Total Summary -->
                    <div
                        v-if="payments && payments.length > 0"
                        class="border-t border-gray-200 bg-gray-50 px-6 py-4 flex-shrink-0"
                    >
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <p class="text-sm text-gray-600 mb-1">Order Total Price</p>
                                <p class="text-xl font-bold text-gray-900">
                                    ₱{{ orderTotalPrice.toLocaleString() }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 mb-1">Total Paid Amount</p>
                                <p class="text-xl font-bold text-green-600">
                                    ₱{{ totalApplied.toLocaleString() }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 mb-1">Remaining Balance</p>
                                <p class="text-xl font-bold text-amber-600">
                                    ₱{{ remainingBalance.toLocaleString() }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </DialogPanel>
    </Dialog>
</template>
