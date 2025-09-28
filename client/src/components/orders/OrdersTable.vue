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
   import { onBeforeMount, onMounted, reactive, ref, watch } from 'vue'
   import { updateOrderStatus } from '@/api/put/orders'
   import UploadedImagesModal from '../designs/UploadedImagesModal.vue'
   import QuantityPerSizeModal from '../designs/QuantityPerSizeModal.vue'
   import { apiService } from '@/api/axios'

   import { useToast } from 'primevue'
   import Toast from 'primevue/toast'
   import StatusBadge from './StatusBadge.vue'
   import PaginationControls from '../PaginationControls.vue'
   import type { PaginatedResponse } from '@/types/pagination'

   const { isAdmin } = useAuthorization()
   const isStatusUpdating = ref<boolean>(false)
   const isOrderLoading = ref<boolean>(true)

   // PAGINATION REFS
   const pagination = reactive({
      page: 1,
      limit: 5,
   })

   const {
      data: orders,
      error,
      refetch,
      isLoading,
   } = useQuery({
      queryKey: ['orders', pagination.page, pagination.limit],
      queryFn: async () => {
         const respData = await apiService.get<PaginatedResponse<Orders>>(`/api/get/orders?page=${pagination.page}&limit=${pagination.limit}`)
         return respData
      },
      enabled: true,
   })

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

   const handleActionDateChange = (orderId: number, date: Date, status: string, close: () => void) => {
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
      { name: 'In Progress', tag: OrderStatus.IN_PROGRESS },
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
   })

   onBeforeMount(() => {
      const container = document.querySelector('.order-table')
      if (container) {
         container.removeEventListener('scroll', onTableScroll)
      }
   })

   const showConfirmModal = ref(false)
   const pendingAction = ref(null) // store the action temporarily

   function confirmAction(orderId, date, status, close) {
      pendingAction.value = { orderId, date, status, close }
      showConfirmModal.value = true
   }

   function proceedAction() {
      if (pendingAction.value) {
         const { orderId, date, status, close } = pendingAction.value
         handleActionDateChange(orderId, date, status, close)
      }
      showConfirmModal.value = false
      pendingAction.value = null
   }
</script>

