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

    const props = defineProps({
        categoryName: String,
        product: {
            type: Object as PropType<ProductDetails[]>,
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

        // SOLO QUANTITY FOR FIXED PRICED PRODUCT
        solo_quantity: null as number | null,

        // QUANTITY FOR EACH SIZE
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
    const paymentAttachmentFile = ref<ProductIndexPayment[] | null>(null)
    const toast = useToast()

    const selectedBusinessDesignId = ref<number | null>(null)
    const selectedProductsData = ref<ProductDetails[] | null>(null)

    // HANDLE PAYMENT ATTACHMENT FILE
    const handlePaymentAttachmentFile = (product_payment_file: ProductIndexPayment[]) => {
        paymentAttachmentFile.value = product_payment_file
    }

    // FILTER SELECTED PRODUCT CATEGORY IF NEEDED THE SIZES INPUT (IF MUGS SELECTED THEREFORE NO SIZES IS AVAILABLE)
    const shouldIncludeSizes = computed(() =>
        sublimationProductCategories.includes(props.categoryName ?? ''),
    )

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

    // SERIALIZING DATA TO THE FORMDATA
    const prepareFormData = () => {
        const data = new FormData()

        data.append('phone_number', formData.value.phone_number)
        data.append('address', formData.value.address)
        data.append('design_type', formData.value.designType)
        data.append('order_option', formData.value.orderOption?.name as string)

        console.log('paymentAttachmentFile: ', paymentAttachmentFile.value)
        console.log('checkedOutProductsArray : ', checkedOutProductsArray.value)


        // Map through the checked out products, appending each product's details and its corresponding payment file
        checkedOutProductsArray.value.forEach((product, idx) => {
            data.append(`products[${idx}][product_id]`, product.id.toString())
            data.append(`products[${idx}][product_unit_price]`, product.unit_price.toString())
            data.append(`products[${idx}][product_color]`, product.color)
            data.append(`products[${idx}][fabric_type_id]`, product.fabric_type_id.toString())

            if (
                Array.isArray(paymentAttachmentFile.value) &&
                paymentAttachmentFile.value[idx] &&
                paymentAttachmentFile.value[idx].file
            ) {
                data.append(
                    `products[${idx}][payment_attachment]`,
                    paymentAttachmentFile.value[idx].file,
                )
            }


            if (product.desired_quantity) {
                data.append(`products[${idx}][total_quantity]`, product.desired_quantity.toString())
            }

            const total_price = (
                Number(product.desired_quantity) * Number(product.unit_price)
            ).toString()

            data.append(`products[${idx}][total_price]`, total_price.toString())
            data.append(`products[${idx}][sizes]`, JSON.stringify(product.size))

            // data.append('total_quantity', totalQuantityAllProducts.value.toString())
            // data.append('total_price', totalPriceAllProducts.value.toString())
        })

        // Null if the product has no corresponding fabric like (mugs, lanyard, etc..)
        // if (firstProduct.fabric_type && firstProduct.fabric_type.id) {
        //     data.append('fabric_type_id', firstProduct.fabric_type.id.toString())
        // }

        // // Conditionally append size quantities or solo quantity
        // if (shouldIncludeSizes.value) {
        //     for (const [sizeId, qty] of Object.entries(formData.value.quantityPerSize)) {
        //         data.append(`sizes[${sizeId}]`, qty.toString())
        //     }
        // } else {
        //     data.append('solo_quantity', formData.value.solo_quantity?.toString() ?? '')
        // }

        if (formData.value.designType === 'own-design' && formData.value.ownDesignFile) {
            data.append('own_design_file', formData.value.ownDesignFile)
        } else if (formData.value.designType === 'business-design') {
            data.append('business_design_url', formData.value.businessDesignURL)
        }
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

    // FINAL TOTAL QUANTITY THAT CATCHES CATEGORY THAT HAS
    // MULTI SIZES (BASKET APPAREL) AND SOLO (MUGS, LANYARD, etc..)
    const totalQuantity = computed(() => {
        return shouldIncludeSizes.value
            ? totalQuantityForMultiSizes.value
            : (formData.value.solo_quantity ?? 0)
    })

    // FINAL TOTAL PRICE THAT CATCHES CATEGORY THAT HAS MULTI SIZES AND SOLO
    const totalPrice = computed(() => {
        return shouldIncludeSizes.value
            ? totalPriceForMultiSizes.value
            : (formData.value.solo_quantity ?? 0) *
                  Number(checkedOutProductsArray.value[0]?.unit_price ?? 0)
    })

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

        // Design validation
        // if (formData.value.designType === 'own-design' && !formData.value.ownDesignFile) return true
        // if (formData.value.designType === 'business-design' && !formData.value.businessDesignURL)
        //     return true

        // // Quantity validation
        // if (shouldIncludeSizes.value) {
        //     const hasQuantity = Object.values(formData.value.quantityPerSize).some(
        //         (qty) => Number(qty) > 0,
        //     )
        //     if (!hasQuantity) return true
        // } else {
        //     if (!formData.value.solo_quantity || formData.value.solo_quantity <= 0) return true
        // }

        return false
    })

    // SUBMIT ORDER HANDLER
    const handlePlaceOrder = async () => {
        // CLOSE QRCODE MODAL FOR LOADER
        showQrCodePaymentModal.value = false

        const formData = prepareFormData()
        // Peek FormData (for debugging)
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

    const swatchColor = computed<string | null>(() => {
        // If custom, use the free-text input; otherwise the selected option label
        const candidate =
            selectedOption.value === 'custom' ? formData.value.color : selectedOption.value

        if (!candidate) return null
        if (colorPalette[candidate]) return colorPalette[candidate]
        if (isValidCssColor(candidate)) return candidate
        return null
    })

    const colorOptions = [
        'Red',
        'Blue',
        'Green',
        'Yellow',
        'Black',
        'Sunset Blaze',
        'Tropical Punch',
        'Ocean Wave',
        'Aqua Breeze',
    ]

    const colorPalette: Record<string, string> = {
        Red: '#FF0000',
        Blue: '#0000FF',
        Green: '#008000',
        Yellow: '#FFFF00',
        Black: '#000000',
        'Sunset Blaze': '#FF6B3D',
        'Tropical Punch': '#FF3B7F',
        'Ocean Wave': '#2E8BC0',
        'Aqua Breeze': '#7FDBFF',
    }

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
            <div class="fixed inset-0 overflow-y-auto bg-gray-900/80 transition-opacity">
                <div
                    class="flex flex-col lg:flex-row items-start lg:items-center justify-center p-4 text-center gap-4 lg:gap-8 min-h-screen"
                >
                    <TransitionChild
                        enter="duration-300 ease-out"
                        enter-from="opacity-0 scale-95"
                        enter-to="opacity-100 scale-100"
                        leave="duration-200 ease-in"
                        leave-from="opacity-100 scale-100"
                        leave-to="opacity-0 scale-95"
                    >
                        <DialogPanel
                            class="w-[1000px] h-[35rem] transform overflow-y-auto bg-white p-6 text-left align-middle shadow-xl transition-all"
                        >
                            <DialogTitle as="h1" class="text-2xl text-gray-900 mb-4">
                                Product Order Details
                            </DialogTitle>

                            <div class="space-y-7">
                                <!-- Products List Section -->
                                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                    <h2 class="text-lg font-semibold text-gray-800 mb-3">
                                        Selected Products
                                    </h2>

                                    <div class="space-y-3">
                                        <div
                                            v-for="(product, index) in checkedOutProductsArray"
                                            :key="product.id + '-' + index"
                                            class="bg-white rounded-md p-3 border border-gray-200"
                                        >
                                            <div class="flex justify-between items-start">
                                                <div class="flex-1">
                                                    <p class="font-medium text-gray-900">
                                                        {{ product.name }}
                                                    </p>
                                                    <p class="text-sm text-gray-600 mt-1">
                                                        Unit Price:
                                                        <strong>₱{{ product.unit_price }}</strong>
                                                    </p>
                                                    <div class="flex items-center gap-2">
                                                        <div
                                                            v-if="product.color"
                                                            class="flex items-center mt-1"
                                                        >
                                                            <span
                                                                class="inline-block px-2 py-0.5 rounded text-xs font-semibold "
                                                                :style="{
                                                                    backgroundColor: colorPalette[product.color] || (isValidCssColor(product.color) ? product.color : '#e0e7ff'),
                                                                    color: '#fff'
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
                                                                class="inline-block px-2 py-0.5 rounded bg-blue-100 text-blue-800 text-xs font-semibold border border-blue-300"
                                                            >
                                                                {{ product.size.name }}
                                                            </span>
                                                        </div>
                                                        <p class="text-sm text-gray-600">
                                                            Quantity:
                                                            <strong>
                                                                {{ product.desired_quantity }}
                                                            </strong>
                                                        </p>
                                                    </div>
                                                </div>

                                                <div class="text-right">
                                                    <p class="font-semibold text-gray-900">
                                                        ₱{{
                                                            (
                                                                Number(product.unit_price) *
                                                                (product.desired_quantity || 0)
                                                            ).toFixed(2)
                                                        }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Summary -->
                                    <div class="mt-4 pt-3 border-t border-gray-300">
                                        <div
                                            class="flex justify-between text-sm text-gray-700 mb-1"
                                        >
                                            <span>Total Items:</span>
                                            <span class="font-medium">
                                                {{ checkedOutProductsArray.length }}
                                            </span>
                                        </div>
                                        <div
                                            class="flex justify-between text-sm text-gray-700 mb-1"
                                        >
                                            <span>Total Quantity:</span>
                                            <span class="font-medium">
                                                {{ totalQuantityAllProducts }}
                                            </span>
                                        </div>
                                        <div
                                            class="flex justify-between text-base font-semibold text-gray-900"
                                        >
                                            <span>Total Amount:</span>
                                            <span>₱{{ totalPriceAllProducts.toFixed(2) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Phone Number Input -->
                                <div class="mb-8">
                                    <label class="block text-sm text-gray-600 mb-1">
                                        Phone Number:
                                    </label>
                                    <div class="flex items-center">
                                        <span
                                            class="px-3 py-2 bg-gray-100 border border-r-0 border-gray-300 rounded-l-md text-gray-700"
                                        >
                                            +63
                                        </span>
                                        <input
                                            v-model="formData.phone_number"
                                            type="number"
                                            placeholder="9XXXXXXXXX"
                                            class="w-full cursor-not-allowed px-3 py-2 border font-medium border-gray-300 rounded-r-md focus:outline-none focus:ring-2 focus:ring-gray-100"
                                            readonly
                                        />
                                    </div>
                                </div>

                                <!-- Full Address Input -->
                                <div class="mb-8">
                                    <label class="block text-sm text-gray-600 mb-1">
                                        Full Address:
                                    </label>
                                    <input
                                        v-model="formData.address"
                                        type="text"
                                        placeholder="Enter Address"
                                        class="w-full cursor-not-allowed px-3 py-2 border font-medium border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-100"
                                        readonly
                                    />
                                </div>

                                <!-- ORDER OPTION -->
                                <div class="mb-8">
                                    <label class="block text-sm text-gray-600 mb-1">
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

                                
                                <!-- Price Display -->
                                <!-- <div class="mb-4">
                                    <label class="block text-sm text-gray-600 mb-2">
                                        Pricing Details:
                                    </label>

                                    <div class="mb-4 bg-gray-400 text-white rounded-md p-3">
                                        <div class="flex justify-between text-md mb-1">
                                            <h1>
                                                Total Quantity:
                                                <br />
                                                {{ totalQuantity }}
                                            </h1>
                                            <h1>
                                                Total Price:
                                                <br />
                                                ₱
                                                {{ totalPrice }}
                                            </h1>
                                        </div>
                                    </div>
                                </div> -->

                                <!-- Place Order Button -->
                                <button
                                    :disabled="isFormInvalid"
                                    @click="openQrCodePaymentModal(checkedOutProductsArray)"
                                    :class="[
                                        'w-full font-medium py-3 px-4 rounded-md transition-colors duration-200',
                                        isFormInvalid
                                            ? 'bg-gray-400 text-white cursor-not-allowed'
                                            : 'bg-gray-800 text-white hover:opacity-75 hover:bg-gray-600',
                                    ]"
                                >
                                    Generate QR Code Payment
                                </button>

                                <!-- Cancel Button -->
                                <button
                                    @click="handleClose"
                                    class="w-full bg-black hover:opacity-75 hover:bg-gray-600 disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-medium py-3 px-4 rounded-md transition-colors duration-200"
                                >
                                    Cancel
                                </button>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>

    <Loader v-if="mutation.isPending.value" msg="Placing Order..." />

    <QrCodePaymentModal
        v-if="showQrCodePaymentModal"
        :selectedProductsData="selectedProductsData"
        @close="showQrCodePaymentModal = false"
        @place_order="handlePlaceOrder"
        @fileSelected="handlePaymentAttachmentFile"
    />

    <Toast />
</template>
