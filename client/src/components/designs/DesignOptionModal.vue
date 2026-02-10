<script lang="ts" setup>
    import { ref, computed, watch, onMounted, nextTick } from 'vue'
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

    const props = defineProps({
        product: {
            type: Object as PropType<Product>,
            required: true,
        },
    })
    onMounted(() => {
        console.log("props.product: ", props.product);
        
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
        fabricTypeId: yup.number().required('Fabric type is required'),
        sizeId: yup.number().required('Size is required'),
        quantity: yup
            .number()
            .required('Quantity is required')
            .min(1, 'Minimum quantity is 1')
            .typeError('Quantity must be a number'),
    })

    const { handleSubmit, resetForm } = useForm({ validationSchema: schema })

    // Field bindings
    const { value: fabricTypeId, errorMessage: fabricTypeError } = useField<number>('fabricTypeId')
    const { value: sizeId, errorMessage: sizeIdError } = useField<number>('sizeId')
    const { value: color, errorMessage: colorError } = useField<string>('color')
    const { value: quantity, errorMessage: quantityError, setValue: setQuantity } = useField<number>('quantity', undefined, { initialValue: 1 })

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
        formData.append('fabric_type_id', values.fabricTypeId.toString())

        if (props.product.fabric_type) {
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
                                Product Order Option
                            </DialogTitle>

                            <div class="space-y-7">
                                <!-- T-shirt Section -->
                                <div>
                                    <div class="flex flex-col mb-5 text-sm">
                                        <p class="font-medium text-gray-700">
                                            Product:
                                            <strong>{{ props.product.name }}</strong>
                                        </p>
                                        <p class="font-medium text-gray-700">
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
                                                class="w-full h-full object-contain absolute inset-0 bg-white"
                                            />
                                        </div>
                                    </div>

                                    <form @submit.prevent="onSubmit" class="space-y-4">
                                        <!-- Fabric Type Input -->
                                        <!-- <div class="mb-5">
                                            <label class="block text-sm text-gray-600 mb-1">
                                                Fabric Type:
                                            </label>
                                            <div class="flex gap-2">
                                                <select
                                                    v-model="fabricTypeId"
                                                    class="font-medium w-full border px-3 py-2 rounded mt-1"
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
                                        </div> -->

                                        <!-- Color Option -->
                                        <div class="mb-8">
                                            <label class="block text-sm text-gray-600 mb-1">
                                                Color:
                                            </label>
                                            <div class="flex gap-2">
                                                <!-- Select Dropdown -->
                                                <select
                                                    v-model="selectedColorOption"
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
                                                    class="w-full px-3 py-2 border font-medium border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500"
                                                />

                                                <!-- Auto-set if dropdown selected -->
                                                <input
                                                    v-else
                                                    :value="color"
                                                    disabled
                                                    class="w-full px-3 py-2 border font-medium border-gray-300 rounded-md bg-gray-100 cursor-not-allowed"
                                                />
                                            </div>
                                        </div>

                                        <!-- Size Selection -->
                                        <div
                                            v-if="props.product.fabric_type"
                                            class="mb-5"
                                        >
                                            <label class="block text-sm text-gray-600 mb-1">
                                                Size:
                                            </label>
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <template v-if="!loadingSizes">
                                                    <button
                                                        v-for="size in sizes"
                                                        :key="size.id"
                                                        class="inline-flex items-center px-2 py-0.5 rounded-md text-sm font-semibold border transition-colors"
                                                        :class="
                                                            sizeId === size.id
                                                                ? 'bg-blue-600 text-white border-blue-600'
                                                                : 'bg-gray-100 text-gray-700 border-gray-300 hover:opacity-75'
                                                        "
                                                        type="button"
                                                        @click="sizeId = size.id"
                                                    >
                                                        {{ size.name }}
                                                    </button>
                                                </template>
                                                <template v-else>
                                                    <span class="text-gray-500 text-sm italic">
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
                                            <label class="block text-sm text-gray-600 mb-1">
                                                Quantity:
                                            </label>
                                            <div class="flex w-full h-10">
                                                <button
                                                    type="button"
                                                    class="flex items-center justify-center bg-[#f1f1f1] border border-[#ccc] rounded-l w-10 h-full text-lg font-semibold select-none transition-colors duration-150 hover:bg-[#e1e1e1] disabled:opacity-50"
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
                                                    class="w-full max-w-[40px] appearance-none border-t border-b border-[#ccc] bg-white text-center focus:outline-none focus:ring-0 px-0 py-0 font-medium text-base"
                                                    style="height: 40px"
                                                    readonly
                                                    tabindex="-1"
                                                />
                                                <button
                                                    type="button"
                                                    class="flex items-center justify-center bg-[#f1f1f1] border border-[#ccc] rounded-r w-10 h-full text-lg font-semibold select-none transition-colors duration-150 hover:bg-[#e1e1e1]"
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
                                                class="flex items-center gap-1 px-4 py-2 bg-gray-900 hover:opacity-75 hover:cursor-pointer text-white text-xs font-semibold rounded transition-colors"
                                                :disabled="addToCartMutation.isPending.value"
                                            >
                                                <ShoppingCartIcon class="size-4" />
                                                Add to Cart
                                            </button>

                                            <button
                                                @click="selectedOrderAction = OrderAction.BUY_NOW"
                                                type="submit"
                                                class="flex items-center gap-1 px-4 py-2 bg-blue-600 hover:opacity-75 hover:cursor-pointer text-white text-xs font-semibold rounded transition-colors"
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
