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
        type FabricTypes,
        type Product,
        type ProductStyle,
    } from '@/types/design'

    import type { PropType } from 'vue'
    import { useProductAttributes } from '@/composables/useProductAttribute'
    import { apiService } from '@/api/axios'
    import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'

    import { useToast } from 'primevue/usetoast'
    import Toast from 'primevue/toast'
    import Loader from '../Loader.vue'
    import { BanknotesIcon, ShoppingCartIcon } from '@heroicons/vue/20/solid'

    // vee-validate
    import { useForm, useField } from 'vee-validate'
    import * as yup from 'yup'
    import { isValidCssColor } from '@/helper/order'
    import { OrderAction, type ProductDetails } from '@/types/order'
    import OrderProductModal from './OrderProductModal.vue'
    import DesignStylesDropdown from './DesignStylesDropdown.vue'
    import { useRouter } from 'vue-router'
    import { colorOptions, colorPalette } from '@/utils/color'
    import { getSavedAiDesigns, type SavedAiDesign } from '@/api/get/designs'

    // Detect dark mode with composition API
    const isDark = ref(false)
    if (typeof window !== 'undefined') {
        isDark.value =
            window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches
    }

    // Optionally listen for changes
    if (typeof window !== 'undefined' && window.matchMedia) {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            isDark.value = e.matches
        })
    }

    const props = defineProps({
        product: {
            type: Object as PropType<Product>,
            required: true,
        },
        productStyles: {
            type: Array as PropType<ProductStyle[]>,
            default: () => [],
        },
        categoryName: {
            type: String,
            default: '',
        },
    })
    onMounted(() => {
        console.log('props.product: ', props.product)
        console.log('props.categoryName: ', props.categoryName)
        fetchSavedAiDesigns()
    })

    const emit = defineEmits(['close'])
    const handleClose = () => emit('close')

    // For controlling dialog open state
    const router = useRouter()
    const isModalOpen = ref(true)
    const showOrderProductModal = ref(false)

    // If the modal closes (user clicks outside), emit close
    function onDialogClose(open: boolean) {
        if (!open) {
            isModalOpen.value = false
            handleClose()
        }
    }

    const { sizes, loadingSizes } = useProductAttributes()

    const selectedStyleIds = ref<number[]>([])

    const selectedColorOption = ref('')
    const selectedOrderAction = ref<OrderAction>()
    const isCartAddingSuccessful = ref<boolean>(false)

    // Product details to checkout
    const checkoutProductDetailsRef = ref<ProductDetails[]>()

    // File upload refs
    const uploadedOwnDesignFile = ref<File | null>(null)
    const fileInputRef = ref<HTMLInputElement | null>(null)

    // Saved AI designs
    const savedAiDesigns = ref<SavedAiDesign[]>([])
    const selectedAiDesignS3Key = ref<string | null>(null)
    const isLoadingAiDesigns = ref(false)

    // Customization refs
    const jerseyNumber = ref<string>('')
    const jerseyName = ref<string>('')
    const includePocketToggle = ref<boolean>(false)
    const pocketCount = ref<number>(1)
    const additionalInstruction = ref<string>('')

    const POCKET_UNIT_COST = 50
    const pocketCost = computed(() => {
        if (includePocketToggle.value && pocketCount.value > 0) {
            return pocketCount.value * POCKET_UNIT_COST
        }
        return 0
    })

    const isApparelCategory = computed(() => {
        const cat = props.categoryName ? props.categoryName.toLowerCase() : ''
        return cat.includes('basketball') || cat.includes('volleyball')
    })

    const fetchSavedAiDesigns = async () => {
        isLoadingAiDesigns.value = true
        try {
            savedAiDesigns.value = await getSavedAiDesigns()
        } catch (e) {
            console.error('Failed to fetch saved AI designs:', e)
        } finally {
            isLoadingAiDesigns.value = false
        }
    }

    const queryClient = useQueryClient()
    const toast = useToast()

    // Load fabric types for select
    const { data: fabricTypes } = useQuery({
        queryKey: ['fabric-types'],
        queryFn: async () => {
            const respData = await apiService.get<FabricTypes[]>('/api/get/fabric/types')
            return respData
        },
    })

    // Form validation schema (add quantity validation)
    const schema = yup.object({
        fabricTypeId: props.product.fabric_type
            ? yup.number().required('Fabric type is required')
            : yup.number().nullable(),
        sizeId: props.product.fabric_type
            ? yup.number().required('Size is required')
            : yup.number().nullable(),
        quantity: yup
            .number()
            .required('Quantity is required')
            .min(1, 'Minimum quantity is 1')
            .typeError('Quantity must be a number'),
        color: yup.string().required('Color is required'),
    })

    const { handleSubmit, resetForm } = useForm({ validationSchema: schema })

    // Field bindings
    const { value: fabricTypeId, errorMessage: fabricTypeError } = useField<number>(
        'fabricTypeId',
        undefined,
        {
            initialValue: props.product.fabric_type?.id,
        },
    )
    const { value: sizeId, errorMessage: sizeIdError } = useField<number>('sizeId')
    const { value: color, errorMessage: colorError } = useField<string>('color')
    const {
        value: quantity,
        errorMessage: quantityError,
        setValue: setQuantity,
    } = useField<number>('quantity', undefined, { initialValue: 1 })

    // For Shopee-style quantity adjustment
    function decrementQuantity() {
        if (quantity.value > 1) setQuantity(quantity.value - 1)
    }
    function incrementQuantity() {
        setQuantity((quantity.value || 1) + 1)
    }

    // Optional: Pre-select first item as available
    watch(
        () => fabricTypes.value,
        (ftypes) => {
            if (ftypes && ftypes.length > 0 && fabricTypeId.value == null) {
                fabricTypeId.value = ftypes[0].id
            }
        },
        { immediate: true },
    )
    watch(
        () => sizes.value,
        (sizeVals) => {
            if (sizeVals && sizeVals.length > 0 && sizeId.value == null) {
                sizeId.value = sizeVals[0].id
            }
        },
        { immediate: true },
    )

    const addToCartMutation = useMutation({
        mutationFn: async (formData: FormData) => {
            const respData = await apiService.post('/api/add/cart', formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            })
            return respData
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['cart_items'] })
            queryClient.invalidateQueries({ queryKey: ['cart_count'] })

            isCartAddingSuccessful.value = true

            if (selectedOrderAction.value === OrderAction.ADD_TO_CART) {
                toast.add({
                    severity: 'success',
                    summary: 'Added to cart successfully!',
                    life: 1000,
                })
                setTimeout(() => {
                    handleClose()
                }, 1500)
            }

            // For BUY_NOW, do the navigation here, not in the submit handler
            if (selectedOrderAction.value === OrderAction.BUY_NOW) {
                window.location.href = '/orders/cart'
            }
        },
        onError: (err) => {
            console.error('Add to cart error', err)
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
                summary: 'Error occur while trying to add to cart, please try again',
                life: 3000,
            })
        },
    })

    // Submit handler
    const onSubmit = handleSubmit(
        (values) => {
            const formData = new FormData()
            formData.append('product_id', props.product.id.toString())
            if (values.fabricTypeId) {
                formData.append('fabric_type_id', values.fabricTypeId.toString())
            }

            if (props.product.fabric_type && values.sizeId) {
                formData.append('size_id', values.sizeId.toString())
            }

            formData.append('color', values.color)
            formData.append('quantity', values.quantity.toString())

            if (uploadedOwnDesignFile.value) {
                formData.append('own_design_file', uploadedOwnDesignFile.value)
            } else if (selectedAiDesignS3Key.value) {
                // User selected a previously saved AI design — pass the S3 key directly
                formData.append('own_design_url', selectedAiDesignS3Key.value)
            }

            if (selectedStyleIds.value.length > 0) {
                formData.append('selected_styles', JSON.stringify(selectedStyleIds.value))
            }

            if (isApparelCategory.value) {
                const customizations: any = {
                    jersey_number: jerseyNumber.value,
                    jersey_name: jerseyName.value,
                    additional_instruction: additionalInstruction.value,
                }
                if (props.product.is_pocket_included && includePocketToggle.value) {
                    customizations.pocket_count = pocketCount.value
                    customizations.pocket_costs = pocketCost.value
                }
                formData.append('customizations', JSON.stringify(customizations))
            }

            // Peek FormData key-values for debugging
            for (const pair of formData.entries()) {
                console.log(pair[0] + ': ' + pair[1])
            }

            addToCartMutation.mutate(formData)
        },
        ({ errors }) => {
            if (errors.color) {
                toast.add({
                    severity: 'warn',
                    summary: 'Selection Required',
                    detail: 'Please select or enter a color to proceed with your order.',
                    life: 3000,
                })
            }
        },
    )

    const swatchColor = computed<string | null>(() => {
        // If custom, use the free-text input; otherwise the selected option label
        const candidate =
            selectedColorOption.value === 'custom' ? color.value : selectedColorOption.value

        if (!candidate) return null
        if (colorPalette[candidate]) return colorPalette[candidate]
        if (isValidCssColor(candidate)) return candidate
        return null
    })

    // UPLOAD HANDLER FOR "OWN DESIGN" ORDER CHOICE
    // @ts-expect-error event
    const handleFileUpload = (event) => {
        const file = event.target.files[0]
        uploadedOwnDesignFile.value = file
        // Clear AI design selection when a local file is picked
        selectedAiDesignS3Key.value = null
    }

    const selectAiDesign = (s3Key: string) => {
        // Toggle: clicking the same one deselects
        if (selectedAiDesignS3Key.value === s3Key) {
            selectedAiDesignS3Key.value = null
        } else {
            selectedAiDesignS3Key.value = s3Key
            // Clear any uploaded local file
            uploadedOwnDesignFile.value = null
            if (fileInputRef.value) fileInputRef.value.value = ''
        }
    }

    const handleShowCheckoutModal = (checkoutProductDetails: ProductDetails[]) => {
        showOrderProductModal.value = true
        checkoutProductDetailsRef.value = checkoutProductDetails
    }

    const ownDesignPreviewUrl = computed(() => {
        if (uploadedOwnDesignFile.value) {
            return URL.createObjectURL(uploadedOwnDesignFile.value)
        }
        return null
    })

    const clearOwnDesignFile = () => {
        uploadedOwnDesignFile.value = null
        if (fileInputRef.value) {
            fileInputRef.value.value = ''
        }
    }

    watch(selectedColorOption, (newVal) => {
        if (newVal && newVal !== 'custom') {
            color.value = newVal // auto-set color to selected option
        } else if (!newVal) {
            color.value = ''
        }
    })
