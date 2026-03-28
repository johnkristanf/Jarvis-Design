<script lang="ts" setup>
    import { ref, computed, watch, onMounted } from 'vue'
    import {
        Dialog,
        DialogPanel,
        DialogTitle,
        TransitionChild,
        TransitionRoot,
    } from '@headlessui/vue'

    import {
        sublimationProductCategories,
        type BusinessProductDesign,
        type Product,
    } from '@/types/design'

    import type { PropType } from 'vue'
    import { useProductAttributes } from '@/composables/useProductAttribute'
    import { apiService } from '@/api/axios'
    import {
        OrderOptions,
        type ProductDetails,
        type QrCodePaymentData,
        type SelectedOrderOption,
    } from '@/types/order'
    import QrCodePaymentModal from './QrCodePaymentModal.vue'
    import ListSelectBox from '../ListSelectBox.vue'
    import { useMutation, useQueryClient } from '@tanstack/vue-query'

    import { useToast } from 'primevue/usetoast'
    import Toast from 'primevue/toast'
    import Loader from '../Loader.vue'
    import { isValidCssColor } from '@/helper/order'
    import { useFetchAuthenticatedUser } from '@/composables/useFetchAuthenticatedUser'
    // @ts-ignore
    import BusinessDesignModal from './BusinessDesignModal.vue'
    import type { ProductIndexPayment } from '@/types/product'
    import { colorPalette } from '@/utils/color'

    const props = defineProps({
        product: {
            type: Object as PropType<ProductDetails[]>,
            required: true,
        },

        selectedCartIds: {
            type: Array as PropType<number[]>,
            required: true,
        },
    })

    const { sizes, loadingSizes } = useProductAttributes()
    const queryClient = useQueryClient()
    const showAIDesignModal = ref<boolean>(false)

    // Define emits
    const emit = defineEmits(['close', 'openAIDesigns'])
    const handleClose = () => emit('close')
    const { authStore } = useFetchAuthenticatedUser()

    // Convert product to array if it isn't already
    const checkedOutProductsArray = computed(() => {
        return Array.isArray(props.product) ? props.product : [props.product]
    })

    // Reactive data
    const formData = ref({
        color: '',
        phone_number: authStore?.user?.phone_number || '',
        address: authStore?.user?.address || '',
        solo_quantity: null as number | null,
        quantityPerSize: {} as Record<number, number>,
        designType: 'own-design',
        orderOption: null as SelectedOrderOption | null,
        ownDesignFile: null as File | null, // for 'own-design'
        businessDesignURL: '', // for 'business-design'
    })

    const orderOptions = ref([
        { id: 1, name: OrderOptions.DELIVERY, tag: 'DELIVERY' },
        { id: 2, name: OrderOptions.PICK_UP, tag: 'PICK-UP' },
    ])

    const businessProductDesign = ref<BusinessProductDesign[]>([])
    const isLoadingBusinessDesigns = ref<boolean>(false)
    const showQrCodePaymentModal = ref<boolean>(false)
    const paymentAttachmentFile = ref<File | null>(null)
    const toast = useToast()

    const selectedBusinessDesignId = ref<number | null>(null)
    const selectedProductsData = ref<ProductDetails[] | null>(null)

    // HANDLE PAYMENT ATTACHMENT FILE
    const handlePaymentAttachmentFile = (file: File | null) => {
        paymentAttachmentFile.value = file
    }

    // FILTER SELECTED PRODUCT CATEGORY IF NEEDED THE SIZES INPUT (IF MUGS SELECTED THEREFORE NO SIZES IS AVAILABLE)
    // const shouldIncludeSizes = computed(() =>
    //     sublimationProductCategories.includes(props.categoryName ?? ''),
    // )

    const openQrCodePaymentModal = (selectedProducts: ProductDetails[]) => {
        selectedProductsData.value = selectedProducts
        showQrCodePaymentModal.value = true
    }

    // FETCH UPLOADED BUSINESS DESIGNS
    const fetchBusinessDesigns = async (product_id: number) => {
        isLoadingBusinessDesigns.value = true
        const designs = await apiService.get<BusinessProductDesign[]>(
            `/api/get/bussiness_designs/${product_id}`,
        )
        businessProductDesign.value = designs
        isLoadingBusinessDesigns.value = false
    }

    // Calculate total quantity across all products
    const totalQuantityAllProducts = computed(() => {
        return checkedOutProductsArray.value.reduce((sum, product) => {
            return sum + (product.desired_quantity || 0)
        }, 0)
    })

    // Calculate total price across all products
    const totalPriceAllProducts = computed(() => {
        return checkedOutProductsArray.value.reduce((sum, product) => {
            const quantity = product.desired_quantity || 0
            const price = Number(product.unit_price || 0)
            return sum + quantity * price
        }, 0)
    })

    // Calculate total pocket costs across all checked out products
    const totalPocketCostsAllProducts = computed(() => {
        return checkedOutProductsArray.value.reduce((sum, product) => {
            const pocketCost = product.customizations?.pocket_costs
            return sum + (pocketCost ? Number(pocketCost) : 0)
        }, 0)
    })

    // SERIALIZING DATA TO THE FORMDATA
    const prepareFormData = () => {
        const data = new FormData()
        data.append('phone_number', formData.value.phone_number)
        data.append('address', formData.value.address)
        data.append('design_type', formData.value.designType)
        data.append('order_option', formData.value.orderOption?.name as string)
        data.append('selected_cart_ids', JSON.stringify(props.selectedCartIds))

        // Append the single payment attachment for the entire order
        if (paymentAttachmentFile.value) {
            data.append('payment_attachment', paymentAttachmentFile.value)
        }

        // Map through the checked out products, appending each product's details
        checkedOutProductsArray.value.forEach((product, idx) => {
            data.append(`products[${idx}][product_id]`, product.id.toString())
            data.append(`products[${idx}][product_unit_price]`, product.unit_price.toString())
            data.append(`products[${idx}][product_color]`, product.color)

            if (product.fabric_type_id) {
                data.append(`products[${idx}][fabric_type_id]`, product.fabric_type_id.toString())
            }

            if (formData.value.designType === 'own-design' && product.own_design_url) {
                data.append(`products[${idx}][own_design_url]`, product.own_design_url)
            }

            if (product.desired_quantity) {
                data.append(`products[${idx}][total_quantity]`, product.desired_quantity.toString())
            }

            const total_price = (
                Number(product.desired_quantity) * Number(product.unit_price)
            ).toString()

            data.append(`products[${idx}][total_price]`, total_price.toString())
            data.append(`products[${idx}][sizes]`, JSON.stringify(product.size))
            if (product.selected_styles) {
                data.append(
                    `products[${idx}][selected_styles]`,
                    JSON.stringify(product.selected_styles),
                )
            }
            if (product.customizations) {
                data.append(
                    `products[${idx}][customizations]`,
                    JSON.stringify(product.customizations),
                )
            }
        })

        return data
    }

    // TOTAL QUANTITY FOR MULTIPLE SIZES
    const totalQuantityForMultiSizes = computed(() =>
        Object.values(formData.value.quantityPerSize).reduce((acc, qty) => acc + (qty || 0), 0),
    )

    // TOTAL PRICE FOR MULTI SIZES
    const totalPriceForMultiSizes = computed(
        () =>
            totalQuantityForMultiSizes.value *
            Number(checkedOutProductsArray.value[0]?.unit_price || 0),
    )

    // PLACE ORDER MUTATION
    const mutation = useMutation({
        mutationFn: async (formData: FormData) => {
            const respData = await apiService.post('/api/place/order', formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            })
            return respData
        },
        onSuccess: (response) => {
            queryClient.invalidateQueries({ queryKey: ['order_notifications'] })
            queryClient.invalidateQueries({ queryKey: ['cart_items'] })
            queryClient.invalidateQueries({ queryKey: ['cart_count'] })

            toast.add({
                severity: 'success',
                summary: 'Order Place Successfully!',
                life: 1000,
            })

            setTimeout(() => {
                handleClose()
            }, 1500)
        },
        onError: (err) => {
            console.error('Place order error', err)

            // @ts-expect-error custom payload
            if (err.statusCode === 401) {
                toast.add({
                    severity: 'error',
                    summary: 'Please login your account to proceed the order.',
                    life: 3000,
                })

                return
            }

            if (err.message == 'Not enough material in stock.') {
                toast.add({
                    severity: 'error',
                    summary: err.message,
                    life: 3000,
                })

                return
            }

            if (err.message == 'Network Error') {
                toast.add({
                    severity: 'error',
                    summary: 'Check your internet connection and try again',
                    life: 3000,
                })

                return
            }

            toast.add({
                severity: 'error',
                summary: 'Placing order error, please try again',
                life: 3000,
            })
        },
    })

    // FORM VALIDATION
    const isFormInvalid = computed(() => {
        if (!formData.value.phone_number) return true
        if (!formData.value.address.trim()) return true
        if (!formData.value.orderOption) return true
        return false
    })

    // SUBMIT ORDER HANDLER
    const handlePlaceOrder = async () => {
        showQrCodePaymentModal.value = false
        const formData = prepareFormData()
        for (const pair of formData.entries()) {
            console.log(pair[0] + ':', pair[1])
        }
        mutation.mutate(formData)
    }

    // WATCHER FOR BUSINESS DESIGN TRIGGER FETCHING
    watch(
        () => formData.value.designType,
        (newVal) => {
            if (newVal === 'business-design') {
                fetchBusinessDesigns(checkedOutProductsArray.value[0]?.id)
            }
        },
    )

    // COLOR SETTING

    const selectedOption = ref('')

    // Watch dropdown changes
    watch(selectedOption, (newValue) => {
        if (newValue !== 'custom') {
            formData.value.color = newValue // Sync dropdown value directly
        } else {
            formData.value.color = '' // Reset if custom selected
        }
    })
