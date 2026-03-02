<script lang="ts" setup>
    import { Popover, PopoverButton, PopoverPanel } from '@headlessui/vue'
    import { FwbButton } from 'flowbite-vue'

    import { ChevronDownIcon, EllipsisVerticalIcon } from '@heroicons/vue/20/solid'

    import DatePicker from 'primevue/datepicker'

    import { useAuthorization } from '@/composables/useAuthorization'
    import { formatDate } from '@/helper/designs'
    import { OrderOptions, OrderStatus, type Orders, type UpdateStatusType } from '@/types/order'
    import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
    import Loader from '../Loader.vue'
    import { onBeforeMount, onMounted, ref, watch } from 'vue'
    import { updateOrderStatus } from '@/api/put/orders'
    import UploadedImagesModal from '../designs/UploadedImagesModal.vue'
    import QuantityPerSizeModal from '../designs/QuantityPerSizeModal.vue'
    import { apiService } from '@/api/axios'

    import { useToast } from 'primevue'
    import StatusBadge from './StatusBadge.vue'
    import PaginationControls from '../PaginationControls.vue'
    import type { PaginatedResponse } from '@/types/pagination'
    import OrderPaymentsModal from './OrderPaymentsModal.vue'
    import CustomerInfoModal from './CustomerInfoModal.vue'
    import ProductInformationModal from './ProductInformationModal.vue'
    import { initializeEcho } from '@/services/echo'
    import { sendChatMessageApi } from '@/api/post/message'
    import DeliveryReceiptModal from './DeliveryReceiptModal.vue'

    const { isAdmin } = useAuthorization()
    const isStatusUpdating = ref<boolean>(false)
    const isOrderLoading = ref<boolean>(true)

    // PAGINATION REFS
    const currentPage = ref(1)

    // Keep a flag to avoid infinite loops if handleStatusChange causes a refetch
    const alreadyUpdatedOrders = new Set<number>()

    const {
        data: orders,
        error,
        refetch,
        isLoading,
    } = useQuery({
        queryKey: ['orders', currentPage],
        queryFn: async () => {
            const respData = await apiService.get<PaginatedResponse<Orders>>(
                `/api/get/orders?page=${currentPage.value}`,
            )
            console.log('respData: ', respData)

            return respData
        },
        enabled: true,
        select: (respData: PaginatedResponse<Orders>) => {
            // Post-process: check if any order is at least 50% paid, and if so, update actual DB status
            respData.data.forEach((order) => {
                const currentTotalPaid = order.total_paid
                const halfTotalPrice = order.total_price / 2

                if (
                    currentTotalPaid >= halfTotalPrice &&
                    order.status !== OrderStatus.COMPLETED &&
                    order.status !== OrderStatus.IN_PROGRESS &&
                    order.status !== OrderStatus.CANCELLED &&
                    order.status !== OrderStatus.FOR_DELIVERY &&
                    order.status !== OrderStatus.FOR_PICKUP &&
                    !alreadyUpdatedOrders.has(order.id)
                ) {
                    // Call handleStatusChange to update the status in the DB
                    alreadyUpdatedOrders.add(order.id)
                    handleStatusChange(order.id, OrderStatus.IN_PROGRESS, () => {})

                    if (order.user?.id) {
                        const itemsList =
                            order.items && order.items.length > 0
                                ? order.items
                                      .map((item) => item.product?.name || 'Product')
                                      .join(', ')
                                : 'your items'

                        const formData = new FormData()
                        formData.append(
                            'content',
                            `🎉 Hello ${order.user.name || 'Customer'}!\n\n\nGood news! Your order **#${order.order_number}** for **${itemsList}** has reached the 50% payment threshold (₱${currentTotalPaid.toLocaleString()}). ✅\n\n\nWe will now begin processing your order. Thank you for your business! 👕✨\n\n[ADMIN_ORDER_LINK:${order.order_number}]`,
                        )
                        formData.append('user_id', order.user.id.toString())
                        sendChatMessageApi(formData).catch((err: any) =>
                            console.error('Failed to send automated message:', err),
                        )
                    }
                }
            })

            return respData // unmodified (let DB be the source of truth)
        },
    })

    import { computed } from 'vue'
    import { useRoute } from 'vue-router'
    import CancelOrderConfirmationDialog from '../CancelOrderConfirmationDialog.vue'

    const route = useRoute()
    const highlightedOrder = computed(() => route.query.highlight as string)

    // Helper: returns true if the specific order has at least one fully_paid payment
    const orderHasFullyPaid = (order: Orders): boolean => {
        return order.order_payments?.some((payment) => payment.status === 'fully_paid') ?? false
    }

    watch(
        () => error,
        (err) => {
            if (err) {
                isOrderLoading.value = false
            }
        },
    )

    const queryClient = useQueryClient()
    const toast = useToast()

    const showOrderPaymentsModal = ref<boolean>(false)
    const selectedOrderId = ref<number | null>(null)

    const handleShowOrderPaymentsModal = (orders: Orders, close: () => void) => {
        close()
        selectedOrderId.value = orders.id
        showOrderPaymentsModal.value = true
    }

    const selectedOrderData = computed(() => {
        if (!selectedOrderId.value || !orders.value?.data) return null
        return orders.value.data.find((o) => o.id === selectedOrderId.value)
    })

    const handleCloseOrderPaymentsModal = () => {
        showOrderPaymentsModal.value = false
        selectedOrderId.value = null
    }

    const handleShowDesignModal = (designId: number) => {
        console.log('handleShowDesignModal called with designId:', designId)
        if (!designId) {
            console.warn('designId is falsy, modal may not show')
        }
        selectedDesignID.value = designId
        showUploadedImageModal.value = true
    }

    // DESIGN IMAGE PREVIEW DIALOG
    const showDesignPreviewDialog = ref<boolean>(false)
    const selectedDesignImageUrl = ref<string>('')

    const handleShowDesignPreview = (tempUrl: string) => {
        selectedDesignImageUrl.value = tempUrl
        showDesignPreviewDialog.value = true
    }

    // PREFERRED ORDER OPTION FOR SETTING STATUS FILTERING
    const selectedOrderOption = ref<string>('')

    // SELECTED DESIGN TO RENDER
    const selectedDesignID = ref<number>()
    const showUploadedImageModal = ref<boolean>(false)

    // ORDER STATUS FOR COMPLETED ORDER FILTERING
    const selectedOrderStatus = ref<string>('')

    const showSizeBreakdownModal = ref(false)
    const selectedOrderSizes = ref([]) // Holds sizes for modal

    const isOrderCancelConfirmed = ref(false)

    // DATE UPDATE MUTATION
    const setDateMutation = useMutation({
        mutationFn: async (formData: FormData) => {
            return await apiService.post('/api/set/order/date', formData)
        },

        onError: (err) => {
            console.error('Update error', err)
            toast.add({
                severity: 'error',
                summary: 'Update date error, please try again',
                life: 3000,
            })
        },
    })

    // DATE SELECTION
    const selectedActionDates = ref<Record<number, Date | null>>({})

    const handleActionDateChange = (
        orderId: number,
        date: Date,
        status: string,
        close: () => void,
    ) => {
        console.log('Selected Date:', date)
        console.log('Status:', status)
        console.log('Order ID:', orderId)

        if (date) {
            const formattedDate = date.toLocaleDateString('en-CA') // 'YYYY-MM-DD'

            const formData = new FormData()
            formData.append('order_id', String(orderId))
            formData.append('status', status)
            formData.append('action_date', formattedDate)

            setDateMutation.mutate(formData, {
                onSuccess: (response) => {
                    console.log('udpate date response: ', response)

                    queryClient.invalidateQueries({ queryKey: ['orders'] })

                    toast.add({
                        severity: 'success',
                        summary: 'Success',
                        detail: 'Date Updated Successfully',
                        life: 1000,
                    })

                    close() // POP MODAL CLOSE
                },
            })
        }
    }

    // ORDER STATUSES
    const orderStatus = ref([
        { name: 'Complete', tag: OrderStatus.COMPLETED },
        // { name: 'In Progress', tag: OrderStatus.IN_PROGRESS }, // Hidden because the 50% payment handles the status set
        { name: 'Cancel', tag: OrderStatus.CANCELLED },
    ])

    const handleShowStatusFilter = (orderOption: string, orderStatus: string) => {
        selectedOrderOption.value = orderOption
        selectedOrderStatus.value = orderStatus
    }

    // UPDATE ORDER MUTATION
    const mutation = useMutation({
        mutationFn: updateOrderStatus,
        onSuccess: async () => {
            queryClient.invalidateQueries({ queryKey: ['orders', 'order_notifications'] })

            try {
                const refetchResult = await refetch()

                if (refetchResult.status === 'success') {
                    isStatusUpdating.value = false
                    isOrderCancelConfirmed.value = false
                }
            } catch (err) {
                console.error('Refetch failed:', err)
                isStatusUpdating.value = false
            }
        },

        onError: (error) => {
            console.error('Mutation error:', error)
        },

        onMutate: () => {
            isStatusUpdating.value = true
        },
    })

    const handleStatusChange = (order_id: number, statusTag: string, close: () => void) => {
        if (statusTag === OrderStatus.CANCELLED && !isOrderCancelConfirmed.value) {
            confirmCancelOrder(order_id, close)
            return
        }

        const statusData: UpdateStatusType = {
            order_id,
            status: statusTag,
        }

        mutation.mutate(statusData)
        close()
    }

    // POPOVER POSITIONING
    const popoverRef = ref<HTMLElement | null>(null)
    const popoverClose = ref<null | (() => void)>(null)
    const popoverPosition = ref({ top: 0, left: 0 })

    const setPopoverPosition = (event: MouseEvent) => {
        const target = event.currentTarget as HTMLElement
        const rect = target.getBoundingClientRect()

        popoverPosition.value = {
            top: rect.bottom + window.scrollY + 8, // below the button
            left: rect.left + window.scrollX - 220, // adjust so it's right-aligned
        }
    }

    // CLOSE POPOVER ON SCROLL
    const onTableScroll = () => {
        if (popoverClose.value) {
            popoverClose.value() // ✅ Properly close using Headless UI's API
        }
    }

    onMounted(() => {
        const container = document.querySelector('.order-table')
        if (container) {
            container.addEventListener('scroll', onTableScroll, { passive: true })
        }

        // Auto-scroll to highlighted order if it exists
        if (highlightedOrder.value) {
            setTimeout(() => {
                const highlightedEl = document.getElementById(`order-row-${highlightedOrder.value}`)
                if (highlightedEl && container) {
                    highlightedEl.scrollIntoView({ behavior: 'smooth', block: 'center' })
                }
            }, 500)
        }

        const echo = initializeEcho()

        echo.private(`payments.update`)
            .subscribed(() => {
                console.log('Admin: Private Channel authorized & subscribed')
            })
            .listen('.payment.update', (event: any) => {
                if (event.payment) {
                    console.log('Payment update received, invalidating orders...')
                    queryClient.invalidateQueries({ queryKey: ['orders'] })
                }
            })
            .error((error: any) => {
                console.error('❌ Websocket Authorization failed:', error)
            })
    })

    onBeforeMount(() => {
        const container = document.querySelector('.order-table')
        if (container) {
            container.removeEventListener('scroll', onTableScroll)
        }
    })

    interface DateChangeActionData {
        orderId: number
        date: Date
        status: string
        close: () => void
    }

    const showConfirmModal = ref(false)
    const dateChangeActionData = ref<DateChangeActionData | null>(null) // store the action temporarily

    function confirmAction(orderId: number, date: Date, status: string, close: () => void) {
        dateChangeActionData.value = { orderId, date, status, close }
        showConfirmModal.value = true
    }

    function proceedAction() {
        if (dateChangeActionData.value) {
            const { orderId, date, status, close } = dateChangeActionData.value
            handleActionDateChange(orderId, date, status, close)
        }
        showConfirmModal.value = false
        dateChangeActionData.value = null
    }

    // DELIVERY RECEIPT MODAL
    const showDeliveryReceiptModal = ref<boolean>(false)
    const selectedReceiptOrder = ref<Orders | null>(null)

    const handleShowDeliveryReceipt = (order: Orders, close: () => void) => {
        close()
        selectedReceiptOrder.value = order
        showDeliveryReceiptModal.value = true
    }

    const handleCloseDeliveryReceiptModal = () => {
        showDeliveryReceiptModal.value = false
        selectedReceiptOrder.value = null
    }

    // Cancel Order Confirmation
    const showCancelConfirmModal = ref(false)
    const cancelOrderData = ref<{ orderId: number; close: () => void } | null>(null)

    function confirmCancelOrder(orderId: number, close: () => void) {
        cancelOrderData.value = { orderId, close }
        showCancelConfirmModal.value = true
        isOrderCancelConfirmed.value = true
    }

    function closeCancelOrderConfirmation() {
        isOrderCancelConfirmed.value = false
    }

    function proceedCancelOrder() {
        if (cancelOrderData.value) {
            const { orderId, close } = cancelOrderData.value
            handleStatusChange(orderId, OrderStatus.CANCELLED, close)
        }
        showCancelConfirmModal.value = false
        cancelOrderData.value = null
    }
