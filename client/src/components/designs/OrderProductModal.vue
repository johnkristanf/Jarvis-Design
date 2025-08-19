<script lang="ts" setup>
    import { ref, computed, watch } from 'vue'
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
    import { OrderOptions, type QrCodePaymentData, type SelectedOrderOption } from '@/types/order'
    import QrCodePaymentModal from './QrCodePaymentModal.vue'
    import ListSelectBox from '../ListSelectBox.vue'
    import { useMutation, useQueryClient } from '@tanstack/vue-query'

    import { useToast } from 'primevue/usetoast'
    import Toast from 'primevue/toast'
    import Loader from '../Loader.vue'

    const props = defineProps({
        categoryName: String,
        product: {
            type: Object as PropType<Product>,
            required: true,
        },
    })

    const { sizes, loadingSizes } = useProductAttributes()
    const queryClient = useQueryClient()

    // Define emits
    const emit = defineEmits(['close', 'openAIDesigns'])
    const handleClose = () => emit('close')

    // Reactive data
    const formData = ref({
        color: '',
        phone_number: '',
        address: '',

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

    // UPLOAD HANDLER FOR "OWN DESIGN" ORDER CHOICE
    // @ts-expect-error event
    const handleFileUpload = (event) => {
        const file = event.target.files[0]
        formData.value.ownDesignFile = file
    }

    const businessProductDesign = ref<BusinessProductDesign[]>([])
    const isLoadingBusinessDesigns = ref<boolean>(false)
    const showQrCodePaymentModal = ref<boolean>(false)
    const paymentAttachmentFile = ref<File | null>(null)
    const toast = useToast()

    const selectedBusinessDesignId = ref<number | null>(null)
    const qrCodePaymentData = ref<QrCodePaymentData | null>(null)

    // HANDLE PAYMENT ATTACHMENT FILE
    const handlePaymentAttachmentFile = (file: File | null) => {
        paymentAttachmentFile.value = file
    }

    // FILTER SELECTED PRODUCT CATEGORY IF NEEDED THE SIZES INPUT (IF MUGS SELECTED THEREFORE NO SIZES IS AVAILABLE)
    const shouldIncludeSizes = computed(() =>
        sublimationProductCategories.includes(props.categoryName ?? ''),
    )

    const openAIDesignModal = () => {
        formData.value.designType = 'ai-generation'
        emit('openAIDesigns')
    }

    const openQrCodePaymentModal = (
        product_name: string,
        total_quantity: number,
        total_price: number,
    ) => {
        console.log('product_name: ', product_name)
        console.log('total_quantity: ', total_quantity)
        console.log('total_price: ', total_price)

        qrCodePaymentData.value = {
            product_name,
            total_quantity,
            total_price,
        }

        showQrCodePaymentModal.value = true
    }

    // FETCH UPLOADED BUSINESS DESIGNS
    const fetchBusinessDesigns = async (product_id: number) => {
        isLoadingBusinessDesigns.value = true
        const designs = await apiService.get<BusinessProductDesign[]>(
            `/api/get/bussiness_designs/${product_id}`,
        )
        console.log('designs: ', designs)
        businessProductDesign.value = designs
        isLoadingBusinessDesigns.value = false
    }

    // SERIALIZING DATA TO THE FORMDATA
    const prepareFormData = () => {
        const data = new FormData()

        console.log('paymentAttachmentFile.value: ', paymentAttachmentFile.value)

        data.append('color', formData.value.color)
        data.append('phone_number', formData.value.phone_number)
        data.append('address', formData.value.address)
        data.append('design_type', formData.value.designType)
        data.append('order_option', formData.value.orderOption?.name as string)
        data.append('fabric_type_id', props.product.fabric_type.id.toString())
        data.append('product_unit_price', props.product.unit_price)

        // Conditionally append size quantities or solo quantity
        if (shouldIncludeSizes.value) {
            for (const [sizeId, qty] of Object.entries(formData.value.quantityPerSize)) {
                data.append(`sizes[${sizeId}]`, qty.toString())
            }
        } else {
            data.append('solo_quantity', formData.value.solo_quantity?.toString() ?? '')
        }

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
        () => totalQuantityForMultiSizes.value * Number(props.product.unit_price),
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
            : (formData.value.solo_quantity ?? 0) * Number(props.product.unit_price ?? 0)
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
            console.log('respData success Order: ', response)
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
        if (!formData.value.color.trim()) return true
        if (!formData.value.orderOption) return true

        // Design validation
        if (formData.value.designType === 'own-design' && !formData.value.ownDesignFile) return true
        if (formData.value.designType === 'business-design' && !formData.value.businessDesignURL)
            return true

        // Quantity validation
        if (shouldIncludeSizes.value) {
            const hasQuantity = Object.values(formData.value.quantityPerSize).some(
                (qty) => Number(qty) > 0,
            )
            if (!hasQuantity) return true
        } else {
            if (!formData.value.solo_quantity || formData.value.solo_quantity <= 0) return true
        }

        return false
    })

    // SUBMIT ORDER HANDLER
    const handlePlaceOrder = async () => {
        // CLOSE QRCODE MODAL FOR LOADER
        showQrCodePaymentModal.value = false

        const formData = prepareFormData()
        for (const [key, value] of formData.entries()) {
            console.log(`${key}:`, value)
        }

        if (totalQuantity.value && totalPrice.value && paymentAttachmentFile.value) {
            formData.append('total_quantity', totalQuantity.value.toString())
            formData.append('total_price', totalPrice.value.toString())
            formData.append('payment_attachment', paymentAttachmentFile.value)
        }

        mutation.mutate(formData)
    }

    // WATCHER FOR BUSINESS DESIGN TRIGGER FETCHING
    watch(
        () => formData.value.designType,
        (newVal) => {
            if (newVal === 'business-design') {
                fetchBusinessDesigns(props.product.id)
            }
        },
    )

    // CHECK IF THE SELECTED CATEGORY HAS SIZES REQUIRED
</script>

<template>
    <TransitionRoot appear :show="true">
        <Dialog as="div" static @close="() => {}" class="relative z-[999]">
            <div class="fixed inset-0 overflow-y-auto bg-gray-900/80">
                <div class="flex min-h-full items-center justify-center p-4 text-center">
                    <TransitionChild
                        enter="duration-300 ease-out"
                        enter-from="opacity-0 scale-95"
                        enter-to="opacity-100 scale-100"
                        leave="duration-200 ease-in"
                        leave-from="opacity-100 scale-100"
                        leave-to="opacity-0 scale-95"
                    >
                        <DialogPanel
                            class="w-[600px] h-[30rem] max-w-5xl transform overflow-y-auto bg-white p-6 text-left align-middle shadow-xl transition-all"
                        >
                            <DialogTitle as="h1" class="text-2xl text-gray-900">
                                Product Order Details
                            </DialogTitle>

                            <div class="space-y-7">
                                <!-- T-shirt Section -->
                                <div>
                                    <div class="flex flex-col mb-5 text-sm">
                                        <p class="font-medium text-gray-700">
                                            Category:
                                            <strong>{{ props.categoryName }}</strong>
                                        </p>
                                        <p class="font-medium text-gray-700">
                                            Product:
                                            <strong>{{ props.product.name }}</strong>
                                        </p>

                                        <p class="font-medium text-gray-700">
                                            Unit Price:
                                            <strong>₱{{ props.product.unit_price }}</strong>
                                        </p>
                                    </div>

                                    <!-- Color Input -->
                                    <div class="mb-8">
                                        <label class="block text-sm text-gray-600 mb-1">
                                            Phone Number:
                                        </label>
                                        <input
                                            v-model="formData.phone_number"
                                            type="number"
                                            placeholder="Enter Phone Number"
                                            class="w-full px-3 py-2 border font-medium border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500"
                                        />
                                    </div>

                                    <!-- Color Input -->
                                    <div class="mb-8">
                                        <label class="block text-sm text-gray-600 mb-1">
                                            Full Address:
                                        </label>
                                        <input
                                            v-model="formData.address"
                                            type="text"
                                            placeholder="Enter Address"
                                            class="w-full px-3 py-2 border font-medium border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500"
                                        />
                                    </div>

                                    <!-- Color Input -->
                                    <div class="mb-8">
                                        <label class="block text-sm text-gray-600 mb-1">
                                            Color:
                                        </label>
                                        <input
                                            v-model="formData.color"
                                            type="text"
                                            placeholder="Enter color"
                                            class="w-full px-3 py-2 border font-medium border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500"
                                        />
                                    </div>

                                    <!-- Quantity for fixed price -->
                                    <div v-if="!shouldIncludeSizes" class="mb-8">
                                        <label class="block text-sm text-gray-600 mb-1">
                                            Quantity:
                                        </label>
                                        <input
                                            v-model="formData.solo_quantity"
                                            type="number"
                                            placeholder="Enter quantity"
                                            class="w-full px-3 py-2 border font-medium border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500"
                                        />
                                    </div>

                                    <!-- Sizes and Quantities as OTP-like inputs -->
                                    <div v-if="shouldIncludeSizes" class="mb-8">
                                        <label class="block text-sm text-gray-600 mb-2">
                                            Size Quantities:
                                        </label>
                                        <div
                                            class="grid grid-cols-4 gap-2"
                                            v-if="Array.isArray(sizes) && !loadingSizes"
                                        >
                                            <div
                                                v-for="size in sizes"
                                                :key="size.id"
                                                class="flex flex-col items-center"
                                            >
                                                <span class="text-xs text-gray-700 mb-1">
                                                    {{ size.name }}
                                                </span>
                                                <input
                                                    type="number"
                                                    min="0"
                                                    class="w-14 text-center font-medium px-2 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500"
                                                    v-model.number="
                                                        formData.quantityPerSize[size.id]
                                                    "
                                                />
                                            </div>
                                        </div>

                                        <!-- LOADING SIZES -->
                                        <div v-if="loadingSizes">
                                            <h1 class="text-center">Loading Sizes...</h1>
                                        </div>
                                    </div>
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

                                <!-- Design Selection Buttons -->
                                <div class="mb-4">
                                    <label class="block text-sm text-gray-600 mb-2">
                                        Design Options:
                                    </label>
                                    <div class="flex gap-2 flex-wrap">
                                        <button
                                            @click="formData.designType = 'own-design'"
                                            :class="[
                                                'px-3 py-1 text-sm rounded-md border transition-colors hover:cursor-pointer hover:opacity-75',
                                                formData.designType === 'own-design'
                                                    ? 'bg-gray-500 text-white border-gray-500'
                                                    : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50',
                                            ]"
                                        >
                                            Own Design
                                        </button>
                                        <button
                                            @click="formData.designType = 'business-design'"
                                            :class="[
                                                'px-3 py-1 text-sm rounded-md border transition-colors hover:cursor-pointer hover:opacity-75',
                                                formData.designType === 'business-design'
                                                    ? 'bg-gray-500 text-white border-gray-500'
                                                    : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50',
                                            ]"
                                        >
                                            Business Design
                                        </button>
                                        <button
                                            @click="openAIDesignModal"
                                            :class="[
                                                'px-3 py-1 text-sm rounded-md border transition-colors hover:cursor-pointer hover:opacity-75',
                                                formData.designType === 'ai-generation'
                                                    ? 'bg-gray-500 text-white border-gray-500'
                                                    : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50',
                                            ]"
                                        >
                                            AI Generation
                                        </button>
                                    </div>
                                </div>

                                <!-- Design Upload/Input Area -->
                                <div class="mb-6" v-if="formData.designType != 'ai-generation'">
                                    <div
                                        class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center bg-gray-50"
                                    >
                                        <!-- Own Design -->
                                        <div v-if="formData.designType === 'own-design'">
                                            <p class="text-sm text-gray-600 mb-3">
                                                Upload your own design
                                            </p>
                                            <input
                                                @change="handleFileUpload"
                                                type="file"
                                                accept="image/*"
                                            />
                                        </div>

                                        <!-- Business Design -->
                                        <div v-else-if="formData.designType === 'business-design'">
                                            <p class="text-sm text-gray-600 mb-3">
                                                Browse business design templates
                                            </p>

                                            <div class="grid grid-cols-3 gap-3">
                                                <div
                                                    v-for="design in businessProductDesign"
                                                    :key="design.id"
                                                    :class="[
                                                        'rounded-md overflow-hidden transition-shadow relative',
                                                        selectedBusinessDesignId === design.id
                                                            ? 'ring-4 ring-gray-600 shadow-lg'
                                                            : 'hover:shadow-md cursor-pointer',
                                                    ]"
                                                    @click="
                                                        () => {
                                                            if (
                                                                selectedBusinessDesignId ===
                                                                design.id
                                                            ) {
                                                                selectedBusinessDesignId = null
                                                                formData.businessDesignURL = ''
                                                            } else {
                                                                selectedBusinessDesignId = design.id
                                                                formData.businessDesignURL =
                                                                    design.image_url
                                                            }
                                                        }
                                                    "
                                                >
                                                    <img
                                                        :src="design.temp_url"
                                                        alt="Business Design"
                                                        class="w-full h-full object-cover mb-3 hover:cursor-pointer"
                                                    />
                                                    <div
                                                        v-if="
                                                            selectedBusinessDesignId === design.id
                                                        "
                                                        class="absolute top-1 right-1 bg-gray-800 text-white text-xs px-2 py-1 rounded-full"
                                                    >
                                                        Selected
                                                    </div>
                                                </div>
                                            </div>

                                            <div v-if="isLoadingBusinessDesigns">
                                                <h1 class="text-center">
                                                    Loading business designs...
                                                </h1>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Price Display -->

                                <div class="mb-4">
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
                                </div>

                                <!-- Place Order Button -->
                                <button
                                    :disabled="isFormInvalid"
                                    @click="
                                        openQrCodePaymentModal(
                                            props.product.name,
                                            totalQuantity,
                                            totalPrice,
                                        )
                                    "
                                    :class="[
                                        'w-full font-medium py-3 px-4 rounded-md transition-colors duration-200',
                                        isFormInvalid
                                            ? 'bg-gray-400 text-white cursor-not-allowed'
                                            : 'bg-gray-800 text-white hover:opacity-75 hover:bg-gray-600',
                                    ]"
                                >
                                    Generate QR Code Payment
                                </button>

                                <!-- Place Order Button -->
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
        :paymentData="qrCodePaymentData"
        @close="showQrCodePaymentModal = false"
        @place_order="handlePlaceOrder"
        @fileSelected="handlePaymentAttachmentFile"
    />

    <Toast />
</template>
