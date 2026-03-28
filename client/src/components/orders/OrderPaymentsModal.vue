<!-- eslint-disable @typescript-eslint/no-explicit-any -->
<script lang="ts" setup>
    import { apiService } from '@/api/axios'
    import { formatDateWithTime } from '@/helper/order'
    import type { Payment, PaymentMethods, UpdatePaymentPayload } from '@/types/payment'
    import { Dialog, DialogPanel } from '@headlessui/vue'
    import { useQuery, useMutation } from '@tanstack/vue-query'
    import { computed, onMounted, ref, watch } from 'vue'
    import { useQueryClient } from '@tanstack/vue-query'
    import PaymentAttachmentPopOver from './PaymentAttachmentPopOver.vue'
    import PaymentStatusBadge from './PaymentStatusBadge.vue'
    import { useToast } from 'primevue/usetoast'
    import { usePayments } from '@/composables/usePayments'
    import EditBalanceForm from './EditBalanceForm.vue'
    import { FwbTooltip } from 'flowbite-vue'
    import { OrderStatus, type Orders } from '@/types/order'

    const props = defineProps<{
        orders: Orders
        isAdmin?: boolean
    }>()

    // MODAL CLOSING EMITS
    const emit = defineEmits(['close', 'status-change'])
    const handleCloseModal = () => emit('close')

    const editingPayments = ref<{ [key: number]: number }>({})
    const editingPaymentMethods = ref<{ [key: number]: number }>({})
    const updatingPayments = ref<Set<number>>(new Set())
    const updatingPaymentMethods = ref<Set<number>>(new Set())
    const toast = useToast()
    const queryClient = useQueryClient()

    // CASH PAYMENT STATES
    const showCashPaymentForm = ref(false)
    const cashAmount = ref<number | null>(null)

    // EDIT BALANCE STATES
    const showEditBalanceForm = ref(false)

    // ADD CASH PAYMENT MUTATION
    const addCashPaymentMutation = useMutation({
        mutationFn: async (data: FormData) => {
            return await apiService.post('/api/add/payment', data)
        },
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Cash Payment Recorded',
                life: 1500,
            })

            showCashPaymentForm.value = false
            cashAmount.value = null

            // Invalidate queries
            queryClient.invalidateQueries({ queryKey: ['payments_by_order', props.orders.id] })
            queryClient.invalidateQueries({ queryKey: ['orders'] })
        },
        onError: (error) => {
            console.error('Error adding cash payment:', error)
            toast.add({
                severity: 'error',
                summary: 'Failed to record payment',
                detail: 'Please try again',
                life: 3000,
            })
        },
    })

    const submitCashPayment = () => {
        if (!cashAmount.value || cashAmount.value <= 0) {
            toast.add({
                severity: 'warn',
                summary: 'Invalid Amount',
                detail: 'Please enter a valid amount',
                life: 2000,
            })
            return
        }

        const formData = new FormData()
        formData.append('order_id', String(props.orders.id))
        formData.append('payment_method_code', 'cash')
        formData.append('amount', String(cashAmount.value))

        addCashPaymentMutation.mutate(formData)
    }

    // Local computed properties to track payment updates dynamically
    const currentTotalPaid = computed(() => {
        if (!payments.value) return props.orders.total_paid || 0
        return payments.value.reduce((sum, payment) => sum + Number(payment.amount_applied), 0)
    })

    const totalPocketCosts = computed(() => {
        return (props.orders.items || []).reduce((sum, item) => {
            const cost = item.customization?.pocket_costs
            return sum + (cost ? Number(cost) : 0)
        }, 0)
    })

    const currentBalance = computed(() => {
        const discountAmount = props.orders.discount ? Number(props.orders.discount.amount) : 0
        const balance =
            props.orders.total_price +
            totalPocketCosts.value -
            currentTotalPaid.value -
            discountAmount
        return Math.max(0, balance)
    })

    const isHalfPaid = computed(() => {
        return currentTotalPaid.value >= (props.orders.total_price + totalPocketCosts.value) / 2
    })

    // FETCH ALL PAYMENTS BY ORDER ID
    const {
        data: payments,
        error,
        isLoading,
    } = useQuery({
        queryKey: ['payments_by_order', props.orders.id],
        queryFn: async () => {
            const respData = await apiService.get<Payment[]>(`/api/get/payments/${props.orders.id}`)
            return respData
        },
    })
    // ... (rest of the script)

    // ... (inside template)

    const { data: paymentMethods } = useQuery({
        queryKey: ['payment_methods'],
        queryFn: async () => {
            const respData = await apiService.get<PaymentMethods[]>(`/api/get/payment/methods`)
            return respData
        },
    })

    // UPDATE PAYMENT AMOUNT MUTATION
    const recordPaymentMutation = useMutation({
        mutationFn: async ({ id, amount }: UpdatePaymentPayload) => {
            return await apiService.patch(`/api/record/payment/${id}`, {
                amount_applied: amount,
            })
        },
        onSuccess: (_, variables) => {
            toast.add({
                severity: 'success',
                summary: 'Payment Recorded Successfully',
                life: 1500,
            })

            // Remove from editing state
            delete editingPayments.value[variables.id]
            updatingPayments.value.delete(variables.id)

            // Invalidate and refetch
            queryClient.invalidateQueries({
                queryKey: ['payments_by_order', props.orders.id],
            })

            // setTimeout(() => {
            //     window.location.href = '/admin/orders'
            // }, 1500)
        },
        onError: (error, variables) => {
            console.error('Failed to update payment:', error)
            updatingPayments.value.delete(variables.id)
            toast.add({
                severity: 'error',
                summary: 'Failed to Update Payment',
                detail: 'Please try again',
                life: 3000,
            })
        },
    })

    // UPDATE PAYMENT METHOD MUTATION
    const updatePaymentMethodMutation = useMutation({
        mutationFn: async ({
            paymentId,
            paymentMethodId,
        }: {
            paymentId: number
            paymentMethodId: number
        }) => {
            return await apiService.patch(`/api/record/payment/${paymentId}`, {
                payment_method_id: paymentMethodId,
            })
        },
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Payment Method Updated Successfully',
                life: 2000,
            })

            // Invalidate and refetch payments
            queryClient.invalidateQueries({
                queryKey: ['payments_by_order', props.orders.id],
            })
        },
        onError: (error) => {
            console.error('Failed to update payment method:', error)
            toast.add({
                severity: 'error',
                summary: 'Failed to Update Payment Method',
                detail: 'Please try again',
                life: 3000,
            })
        },
    })

    // DECLINE PAYMENT MUTATION & STATE
    const showDeclineModal = ref(false)
    const declineRemarks = ref('')
    const paymentToDecline = ref<number | null>(null)

    const declinePaymentMutation = useMutation({
        mutationFn: async ({ paymentId, remarks }: { paymentId: number; remarks: string }) => {
            return await apiService.patch(`/api/decline/payment/${paymentId}`, { remarks })
        },
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Payment Declined',
                life: 1500,
            })
            showDeclineModal.value = false
            declineRemarks.value = ''
            paymentToDecline.value = null

            queryClient.invalidateQueries({
                queryKey: ['payments_by_order', props.orders.id],
            })
        },
        onError: (error) => {
            console.error('Failed to decline payment:', error)
            toast.add({
                severity: 'error',
                summary: 'Failed to Decline Payment',
                detail: 'Please try again',
                life: 3000,
            })
        },
    })

    const openDeclineModal = (paymentId: number) => {
        paymentToDecline.value = paymentId
        declineRemarks.value = ''
        showDeclineModal.value = true
    }

    const submitDecline = () => {
        if (!declineRemarks.value.trim()) {
            toast.add({ severity: 'warn', summary: 'Remarks are required', life: 2000 })
            return
        }
        if (paymentToDecline.value) {
            declinePaymentMutation.mutate({
                paymentId: paymentToDecline.value,
                remarks: declineRemarks.value,
            })
        }
    }

    // Payment amount editing functions
    const startEditing = (paymentId: number, currentAmount: number) => {
        editingPayments.value[paymentId] = currentAmount
    }

    const cancelEditing = (paymentId: number) => {
        delete editingPayments.value[paymentId]
    }

    const savePayment = async (paymentId: number) => {
        const newAmount = editingPayments.value[paymentId]

        updatingPayments.value.add(paymentId)
        recordPaymentMutation.mutate({ id: paymentId, amount: newAmount })
    }

    const isEditing = (paymentId: number) => paymentId in editingPayments.value
    const isUpdating = (paymentId: number) => updatingPayments.value.has(paymentId)

    // Payment method editing functions
    const startEditingPaymentMethod = (paymentId: number, currentPaymentMethodId: number) => {
        editingPaymentMethods.value[paymentId] = currentPaymentMethodId
    }

    const cancelEditingPaymentMethod = (paymentId: number) => {
        delete editingPaymentMethods.value[paymentId]
    }

    const savePaymentMethod = async (paymentId: number) => {
        const newPaymentMethodId = editingPaymentMethods.value[paymentId]

        if (
            newPaymentMethodId === undefined ||
            newPaymentMethodId === null ||
            newPaymentMethodId <= 0
        ) {
            toast.add({
                severity: 'warn',
                summary: 'Please select a payment method',
                life: 2000,
            })
            return
        }

        updatingPaymentMethods.value.add(paymentId)
        updatePaymentMethodMutation.mutate(
            { paymentId, paymentMethodId: newPaymentMethodId },
            {
                onSettled: () => {
                    updatingPaymentMethods.value.delete(paymentId)
                    delete editingPaymentMethods.value[paymentId]
                },
            },
        )
    }

    const isEditingPaymentMethod = (paymentId: number) => paymentId in editingPaymentMethods.value
    const isUpdatingPaymentMethod = (paymentId: number) =>
        updatingPaymentMethods.value.has(paymentId)

    // Payment composable
    const { hasFullyPaid } = usePayments(computed(() => payments.value || []))
