<script lang="ts" setup>
    import { Popover, PopoverButton, PopoverPanel } from '@headlessui/vue'
    import { FwbButton } from 'flowbite-vue'

    import { ChevronDownIcon, EllipsisVerticalIcon } from '@heroicons/vue/20/solid'

    import DatePicker from 'primevue/datepicker'

    import { getAllOrders } from '@/api/get/orders'
    import { useAuthorization } from '@/composables/useAuthorization'
    import { formatCurrency, formatDate } from '@/helper/designs'
    import { OrderStatus, type UpdateStatusType } from '@/types/order'
    import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
    import Loader from '../Loader.vue'
    import { ref, watch } from 'vue'
    import { updateOrderStatus } from '@/api/put/orders'
    import UploadedImagesModal from '../designs/UploadedImagesModal.vue'
    import QuantityPerSizeModal from '../designs/QuantityPerSizeModal.vue'
    import { apiService } from '@/api/axios'

    import { useToast } from 'primevue'
    import Toast from 'primevue/toast'

    const { isAdmin } = useAuthorization()
    const isStatusUpdating = ref<boolean>(false)
    const isOrderLoading = ref<boolean>(true)

    const orderQuery = useQuery({
        queryKey: ['orders'],
        queryFn: getAllOrders,
        enabled: true,
    })

    watch(
        () => orderQuery.error,
        (err) => {
            if (err) {
                isOrderLoading.value = false
            }
        },
    )

    const queryClient = useQueryClient()
    const toast = useToast()

    // PREFERRED ORDER OPTION FOR SETTING STATUS FILTERING
    const selectedOrderOption = ref<string>('')

    // SELECTED DESIGN TO RENDER
    const selectedDesignID = ref<number>()
    const showUploadedImageModal = ref<boolean>(false)

    // ORDER STATUS FOR COMPLETED ORDER FILTERING
    const selectedOrderStatus = ref<string>('')

    const showSizeBreakdownModal = ref(false)
    const selectedOrderSizes = ref([]) // Holds sizes for modal

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

    const handleActionDateChange = (orderId: number, date: Date, close: () => void) => {
        console.log('Selected Date:', date)
        console.log('Order ID:', orderId)

        if (date) {
            const formattedDate = date.toLocaleDateString('en-CA') // 'YYYY-MM-DD'

            const formData = new FormData()
            formData.append('order_id', String(orderId))
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

    const status = ref([
        { name: 'Approved', tag: 'completed' },
        { name: 'Cancelled', tag: 'cancelled' },
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
                const refetchResult = await orderQuery.refetch()

                if (refetchResult.status === 'success') {
                    isStatusUpdating.value = false
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

    const handleStatusChange = (order_id: number, status: string, close: () => void) => {
        console.log('Selected order_id:', order_id)
        console.log('Selected status:', status)

        const statusData: UpdateStatusType = {
            order_id,
            status,
        }

        mutation.mutate(statusData)
        close()
    }

    // const handleOpenUploadedImagesModal = (designID: number) => {
    //     selectedDesignID.value = designID
    //     showUploadedImageModal.value = true
    // }

    // @ts-expect-error any
    const handleShowSizes = (sizes) => {
        selectedOrderSizes.value = sizes
        showSizeBreakdownModal.value = true
    }
</script>

<template>
    <div class="relative h-[75%] overflow-y-auto shadow-md sm:rounded-lg mt-5">
        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
            <thead
                class="text-xs text-white uppercase bg-gray-900 dark:bg-gray-700 dark:text-gray-400"
            >
                <tr>
                    <!-- <th scope="col" class="px-16 py-3">
                        <span>Order ID</span>
                    </th> -->
                    <th scope="col" class="px-6 py-3">Order No.</th>

                    <th scope="col" class="px-16 py-3">
                        <span>Image Design</span>
                    </th>

                    <th scope="col" class="px-6 py-3">Name</th>

                    <th scope="col" class="px-6 py-3">Phone Number</th>
                    <th scope="col" class="px-6 py-3">Address</th>

                    <th scope="col" class="px-6 py-3">Quantity</th>

                    <th scope="col" class="px-6 py-3">Color</th>

                    <!-- <th scope="col" class="px-6 py-3">Order Option</th> -->

                    <th scope="col" class="px-6 py-3">Total Price</th>

                    <th scope="col" class="px-6 py-3 flex items-center">Order Status</th>

                    <th scope="col" class="px-6 py-3">Delivery / Pick-Up Date</th>

                    <th v-if="isAdmin" scope="col" class="px-6 py-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="order in orderQuery.data.value"
                    :key="order.id"
                    class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 border-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600"
                >
                    <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                        {{ order.order_number }}
                    </td>

                    <!-- <td class="p-4">
                        <button
                            @click="handleOpenUploadedImagesModal(order.design_id)"
                            class="text-gray-900 rounded-md p-2 hover:opacity-75 hover:cursor-pointer hover:underline font-medium"
                        >
                            Show Uploaded Images
                        </button>
                    </td> -->

                    <td class="p-4">
                        <img
                            :src="order.temp_url"
                            class="w-24 h-24 object-cover rounded-md border"
                            alt="Design Image"
                        />
                    </td>

                    <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                        {{ order.user?.name }}
                    </td>

                    <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                        {{ order.phone_number }}
                    </td>

                    <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                        {{ order.address }}
                    </td>

                    <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                        <div v-if="order.solo_quantity !== null">
                            {{ order.solo_quantity }}
                        </div>
                        <div v-else>
                            <button
                                @click="handleShowSizes(order.sizes)"
                                class="text-gray-900 hover:underline hover:cursor-pointer"
                            >
                                View Quantity per Size
                            </button>
                        </div>
                    </td>

                    <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                        {{ order.color }}
                    </td>

                    <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                        {{ formatCurrency(order.total_price.toString()) }}
                    </td>

                    <td class="px-6 py-4">
                        <span
                            :class="{
                                'bg-yellow-100 text-yellow-800 px-2 py-1 rounded':
                                    order.status === OrderStatus.PENDING,
                                'bg-red-100 text-red-800 px-2 py-1 rounded':
                                    order.status === OrderStatus.CANCELLED,
                                'bg-green-100 text-green-800 px-2 py-1 rounded':
                                    order.status === OrderStatus.APPROVED,
                            }"
                        >
                            {{
                                order.status === 'completed'
                                    ? 'APPROVED'
                                    : order.status.toUpperCase()
                            }}
                        </span>
                    </td>

                    <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                        {{ order.delivery_date ? formatDate(order.delivery_date) : 'N/A' }}
                    </td>

                    <!-- <td
                        v-if="!isAdmin"
                        class="relative flex items-center px-3 py-12 font-semibold text-gray-900 dark:text-white"
                    >
                        <router-link to="/message">
                            <fwb-button color="light">Chat to Seller</fwb-button>
                        </router-link>
                    </td> -->

                    <!-- UPDATE STATUS ACTION BUTTON -->
                    <td
                        v-if="isAdmin"
                        class="relative pr-5 py-4 font-semibold text-gray-900 dark:text-white"
                    >
                        <div
                            v-if="
                                order.status !== OrderStatus.APPROVED &&
                                order.status !== OrderStatus.CANCELLED
                            "
                            class="w-full max-w-sm px-4"
                        >
                            <Popover v-slot="{ close }" class="relative z-10">
                                <PopoverButton>
                                    <span>
                                        <EllipsisVerticalIcon class="size-6" />
                                    </span>
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
                                        class="absolute flex flex-col gap-5 z-[9999] left-[-20rem] mt-2 w-[30vw] p-3 rounded-lg bg-white shadow-lg ring-1 ring-black/5"
                                    >
                                        <!-- CHAT TO CUSTOMER ACTION -->

                                        <fwb-button
                                            v-if="
                                                order.status !== OrderStatus.APPROVED &&
                                                order.status !== OrderStatus.CANCELLED
                                            "
                                            color="light"
                                        >
                                            <router-link class="w-full" to="/admin/message">
                                                Chat to Customer
                                            </router-link>
                                        </fwb-button>

                                        <fwb-button
                                            v-if="
                                                order.status !== OrderStatus.APPROVED &&
                                                order.status !== OrderStatus.CANCELLED
                                            "
                                            color="light"
                                        >
                                            Payment Screenshot
                                        </fwb-button>

                                        <!-- PICK-UP OR DELIVERY DATE -->
                                        <div>
                                            <DatePicker
                                                class="w-full z-[9999]"
                                                showIcon
                                                iconDisplay="input"
                                                placeholder="Set Delivery / Pick-up Date"
                                                v-model="selectedActionDates[order.id]"
                                                :minDate="new Date()"
                                                @update:model-value="
                                                    (val) => {
                                                        if (val instanceof Date) {
                                                            handleActionDateChange(
                                                                order.id,
                                                                val,
                                                                close,
                                                            )
                                                        }
                                                    }
                                                "
                                            />
                                        </div>

                                        <!-- STATUS UPDATE BUTTON -->
                                        <div class="w-full">
                                            <Popover v-slot="{ open, close }" class="relative">
                                                <PopoverButton
                                                    @click="
                                                        handleShowStatusFilter(
                                                            order.order_option,
                                                            order.status,
                                                        )
                                                    "
                                                    :class="open ? 'text-white' : 'text-white/90'"
                                                    class="group hover:opacity-75 hover:cursor-pointer items-center rounded-md w-full flex justify-center bg-gray-800 px-3 py-2 text-base font-medium hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-white/75"
                                                >
                                                    <span>Status</span>
                                                    <ChevronDownIcon
                                                        :class="
                                                            open
                                                                ? 'text-gray-300'
                                                                : 'text-gray-300/70'
                                                        "
                                                        class="ml-2 h-5 w-5 transition duration-150 ease-in-out group-hover:text-gray-300/80"
                                                        aria-hidden="true"
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
                                                        class="absolute z-[9999] w-full mt-2rounded-lg bg-white shadow-lg ring-1 ring-black/5"
                                                    >
                                                        <div
                                                            class="overflow-hidden z-[9999] rounded-lg shadow-lg ring-1 ring-black/5"
                                                        >
                                                            <div
                                                                class="flex flex-col gap-2 bg-white p-3"
                                                            >
                                                                <h1
                                                                    v-for="item in status"
                                                                    :key="item.name"
                                                                    @click="
                                                                        handleStatusChange(
                                                                            order.id,
                                                                            item.tag,
                                                                            close,
                                                                        )
                                                                    "
                                                                    class="hover:cursor-pointer justify-center hover:bg-gray-800 hover:text-white flex items-center rounded-lg p-2 transition duration-150 ease-in-out focus:outline-none focus-visible:ring focus-visible:ring-orange-500/50"
                                                                >
                                                                    <div>
                                                                        <p
                                                                            class="text-sm font-medium"
                                                                        >
                                                                            {{
                                                                                item.name.toUpperCase()
                                                                            }}
                                                                        </p>
                                                                    </div>
                                                                </h1>
                                                            </div>
                                                        </div>
                                                    </PopoverPanel>
                                                </transition>
                                            </Popover>
                                        </div>
                                    </PopoverPanel>
                                </transition>
                            </Popover>
                        </div>

                        <div v-else>
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
                        </div>
                    </td>
                </tr>

                <!-- Empty state message -->
                <tr
                    v-if="
                        orderQuery.data.value &&
                        orderQuery.data.value.length === 0 &&
                        !orderQuery.isLoading.value
                    "
                >
                    <td colspan="12" class="px-6 py-4 text-center">No orders found.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <Loader v-if="orderQuery.isLoading.value && isOrderLoading" msg="Loading Orders..." />

    <Loader v-if="isStatusUpdating" msg="Updating Order Status..." />

    <Loader v-if="setDateMutation.isPending.value" msg="Updating Delivery / Pick-Up Date..." />

    <!-- UPLOADED IMAGE MODAL -->
    <UploadedImagesModal
        v-if="showUploadedImageModal && selectedDesignID"
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

    <Toast />
</template>