</script>

<template>
    <TransitionRoot appear :show="isModalOpen">
        <Dialog as="div" class="relative z-[999]" :open="isModalOpen" @close="onDialogClose">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black/50 transition-opacity" aria-hidden="true" />

            <!-- Full-screen scroll container -->
            <div class="fixed inset-0 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4 sm:p-6 lg:p-8">
                    <TransitionChild
                        enter="duration-300 ease-out"
                        enter-from="opacity-0 scale-95"
                        enter-to="opacity-100 scale-100"
                        leave="duration-200 ease-in"
                        leave-from="opacity-100 scale-100"
                        leave-to="opacity-0 scale-95"
                    >
                        <DialogPanel
                            class="w-full max-w-xl sm:max-w-3xl lg:max-w-5xl max-h-[90vh] overflow-y-auto transform rounded-lg p-6 text-left shadow-2xl transition-all"
                            :class="[isDark ? 'bg-zinc-900 border border-zinc-700' : 'bg-white']"
                        >
                            <!-- Header -->
                            <div class="flex items-center justify-between mb-5">
                                <DialogTitle
                                    as="h1"
                                    class="text-xl font-semibold"
                                    :class="isDark ? 'text-gray-100' : 'text-gray-900'"
                                >
                                    Product Order Option
                                </DialogTitle>
                                <button
                                    type="button"
                                    @click="handleClose"
                                    class="rounded-md p-1.5 transition-colors hover:cursor-pointer"
                                    :class="
                                        isDark
                                            ? 'text-gray-400 hover:bg-zinc-800 hover:text-gray-200'
                                            : 'text-gray-400 hover:bg-gray-100 hover:text-gray-600'
                                    "
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-5 w-5"
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

                            <div class="space-y-5">
                                <!-- Product info -->
                                <div class="flex flex-col text-sm">
                                    <p
                                        :class="[
                                            'font-medium',
                                            isDark ? 'text-gray-200' : 'text-gray-700',
                                        ]"
                                    >
                                        Product:
                                        <strong>{{ props.product.name }}</strong>
                                    </p>
                                    <p
                                        :class="[
                                            'font-medium',
                                            isDark ? 'text-gray-200' : 'text-gray-700',
                                        ]"
                                    >
                                        Unit Price:
                                        <strong>₱{{ props.product.unit_price }}</strong>
                                    </p>
                                </div>

                                <!-- Product design image -->
                                <div
                                    v-if="props.product.designs && props.product.designs.length"
                                    class="w-full rounded-md overflow-hidden"
                                    :class="isDark ? 'bg-zinc-800' : 'bg-gray-50'"
                                >
                                    <img
                                        :src="props.product.designs[0].temp_url"
                                        alt="Product Design"
                                        class="w-full h-auto object-contain max-h-48 sm:max-h-64 lg:max-h-80"
                                    />
                                </div>

                                <form @submit.prevent="onSubmit" class="space-y-5">
                                    <!-- Upload your own design -->
                                    <div>
                                        <p
                                            :class="[
                                                'text-sm mb-2',
                                                isDark ? 'text-gray-400' : 'text-gray-600',
                                            ]"
                                        >
                                            Upload your own design
                                            <span class="italic">(if preferred)</span>
                                        </p>
                                        <input
                                            ref="fileInputRef"
                                            @change="handleFileUpload"
                                            type="file"
                                            accept="image/*"
                                            class="w-full text-sm"
                                        />
                                        <!-- Image Preview -->
                                        <div v-if="ownDesignPreviewUrl" class="mt-3">
                                            <div class="relative inline-block">
                                                <img
                                                    :src="ownDesignPreviewUrl"
                                                    alt="Design Preview"
                                                    class="max-w-full h-auto max-h-48 rounded-md border"
                                                    :class="
                                                        isDark
                                                            ? 'border-zinc-700'
                                                            : 'border-gray-300'
                                                    "
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

                                        <!-- Saved AI Designs Picker -->
                                        <div v-if="savedAiDesigns.length > 0" class="mt-4">
                                            <p
                                                :class="[
                                                    'text-xs font-semibold mb-2',
                                                    isDark ? 'text-gray-400' : 'text-gray-500',
                                                ]"
                                            >
                                                Or pick from your saved AI designs:
                                            </p>
                                            <div class="flex flex-wrap gap-2">
                                                <div
                                                    v-for="design in savedAiDesigns"
                                                    :key="design.s3_key"
                                                    @click="selectAiDesign(design.s3_key)"
                                                    class="relative w-16 h-16 rounded-md overflow-hidden cursor-pointer border-2 transition-all duration-150 shrink-0"
                                                    :class="[
                                                        selectedAiDesignS3Key === design.s3_key
                                                            ? 'border-blue-500 ring-2 ring-blue-400'
                                                            : isDark
                                                              ? 'border-zinc-600 hover:border-zinc-400'
                                                              : 'border-gray-300 hover:border-gray-500',
                                                    ]"
                                                    :title="'Select this AI design'"
                                                >
                                                    <img
                                                        :src="design.temp_url"
                                                        alt="Saved AI design"
                                                        class="w-full h-full object-cover"
                                                    />
                                                    <!-- Selected overlay -->
                                                    <div
                                                        v-if="
                                                            selectedAiDesignS3Key === design.s3_key
                                                        "
                                                        class="absolute inset-0 bg-blue-500/30 flex items-center justify-center"
                                                    >
                                                        <svg
                                                            xmlns="http://www.w3.org/2000/svg"
                                                            class="h-6 w-6 text-white drop-shadow"
                                                            viewBox="0 0 20 20"
                                                            fill="currentColor"
                                                        >
                                                            <path
                                                                fill-rule="evenodd"
                                                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                                clip-rule="evenodd"
                                                            />
                                                        </svg>
                                                    </div>
                                                </div>
                                            </div>
                                            <p
                                                v-if="selectedAiDesignS3Key"
                                                :class="[
                                                    'text-xs mt-1 font-medium',
                                                    isDark ? 'text-blue-400' : 'text-blue-600',
                                                ]"
                                            >
                                                ✓ AI design selected
                                            </p>
                                        </div>
                                        <p
                                            v-else-if="isLoadingAiDesigns"
                                            :class="[
                                                'text-xs mt-3',
                                                isDark ? 'text-gray-400' : 'text-gray-500',
                                            ]"
                                        >
                                            Loading saved AI designs...
                                        </p>
                                    </div>

                                    <!-- Color -->
                                    <div>
                                        <label
                                            :class="[
                                                'block text-sm mb-1',
                                                isDark ? 'text-gray-400' : 'text-gray-600',
                                            ]"
                                        >
                                            Color:
                                        </label>
                                        <div class="flex gap-2">
                                            <select
                                                v-model="selectedColorOption"
                                                :class="[
                                                    'w-1/3 px-3 py-2 border font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500',
                                                    isDark
                                                        ? 'bg-zinc-900 border-zinc-600 text-gray-100'
                                                        : 'border-gray-300',
                                                ]"
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
                                                class="w-6 h-6 rounded border shrink-0 self-center"
                                                :class="
                                                    isDark ? 'border-zinc-700' : 'border-gray-300'
                                                "
                                                :style="{
                                                    backgroundColor: swatchColor || 'transparent',
                                                }"
                                                :title="
                                                    swatchColor
                                                        ? `Preview: ${swatchColor}`
                                                        : 'No color selected/invalid color'
                                                "
                                            />

                                            <!-- Free Text Input -->
                                            <input
                                                v-if="selectedColorOption === 'custom'"
                                                v-model="color"
                                                type="text"
                                                placeholder="Enter custom color"
                                                :class="[
                                                    'w-full px-3 py-2 border font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500',
                                                    isDark
                                                        ? 'bg-zinc-900 border-zinc-600 text-gray-100 placeholder:text-gray-400'
                                                        : 'border-gray-300',
                                                ]"
                                            />
                                            <!-- Auto-set if dropdown selected -->
                                            <input
                                                v-else
                                                :value="color"
                                                disabled
                                                :class="[
                                                    'w-full px-3 py-2 border font-medium rounded-md cursor-not-allowed',
                                                    isDark
                                                        ? 'bg-zinc-900 border-zinc-700 text-gray-400'
                                                        : 'bg-gray-100 border-gray-300',
                                                ]"
                                            />
                                        </div>
                                    </div>

                                    <!-- Size -->
                                    <div v-if="props.product.fabric_type">
                                        <label
                                            :class="[
                                                'block text-sm mb-1',
                                                isDark ? 'text-gray-400' : 'text-gray-600',
                                            ]"
                                        >
                                            Size:
                                        </label>
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <template v-if="!loadingSizes">
                                                <button
                                                    v-for="size in sizes"
                                                    :key="size.id"
                                                    :class="[
                                                        'inline-flex items-center px-2 py-0.5 rounded-md text-sm font-semibold border transition-colors',
                                                        sizeId === size.id
                                                            ? 'bg-blue-600 text-white border-blue-600'
                                                            : isDark
                                                              ? 'bg-zinc-800 text-gray-200 border-zinc-700 hover:bg-zinc-700'
                                                              : 'bg-gray-100 text-gray-700 border-gray-300 hover:opacity-75',
                                                    ]"
                                                    type="button"
                                                    @click="sizeId = size.id"
                                                >
                                                    {{ size.name }}
                                                </button>
                                            </template>
                                            <template v-else>
                                                <span
                                                    :class="[
                                                        'text-sm italic',
                                                        isDark ? 'text-gray-400' : 'text-gray-500',
                                                    ]"
                                                >
                                                    Loading sizes...
                                                </span>
                                            </template>
                                        </div>
                                        <p class="text-sm text-red-500 mt-1">{{ sizeIdError }}</p>
                                    </div>

                                    <!-- Customizations for Apparel -->
                                    <div
                                        v-if="isApparelCategory"
                                        class="space-y-4 pt-2 pb-2 border-y border-gray-200 dark:border-zinc-700"
                                    >
                                        <p
                                            class="text-sm"
                                            :class="isDark ? 'text-gray-200' : 'text-gray-800'"
                                        >
                                            Apparel Customizations (Optional)
                                        </p>

                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div>
                                                <label
                                                    :class="[
                                                        'block text-sm mb-1',
                                                        isDark ? 'text-gray-400' : 'text-gray-600',
                                                    ]"
                                                >
                                                    Jersey Number:
                                                </label>
                                                <input
                                                    v-model="jerseyNumber"
                                                    type="text"
                                                    placeholder="e.g. 23"
                                                    :class="[
                                                        'w-full font-medium px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500',
                                                        isDark
                                                            ? 'bg-zinc-900 border-zinc-600 text-gray-100 placeholder:text-gray-500'
                                                            : 'border-gray-300',
                                                    ]"
                                                />
                                            </div>
                                            <div>
                                                <label
                                                    :class="[
                                                        'block text-sm mb-1',
                                                        isDark ? 'text-gray-400' : 'text-gray-600',
                                                    ]"
                                                >
                                                    Jersey Name:
                                                </label>
                                                <input
                                                    v-model="jerseyName"
                                                    type="text"
                                                    placeholder="e.g. JORDAN"
                                                    :class="[
                                                        'w-full font-medium  px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500',
                                                        isDark
                                                            ? 'bg-zinc-900 border-zinc-600 text-gray-100 placeholder:text-gray-500'
                                                            : 'border-gray-300',
                                                    ]"
                                                />
                                            </div>
                                        </div>

                                        <div>
                                            <label
                                                :class="[
                                                    'block text-sm mb-1',
                                                    isDark ? 'text-gray-400' : 'text-gray-600',
                                                ]"
                                            >
                                                Additional Instructions:
                                            </label>
                                            <textarea
                                                v-model="additionalInstruction"
                                                rows="2"
                                                placeholder="Any specific instructions for production..."
                                                :class="[
                                                    'w-full font-medium px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500 text-sm',
                                                    isDark
                                                        ? 'bg-zinc-900 border-zinc-600 text-gray-100 placeholder:text-gray-500'
                                                        : 'border-gray-300',
                                                ]"
                                            ></textarea>
                                        </div>

                                        <div
                                            v-if="props.product.is_pocket_included"
                                            class="flex flex-col gap-2"
                                        >
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input
                                                    type="checkbox"
                                                    v-model="includePocketToggle"
                                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                                                />
                                                <span
                                                    :class="[
                                                        'text-sm',
                                                        isDark ? 'text-gray-300' : 'text-gray-700',
                                                    ]"
                                                >
                                                    Include pockets for shorts?
                                                </span>
                                            </label>

                                            <div v-if="includePocketToggle" class="pl-6">
                                                <label
                                                    :class="[
                                                        'block text-sm mb-1',
                                                        isDark ? 'text-gray-400' : 'text-gray-600',
                                                    ]"
                                                >
                                                    Number of pockets:
                                                </label>
                                                <input
                                                    v-model.number="pocketCount"
                                                    type="number"
                                                    min="1"
                                                    :class="[
                                                        'w-20 font-medium px-3 py-1 border rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500',
                                                        isDark
                                                            ? 'bg-zinc-900 border-zinc-600 text-gray-100'
                                                            : 'border-gray-300',
                                                    ]"
                                                />
                                                <p
                                                    class="text-xs mt-1"
                                                    :class="
                                                        isDark
                                                            ? 'text-yellow-400'
                                                            : 'text-yellow-600'
                                                    "
                                                >
                                                    Additional cost: ₱50 per pocket
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Product Styles -->
                                    <DesignStylesDropdown
                                        v-model="selectedStyleIds"
                                        :product-styles="props.productStyles"
                                        :is-dark="isDark"
                                    />

                                    <!-- Quantity -->
                                    <div>
                                        <label
                                            :class="[
                                                'block text-sm mb-1',
                                                isDark ? 'text-gray-400' : 'text-gray-600',
                                            ]"
                                        >
                                            Quantity:
                                        </label>
                                        <div class="flex h-10 w-fit">
                                            <button
                                                type="button"
                                                :class="[
                                                    'flex items-center justify-center border rounded-l w-10 h-full text-lg font-semibold select-none transition-colors duration-150 disabled:opacity-50',
                                                    isDark
                                                        ? 'bg-zinc-800 border-zinc-700 text-gray-200 hover:bg-zinc-700'
                                                        : 'bg-[#f1f1f1] border-[#ccc] hover:bg-[#e1e1e1]',
                                                ]"
                                                @click="decrementQuantity"
                                                :disabled="quantity <= 1"
                                                aria-label="Decrease"
                                            >
                                                <span>-</span>
                                            </button>
                                            <input
                                                v-model.number="quantity"
                                                type="number"
                                                min="1"
                                                :class="[
                                                    'w-12 appearance-none text-center focus:outline-none focus:ring-0 px-0 py-0 font-medium text-base [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none',
                                                    isDark
                                                        ? 'bg-zinc-900 border-t border-b border-zinc-700 text-gray-100'
                                                        : 'bg-white border-t border-b border-[#ccc]',
                                                ]"
                                                style="
                                                    height: 40px;
                                                    appearance: textfield;
                                                    -moz-appearance: textfield;
                                                "
                                                @blur="
                                                    () => {
                                                        if (!quantity || quantity < 1)
                                                            setQuantity(1)
                                                    }
                                                "
                                            />
                                            <button
                                                type="button"
                                                :class="[
                                                    'flex items-center justify-center border rounded-r w-10 h-full text-lg font-semibold select-none transition-colors duration-150',
                                                    isDark
                                                        ? 'bg-zinc-800 border-zinc-700 text-gray-200 hover:bg-zinc-700'
                                                        : 'bg-[#f1f1f1] border-[#ccc] hover:bg-[#e1e1e1]',
                                                ]"
                                                @click="incrementQuantity"
                                                aria-label="Increase"
                                            >
                                                <span>+</span>
                                            </button>
                                        </div>
                                        <p class="text-sm text-red-500 mt-1">{{ quantityError }}</p>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="flex justify-end items-center gap-5 pt-2">
                                        <button
                                            @click="selectedOrderAction = OrderAction.ADD_TO_CART"
                                            type="submit"
                                            :class="[
                                                'flex items-center gap-1 px-4 py-2 hover:opacity-75 hover:cursor-pointer text-white text-xs font-semibold rounded transition-colors',
                                                isDark ? 'bg-zinc-800' : 'bg-gray-900',
                                            ]"
                                            :disabled="addToCartMutation.isPending.value"
                                        >
                                            <ShoppingCartIcon class="size-4" />
                                            Add to Cart
                                        </button>

                                        <button
                                            @click="selectedOrderAction = OrderAction.BUY_NOW"
                                            type="submit"
                                            :class="[
                                                'flex items-center gap-1 px-4 py-2 text-white text-xs font-semibold rounded transition-colors hover:opacity-75 hover:cursor-pointer',
                                                isDark ? 'bg-blue-700' : 'bg-blue-600',
                                            ]"
                                            :disabled="addToCartMutation.isPending.value"
                                        >
                                            <BanknotesIcon class="size-4" />
                                            Buy Now
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>

    <Loader
        v-if="addToCartMutation.isPending.value && selectedOrderAction === OrderAction.ADD_TO_CART"
        msg="Adding to Cart..."
    />

    <Loader
        v-if="addToCartMutation.isPending.value && selectedOrderAction === OrderAction.BUY_NOW"
        msg="Processing Order..."
    />
</template>
