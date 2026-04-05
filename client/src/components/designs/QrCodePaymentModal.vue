<script lang="ts" setup>
    import type { ProductDetails } from '@/types/order'
    import {
        TransitionRoot,
        TransitionChild,
        Dialog,
        DialogPanel,
        DialogTitle,
    } from '@headlessui/vue'
    import { ref, reactive, onMounted, computed, nextTick } from 'vue'
    import { useToast } from 'primevue/usetoast'
    import type { ProductIndexPayment } from '@/types/product'
    import { useFetchAuthenticatedUser } from '@/composables/useFetchAuthenticatedUser'

    const props = defineProps<{
        selectedProductsData: ProductDetails[] | null
    }>()

    onMounted(() => {
        console.log('QrCodePaymentModal selectedProductsData:', props.selectedProductsData)
    })

    // -- Emits updated to match single file structure --
    const emit = defineEmits(['close', 'place_order', 'fileSelected'])
    const handleCloseModal = () => emit('close')
    const handleTriggerPlaceOrder = () => emit('place_order')
    const toast = useToast()
    const { authStore } = useFetchAuthenticatedUser()

    // 1 Order = 1 Payment File
    const paymentFile = ref<File | null>(null)
    const previewUrl = ref<string | undefined>(undefined)
    const fileInputRef = ref<HTMLInputElement | null>(null)

    const triggerFileSelect = () => {
        const input = fileInputRef.value
        if (!input) return

        // Give the browser one tick + a little delay — mobile needs it more often
        setTimeout(() => {
            try {
                input.click()
            } catch (err) {
                console.warn('Direct click failed, trying focus + click', err)
                input.focus()
                input.click()
            }
        }, 80)
    }

    const handleFileChange = (e: Event) => {
        const target = e.target as HTMLInputElement
        if (target && target.files && target.files[0]) {
            const selectedFile = target.files[0]
            // if (!selectedFile.type.startsWith('image/')) {
            //     toast.add({
            //         severity: 'warn',
            //         summary: 'Only image files are allowed (jpg, png, etc.)',
            //         life: 1500,
            //     })
            //     target.value = ''
            //     return
            // }
            paymentFile.value = selectedFile
            previewUrl.value = URL.createObjectURL(selectedFile)
            emit('fileSelected', paymentFile.value)
        }
    }

    const clearFile = () => {
        paymentFile.value = null
        previewUrl.value = undefined
        if (fileInputRef.value) fileInputRef.value.value = ''
        emit('fileSelected', null)
    }

    const isAllPaymentsAttached = computed(() => {
        return !!paymentFile.value && !!previewUrl.value
    })

    const getProductTotal = (p: ProductDetails) => {
        const qty = p.desired_quantity ?? 1
        const unitPrice = Number(p.unit_price || 0)
        return qty * unitPrice
    }

    const grandTotal = computed(() => {
        let total = 0
        if (props.selectedProductsData) {
            total = props.selectedProductsData.reduce((acc, product) => {
                return acc + getProductTotal(product)
            }, 0)
        }

        const promptCredit = authStore.currentUser?.prompt_credit || 0
        return total + promptCredit
    })

    const totalPocketCosts = computed(() => {
        if (!props.selectedProductsData) return 0
        return props.selectedProductsData.reduce((sum, product) => {
            const cost = (product.customizations || []).reduce(
                (cSum: number, c: any) => cSum + (c.pocket_costs ? Number(c.pocket_costs) : 0),
                0,
            )
            return sum + cost
        }, 0)
    })
</script>