</script>

<template>
    <div class="order-table relative h-full overflow-y-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
            <thead
                class="text-xs text-white uppercase bg-gray-900 dark:bg-gray-700 dark:text-gray-400"
            >
                <tr>
                    <!-- <th scope="col" class="px-16 py-3">
                        <span>Order ID</span>
                    </th> -->
                    <th scope="col" class="px-3 py-3">Order No.</th>
                    <th scope="col" class="px-3 py-3">Customer Information</th>

                    <th scope="col" class="px-3 py-3">Product Information</th>

                    <!-- <th scope="col" class="px-6 py-3">Name</th>

                    <th scope="col" class="px-6 py-3">Phone Number</th>
                    <th scope="col" class="px-6 py-3">Address</th>

                    <th scope="col" class="px-6 py-3">Quantity</th>

                    <th scope="col" class="px-6 py-3">Color</th> -->

                    <!-- <th scope="col" class="px-6 py-3">Total Price</th> -->
                    <th scope="col" class="px-3 py-3">Option</th>

                    <th scope="col" class="px-3 py-3">Status</th>

                    <th scope="col" class="px-3 py-3">Delivery / Pick-Up Date</th>

                    <th v-if="isAdmin" scope="col" class="px-3 py-3">Actions</th>
                </tr>
            </thead>
            <tbody v-if="orders">
                <tr
                    v-for="order in orders.data"
                    :key="order.id"
                    :id="`order-row-${order.order_number}`"
                    :class="[
                        'border-b dark:border-gray-700 transition-colors duration-300',
                        highlightedOrder === order.order_number
                            ? 'bg-gray-300 dark:bg-orange-900/40 border-orange-200 hover:bg-orange-100 dark:hover:bg-orange-900/60'
                            : 'bg-white dark:bg-gray-800 border-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600',
                    ]"
                >
                    <td class="px-3 py-4 font-semibold text-gray-900 dark:text-white">
                        {{ order.order_number }}
                    </td>

                    <td class="px-3 py-4 font-semibold text-gray-900 dark:text-white">
                        <CustomerInfoModal
                            :customer-name="order.user?.name"
                            :email="order.user?.email"
                            :phone-number="order.phone_number"
                            :address="order.address"
                        />
                    </td>

                    <td class="px-3 py-4 font-semibold text-gray-900 dark:text-white">
                        <ProductInformationModal :items="order.items" />
                    </td>

                    <td class="px-3 py-4 font-semibold text-gray-900 dark:text-white">
                        {{ order.order_option.toUpperCase() }}
                    </td>

                    <td class="px-3 py-4">
                        <StatusBadge :status="order.status" />
                    </td>

                    <td class="px-3 py-4 font-semibold text-gray-900 dark:text-white">
                        {{ order.delivery_date ? formatDate(order.delivery_date) : 'N/A' }}
                    </td>

                    <!-- UPDATE STATUS ACTION BUTTON -->
                    <td
                        v-if="isAdmin"
                        class="px-3 py-4 font-semibold text-gray-900 dark:text-white"
                    >
                        <div
                            v-if="
                                order.status !== OrderStatus.COMPLETED &&
                                order.status !== OrderStatus.CANCELLED
                            "
                            class="w-full max-w-sm px-4"
                        >
                            <Popover v-slot="{ open, close }">
                                <div v-if="!(popoverClose = close)"></div>

                                <!-- Ellipsis Button -->
                                <PopoverButton
                                    class="focus:outline-none"
                                    @click="setPopoverPosition($event)"
                                >
                                    <EllipsisVerticalIcon class="w-6 h-6 cursor-pointer" />
                                </PopoverButton>

                                <!-- Transition -->
                                <transition
                                    enter-active-class="transition duration-200 ease-out"
                                    enter-from-class="translate-y-1 opacity-0"
                                    enter-to-class="translate-y-0 opacity-100"
                                    leave-active-class="transition duration-150 ease-in"
                                    leave-from-class="translate-y-0 opacity-100"
                                    leave-to-class="translate-y-1 opacity-0"
                                >
                                    <teleport to="body">
                                        <PopoverPanel
                                            v-if="open"
                                            class="absolute z-[999] w-64 rounded-lg bg-white dark:bg-gray-800 dark:border dark:border-gray-700 shadow-lg ring-1 ring-black/5 p-3"
                                            :style="{
                                                top: `${popoverPosition.top}px`,
                                                left: `${popoverPosition.left}px`,
                                            }"
                                            ref="popoverRef"
                                        >
                                            <!-- Actions -->
                                            <div class="flex flex-col gap-4">
                                                <!-- Payment Screenshot -->
                                                <button
                                                    v-if="
                                                        order.status !== OrderStatus.COMPLETED &&
                                                        order.status !== OrderStatus.CANCELLED
                                                    "
                                                    @click="
                                                        handleShowOrderPaymentsModal(order, close)
                                                    "
                                                    class="w-full text-sm font-medium px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors"
                                                >
                                                    Payments
                                                </button>

                                                <!-- Chat Button -->
                                                <button
                                                    v-if="
                                                        order.status !== OrderStatus.COMPLETED &&
                                                        order.status !== OrderStatus.CANCELLED
                                                    "
                                                    class="w-full text-sm font-medium px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors"
                                                >
                                                    <router-link
                                                        class="w-full block"
                                                        :to="`/admin/message/${order.user?.id}`"
                                                    >
                                                        Chat to Customer
                                                    </router-link>
                                                </button>

                                                <!-- Delivery or Pickup Date -->
                                                <div
                                                    v-if="
                                                        order.status == OrderStatus.IN_PROGRESS &&
                                                        orderHasFullyPaid(order) &&
                                                        !order.delivery_date
                                                    "
                                                >
                                                    <DatePicker
                                                        class="w-full z-[999999]"
                                                        showIcon
                                                        iconDisplay="input"
                                                        :placeholder="
                                                            order.order_option ===
                                                            OrderOptions.DELIVERY
                                                                ? 'Set Delivery Date'
                                                                : 'Set Pick-up Date'
                                                        "
                                                        v-model="selectedActionDates[order.id]"
                                                        :minDate="new Date()"
                                                        @update:model-value="
                                                            (val) => {
                                                                if (val instanceof Date) {
                                                                    confirmAction(
                                                                        order.id,
                                                                        val,
                                                                        order.order_option ===
                                                                            OrderOptions.DELIVERY
                                                                            ? OrderStatus.FOR_DELIVERY
                                                                            : OrderStatus.FOR_PICKUP,
                                                                        close,
                                                                    )
                                                                }
                                                            }
                                                        "
                                                    />
                                                </div>

                                                <!-- Show Delivery Receipt Button -->
                                                <button
                                                    v-if="order.delivery_date"
                                                    @click="handleShowDeliveryReceipt(order, close)"
                                                    class="w-full text-sm font-medium px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-600 hover:opacity-75 hover:cursor-pointer transition-colors"
                                                >
                                                    {{
                                                        order.order_option === OrderOptions.DELIVERY
                                                            ? 'Show Delivery Receipt'
                                                            : 'Show Pick-up Receipt'
                                                    }}
                                                </button>

                                                <!-- Status Update Button -->
                                                <div class="w-full">
                                                    <Popover
                                                        v-slot="{ open, close }"
                                                        class="relative z-[999999]"
                                                    >
                                                        <PopoverButton
                                                            @click="
                                                                handleShowStatusFilter(
                                                                    order.order_option,
                                                                    order.status,
                                                                )
                                                            "
                                                            :class="
                                                                open
                                                                    ? 'text-white'
                                                                    : 'text-white/90'
                                                            "
                                                            class="group hover:opacity-75 hover:cursor-pointer items-center rounded-md w-full flex justify-center bg-gray-800 px-3 py-2 text-base font-medium hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-white/75"
                                                        >
                                                            <span>Set Status</span>
                                                            <ChevronDownIcon
                                                                :class="
                                                                    open
                                                                        ? 'text-gray-300'
                                                                        : 'text-gray-300/70'
                                                                "
                                                                class="ml-2 h-5 w-5 transition duration-150 ease-in-out group-hover:text-gray-300/80"
                                                            />
                                                        </PopoverButton>

                                                        <transition
                                                            enter-active-class="transition duration-200 ease-out"
                                                            enter-from-class="translate-y-1 opacity-0"
                                                            enter-to-class="translate-y-0 opacity-100"
                                                            leave-active-class="transition duration-150 ease-in"
                                                            leave-from-class="translate-y-0 opacity-100"
                                                            leave-to-class="translate-y-1 opacity-0"
                                                        >
                                                            <PopoverPanel
                                                                class="absolute z-[9999] mt-2 w-full rounded-lg bg-white dark:bg-gray-800 dark:border dark:border-gray-700 shadow-lg ring-1 ring-black/5"
                                                            >
                                                                <div
                                                                    class="flex flex-col gap-2 bg-white dark:bg-gray-800 p-3"
                                                                >
                                                                    <h1
                                                                        v-for="item in orderStatus.filter(
                                                                            (s) =>
                                                                                !(
                                                                                    // Hide COMPLETED if no delivery date
                                                                                    (
                                                                                        (s.tag ===
                                                                                            OrderStatus.COMPLETED &&
                                                                                            !order.delivery_date) ||
                                                                                        // Hide IN_PROGRESS if already in progress
                                                                                        (s.tag ===
                                                                                            OrderStatus.IN_PROGRESS &&
                                                                                            order.status ===
                                                                                                OrderStatus.IN_PROGRESS)
                                                                                    )
                                                                                ),
                                                                        )"
                                                                        :key="item.name"
                                                                        @click="
                                                                            handleStatusChange(
                                                                                order.id,
                                                                                item.tag,
                                                                                close,
                                                                            )
                                                                        "
                                                                        :class="[
                                                                            'hover:cursor-pointer justify-center flex items-center rounded-lg p-2 transition duration-150 ease-in-out focus:outline-none focus-visible:ring focus-visible:ring-orange-500/50',
                                                                            item.tag ===
                                                                            OrderStatus.COMPLETED
                                                                                ? 'hover:bg-green-600 hover:text-white'
                                                                                : item.tag ===
                                                                                    OrderStatus.IN_PROGRESS
                                                                                  ? 'hover:bg-orange-600 hover:text-white'
                                                                                  : item.tag ===
                                                                                      OrderStatus.CANCELLED
                                                                                    ? 'hover:bg-red-600 hover:text-white'
                                                                                    : 'hover:bg-gray-800 hover:text-white',
                                                                        ]"
                                                                    >
                                                                        <p
                                                                            class="text-sm font-medium dark:text-white"
                                                                        >
                                                                            {{
                                                                                item.name.toUpperCase()
                                                                            }}
                                                                        </p>
                                                                    </h1>
                                                                </div>
                                                            </PopoverPanel>
                                                        </transition>
                                                    </Popover>
                                                </div>
                                            </div>
                                        </PopoverPanel>
                                    </teleport>
                                </transition>
                            </Popover>
                        </div>

                        <!-- CANCEL ORDER CONFIRMATION -->
                        <CancelOrderConfirmationDialog
                            v-if="isOrderCancelConfirmed"
                            @confirmCancel="proceedCancelOrder"
                            @close="closeCancelOrderConfirmation"
                        />

                        <!-- ORDER STATUS OF USER -->
                        <!-- <div v-else>
                            <h1
                                class="text-center"
                                :class="{
                                    'text-green-800': order.status === 'completed',
                                    'text-red-800': order.status === 'cancelled',
                                }"
                            >
                                {{
                                    order.status === 'completed'
                                        ? 'Order Approved'
                                        : order.status === 'cancelled'
                                          ? 'Order Cancelled'
                                          : 'Order Status Unknown'
                                }}
                            </h1>
                        </div> -->
                    </td>
                </tr>

                <!-- Empty state message -->
                <tr v-if="orders?.data && orders?.data.length === 0 && !isLoading">
                    <td colspan="12" class="px-6 py-4 text-center">No orders found.</td>
                </tr>

                <PaginationControls
                    :currentPage="orders.current_page"
                    :lastPage="orders.last_page"
                    @changePage="currentPage = $event"
                />
            </tbody>
        </table>
    </div>

    <Loader v-if="isLoading && isOrderLoading" msg="Loading Orders..." />

    <Loader v-if="isStatusUpdating" msg="Updating Order Status..." />

    <Loader v-if="setDateMutation.isPending.value" msg="Updating Delivery / Pick-Up Date..." />

    <!-- DESIGN IMAGE PREVIEW DIALOG -->
    <Teleport to="body">
        <div
            v-if="showDesignPreviewDialog"
            class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/60 backdrop-blur-[2px]"
            @click.self="showDesignPreviewDialog = false"
        >
            <div
                class="relative max-w-3xl w-full mx-4 bg-white dark:bg-gray-900 rounded-2xl shadow-2xl overflow-hidden"
            >
                <!-- Header -->
                <div
                    class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-700"
                >
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                        Design Preview
                    </h2>
                    <button
                        @click="showDesignPreviewDialog = false"
                        class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full p-1.5 transition-colors duration-150 cursor-pointer"
                        aria-label="Close"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="w-5 h-5"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </button>
                </div>
                <!-- Image -->
                <div
                    class="flex items-center justify-center bg-gray-50 dark:bg-gray-800 p-8 min-h-[75vh]"
                >
                    <img
                        :src="selectedDesignImageUrl"
                        alt="Design Preview"
                        class="max-h-[82vh] max-w-full object-contain rounded-lg shadow-md"
                    />
                </div>
            </div>
        </div>
    </Teleport>

    <!-- UPLOADED IMAGE MODAL -->
    <UploadedImagesModal
        v-if="showUploadedImageModal && selectedDesignID !== undefined"
        :selectedDesignID="selectedDesignID"
        :isAdmin="isAdmin"
        @close="showUploadedImageModal = false"
    />

    <!-- QUANTITY PER SIZE MODAL -->
    <QuantityPerSizeModal
        v-if="showSizeBreakdownModal && selectedOrderSizes"
        :selectedOrderSizes="selectedOrderSizes"
        @close="showSizeBreakdownModal = false"
    />

    <OrderPaymentsModal
        v-if="showOrderPaymentsModal && selectedOrderData"
        :orders="selectedOrderData"
        :isAdmin="isAdmin"
        @close="handleCloseOrderPaymentsModal"
        @status-change="(orderId, status) => handleStatusChange(orderId, status, () => {})"
    />

    <!-- DELIVERY RECEIPT MODAL -->
    <DeliveryReceiptModal
        v-if="selectedReceiptOrder"
        :order="selectedReceiptOrder"
        :isOpen="showDeliveryReceiptModal"
        @close="handleCloseDeliveryReceiptModal"
    />

    <!-- SET DELIVERY DATE CONFIRMATION MODAL -->
    <div
        v-if="showConfirmModal"
        class="fixed inset-0 flex items-center justify-center bg-black/50 z-[9999999]"
    >
        <div
            class="bg-white dark:bg-gray-900 dark:border dark:border-gray-700 p-6 rounded-xl shadow-xl w-[90%] max-w-md"
        >
            <h2 class="text-md font-semibold text-gray-900 dark:text-white">Confirm Schedule</h2>
            <p class="text-md mt-2 text-gray-700 dark:text-gray-300">
                Are you sure you want to set this
                <span class="font-semibold">
                    {{
                        dateChangeActionData?.status === OrderStatus.FOR_DELIVERY
                            ? 'delivery'
                            : 'pick-up'
                    }}
                </span>
                date to
                <strong>{{ dateChangeActionData?.date?.toLocaleDateString() }}</strong>
                ?
            </p>

            <div class="mt-4 flex justify-end gap-2">
                <button
                    class="text-sm px-4 py-2 rounded-lg bg-gray-200 dark:bg-gray-700 dark:text-white hover:opacity-75 hover:cursor-pointer"
                    @click="showConfirmModal = false"
                >
                    Cancel
                </button>
                <button
                    class="text-sm px-4 py-2 rounded-lg bg-gray-900 text-white hover:opacity-75 hover:cursor-pointer"
                    @click="proceedAction"
                >
                    Yes, confirm
                </button>
            </div>
        </div>
    </div>
</template>
