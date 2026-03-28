<script setup lang="ts">
    import { ref } from 'vue'
    import {
        TransitionRoot,
        TransitionChild,
        Dialog,
        DialogPanel,
        DialogTitle,
    } from '@headlessui/vue'
    import { EyeIcon } from '@heroicons/vue/20/solid'

    const props = defineProps({
        items: {
            type: Array as () => any[],
            required: true,
        },
    })

    const isOpen = ref(false)

    function closeModal() {
        isOpen.value = false
    }
    function openModal() {
        isOpen.value = true
    }

    // DESIGN IMAGE PREVIEW DIALOG
    const showDesignPreviewDialog = ref<boolean>(false)
    const selectedDesignImageUrl = ref<string>('')

    const handleShowDesignPreview = (tempUrl: string) => {
        selectedDesignImageUrl.value = tempUrl
        showDesignPreviewDialog.value = true
    }
</script>

<template>
    <div class="flex items-center justify-start ml-2">
        <button
            type="button"
            @click="openModal"
            class="flex items-center gap-2 rounded-md bg-gray-900 hover:cursor-pointer px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-500"
        >
            <EyeIcon class="size-5" />
            View
        </button>
    </div>

    <!-- MAIN MODAL -->
    <TransitionRoot appear :show="isOpen" as="template">
        <Dialog as="div" @close="closeModal" class="relative z-10">
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

            <div class="fixed inset-0 overflow-y-auto z-[999999]">
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
                            class="w-full max-w-4xl transform overflow-hidden rounded-2xl bg-white dark:bg-gray-900 text-left align-middle shadow-xl transition-all"
                        >
                            <!-- Header -->
                            <div class="bg-gray-900 px-6 py-4 flex justify-between items-center">
                                <DialogTitle as="h3" class="text-lg font-semibold text-white">
                                    Product Information
                                </DialogTitle>
                                <button
                                    type="button"
                                    class="text-gray-400 hover:text-white transition"
                                    @click="closeModal"
                                    aria-label="Close"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-6 w-6"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
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

                            <!-- Content Area -->
                            <div
                                class="p-6 max-h-[70vh] overflow-y-auto bg-gray-50 dark:bg-gray-800 space-y-6"
                            >
                                <div
                                    v-if="!items || items.length === 0"
                                    class="text-center text-gray-500 dark:text-gray-400 py-4"
                                >
                                    No items found.
                                </div>
                                <div
                                    v-for="(item, index) in items"
                                    :key="item.id || index"
                                    class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden"
                                >
                                    <div class="p-5 flex flex-col md:flex-row gap-6">
                                        <!-- Design Thumbnail -->
                                        <div class="shrink-0">
                                            <div
                                                class="h-32 w-32 md:h-40 md:w-40 bg-gray-100 dark:bg-gray-800 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700"
                                            >
                                                <img
                                                    v-if="item.temp_url"
                                                    :src="item.temp_url"
                                                    alt="Design Image"
                                                    class="h-full w-full object-cover cursor-pointer hover:opacity-80 transition-opacity"
                                                    @click="handleShowDesignPreview(item.temp_url)"
                                                />
                                                <div
                                                    v-else
                                                    class="h-full w-full flex items-center justify-center text-gray-400 dark:text-gray-500 text-sm"
                                                >
                                                    No Design
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Item Details -->
                                        <div class="flex-grow space-y-3">
                                            <!-- Title & Quantity -->
                                            <div
                                                class="flex justify-between items-start border-b border-gray-100 dark:border-gray-700 pb-3"
                                            >
                                                <div>
                                                    <h4
                                                        class="text-lg font-bold text-gray-900 dark:text-white"
                                                    >
                                                        {{
                                                            item.product?.name || 'Unknown Product'
                                                        }}
                                                    </h4>
                                                </div>
                                                <div class="text-right">
                                                    <div
                                                        class="inline-flex items-center px-3 py-1 rounded-full bg-blue-50 text-blue-700 font-semibold border border-blue-100"
                                                    >
                                                        x{{ item.total_quantity || 1 }}
                                                    </div>
                                                    <div
                                                        v-if="
                                                            Math.floor(
                                                                (item.total_quantity || 0) / 15,
                                                            ) > 0
                                                        "
                                                        class="mt-1 text-xs font-bold text-green-600"
                                                    >
                                                        (+
                                                        {{
                                                            Math.floor(
                                                                (item.total_quantity || 0) / 15,
                                                            )
                                                        }}
                                                        free)
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Attributes -->
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-1">
                                                <!-- Color -->
                                                <div>
                                                    <span
                                                        class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold tracking-wider"
                                                    >
                                                        Color
                                                    </span>
                                                    <p
                                                        class="font-medium text-gray-800 dark:text-gray-200"
                                                    >
                                                        {{ item.color || 'N/A' }}
                                                    </p>
                                                </div>

                                                <!-- Breakdown (Sizes or Solo Quantity) -->
                                                <div>
                                                    <span
                                                        class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold tracking-wider"
                                                    >
                                                        Breakdown
                                                    </span>
                                                    <div
                                                        v-if="item.sizes && item.sizes.length > 0"
                                                        class="mt-1 flex flex-wrap gap-1.5"
                                                    >
                                                        <span
                                                            v-for="size in item.sizes"
                                                            :key="size.id"
                                                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-600"
                                                        >
                                                            {{ size.name }}
                                                            <span
                                                                v-if="
                                                                    size.pivot &&
                                                                    size.pivot.quantity
                                                                "
                                                                class="ml-1 text-gray-500 font-normal"
                                                            >
                                                                ({{ size.pivot.quantity }})
                                                            </span>
                                                        </span>
                                                    </div>
                                                    <div
                                                        v-else-if="item.solo_quantity !== null"
                                                        class="mt-1"
                                                    >
                                                        <span
                                                            class="font-medium text-gray-800 dark:text-gray-200"
                                                        >
                                                            {{ item.solo_quantity }} items
                                                        </span>
                                                    </div>
                                                    <div
                                                        v-else
                                                        class="mt-1 text-sm text-gray-400 dark:text-gray-500 italic"
                                                    >
                                                        None specified
                                                    </div>
                                                </div>

                                                <!-- Selected Styles -->
                                                <div
                                                    v-if="
                                                        item.selected_product_styles &&
                                                        item.selected_product_styles.length > 0
                                                    "
                                                >
                                                    <span
                                                        class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold tracking-wider"
                                                    >
                                                        Selected Styles
                                                    </span>
                                                    <div class="mt-1 flex flex-wrap gap-1.5">
                                                        <span
                                                            v-for="style in item.selected_product_styles"
                                                            :key="style.id"
                                                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 border border-blue-100 dark:border-blue-800"
                                                        >
                                                            {{ style.name }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Customizations -->
                                            <div
                                                v-if="item.customization"
                                                class="mt-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800"
                                            >
                                                <span
                                                    class="text-xs text-yellow-700 dark:text-yellow-300 uppercase font-semibold tracking-wider"
                                                >
                                                    Customizations
                                                </span>
                                                <div
                                                    class="mt-1.5 flex flex-wrap gap-x-5 gap-y-1 text-sm"
                                                >
                                                    <span
                                                        v-if="item.customization.jersey_name"
                                                        class="text-gray-700 dark:text-gray-300"
                                                    >
                                                        Name:
                                                        <strong
                                                            class="text-gray-900 dark:text-white"
                                                        >
                                                            {{ item.customization.jersey_name }}
                                                        </strong>
                                                    </span>
                                                    <span
                                                        v-if="item.customization.jersey_number"
                                                        class="text-gray-700 dark:text-gray-300"
                                                    >
                                                        Number:
                                                        <strong
                                                            class="text-gray-900 dark:text-white"
                                                        >
                                                            {{ item.customization.jersey_number }}
                                                        </strong>
                                                    </span>
                                                    <span
                                                        v-if="item.customization.pocket_count"
                                                        class="text-gray-700 dark:text-gray-300"
                                                    >
                                                        Pockets:
                                                        <strong
                                                            class="text-gray-900 dark:text-white"
                                                        >
                                                            {{ item.customization.pocket_count }}
                                                        </strong>
                                                    </span>
                                                    <span
                                                        v-if="
                                                            item.customization.pocket_costs &&
                                                            Number(
                                                                item.customization.pocket_costs,
                                                            ) > 0
                                                        "
                                                        class="text-yellow-700 dark:text-yellow-400 font-semibold"
                                                    >
                                                        + ₱{{
                                                            Number(
                                                                item.customization.pocket_costs,
                                                            ).toFixed(2)
                                                        }}
                                                        (pocket costs)
                                                    </span>
                                                </div>
                                                <p
                                                    v-if="item.customization.additional_instruction"
                                                    class="text-xs italic text-gray-600 dark:text-gray-400 mt-1.5"
                                                >
                                                    <strong>Instructions:</strong>
                                                    {{ item.customization.additional_instruction }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer -->
                            <div class="bg-gray-100 dark:bg-gray-800 px-6 py-4 flex justify-end">
                                <button
                                    type="button"
                                    class="inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-5 py-2 text-sm font-medium text-gray-700 dark:text-white hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 transition"
                                    @click="closeModal"
                                >
                                    Close
                                </button>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>

    <!-- DESIGN IMAGE PREVIEW (Nested Dialog) -->
    <Teleport to="body">
        <div
            v-if="showDesignPreviewDialog"
            class="fixed inset-0 z-[9999999] flex items-center justify-center bg-black/80 backdrop-blur-sm"
            @click.self="showDesignPreviewDialog = false"
        >
            <div class="relative max-w-4xl w-full mx-4 shadow-2xl">
                <!-- Close Button over image -->
                <button
                    @click="showDesignPreviewDialog = false"
                    class="absolute -top-4 -right-4 md:-top-6 md:-right-6 bg-white text-gray-900 hover:bg-gray-200 rounded-full p-2 transition-colors duration-150 cursor-pointer shadow-lg z-10"
                    aria-label="Close Preview"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="w-6 h-6"
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
                <img
                    :src="selectedDesignImageUrl"
                    alt="Design Preview Expanded"
                    class="w-full h-auto max-h-[85vh] object-contain rounded-lg"
                />
            </div>
        </div>
    </Teleport>
</template>
