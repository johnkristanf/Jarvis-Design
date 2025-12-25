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

    const props = defineProps<{
        selectedProductsData: ProductDetails[] | null
    }>()

    onMounted(() => {
        console.log('QrCodePaymentModal selectedProductsData:', props.selectedProductsData)
    })

    // -- Emits updated to match flexible array of { productId, file } --
    const emit = defineEmits(['close', 'place_order', 'fileSelected'])
    const handleCloseModal = () => emit('close')
    const handleTriggerPlaceOrder = () => emit('place_order')
    const toast = useToast()

    // One upload area per product (use a map for files, previews, and refs)
    const paymentFiles = reactive<Record<number, File | null>>({})
    const previewUrls = reactive<Record<number, string | undefined>>({})
    const fileInputRefs = reactive<Record<number, HTMLInputElement | null>>({})

    // Maintain a flexible array for all file selections
    const selectedPaymentProofs = ref<{ productId: number, file: File | null }[]>([])

    // Helper to return the array for emit and keep it normalized
    const updateSelectedPaymentProofs = () => {
        selectedPaymentProofs.value = (props.selectedProductsData || []).map(product => ({
            productId: product.id,
            file: paymentFiles[product.id] || null,
        }))

        console.log("selectedPaymentProofs: ", selectedPaymentProofs);
        
        emit('fileSelected', selectedPaymentProofs.value)
    }

    const triggerFileSelect = (id: number) => {
        console.log("ID SA FILE SELECT PRODUCT: ", id);
        
        nextTick(() => {
            fileInputRefs[id]?.click()
        })
    }

    const handleFileChange = (e: Event, id: number) => {
        console.log("ID SA PRODUCT: ", id);
        
        const target = e.target as HTMLInputElement
        if (target && target.files && target.files[0]) {
            const selectedFile = target.files[0]

            if (!selectedFile.type.startsWith('image/')) {
                toast.add({
                    severity: 'warn',
                    summary: 'Only image files are allowed (jpg, png, etc.)',
                    life: 1500,
                })
                target.value = ''
                return
            }

            paymentFiles[id] = selectedFile
            previewUrls[id] = URL.createObjectURL(selectedFile) || undefined

            updateSelectedPaymentProofs()
        }
    }

    const clearFile = (id: number) => {
        paymentFiles[id] = null
        previewUrls[id] = undefined
        const inputRef = fileInputRefs[id]
        if (inputRef) inputRef.value = ''
        updateSelectedPaymentProofs()
    }

    // Can only place order if ALL products have an image attached
    const isAllPaymentsAttached = computed(() => {
        if (!props.selectedProductsData) return false
        return props.selectedProductsData.every(
            prod => paymentFiles[prod.id] && previewUrls[prod.id]
        )
    })
    const getProductTotal = (p: ProductDetails) => {
        const qty = p.desired_quantity ?? 1
        const unitPrice = Number(p.unit_price || 0)
        return qty * unitPrice
    }

    // Prepare maps if not set
    onMounted(() => {
        if (props.selectedProductsData) {
            props.selectedProductsData.forEach(product => {
                if (!(product.id in paymentFiles)) paymentFiles[product.id] = null
                if (!(product.id in previewUrls)) previewUrls[product.id] = undefined
                if (!(product.id in fileInputRefs)) fileInputRefs[product.id] = null
            })
            updateSelectedPaymentProofs()
        }
    })
</script>