<template>
   <div class="order-table relative h-[75%] overflow-y-auto shadow-md sm:rounded-lg">
      <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
         <thead class="text-xs text-white uppercase bg-gray-900 dark:bg-gray-700 dark:text-gray-400">
            <tr>
               <!-- <th scope="col" class="px-16 py-3">
                        <span>Order ID</span>
                    </th> -->
               <th scope="col" class="px-6 py-3">Order No.</th>

               <th scope="col" class="px-16 py-3">
                  <span>Design</span>
               </th>

               <!-- <th scope="col" class="px-6 py-3">Name</th>

                    <th scope="col" class="px-6 py-3">Phone Number</th>
                    <th scope="col" class="px-6 py-3">Address</th>

                    <th scope="col" class="px-6 py-3">Quantity</th>

                    <th scope="col" class="px-6 py-3">Color</th> -->

               <!-- <th scope="col" class="px-6 py-3">Total Price</th> -->
               <th scope="col" class="px-6 py-3">Option</th>

               <th scope="col" class="px-6 py-3">Status</th>

               <th scope="col" class="px-6 py-3">Delivery / Pick-Up Date</th>

               <th v-if="isAdmin" scope="col" class="px-6 py-3">Actions</th>
            </tr>
         </thead>
         <tbody v-if="orders">
            <tr
               v-for="order in orders.data"
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
                  <img :src="order.temp_url" class="w-24 h-24 object-cover rounded-md border" alt="Design Image" />
               </td>

               <!-- <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                        {{ order.user.name }}
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
                    </td> -->

               <!-- <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                        {{ formatCurrency(order.total_price.toString()) }}
                    </td> -->

               <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                  {{ order.order_option.toUpperCase() }}
               </td>

               <td class="px-6 py-4">
                  <StatusBadge :status="order.status" />
               </td>

               <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                  {{ order.delivery_date ? formatDate(order.delivery_date) : 'N/A' }}
               </td>

               <!-- UPDATE STATUS ACTION BUTTON -->
               <td v-if="isAdmin" class="pr-5 py-4 font-semibold text-gray-900 dark:text-white">
                  <div v-if="order.status !== OrderStatus.COMPLETED && order.status !== OrderStatus.CANCELLED" class="w-full max-w-sm px-4">
                     <Popover v-slot="{ open, close }">
                        <div v-if="!(popoverClose = close)"></div>

                        <!-- Ellipsis Button -->
                        <PopoverButton class="focus:outline-none" @click="setPopoverPosition($event)">
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
                                 class="absolute z-[999] w-64 rounded-lg bg-white shadow-lg ring-1 ring-black/5 p-3"
                                 :style="{
                                    top: `${popoverPosition.top}px`,
                                    left: `${popoverPosition.left}px`,
                                 }"
                                 ref="popoverRef"
                              >
                                 <!-- Actions -->
                                 <div class="flex flex-col gap-4">
                                    <!-- Chat Button -->
                                    <fwb-button v-if="order.status !== OrderStatus.COMPLETED && order.status !== OrderStatus.CANCELLED" color="light">
                                       <router-link class="w-full" to="/admin/message">Chat to Customer</router-link>
                                    </fwb-button>

                                    <!-- Payment Screenshot -->
                                    <fwb-button v-if="order.status !== OrderStatus.COMPLETED && order.status !== OrderStatus.CANCELLED" color="light">
                                       Payment Screenshot
                                    </fwb-button>

                                    <!-- Delivery or Pickup Date -->
                                    <div v-if="!order.delivery_date">
                                       <DatePicker
                                          class="w-full z-[999999]"
                                          showIcon
                                          iconDisplay="input"
                                          :placeholder="order.order_option === OrderOptions.DELIVERY ? 'Set Delivery Date' : 'Set Pick-up Date'"
                                          v-model="selectedActionDates[order.id]"
                                          :minDate="new Date()"
                                          @update:model-value="
                                             (val) => {
                                                if (val instanceof Date) {
                                                   confirmAction(
                                                      order.id,
                                                      val,
                                                      order.order_option === OrderOptions.DELIVERY
                                                         ? OrderStatus.FOR_DELIVERY
                                                         : OrderStatus.FOR_PICKUP,
                                                      close,
                                                   )
                                                }
                                             }
                                          "
                                       />
                                    </div>

                                    <!-- Status Update Button -->
                                    <div class="w-full">
                                       <Popover v-slot="{ open, close }" class="relative z-[999999]">
                                          <PopoverButton
                                             @click="handleShowStatusFilter(order.order_option, order.status)"
                                             :class="open ? 'text-white' : 'text-white/90'"
                                             class="group hover:opacity-75 hover:cursor-pointer items-center rounded-md w-full flex justify-center bg-gray-800 px-3 py-2 text-base font-medium hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-white/75"
                                          >
                                             <span>Set Status</span>
                                             <ChevronDownIcon
                                                :class="open ? 'text-gray-300' : 'text-gray-300/70'"
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
                                             <PopoverPanel class="absolute z-[9999] mt-2 w-full rounded-lg bg-white shadow-lg ring-1 ring-black/5">
                                                <div class="flex flex-col gap-2 bg-white p-3">
                                                   <h1
                                                      v-for="item in orderStatus.filter(
                                                         (s) =>
                                                            !(
                                                               // Hide COMPLETED if no delivery date
                                                               (
                                                                  (s.tag === OrderStatus.COMPLETED && !order.delivery_date) ||

                                                                  // Hide IN_PROGRESS if already in progress
                                                                  (s.tag === OrderStatus.IN_PROGRESS && order.status === OrderStatus.IN_PROGRESS)
                                                               )
                                                            ),
                                                      )"
                                                      :key="item.name"
                                                      @click="handleStatusChange(order.id, item.tag, close)"
                                                      :class="[
                                                         'hover:cursor-pointer justify-center flex items-center rounded-lg p-2 transition duration-150 ease-in-out focus:outline-none focus-visible:ring focus-visible:ring-orange-500/50',
                                                         item.tag === OrderStatus.COMPLETED
                                                            ? 'hover:bg-green-600 hover:text-white'
                                                            : item.tag === OrderStatus.IN_PROGRESS
                                                              ? 'hover:bg-orange-600 hover:text-white'
                                                              : item.tag === OrderStatus.CANCELLED
                                                                ? 'hover:bg-red-600 hover:text-white'
                                                                : 'hover:bg-gray-800 hover:text-white',
                                                      ]"
                                                   >
                                                      <p class="text-sm font-medium">
                                                         {{ item.name.toUpperCase() }}
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

            <PaginationControls :currentPage="orders.current_page" :lastPage="orders.last_page" @changePage="pagination.page = $event" />
         </tbody>
      </table>
   </div>

   <Loader v-if="isLoading && isOrderLoading" msg="Loading Orders..." />

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

   <!-- SET DELIVERY DATE CONFIRMATION MODAL -->
   <div v-if="showConfirmModal" class="fixed inset-0 flex items-center justify-center bg-black/50 z-[9999999]">
      <div class="bg-white p-6 rounded-xl shadow-xl w-[90%] max-w-md">
         <h2 class="text-md font-semibold text-gray-900">Confirm Schedule</h2>
         <p class="text-md mt-2 text-gray-700">
            Are you sure you want to set this
            <span class="font-semibold">
               {{ pendingAction?.status === OrderStatus.FOR_DELIVERY ? 'delivery' : 'pick-up' }}
            </span>
            date to
            <strong>{{ pendingAction?.date?.toLocaleDateString() }}</strong>
            ?
         </p>

         <div class="mt-4 flex justify-end gap-2">
            <button class="text-sm px-4 py-2 rounded-lg bg-gray-200 hover:opacity-75 hover:cursor-pointer" @click="showConfirmModal = false">
               Cancel
            </button>
            <button class="text-sm px-4 py-2 rounded-lg bg-gray-900 text-white hover:opacity-75 hover:cursor-pointer" @click="proceedAction">
               Yes, confirm
            </button>
         </div>
      </div>
   </div>
</template>
