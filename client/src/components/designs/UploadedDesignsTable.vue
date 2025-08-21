<script lang="ts" setup>
    import { getAllUploadedDesigns } from '@/api/get/designs'
    import { formatCurrency, formatDate } from '@/helper/designs'
    import { useAuthStore } from '@/stores/user'
    import { DesignStatus, type UploadedDesign } from '@/types/design'
    import { onMounted, reactive, ref, watch } from 'vue'
    import Loader from '../Loader.vue'
    import { useQuery } from '@tanstack/vue-query'
    import PaymentModal from './PaymentModal.vue'
    import type { DesignAttribute, ProceedPaymentData } from '@/types/payment'

    import { initFlowbite } from 'flowbite'
    import { InformationCircleIcon } from '@heroicons/vue/20/solid'
    import { OrderTypes } from '@/types/order'
    import { useAuthorization } from '@/composables/useAuthorization'

    import Toast from 'primevue/toast'
    // import { useToast } from 'primevue/usetoast'
import UploadedImagesModal from './UploadedImagesModal.vue'

    const emit = defineEmits<{
        (e: 'openUpdateModal', design: UploadedDesign): void
    }>()

    const handleOpenUpdateModal = (design: UploadedDesign) => {
        emit('openUpdateModal', design)
    }

    // MODALS TOGGLER
    const modals = reactive({
        show_attach_materials_modal: false,
        show_payment_modal: false,
        show_uploaded_images: false,
    })

    // SELECTED DESIGN ID TO BE ATTACHED SOME MATERIALS IN IT
    const selectedDesignID = ref<number>()

    const authStore = useAuthStore()

    // THIS IS FOR THE CUSTOMER SIDE SELECTING SHIRT RELATED ATTRIBUTES
    const designAttributeData = ref<DesignAttribute>({
        design_id: -1,
        color: -1,
        size: -1,
        quantity: 1,
    })

    const paymentData = ref<ProceedPaymentData>({
        order_option: '',
        price: -1,
        name: '',
    })

    // AUTHOTIZATION
    const { isAdmin, isUser } = useAuthorization()
    const { data, isLoading, refetch, isError } = useQuery({
        queryKey: ['uploaded-designs'],
        queryFn: getAllUploadedDesigns,
        enabled: true,
    })

    watch(
        () => authStore.isAuthenticated,
        (isAuthenticated) => {
            if (isAuthenticated) {
                refetch()
            }
        },
    )

    console.log('uploaded designs data: ', data.value)

    const handleOpenPaymentModal = (design: UploadedDesign) => {


        if (design) {
            modals.show_payment_modal = true

            paymentData.value.name = `Uploaded Design ${design.id}`
            paymentData.value.price = design.price
            paymentData.value.order_option = design.order_option

            designAttributeData.value.design_id = design.id

            designAttributeData.value.quantity = design.quantity
            designAttributeData.value.color = design.color.id
            designAttributeData.value.size = design.size.id
        }
    }

    const handleOpenAttachMaterialsModal = (designID: number) => {
        selectedDesignID.value = designID
        modals.show_attach_materials_modal = true
    }

    const handleOpenUploadedImagesModal = (designID: number) => {
        selectedDesignID.value = designID
        modals.show_uploaded_images = true
    }

    onMounted(() => {
        initFlowbite()
    })
</script>

