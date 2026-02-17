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
    import { useRouter } from 'vue-router'
    import { colorOptions, colorPalette } from '@/utils/color'

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
    })
    onMounted(() => {
        console.log('props.product: ', props.product)
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

    const selectedColorOption = ref('')
    const selectedOrderAction = ref<OrderAction>()
    const isCartAddingSuccessful = ref<boolean>(false)

    // Product details to checkout
    const checkoutProductDetailsRef = ref<ProductDetails[]>()

    // File upload refs
    const uploadedOwnDesignFile = ref<File | null>(null)
    const fileInputRef = ref<HTMLInputElement | null>(null)

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
    const onSubmit = handleSubmit((values) => {
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
        }

        // Peek FormData key-values for debugging
        for (const pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1])
        }

        addToCartMutation.mutate(formData)
    })

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
        }
    })
</script>

<template>
    <TransitionRoot appear :show="isModalOpen">
        <Dialog as="div" class="relative z-[999]" :open="isModalOpen" @close="onDialogClose">
            <!-- Modal overlay -->
            <div class="fixed inset-0 overflow-y-auto transition-opacity dark:bg-black/60">
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
                            class="w-[600px] max-w-7xl h-[30rem] transform overflow-y-auto p-6 text-left align-middle shadow-xl transition-all"
                            :class="[isDark ? 'bg-zinc-900 border border-zinc-700' : 'bg-white']"
                        >
                            <DialogTitle
                                as="h1"
                                class="text-2xl"
                                :class="isDark ? 'text-gray-100' : 'text-gray-900'"
                            >
                                Product Order Option
                            </DialogTitle>

                            <div class="space-y-7">
                                <!-- T-shirt Section -->
                                <div>
                                    <div class="flex flex-col mb-5 text-sm">
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
                                    <div
                                        v-if="props.product.designs && props.product.designs.length"
                                        class="w-full flex"
                                    >
                                        <div class="w-full aspect-video relative">
                                            <img
                                                :src="props.product.designs[0].temp_url"
                                                alt="Product Design"
                                                class="w-full h-full object-contain absolute inset-0"
                                                :class="isDark ? 'bg-zinc-800' : 'bg-white'"
                                            />
                                        </div>
                                    </div>

                                    <form @submit.prevent="onSubmit" class="space-y-4">
                                        <!-- Fabric Type Input -->
                                        <!--
                                        <div class="mb-5">
                                            <label :class="[
                                                    'block text-sm mb-1',
                                                    isDark ? 'text-gray-400' : 'text-gray-600'
                                                ]">
                                                Fabric Type:
                                            </label>
                                            <div class="flex gap-2">
                                                <select
                                                    v-model="fabricTypeId"
                                                    :class="[
                                                        'font-medium w-full border px-3 py-2 rounded mt-1',
                                                        isDark
                                                            ? 'bg-zinc-900 border-zinc-600 text-gray-100'
                                                            : ''
                                                    ]"
                                                >
                                                    <option :value="null" disabled>
                                                        Select fabric type
                                                    </option>
                                                    <option
                                                        v-for="fab in fabricTypes"
                                                        :key="fab.id"
                                                        :value="fab.id"
                                                    >
                                                        {{ fab.name }}
                                                    </option>
                                                </select>
                                            </div>
                                            <p class="text-sm text-red-500 mt-1">
                                                {{ fabricTypeError }}
                                            </p>
                                        </div>
                                        -->

                                        <!-- Upload your own design field -->
                                        <div class="my-10">
                                            <p
                                                :class="[
                                                    'text-sm mb-3',
                                                    isDark ? 'text-gray-400' : 'text-gray-600',
                                                ]"
                                            >
                                                Upload your own design (if preferred)
                                            </p>
                                            <input
                                                ref="fileInputRef"
                                                @change="handleFileUpload"
                                                type="file"
                                                accept="image/*"
                                                class="w-full"
                                            />

                                            <!-- Image Preview -->
                                            <div v-if="ownDesignPreviewUrl" class="mt-4">
                                                <div class="relative inline-block">
                                                    <img
                                                        :src="ownDesignPreviewUrl"
                                                        alt="Design Preview"
                                                        class="max-w-full h-auto max-h-[200px] rounded-md border"
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
                                        </div>

                                        <!-- Color Option -->
                                        <div class="mb-8">
                                            <label
                                                :class="[
                                                    'block text-sm mb-1',
                                                    isDark ? 'text-gray-400' : 'text-gray-600',
                                                ]"
                                            >
                                                Color:
                                            </label>
                                            <div class="flex gap-2">
                                                <!-- Select Dropdown -->
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
                                                    class="w-6 h-6 rounded border shrink-0"
                                                    :class="
                                                        isDark
                                                            ? 'border-zinc-700'
                                                            : 'border-gray-300'
                                                    "
                                                    :style="{
                                                        backgroundColor:
                                                            swatchColor || 'transparent',
                                                    }"
                                                    :title="
                                                        swatchColor
                                                            ? `Preview: ${swatchColor}`
                                                            : 'No color selected/invalid color'
                                                    "
                                                ></div>

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

                                        <!-- Size Selection -->
                                        <div v-if="props.product.fabric_type" class="mb-5">
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
                                                        :class="
                                                            isDark
                                                                ? 'text-gray-400'
                                                                : 'text-gray-500' +
                                                                  ' text-sm italic'
                                                        "
                                                    >
                                                        Loading sizes...
                                                    </span>
                                                </template>
                                            </div>
                                            <p class="text-sm text-red-500 mt-1">
                                                {{ sizeIdError }}
                                            </p>
                                        </div>

                                        <!-- Shopee-style quantity field copied from cart view -->
                                        <div class="mb-5">
                                            <label
                                                :class="[
                                                    'block text-sm mb-1',
                                                    isDark ? 'text-gray-400' : 'text-gray-600',
                                                ]"
                                            >
                                                Quantity:
                                            </label>
                                            <div class="flex w-full h-10">
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
                                                    :value="quantity"
                                                    type="text"
                                                    min="1"
                                                    :class="[
                                                        'w-full max-w-[40px] appearance-none text-center focus:outline-none focus:ring-0 px-0 py-0 font-medium text-base',
                                                        isDark
                                                            ? 'bg-zinc-900 border-t border-b border-zinc-700 text-gray-100'
                                                            : 'bg-white border-t border-b border-[#ccc]',
                                                    ]"
                                                    style="height: 40px"
                                                    readonly
                                                    tabindex="-1"
                                                />
                                                <button
                                                    type="button"
                                                    :class="[
                                                        'flex items-center justify-center border rounded-r w-10 h-full text-lg font-semibold select-none transition-colors duration-150',
                                                        isDark
                                                            ? 'bg-gray-900 border-zinc-700 text-gray-200 hover:bg-zinc-700'
                                                            : 'bg-[#f1f1f1] border-[#ccc] hover:bg-[#e1e1e1]',
                                                    ]"
                                                    @click="incrementQuantity"
                                                    aria-label="Increase"
                                                >
                                                    <span>+</span>
                                                </button>
                                            </div>
                                            <p class="text-sm text-red-500 mt-1">
                                                {{ quantityError }}
                                            </p>
                                        </div>

                                        <div class="flex justify-end items-center gap-5">
                                            <button
                                                @click="
                                                    selectedOrderAction = OrderAction.ADD_TO_CART
                                                "
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

    <!-- <OrderProductModal
        v-if="showOrderProductModal"
        :product="{}"
        @close="showOrderProductModal = false"
    /> -->
</template>
