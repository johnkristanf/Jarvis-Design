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
    import GenerateAIDesignsModal from './GenerateAIDesigns.vue'
    import { useFetchAuthenticatedUser } from '@/composables/useFetchAuthenticatedUser'
    // @ts-ignore
    import BusinessDesignModal from './BusinessDesignModal.vue'

    const props = defineProps({
        categoryName: String,
        product: {
            type: Object as PropType<Product | ProductDetails>,
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

    // UPLOAD HANDLER FOR "OWN DESIGN" ORDER CHOICE
    // @ts-expect-error event
    const handleFileUpload = (event) => {
        const file = event.target.files[0]
        formData.value.ownDesignFile = file
    }

    const ownDesignPreviewUrl = computed(() => {
        if (formData.value.ownDesignFile) {
            return URL.createObjectURL(formData.value.ownDesignFile)
        }
        return null
    })

    const fileInputRef = ref<HTMLInputElement | null>(null)

    const clearOwnDesignFile = () => {
        formData.value.ownDesignFile = null
        if (fileInputRef.value) {
            fileInputRef.value.value = ''
        }
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
        // emit('openAIDesigns')
        showAIDesignModal.value = true
    }

    const openQrCodePaymentModal = (
        product_name: string,
        total_quantity: number,
        total_price: number,
    ) => {
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
        businessProductDesign.value = designs
        isLoadingBusinessDesigns.value = false
    }

    // SERIALIZING DATA TO THE FORMDATA
    const prepareFormData = () => {
        const data = new FormData()

        data.append('color', formData.value.color)
        data.append('phone_number', formData.value.phone_number)
        data.append('address', formData.value.address)
        data.append('design_type', formData.value.designType)
        data.append('order_option', formData.value.orderOption?.name as string)
        data.append('product_unit_price', props.product.unit_price)
        data.append('product_id', props.product.id.toString())

        // Null if the product has no corresponding fabric like (mugs, lanyard, etc..)
        // if (props.product.fabric_type && props.product.fabric_type.id) {
        //     data.append('fabric_type_id', props.product.fabric_type.id.toString())
        // }

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
                            class="w-[600px] max-w-7xl h-[30rem] transform overflow-y-auto bg-white p-6 text-left align-middle shadow-xl transition-all"
                        >
                            <DialogTitle as="h1" class="text-2xl text-gray-900">
                                Product Order Details
                            </DialogTitle>

                            <div class="space-y-7">
                                <!-- T-shirt Section -->
                                <div>
                                    <div class="flex flex-col mb-5 text-sm">
                                        <!-- <p class="font-medium text-gray-700">
                                            Category:
                                            <strong>{{ props.categoryName }}</strong>
                                        </p> -->
                                        <p class="font-medium text-gray-700">
                                            Product:
                                            <strong>{{ props.product.name }}</strong>
                                        </p>

                                        <p class="font-medium text-gray-700">
                                            Unit Price:
                                            <strong>₱{{ props.product.unit_price }}</strong>
                                        </p>
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

                                    <!-- Color Input -->
                                    <div class="mb-8">
                                        <label class="block text-sm text-gray-600 mb-1">
                                            Color:
                                        </label>
                                        <div class="flex gap-2">
                                            <!-- Select Dropdown -->
                                            <select
                                                v-model="selectedOption"
                                                class="w-1/3 px-3 py-2 border font-medium border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500"
                                            >
                                                <option value="">-- Select --</option>
                                                <option
                                                    v-for="color in colorOptions"
                                                    :key="color"
                                                    :value="color"
                                                >
                                                    {{ color }}
                                                </option>
                                                <option value="custom">Custom</option>
                                            </select>

                                            <!-- Swatch -->
                                            <div
                                                class="w-6 h-6 rounded border border-gray-300 shrink-0"
                                                :style="{
                                                    backgroundColor: swatchColor || 'transparent',
                                                }"
                                                :title="
                                                    swatchColor
                                                        ? `Preview: ${swatchColor}`
                                                        : 'No color selected/invalid color'
                                                "
                                            ></div>

                                            <!-- Free Text Input -->
                                            <input
                                                v-if="selectedOption === 'custom'"
                                                v-model="formData.color"
                                                type="text"
                                                placeholder="Enter custom color"
                                                class="w-full px-3 py-2 border font-medium border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500"
                                            />

                                            <!-- Auto-set if dropdown selected -->
                                            <input
                                                v-else
                                                :value="formData.color"
                                                disabled
                                                class="w-full px-3 py-2 border font-medium border-gray-300 rounded-md bg-gray-100 cursor-not-allowed"
                                            />
                                        </div>
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
                                                ref="fileInputRef"
                                                @change="handleFileUpload"
                                                type="file"
                                                accept="image/*"
                                            />

                                            <!-- Image Preview -->
                                            <div v-if="ownDesignPreviewUrl" class="mt-4">
                                                <div class="relative inline-block">
                                                    <img
                                                        :src="ownDesignPreviewUrl"
                                                        alt="Design Preview"
                                                        class="max-w-full h-auto max-h-[200px] rounded-md border border-gray-300"
                                                    />
                                                    <button
                                                        @click="clearOwnDesignFile"
                                                        class="absolute top-2 right-2 bg-red-500 hover:bg-red-600 text-white rounded-full hover:cursor-pointer p-1 shadow-lg transition-colors"
                                                        title="Remove image"
                                                    >
                                                        <svg
                                                            xmlns="http://www.w3.org/2000/svg"
                                                            class="h-4 w-4"
                                                            fill="none"
                                                            viewBox="0 0 24 24"
                                                            stroke="currentColor"
                                                            stroke-width="2"
                                                        >
                                                            <path
                                                                stroke-linecap="round"
                                                                stroke-linejoin="round"
                                                                d="M6 18L18 6M6 6l12 12"
                                                            />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Business Design -->
                                        <div v-else-if="formData.designType === 'business-design'">
                                            <p class="text-sm text-gray-600 mb-3">
                                                Browse business design templates
                                            </p>

                                            <div
                                                class="max-h-[500px] max-w-[700px] overflow-y-auto pr-2"
                                            >
                                                <div class="grid grid-cols-2 md:grid-cols-3 gap-5">
                                                    <div
                                                        v-for="design in businessProductDesign"
                                                        :key="design.id"
                                                        :class="[
                                                            'rounded-md overflow-hidden transition-shadow relative',
                                                        ]"
                                                    >
                                                        <!-- Icon Buttons Topbar -->
                                                        <div
                                                            class="absolute flex right-0 top-[-5px] z-10 gap-2"
                                                        >
                                                            <!-- Open modal icon (eye) -->
                                                            <BusinessDesignModal
                                                                :temp_url="design.temp_url"
                                                            />

                                                            <!-- Select icon (check-circle) -->
                                                            <button
                                                                @click.stop="
                                                                    () => {
                                                                        if (
                                                                            selectedBusinessDesignId ===
                                                                            design.id
                                                                        ) {
                                                                            selectedBusinessDesignId =
                                                                                null
                                                                            formData.businessDesignURL =
                                                                                ''
                                                                        } else {
                                                                            selectedBusinessDesignId =
                                                                                design.id
                                                                            formData.businessDesignURL =
                                                                                design.image_url
                                                                        }
                                                                    }
                                                                "
                                                                :title="
                                                                    selectedBusinessDesignId ===
                                                                    design.id
                                                                        ? 'Deselect this design'
                                                                        : 'Select this design'
                                                                "
                                                                class="bg-white/90 hover:bg-gray-100 border border-gray-300 hover:cursor-pointer rounded-full p-1 shadow focus:outline-none"
                                                            >
                                                                <svg
                                                                    v-if="
                                                                        selectedBusinessDesignId ===
                                                                        design.id
                                                                    "
                                                                    xmlns="http://www.w3.org/2000/svg"
                                                                    class="h-5 w-5 text-green-600"
                                                                    fill="none"
                                                                    viewBox="0 0 24 24"
                                                                    stroke="currentColor"
                                                                    stroke-width="2"
                                                                >
                                                                    <path
                                                                        stroke-linecap="round"
                                                                        stroke-linejoin="round"
                                                                        d="M9 12l2 2l4-4m5 2a9 9 0 11-18 0a9 9 0 0118 0z"
                                                                    />
                                                                </svg>
                                                                <svg
                                                                    v-else
                                                                    xmlns="http://www.w3.org/2000/svg"
                                                                    class="h-5 w-5 text-gray-400"
                                                                    fill="none"
                                                                    viewBox="0 0 24 24"
                                                                    stroke="currentColor"
                                                                    stroke-width="2"
                                                                >
                                                                    <circle
                                                                        cx="12"
                                                                        cy="12"
                                                                        r="9"
                                                                        stroke="currentColor"
                                                                        stroke-width="2"
                                                                        fill="none"
                                                                    />
                                                                </svg>
                                                            </button>
                                                        </div>
                                                        <img
                                                            :src="design.temp_url"
                                                            alt="Business Design"
                                                            class="w-full h-[120px] object-contain bg-white mb-3 hover:cursor-pointer"
                                                            @click="
                                                                () => {
                                                                    if (
                                                                        selectedBusinessDesignId ===
                                                                        design.id
                                                                    ) {
                                                                        selectedBusinessDesignId =
                                                                            null
                                                                        formData.businessDesignURL =
                                                                            ''
                                                                    } else {
                                                                        selectedBusinessDesignId =
                                                                            design.id
                                                                        formData.businessDesignURL =
                                                                            design.image_url
                                                                    }
                                                                }
                                                            "
                                                        />
                                                        <!-- <div
                                                            v-if="
                                                                selectedBusinessDesignId ===
                                                                design.id
                                                            "
                                                            class="absolute top-2 right-2 bg-gray-800 text-white text-xs px-2 py-1 rounded-full"
                                                        >
                                                            Selected
                                                        </div> -->
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

                    <!-- GENERATE AI DESIGNS PROMPT COMPONENT -->
                    <GenerateAIDesignsModal
                        v-if="showAIDesignModal"
                        @close="showAIDesignModal = false"
                    />
                </div>
            </div>
        </Dialog>
    </TransitionRoot>

    <Loader v-if="mutation.isPending.value" msg="Placing Order..." />

    <!-- <QrCodePaymentModal
        v-if="showQrCodePaymentModal"
        :paymentData="qrCodePaymentData"
        @close="showQrCodePaymentModal = false"
        @place_order="handlePlaceOrder"
        @fileSelected="handlePaymentAttachmentFile"
    /> -->

    <Toast />
</template>