<template>
    <TransitionRoot appear :show="true" as="template">
        <Dialog as="div" @close="handleCloseModal" class="relative z-[9999]">
            <TransitionChild
                as="template"
                enter="duration-300 ease-out"
                enter-from="opacity-0"
                enter-to="opacity-100"
                leave="duration-200 ease-in"
                leave-from="opacity-100"
                leave-to="opacity-0"
            >
                <div class="fixed inset-0 bg-black/50" />
            </TransitionChild>

            <div class="fixed inset-0 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4 text-center">
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
                            class="w-[1000px] max-w-7xl transform overflow-hidden rounded-2xl bg-white p-6 mb-8 text-left align-middle shadow-xl transition-all"
                        >
                            <DialogTitle
                                as="h3"
                                class="text-lg font-medium leading-6 text-gray-900"
                            >
                                Scan Payment Method QR Code
                            </DialogTitle>

                            <div class="mt-2">
                                <template v-if="props.selectedProductsData && props.selectedProductsData.length === 1">
                                    <p class="text-sm text-gray-500">
                                        Product Name: {{ props.selectedProductsData[0].name }}
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        Quantity: {{ props.selectedProductsData[0].desired_quantity ?? 1 }}
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        Total Price: ₱{{ getProductTotal(props.selectedProductsData[0]) }}
                                    </p>
                                </template>
                                <template v-else-if="props.selectedProductsData && props.selectedProductsData.length > 1">
                                    <div>
                                        <p class="text-sm text-gray-500 mb-1">
                                            <span class="font-bold">Order includes the following products:</span>
                                        </p>
                                        <ul class="ml-2 text-xs text-gray-500 list-disc">
                                            <li v-for="product in props.selectedProductsData" :key="product.id">
                                                {{ product.name }} - Qty: {{ product.desired_quantity ?? 1 }}, ₱{{ getProductTotal(product) }}
                                            </li>
                                        </ul>
                                    </div>
                                </template>
                            </div>

                            <p class="text-sm text-center my-5">
                                <strong class="text-lg">Note:</strong>
                                <br />
                                A minimum payment of
                                <strong>50% of the total amount</strong>
                                is required. Orders with payments below this threshold will not be
                                approved or processed.
                            </p>

                            <div
                                v-if="props.selectedProductsData && props.selectedProductsData.length"
                                class="flex flex-col gap-10"
                            >
                                <div
                                    v-for="product in props.selectedProductsData"
                                    :key="product.id"
                                    class="border border-gray-200 rounded-xl shadow-sm p-6 flex flex-col gap-5 bg-gray-50"
                                >
                                    <!-- Product Info and QR Codes (VERTICAL alignment, no flex-row) -->
                                    <div class="mb-2">
                                        <h2 class="font-bold">{{ product.id }}</h2>
                                        <h2 class="font-bold">{{ product.name }}</h2>
                                        <div class="text-sm text-gray-600">
                                            Quantity: <b>{{ product.desired_quantity ?? 1 }}</b>
                                            <br>
                                            Price per unit: ₱{{ product.unit_price }}
                                            <br>
                                            <span>
                                                Total Price:
                                                <span class="font-bold text-blue-700">
                                                    ₱{{ getProductTotal(product) }}
                                                </span>
                                            </span>
                                        </div>
                                    </div>
                                    <!-- QR Codes in a row but block context -->
                                    <div class="flex items-center justify-center gap-12 h-full mb-0">
                                        <div class="flex flex-col items-center justify-center">
                                            <img
                                                src="/jarvis-gcash-qr.webp"
                                                alt="Generated QR CODE"
                                                width="180"
                                            />
                                            <h1 class="text-gray-500 text-sm">JA**N S.</h1>
                                            <h1 class="text-gray-500 flex gap-2">
                                                <p class="text-blue-600">Gcash</p>
                                            </h1>
                                        </div>
                                        <div class="flex flex-col items-center justify-center">
                                            <img src="/jd-maya.jpeg" alt="Generated QR CODE" width="180" />
                                            <h1 class="text-gray-500 text-sm">Roanne Mae Na Anunciado</h1>
                                            <h1 class="text-gray-500 flex gap-2">
                                                <p class="text-green-600">Maya</p>
                                            </h1>
                                        </div>
                                        <div class="flex flex-col items-center justify-center">
                                            <img
                                                src="/jd-union-pay-1.jpeg"
                                                alt="Generated QR CODE"
                                                width="180"
                                            />
                                            <h1 class="text-gray-500 text-sm">Roanne Mae Na Anunciado</h1>
                                            <h1 class="text-gray-500 flex gap-2">
                                                <p class="text-orange-600">UnionBank</p>
                                            </h1>
                                        </div>
                                    </div>
                                    <!-- Payment Screenshot Upload, full width, VERTICAL under QR -->
                                    <div class="flex flex-col gap-2 w-full">
                                        <label class="text-xs font-medium mb-1 text-gray-700">Screenshot of Payment Confirmation</label>
                                        <div
                                            class="w-full border-2 border-dashed border-gray-300 rounded-md p-4 flex flex-col items-center justify-center relative h-[170px] bg-white"
                                        >
                                            <div v-if="previewUrls[product.id]" class="relative w-full h-full">
                                                <img
                                                    :src="previewUrls[product.id]!"
                                                    alt="Payment Preview"
                                                    class="w-full h-full object-cover rounded-md"
                                                />
                                                <button
                                                    @click="clearFile(product.id)"
                                                    class="absolute top-[-12px] right-0 text-red-800 text-xl rounded-md p-1 hover:cursor-pointer"
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
                                                    class="w-12 h-12 text-gray-400"
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
                                                <p class="text-sm text-gray-600 mt-2">
                                                    Screenshot of Payment Confirmation
                                                </p>
                                                <input
                                                    :ref="el => { fileInputRefs[product.id] = el }"
                                                    type="file"
                                                    accept="image/*"
                                                    class="hidden"
                                                    @change="e => handleFileChange(e, product.id)"
                                                />
                                                <button
                                                    type="button"
                                                    @click="() => triggerFileSelect(product.id)"
                                                    class="mt-3 px-4 py-2 bg-white border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none"
                                                >
                                                    Select File
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="py-10">
                                <h1 class="text-center text-gray-500">No product selected.</h1>
                            </div>

                            <div class="mt-4 flex justify-end gap-3">
                                <button
                                    type="button"
                                    @click="handleCloseModal"
                                    class="inline-flex justify-center rounded-md bg-black px-4 py-2 text-sm font-medium text-white hover:opacity-75 hover:cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="button"
                                    :disabled="!isAllPaymentsAttached"
                                    @click="handleTriggerPlaceOrder"
                                    :class="[
                                        'inline-flex justify-center rounded-md border border-transparent px-4 py-2 text-sm font-medium text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2',
                                        !isAllPaymentsAttached
                                            ? 'bg-gray-400 hover:cursor-not-allowed'
                                            : 'bg-gray-900 hover:opacity-75 hover:cursor-pointer ',
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