<template>
    <TransitionRoot appear :show="true" as="template">
        <Dialog as="div" @close="handleCloseModal" class="relative z-[9999]" :open="true">
            <TransitionChild
                as="template"
                enter="duration-300 ease-out"
                enter-from="opacity-0"
                enter-to="opacity-100"
                leave="duration-200 ease-in"
                leave-from="opacity-100"
                leave-to="opacity-0"
            >
                <div class="fixed inset-0 bg-black/60 dark:bg-black/80" />
            </TransitionChild>

            <div class="fixed inset-0 overflow-y-auto">
                <div
                    class="flex min-h-full items-center justify-center p-4 text-center bg-black/60 dark:bg-gray-900"
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
                            class="relative w-full max-w-[1000px] transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 dark:border dark:border-gray-700 p-4 sm:p-6 mb-8 text-left align-middle shadow-xl transition-all"
                        >
                            <!-- Overall Total Price Display (Absolute) -->
                            <div
                                v-if="props.selectedProductsData?.length"
                                class="absolute top-4 right-4 sm:top-6 sm:right-6 text-right"
                            >
                                <p
                                    class="text-[10px] sm:text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-semibold"
                                >
                                    Overall Total Price
                                </p>
                                <div class="flex items-baseline justify-end gap-2 flex-wrap">
                                    <p class="text-2xl sm:text-3xl font-bold text-blue-500">
                                        ₱{{
                                            grandTotal - (authStore.currentUser?.prompt_credit || 0)
                                        }}
                                    </p>
                                    <span
                                        v-if="totalPocketCosts > 0"
                                        class="text-sm text-yellow-500 dark:text-yellow-400 font-semibold"
                                    >
                                        + ₱{{ totalPocketCosts.toFixed(2) }} (pocket costs)
                                    </span>
                                </div>
                                <div
                                    v-if="
                                        authStore.currentUser &&
                                        typeof authStore.currentUser.prompt_credit === 'number' &&
                                        authStore.currentUser.prompt_credit > 0
                                    "
                                    class="text-[10px] text-red-500 dark:text-red-400 font-semibold"
                                >
                                    +{{ authStore.currentUser.prompt_credit }} (credits)
                                </div>
                            </div>

                            <DialogTitle
                                as="h3"
                                class="text-base sm:text-lg font-medium leading-6 text-gray-900 dark:text-gray-100 mb-4"
                            >
                                Scan Payment Method QR Code
                            </DialogTitle>

                            <div class="mt-2">
                                <template
                                    v-if="
                                        props.selectedProductsData &&
                                        props.selectedProductsData.length === 1
                                    "
                                >
                                    <p class="text-sm text-gray-500 dark:text-gray-300">
                                        Product Name: {{ props.selectedProductsData[0].name }}
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-300">
                                        Quantity:
                                        {{ props.selectedProductsData[0].desired_quantity ?? 1 }}
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-300">
                                        Total Price: ₱{{
                                            getProductTotal(props.selectedProductsData[0])
                                        }}
                                        <span
                                            v-if="
                                                (
                                                    props.selectedProductsData[0].customizations ||
                                                    []
                                                ).reduce(
                                                    (cSum: number, c: any) =>
                                                        cSum +
                                                        (c.pocket_costs
                                                            ? Number(c.pocket_costs)
                                                            : 0),
                                                    0,
                                                ) > 0
                                            "
                                            class="text-xs text-yellow-500 dark:text-yellow-400 font-semibold ml-1"
                                        >
                                            + ₱{{
                                                (props.selectedProductsData[0].customizations || [])
                                                    .reduce(
                                                        (cSum: number, c: any) =>
                                                            cSum +
                                                            (c.pocket_costs
                                                                ? Number(c.pocket_costs)
                                                                : 0),
                                                        0,
                                                    )
                                                    .toFixed(2)
                                            }}
                                            (pocket costs)
                                        </span>
                                    </p>
                                </template>
                                <template
                                    v-else-if="
                                        props.selectedProductsData &&
                                        props.selectedProductsData.length > 1
                                    "
                                >
                                    <div>
                                        <p class="text-sm text-gray-500 dark:text-gray-300 mb-1">
                                            <span class="font-bold">
                                                Order includes the following products:
                                            </span>
                                        </p>
                                        <ul
                                            class="ml-2 text-xs text-gray-500 dark:text-gray-300 list-disc"
                                        >
                                            <li
                                                v-for="(product, idx) in props.selectedProductsData"
                                                :key="`prod-${idx}`"
                                            >
                                                {{ product.name }}
                                                <span v-if="product.size?.name">
                                                    ({{ product.size?.name }})
                                                </span>
                                                - Qty: {{ product.desired_quantity ?? 1 }}, ₱{{
                                                    getProductTotal(product)
                                                }}
                                                <span
                                                    v-if="
                                                        (product.customizations || []).reduce(
                                                            (cSum: number, c: any) =>
                                                                cSum +
                                                                (c.pocket_costs
                                                                    ? Number(c.pocket_costs)
                                                                    : 0),
                                                            0,
                                                        ) > 0
                                                    "
                                                    class="text-yellow-500 dark:text-yellow-400 font-semibold"
                                                >
                                                    + ₱{{
                                                        (product.customizations || [])
                                                            .reduce(
                                                                (cSum: number, c: any) =>
                                                                    cSum +
                                                                    (c.pocket_costs
                                                                        ? Number(c.pocket_costs)
                                                                        : 0),
                                                                0,
                                                            )
                                                            .toFixed(2)
                                                    }}
                                                    (pocket costs)
                                                </span>
                                            </li>
                                        </ul>
                                    </div>
                                </template>
                            </div>

                            <p
                                class="text-xs sm:text-sm text-center my-4 sm:my-5 text-gray-700 dark:text-gray-200"
                            >
                                <strong class="text-base sm:text-lg">Note:</strong>
                                <br />
                                A minimum payment of
                                <strong>50% of the total amount</strong>
                                is required. Orders with payments below this threshold will not be
                                approved or processed.
                            </p>

                            <div
                                v-if="
                                    props.selectedProductsData && props.selectedProductsData.length
                                "
                                class="flex flex-col gap-6 sm:gap-10"
                            >
                                <div
                                    class="border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-4 sm:p-6 flex flex-col gap-4 sm:gap-5 bg-gray-50 dark:bg-gray-900"
                                >
                                    <!-- QR Codes - responsive grid -->
                                    <div
                                        class="grid grid-cols-1 sm:grid-cols-3 gap-6 sm:gap-8 lg:gap-12 mb-0"
                                    >
                                        <div class="flex flex-col items-center justify-center">
                                            <img
                                                src="/jarvis-gcash-qr.webp"
                                                alt="Generated QR CODE"
                                                class="w-40 sm:w-44 lg:w-48"
                                            />
                                            <h1
                                                class="text-gray-500 dark:text-gray-300 text-xs sm:text-sm mt-2"
                                            >
                                                JA**N S.
                                            </h1>
                                            <h1 class="text-gray-500 dark:text-gray-300 flex gap-2">
                                                <p
                                                    class="text-blue-600 dark:text-blue-500 text-xs sm:text-sm"
                                                >
                                                    Gcash
                                                </p>
                                            </h1>
                                        </div>
                                        <div class="flex flex-col items-center justify-center">
                                            <img
                                                src="/jd-maya.jpeg"
                                                alt="Generated QR CODE"
                                                class="w-40 sm:w-44 lg:w-48"
                                            />
                                            <h1
                                                class="text-gray-500 dark:text-gray-300 text-xs sm:text-sm mt-2"
                                            >
                                                Roanne Mae Na Anunciado
                                            </h1>
                                            <h1 class="text-gray-500 dark:text-gray-300 flex gap-2">
                                                <p
                                                    class="text-green-600 dark:text-green-400 text-xs sm:text-sm"
                                                >
                                                    Maya
                                                </p>
                                            </h1>
                                        </div>
                                        <div class="flex flex-col items-center justify-center">
                                            <img
                                                src="/jd-union-pay-1.jpeg"
                                                alt="Generated QR CODE"
                                                class="w-40 sm:w-44 lg:w-48"
                                            />
                                            <h1
                                                class="text-gray-500 dark:text-gray-300 text-xs sm:text-sm mt-2"
                                            >
                                                Roanne Mae Na Anunciado
                                            </h1>
                                            <h1 class="text-gray-500 dark:text-gray-300 flex gap-2">
                                                <p
                                                    class="text-orange-600 dark:text-orange-400 text-xs sm:text-sm"
                                                >
                                                    UnionBank
                                                </p>
                                            </h1>
                                        </div>
                                    </div>

                                    <!-- Payment Screenshot Upload -->
                                    <div
                                        class="flex flex-col gap-2 w-full mt-4 border-t pt-4 dark:border-gray-700"
                                    >
                                        <label
                                            class="text-xs sm:text-sm font-medium mb-1 text-gray-700 dark:text-gray-200"
                                        >
                                            Screenshot of Payment Confirmation
                                        </label>

                                        <!-- Hidden file input with mobile-friendly attributes -->
                                        <input
                                            ref="fileInputRef"
                                            type="file"
                                            accept="image/*"
                                            class="hidden"
                                            @change="handleFileChange"
                                        />

                                        <div
                                            class="w-full border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-md p-4 flex flex-col items-center justify-center relative min-h-[170px] bg-white dark:bg-gray-800"
                                        >
                                            <div
                                                v-if="previewUrl"
                                                class="relative w-full h-full flex justify-center"
                                            >
                                                <img
                                                    :src="previewUrl"
                                                    alt="Payment Preview"
                                                    class="h-48 object-contain rounded-md"
                                                />
                                                <button
                                                    type="button"
                                                    @click="clearFile"
                                                    class="absolute top-[-12px] right-0 text-red-800 dark:text-red-300 text-xl rounded-md p-1 hover:cursor-pointer focus:outline-none"
                                                    aria-label="Remove image"
                                                >
                                                    ✕
                                                </button>
                                            </div>
                                            <div
                                                v-else
                                                class="flex flex-col items-center justify-center text-center h-full w-full"
                                            >
                                                <svg
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    class="w-10 h-10 sm:w-12 sm:h-12 text-gray-400 dark:text-gray-500"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
                                                    />
                                                </svg>
                                                <p
                                                    class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 mt-2 px-2"
                                                >
                                                    Screenshot of Payment Confirmation for Entire
                                                    Order
                                                </p>
                                                <button
                                                    type="button"
                                                    @click="triggerFileSelect"
                                                    class="mt-3 px-4 py-2 touch-manipulation bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 active:bg-gray-100 dark:active:bg-gray-700"
                                                >
                                                    Select File
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="py-10">
                                <h1 class="text-center text-gray-500 dark:text-gray-300 text-sm">
                                    No product selected.
                                </h1>
                            </div>

                            <div class="mt-4 flex flex-col sm:flex-row justify-end gap-2 sm:gap-3">
                                <button
                                    type="button"
                                    @click="handleCloseModal"
                                    class="inline-flex touch-manipulation justify-center rounded-md bg-black dark:bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:opacity-75 active:opacity-60 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:focus-visible:ring-blue-400 focus-visible:ring-offset-2"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="button"
                                    :disabled="!isAllPaymentsAttached"
                                    @click="handleTriggerPlaceOrder"
                                    :class="[
                                        'inline-flex justify-center rounded-md border border-transparent px-4 py-2 text-sm font-medium text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:focus-visible:ring-blue-400 focus-visible:ring-offset-2',
                                        !isAllPaymentsAttached
                                            ? 'bg-gray-400 dark:bg-gray-700 cursor-not-allowed'
                                            : 'bg-gray-900 dark:bg-blue-900 hover:opacity-75 active:opacity-60',
                                    ]"
                                >
                                    Place Order
                                </button>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>