<template>
    <div class="card mt-5">
        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
            <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                <thead
                    class="text-xs text-white uppercase bg-gray-900 dark:bg-gray-700 dark:text-gray-400"
                >
                    <tr>
                        <th scope="col" class="px-16 py-3">
                            <span>Image</span>
                        </th>

                        <th scope="col" class="px-6 py-3">Original Price</th>

                        <th scope="col" class="px-6 py-3">Quantity</th>

                        <th scope="col" class="px-6 py-3">Total Price</th>

                        <th scope="col" class="px-6 py-3">Color</th>
                        <th scope="col" class="px-6 py-3">Size</th>
                        <th scope="col" class="px-6 py-3 flex items-center">
                            Pricing Status

                            <button
                                data-tooltip-target="tooltip-default"
                                type="button"
                                class="focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-1 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800"
                            >
                                <InformationCircleIcon class="size-5" />
                            </button>

                            <div
                                id="tooltip-default"
                                role="tooltip"
                                class="normal-case absolute z-10 px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-900 rounded-lg shadow-xs opacity-0 tooltip dark:bg-gray-700"
                            >
                                Indicates the price state of your uploaded design: (PENDING,
                                ACKNOWLEDGE, TAGGED)
                                <div class="tooltip-arrow" data-popper-arrow></div>
                            </div>
                        </th>
                        <th scope="col" class="px-6 py-3">Upload Date</th>

                        <th scope="col" class="px-6 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="design in data"
                        :key="design.id"
                        class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 border-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600"
                    >
                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                            <button
                                @click="handleOpenUploadedImagesModal(design.id)"
                                class="text-gray-900 rounded-md p-2 hover:opacity-75 hover:cursor-pointer hover:underline font-medium"
                            >
                                Show Uploaded Images
                            </button>
                        </td>

                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                            {{ formatCurrency(design.price.toString()) }}
                        </td>

                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                            {{ design.quantity }}
                        </td>

                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                            {{ formatCurrency((design.quantity * design.price).toString()) }}
                        </td>

                        <td class="px-6 py-4 text-gray-900 dark:text-white">
                            {{ design.color.name }}
                        </td>

                        <td class="px-6 py-4 text-gray-900 dark:text-white">
                            {{ design.size.name }}
                        </td>

                        <td class="px-6 py-4">
                            <span
                                :class="{
                                    'bg-yellow-100 text-yellow-800 px-2 py-1 rounded':
                                        design.status == DesignStatus.PENDING,
                                    'bg-blue-100 text-blue-800 px-2 py-1 rounded':
                                        design.status == DesignStatus.ACKNOWLEDGE,
                                    'bg-green-100 text-green-800 px-2 py-1 rounded':
                                        design.status == DesignStatus.TAGGED,
                                }"
                            >
                                {{ design.status.toUpperCase() }}
                            </span>
                        </td>

                        <td class="px-6 py-4 text-gray-900 dark:text-white">
                            {{ formatDate(design.created_at) }}
                        </td>

                        <td class="px-6 py-4">
                            <!-- ADMIN ACCESSED BUTTONS -->
                            <div v-if="isAdmin" class="flex items-center">
                                <button
                                    @click="handleOpenAttachMaterialsModal(design.id)"
                                    class="text-gray-900 rounded-md p-2 hover:opacity-75 hover:cursor-pointer hover:underline font-medium"
                                >
                                    Attach Materials
                                </button>

                                <button
                                    @click="handleOpenUpdateModal(design)"
                                    class="text-blue-600 rounded-md p-2 hover:opacity-75 hover:cursor-pointer hover:underline font-medium"
                                >
                                    Update
                                </button>
                            </div>

                            <!-- CUSTOMER ACCESSED BUTTONS -->

                            <button
                                v-else-if="isUser && design.status == DesignStatus.TAGGED"
                                @click="handleOpenPaymentModal(design)"
                                class="text-blue-600 rounded-md p-2 hover:opacity-75 hover:underline hover:cursor-pointer"
                            >
                                Place Order
                            </button>

                            <h1 v-else>Once design is price tagged you can proceed to order.</h1>
                        </td>
                    </tr>

                    <!-- Empty state message -->
                    <tr v-if="data && data.length === 0 && !isLoading">
                        <td colspan="12" class="px-6 py-4 text-center">No designs found.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- ATTACH MATERIALS MODAL -->
        <!-- <AttachMaterialsModal
            v-if="modals.show_attach_materials_modal && selectedDesignID"
            :selectedDesignID="selectedDesignID"
            :designType="OrderTypes.UPLOADED"
            @close="modals.show_attach_materials_modal = false"
        /> -->

        <!-- UPLOADED IMAGE MODAL -->
        <UploadedImagesModal
            v-if="modals.show_uploaded_images && selectedDesignID"
            :selectedDesignID="selectedDesignID"
            :isAdmin="isAdmin"
            @close="modals.show_uploaded_images = false"
        />

        <!-- LOADER -->
        <div v-if="isLoading && !isError">
            <Loader msg="Loading Uploaded Designs..." />
        </div>

        <!-- PAYMENT MODAL -->
        <PaymentModal
            v-if="designAttributeData && paymentData && modals.show_payment_modal"
            :orderType="OrderTypes.UPLOADED"
            :paymentData="paymentData"
            :attributeData="designAttributeData"
            :isOpen="modals.show_payment_modal"
            @close="modals.show_payment_modal = false"
        />

        <Toast />
    </div>
</template>