</script>

<template>
    <Dialog
        :open="true"
        @close="handleCloseModal"
        class="fixed inset-0 z-[999] flex items-center justify-center bg-gray-900/70"
    >
        <DialogPanel class="w-full max-w-4xl mx-4">
            <div
                class="bg-white dark:bg-gray-900 max-h-[90vh] flex flex-col overflow-hidden rounded-2xl shadow-2xl"
            >
                <!-- Header -->
                <div class="bg-gray-900 text-white px-6 py-4 flex items-center justify-between">
                    <div>
                        <h1 class="text-xl font-bold">Payment Management</h1>
                        <p class="text-gray-300 text-sm">Order # {{ props.orders.order_number }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <button
                            v-if="props.isAdmin"
                            @click="
                                () => {
                                    showCashPaymentForm = !showCashPaymentForm
                                    showEditBalanceForm = false
                                }
                            "
                            class="text-sm bg-white text-gray-900 px-3 py-2 rounded-lg font-medium hover:bg-gray-100 transition-colors"
                        >
                            {{
                                showCashPaymentForm ? 'Cancel Cash Payment' : 'Receive Cash Payment'
                            }}
                        </button>
                        <button
                            v-if="props.isAdmin && isHalfPaid"
                            @click="
                                () => {
                                    showEditBalanceForm = !showEditBalanceForm
                                    showCashPaymentForm = false
                                }
                            "
                            class="text-sm bg-gray-700 text-white border border-gray-600 px-3 py-2 rounded-lg font-medium hover:bg-gray-600 transition-colors"
                        >
                            {{ showEditBalanceForm ? 'Cancel Edit Balance' : 'Edit Balance' }}
                        </button>
                        <button
                            @click="handleCloseModal"
                            class="text-gray-300 hover:text-white p-2 rounded-lg hover:bg-gray-800 transition-colors"
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

                <!-- Cash Payment Form -->
                <div
                    v-if="showCashPaymentForm"
                    class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 p-6"
                >
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                        Record Cash Payment
                    </h3>
                    <div class="flex gap-4 items-end">
                        <div class="flex-1">
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                            >
                                Amount Received (₱)
                            </label>
                            <input
                                v-model="cashAmount"
                                type="number"
                                min="1"
                                step="0.01"
                                placeholder="Enter amount"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 shadow-sm focus:border-gray-900 focus:ring-gray-900"
                                @keypress.enter="submitCashPayment"
                            />
                        </div>
                        <button
                            @click="submitCashPayment"
                            :disabled="addCashPaymentMutation.isPending.value || !cashAmount"
                            class="bg-gray-900 text-white px-4 py-2 rounded-lg font-medium hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 disabled:opacity-50 disabled:cursor-not-allowed h-[42px]"
                        >
                            <span v-if="addCashPaymentMutation.isPending.value">Processing...</span>
                            <span v-else>Confirm Payment</span>
                        </button>
                    </div>
                </div>

                <!-- Edit Balance Form -->
                <EditBalanceForm
                    v-if="showEditBalanceForm"
                    :order="props.orders"
                    :current-total-paid="currentTotalPaid"
                    @close="showEditBalanceForm = false"
                />

                <!-- Content -->
                <div class="flex flex-col flex-1 min-h-0">
                    <!-- Scrollable Content Area -->
                    <div class="flex-1 overflow-hidden flex flex-col p-6">
                        <!-- Loading State -->
                        <div v-if="isLoading" class="flex items-center justify-center py-12 flex-1">
                            <div class="text-center">
                                <div
                                    class="animate-spin rounded-full h-12 w-12 border-b-2 border-gray-900 dark:border-white mx-auto mb-4"
                                ></div>
                                <p class="text-gray-600 dark:text-gray-400">Loading payments...</p>
                            </div>
                        </div>

                        <!-- Error State -->
                        <div v-else-if="error" class="text-center py-12 flex-1">
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
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                                Failed to load payments
                            </h3>
                            <p class="text-gray-600 dark:text-gray-400">
                                There was an error loading the payment data.
                            </p>
                        </div>

                        <!-- Payment Cards -->
                        <div
                            v-else-if="payments && payments.length > 0"
                            class="flex-1 flex flex-col min-h-0"
                        >
                            <!-- Payment List - Scrollable -->
                            <div class="flex-1 overflow-y-auto space-y-4 pr-2">
                                <div
                                    v-for="payment in payments"
                                    :key="payment.id"
                                    class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6 hover:shadow-md transition-shadow duration-200 relative"
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
                                                <h4
                                                    class="font-semibold text-gray-900 dark:text-white"
                                                >
                                                    {{ payment.payment_number }}
                                                </h4>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <PaymentStatusBadge :status="payment.status" />
                                            <PaymentAttachmentPopOver
                                                v-if="payment.payment_attachments?.temp_url"
                                                :paymentAttachmentURL="
                                                    payment.payment_attachments.temp_url
                                                "
                                            />
                                            <button
                                                v-if="
                                                    props.isAdmin && payment.status === 'in_review'
                                                "
                                                @click="openDeclineModal(payment.id)"
                                                class="text-xs bg-red-800 text-white px-3 py-1.5 rounded-lg font-medium hover:opacity-75 hover:cursor-pointer transition-colors"
                                            >
                                                Decline
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Payment Details -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                        <div>
                                            <label
                                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                                            >
                                                Amount Applied
                                            </label>
                                            <div
                                                v-if="!isEditing(payment.id)"
                                                class="flex items-center justify-between bg-gray-50 dark:bg-gray-700 rounded-lg p-3"
                                            >
                                                <span
                                                    class="text-xl font-bold text-gray-900 dark:text-white"
                                                >
                                                    ₱ {{ payment.amount_applied }}
                                                </span>

                                                <div class="flex items-center">
                                                    <fwb-tooltip>
                                                        <template #trigger>
                                                            <button
                                                                v-if="!hasFullyPaid"
                                                                @click="
                                                                    startEditing(
                                                                        payment.id,
                                                                        payment.amount_applied,
                                                                    )
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
                                                                        d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"
                                                                    />
                                                                </svg>
                                                            </button>
                                                        </template>
                                                        <template #content>
                                                            <h1 class="text-xs font-normal">
                                                                Record Payment
                                                            </h1>
                                                        </template>
                                                    </fwb-tooltip>
                                                </div>
                                            </div>
                                            <div v-else class="flex space-x-2">
                                                <div class="relative flex-1">
                                                    <span
                                                        class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500"
                                                    >
                                                        ₱
                                                    </span>
                                                    <input
                                                        v-model="editingPayments[payment.id]"
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        class="w-full pl-8 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-gray-900 focus:border-transparent"
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
                                            <label
                                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                                            >
                                                Payment Method
                                            </label>
                                            <div
                                                v-if="!isEditingPaymentMethod(payment.id)"
                                                class="flex items-center justify-between bg-gray-50 dark:bg-gray-700 rounded-lg p-3"
                                            >
                                                <span class="text-gray-900 dark:text-white">
                                                    {{ payment.payment_methods.name }}
                                                </span>
                                                <fwb-tooltip>
                                                    <template #trigger>
                                                        <button
                                                            @click="
                                                                startEditingPaymentMethod(
                                                                    payment.id,
                                                                    payment.payment_method_id,
                                                                )
                                                            "
                                                            :disabled="
                                                                isUpdatingPaymentMethod(payment.id)
                                                            "
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
                                                    </template>
                                                    <template #content>
                                                        <h1 class="text-xs font-normal">
                                                            Edit Payment Method
                                                        </h1>
                                                    </template>
                                                </fwb-tooltip>
                                            </div>
                                            <div v-else class="flex space-x-2">
                                                <select
                                                    v-model="editingPaymentMethods[payment.id]"
                                                    class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-transparent"
                                                    :disabled="isUpdatingPaymentMethod(payment.id)"
                                                >
                                                    <option :value="null" disabled>
                                                        Select Payment Method
                                                    </option>
                                                    <option
                                                        v-for="method in paymentMethods?.filter(
                                                            (m) => m.name.toLowerCase() !== 'cash',
                                                        )"
                                                        :key="method.id"
                                                        :value="method.id"
                                                    >
                                                        {{ method.name }}
                                                    </option>
                                                </select>
                                                <button
                                                    @click="savePaymentMethod(payment.id)"
                                                    :disabled="isUpdatingPaymentMethod(payment.id)"
                                                    class="px-3 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 focus:ring-2 focus:ring-gray-900 focus:ring-offset-2 disabled:opacity-50 transition-colors"
                                                >
                                                    <svg
                                                        v-if="isUpdatingPaymentMethod(payment.id)"
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
                                                    @click="cancelEditingPaymentMethod(payment.id)"
                                                    :disabled="isUpdatingPaymentMethod(payment.id)"
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
                                    </div>

                                    <!-- Timestamps -->
                                    <div
                                        class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400 pt-4 border-t border-gray-200 dark:border-gray-700"
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
                        <div v-else class="flex-1 flex items-center justify-center">
                            <div class="text-center py-12">
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
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                                    No payments found
                                </h3>
                                <p class="text-gray-600 dark:text-gray-400">
                                    There are no payments associated with this order.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Total Summary - Fixed Footer -->
                    <div
                        v-if="payments && payments.length > 0"
                        class="border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-6 py-4 flex-shrink-0"
                    >
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">
                                    Total Price
                                </p>
                                <p class="text-xl font-bold text-gray-900 dark:text-white">
                                    ₱{{ orders.total_price.toLocaleString() }}
                                </p>
                                <p
                                    v-if="totalPocketCosts > 0"
                                    class="text-xs text-yellow-600 dark:text-yellow-400 font-semibold mt-0.5"
                                >
                                    + ₱{{ totalPocketCosts.toFixed(2) }} (pocket costs)
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">
                                    Total Paid Amount
                                </p>
                                <p class="text-xl font-bold text-green-600">
                                    ₱{{ currentTotalPaid.toLocaleString() }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">
                                    Remaining Balance
                                </p>
                                <div class="flex items-center gap-2">
                                    <p class="text-xl font-bold text-amber-600">
                                        ₱{{ currentBalance.toLocaleString() }}
                                    </p>
                                    <span
                                        v-if="
                                            props.orders.discount &&
                                            Number(props.orders.discount.amount) > 0
                                        "
                                        class="text-[10px] bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 px-2 py-0.5 rounded-full font-bold uppercase tracking-wider border border-emerald-200 dark:border-emerald-800"
                                    >
                                        Discounted
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </DialogPanel>
        <!-- Decline Payment Modal -->
        <Dialog
            :open="showDeclineModal"
            @close="showDeclineModal = false"
            class="fixed inset-0 z-[1000] flex items-center justify-center bg-gray-900/70"
        >
            <DialogPanel
                class="w-full max-w-md mx-4 bg-white dark:bg-gray-900 dark:border dark:border-gray-700 rounded-xl shadow-2xl p-6"
            >
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                    Decline Payment
                </h3>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Remarks / Reason for Decline
                    </label>
                    <textarea
                        v-model="declineRemarks"
                        rows="4"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 shadow-sm focus:border-red-500 focus:ring-red-500"
                        placeholder="Enter reason for declining this payment..."
                    ></textarea>
                </div>
                <div class="flex justify-end gap-3">
                    <button
                        @click="showDeclineModal = false"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-white bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors"
                        :disabled="declinePaymentMutation.isPending.value"
                    >
                        Cancel
                    </button>
                    <button
                        @click="submitDecline"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors disabled:opacity-50"
                        :disabled="declinePaymentMutation.isPending.value || !declineRemarks.trim()"
                    >
                        <span v-if="declinePaymentMutation.isPending.value">Declining...</span>
                        <span v-else>Confirm Decline</span>
                    </button>
                </div>
            </DialogPanel>
        </Dialog>
    </Dialog>
</template>