</script>

<template>
    <TransitionRoot appear :show="true">
        <Dialog as="div" static @close="() => {}" class="relative z-[999]">
            <div
                class="fixed inset-0 overflow-y-auto bg-gray-900/80 dark:bg-black/80 transition-opacity"
            >
                <div
                    class="flex flex-col lg:flex-row items-start lg:items-center justify-center p-4 text-center gap-4 lg:gap-8 min-h-screen"
                >
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
                            class="w-full max-w-[1000px] max-h-[calc(100vh-16rem)] md:max-h-[calc(100vh-12rem)] transform overflow-y-auto rounded-2xl bg-white dark:bg-gray-900 p-4 md:p-6 text-left align-middle shadow-xl transition-all"
                        >
                            <DialogTitle
                                as="h1"
                                class="text-2xl text-gray-900 dark:text-gray-100 mb-6"
                            >
                                Product Order Details
                            </DialogTitle>

                            <div class="space-y-7">
                                <!-- Products List Section -->
                                <div
                                    class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700"
                                >
                                    <div
                                        class="flex flex-col sm:flex-row justify-between sm:items-center gap-2 mb-3"
                                    >
                                        <h2
                                            class="text-lg font-semibold text-gray-800 dark:text-gray-100"
                                        >
                                            Selected Products
                                        </h2>
                                        <div class="text-left sm:text-right">
                                            <div
                                                class="flex items-baseline gap-2 justify-start sm:justify-end"
                                            >
                                                <span
                                                    class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider"
                                                >
                                                    Total:
                                                </span>
                                                <span
                                                    class="text-2xl font-bold text-blue-600 dark:text-blue-400"
                                                >
                                                    ₱{{ totalPriceAllProducts.toFixed(2) }}
                                                </span>
                                                <span
                                                    v-if="totalPocketCostsAllProducts > 0"
                                                    class="text-sm text-yellow-500 dark:text-yellow-400 font-semibold"
                                                >
                                                    + ₱{{ totalPocketCostsAllProducts.toFixed(2) }}
                                                    (pocket costs)
                                                </span>
                                            </div>
                                            <div
                                                v-if="
                                                    authStore.currentUser &&
                                                    typeof authStore.currentUser.prompt_credit ===
                                                        'number' &&
                                                    authStore.currentUser.prompt_credit > 0
                                                "
                                                class="text-[10px] text-red-500 dark:text-red-400 font-semibold"
                                            >
                                                +{{ authStore.currentUser.prompt_credit }} (credits)
                                            </div>
                                        </div>
                                    </div>

                                    <div class="space-y-3">
                                        <div
                                            v-for="(product, index) in checkedOutProductsArray"
                                            :key="product.id + '-' + index"
                                            class="bg-white dark:bg-gray-900 rounded-md p-3 border border-gray-200 dark:border-gray-700"
                                        >
                                            <div class="flex justify-between items-start gap-2">
                                                <div class="flex-1 min-w-0">
                                                    <p
                                                        class="font-medium text-gray-900 dark:text-gray-100 truncate"
                                                    >
                                                        {{ product.name }}
                                                    </p>
                                                    <p
                                                        class="text-sm text-gray-600 dark:text-gray-300 mt-1"
                                                    >
                                                        Unit Price:
                                                        <strong>₱{{ product.unit_price }}</strong>
                                                    </p>
                                                    <div
                                                        class="flex flex-wrap items-center gap-2 mt-2"
                                                    >
                                                        <div
                                                            v-if="product.color"
                                                            class="flex items-center"
                                                        >
                                                            <span
                                                                class="inline-block px-2 py-0.5 rounded text-xs font-semibold"
                                                                :style="{
                                                                    backgroundColor:
                                                                        colorPalette[
                                                                            product.color
                                                                        ] ||
                                                                        (isValidCssColor(
                                                                            product.color,
                                                                        )
                                                                            ? product.color
                                                                            : '#e0e7ff'),
                                                                    color: '#fff',
                                                                }"
                                                            >
                                                                {{ product.color }}
                                                            </span>
                                                        </div>

                                                        <div
                                                            v-if="product.size?.name"
                                                            class="flex items-center mt-1"
                                                        >
                                                            <span
                                                                class="inline-block px-2 py-0.5 rounded bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 text-xs font-semibold border border-blue-300 dark:border-blue-700"
                                                            >
                                                                {{ product.size.name }}
                                                            </span>
                                                        </div>
                                                        <p
                                                            class="text-sm text-gray-600 dark:text-gray-200"
                                                        >
                                                            Quantity:
                                                            <strong>
                                                                {{ product.desired_quantity }}
                                                            </strong>
                                                            <strong
                                                                v-if="
                                                                    Math.floor(
                                                                        (product.desired_quantity ||
                                                                            0) / 15,
                                                                    ) > 0
                                                                "
                                                                class="ml-2 text-green-600 dark:text-green-400"
                                                            >
                                                                (+
                                                                {{
                                                                    Math.floor(
                                                                        (product.desired_quantity ||
                                                                            0) / 15,
                                                                    )
                                                                }}
                                                                free)
                                                            </strong>
                                                        </p>
                                                    </div>
                                                </div>

                                                <div class="flex gap-2 text-right shrink-0 ml-2">
                                                    <p
                                                        class="font-semibold text-gray-900 dark:text-gray-100"
                                                    >
                                                        ₱{{
                                                            (
                                                                Number(product.unit_price) *
                                                                (product.desired_quantity || 0)
                                                            ).toFixed(2)
                                                        }}
                                                    </p>

                                                    <!-- Pocket cost indicator per product -->
                                                    <p
                                                        v-if="
                                                            product.customizations?.pocket_costs &&
                                                            Number(
                                                                product.customizations.pocket_costs,
                                                            ) > 0
                                                        "
                                                        class="text-xs text-yellow-600 dark:text-yellow-400 font-semibold mt-1"
                                                    >
                                                        + ₱{{
                                                            Number(
                                                                product.customizations.pocket_costs,
                                                            ).toFixed(2)
                                                        }}
                                                        (pocket costs)
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Summary -->
                                    <div
                                        class="mt-4 pt-3 border-t border-gray-300 dark:border-gray-700"
                                    >
                                        <div
                                            class="flex justify-between text-sm text-gray-700 dark:text-gray-200 mb-1"
                                        >
                                            <span>Total Items:</span>
                                            <span class="font-medium">
                                                {{ checkedOutProductsArray.length }}
                                            </span>
                                        </div>
                                        <div
                                            class="flex justify-between text-sm text-gray-700 dark:text-gray-200 mb-1"
                                        >
                                            <span>Total Quantity:</span>
                                            <span class="font-medium">
                                                {{ totalQuantityAllProducts }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Phone number and address as plain text at the top -->
                                <div
                                    class="mb-4 bg-gray-100 dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700"
                                >
                                    <div
                                        class="mb-2 flex flex-col sm:flex-row sm:items-baseline gap-1 sm:gap-2"
                                    >
                                        <span
                                            class="font-semibold text-gray-700 dark:text-gray-200 text-sm shrink-0"
                                        >
                                            Phone Number:
                                        </span>
                                        <span
                                            class="text-gray-800 dark:text-gray-100 text-sm break-words"
                                        >
                                            {{ formData.phone_number }}
                                        </span>
                                    </div>
                                    <div
                                        class="flex flex-col sm:flex-row sm:items-baseline gap-1 sm:gap-2"
                                    >
                                        <span
                                            class="font-semibold text-gray-700 dark:text-gray-200 text-sm shrink-0"
                                        >
                                            Full Address:
                                        </span>
                                        <span
                                            class="text-gray-800 dark:text-gray-100 text-sm break-words"
                                        >
                                            {{ formData.address }}
                                        </span>
                                    </div>
                                </div>

                                <!-- ORDER OPTION -->
                                <div class="mb-8">
                                    <label
                                        class="block text-sm text-gray-600 dark:text-gray-200 mb-1"
                                    >
                                        Order Option:
                                    </label>
                                    <div class="mt-4 w-full">
                                        <ListSelectBox
                                            v-model="formData.orderOption"
                                            :options="orderOptions"
                                            displayKey="tag"
                                        />
                                    </div>
                                </div>

                                <!-- Place Order Button -->
                                <button
                                    :disabled="isFormInvalid"
                                    @click="openQrCodePaymentModal(checkedOutProductsArray)"
                                    :class="[
                                        'w-full font-medium py-3 px-4 rounded-md transition-colors duration-200 ',
                                        isFormInvalid
                                            ? 'bg-gray-400 dark:bg-gray-700 text-white cursor-not-allowed'
                                            : 'bg-gray-800 dark:bg-white text-white hover:cursor-pointer dark:text-gray-900 hover:opacity-75 hover:bg-gray-600 dark:hover:bg-gray-200',
                                    ]"
                                >
                                    Generate QR Code Payment
                                </button>

                                <!-- Cancel Button -->
                                <button
                                    @click="handleClose"
                                    class="w-full bg-black dark:bg-gray-700 hover:opacity-75 hover:cursor-pointer hover:bg-gray-600 dark:hover:bg-gray-500 disabled:bg-gray-300 dark:disabled:bg-gray-800 disabled:cursor-not-allowed text-white font-medium py-3 px-4 rounded-md transition-colors duration-200"
                                >
                                    Cancel
                                </button>
                            </div>

                            <QrCodePaymentModal
                                v-if="showQrCodePaymentModal"
                                :selectedProductsData="selectedProductsData"
                                @close="showQrCodePaymentModal = false"
                                @place_order="handlePlaceOrder"
                                @fileSelected="handlePaymentAttachmentFile"
                            />
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>

    <Loader v-if="mutation.isPending.value" msg="Placing Order..." />
    <Toast />
</template>
