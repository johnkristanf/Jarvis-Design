<script lang="ts" setup>
    import { formatDateWithTime, getStatusBadgeClass } from '@/helper/order'
    import { OrderOptions, type Orders } from '@/types/order'
    import {
        TransitionRoot,
        TransitionChild,
        Dialog,
        DialogPanel,
        DialogTitle,
    } from '@headlessui/vue'

    defineProps<{
        orderDetails: Orders
        isOpen: boolean
    }>()

    const emit = defineEmits(['close'])
    const handleCloseModal = () => emit('close')
</script>

<template>
    <TransitionRoot appear :show="isOpen" as="template">
        <Dialog as="div" @close="handleCloseModal" class="relative z-10">
            <TransitionChild
                as="template"
                enter="duration-300 ease-out"
                enter-from="opacity-0"
                enter-to="opacity-100"
                leave="duration-200 ease-in"
                leave-from="opacity-100"
                leave-to="opacity-0"
            >
                <div class="fixed inset-0 bg-black/25" />
            </TransitionChild>

            <div class="fixed inset-0 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
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
                            class="w-full max-w-3xl transform overflow-hidden bg-white shadow-2xl transition-all"
                        >
                            <!-- Header -->
                            <div class="bg-gray-900 text-white p-6 border-b border-gray-200">
                                <div class="flex items-center justify-between">
                                    <DialogTitle as="h2" class="text-xl font-bold">
                                        Order Details
                                    </DialogTitle>
                                    <button
                                        @click="handleCloseModal"
                                        class="text-white hover:text-gray-300 transition-colors"
                                    >
                                        <svg
                                            class="w-6 h-6"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12"
                                            />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Content -->
                            <div class="p-6 max-h-96 overflow-y-auto">
                                <!-- Order Header Info -->
                                <div
                                    class="grid grid-cols-2 gap-4 mb-6 pb-6 border-b border-gray-200"
                                >
                                    <div>
                                        <h3
                                            class="text-sm font-medium text-gray-600 uppercase tracking-wide"
                                        >
                                            Order Number
                                        </h3>
                                        <p class="text-lg font-bold text-black mt-1">
                                            {{ orderDetails.order_number }}
                                        </p>
                                    </div>
                                    <div>
                                        <h3
                                            class="text-sm font-medium text-gray-600 uppercase tracking-wide"
                                        >
                                            Status
                                        </h3>
                                        <div class="mt-1">
                                            <span :class="getStatusBadgeClass(orderDetails.status)">
                                                {{ orderDetails.status.toUpperCase() }}
                                            </span>
                                        </div>
                                    </div>
                                    <div>
                                        <h3
                                            class="text-sm font-medium text-gray-600 uppercase tracking-wide"
                                        >
                                            Created
                                        </h3>
                                        <p class="text-sm text-black mt-1">
                                            {{ formatDateWithTime(orderDetails.created_at) }}
                                        </p>
                                    </div>
                                    <div>
                                        <h3
                                            class="text-sm font-medium text-gray-600 uppercase tracking-wide"
                                        >
                                            Delivery Date
                                        </h3>
                                        <p class="text-sm text-black mt-1">
                                            {{
                                                orderDetails.delivery_date
                                                    ? formatDateWithTime(orderDetails.delivery_date)
                                                    : 'N/A'
                                            }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Customer Info -->
                                <div class="mb-6 pb-6 border-b border-gray-200">
                                    <h3
                                        class="text-lg font-semibold text-black mb-3 border-l-4 border-black pl-3"
                                    >
                                        Customer Information
                                    </h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <p class="text-sm text-gray-600">Name</p>
                                            <p class="font-medium text-black">
                                                {{ orderDetails.user?.name }}
                                            </p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-600">Phone</p>
                                            <p class="font-medium text-black">
                                                {{ orderDetails.phone_number }}
                                            </p>
                                        </div>
                                        <div class="md:col-span-2">
                                            <p class="text-sm text-gray-600">Address</p>
                                            <p class="font-medium text-black">
                                                {{ orderDetails.address }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Product Info -->
                                <div class="mb-6 pb-6 border-b border-gray-200">
                                    <h3
                                        class="text-lg font-semibold text-black mb-3 border-l-4 border-black pl-3"
                                    >
                                        Product Details
                                    </h3>
                                    <div class="flex gap-4">
                                        <!-- Product Image -->
                                        <div
                                            v-if="orderDetails.image_path || orderDetails.temp_url"
                                            class="flex-shrink-0"
                                        >
                                            <img
                                                :src="
                                                    orderDetails.temp_url || orderDetails.image_path
                                                "
                                                :alt="`Design ${orderDetails.design_id}`"
                                                class="w-20 h-20 object-cover border-2 border-gray-300"
                                            />
                                        </div>

                                        <!-- Product Details -->
                                        <div class="flex-1">
                                            <div class="grid grid-cols-2 gap-4">
                                                <div>
                                                    <p class="text-sm text-gray-600">Color</p>
                                                    <div class="flex items-center gap-2">
                                                        <p class="font-medium text-black">
                                                            {{ orderDetails.color }}
                                                        </p>
                                                    </div>
                                                </div>
                                                <div>
                                                    <p class="text-sm text-gray-600">Option</p>
                                                    <p class="font-medium text-black">
                                                        {{
                                                            orderDetails.order_option ===
                                                            OrderOptions.DELIVERY
                                                                ? 'Delivery'
                                                                : 'Pick-up'
                                                        }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Sizes -->
                                    <div
                                        v-if="orderDetails.sizes && orderDetails.sizes.length > 0"
                                        class="mt-4"
                                    >
                                        <p class="text-sm text-gray-600 mb-2">Sizes & Quantities</p>
                                        <div class="flex flex-wrap gap-2">
                                            <div
                                                v-for="size in orderDetails.sizes"
                                                :key="size.id"
                                                class="bg-gray-100 border border-gray-300 px-3 py-1 text-sm"
                                            >
                                                <span class="font-medium">{{ size.name }}</span>
                                                <span v-if="size.pivot" class="text-gray-600 ml-1">
                                                    ({{ size.pivot.quantity || 'N/A' }})
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Pricing -->
                                <div class="mb-6">
                                    <h3
                                        class="text-lg font-semibold text-black mb-3 border-l-4 border-black pl-3"
                                    >
                                        Pricing
                                    </h3>
                                    <div class="bg-gray-50 p-4 border border-gray-200">
                                        <!-- <div class="flex justify-between items-center mb-2">
                                            <span class="text-gray-600">Paid Amount:</span>
                                            <span class="font-medium text-black">
                                                ₱{{ orderDetails.paid_amount }}
                                            </span>
                                        </div> -->

                                        <div class="flex justify-between items-center mb-2">
                                            <span class="text-gray-600">Unit Price:</span>
                                            <span class="font-medium text-black">
                                                ₱ {{ Math.floor(orderDetails.product_unit_price) }}
                                            </span>
                                        </div>

                                        <div class="flex justify-between items-center mb-2">
                                            <span class="text-gray-600">Total Quantity:</span>
                                            <span class="font-medium text-black">
                                                {{
                                                    orderDetails.solo_quantity
                                                        ? orderDetails.solo_quantity
                                                        : orderDetails.total_quantity
                                                }}
                                            </span>
                                        </div>
                                        <div class="border-t border-gray-300 pt-2 mt-2">
                                            <div class="flex justify-between items-center">
                                                <span class="font-semibold text-black">
                                                    Total Price:
                                                </span>
                                                <span class="font-bold text-xl text-black">
                                                    ₱ {{ Math.floor(orderDetails.total_price).toLocaleString() }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- User Info (if available) -->
                                <div v-if="orderDetails.user" class="mb-6">
                                    <h3
                                        class="text-lg font-semibold text-black mb-3 border-l-4 border-black pl-3"
                                    >
                                        Account Information
                                    </h3>
                                    <div class="bg-gray-50 p-4 border border-gray-200">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <p class="text-sm text-gray-600">Account Name</p>
                                                <p class="font-medium text-black">
                                                    {{ orderDetails.user.name }}
                                                </p>
                                            </div>
                                            <div>
                                                <p class="text-sm text-gray-600">Email</p>
                                                <p class="font-medium text-black">
                                                    {{ orderDetails.user.email }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>
