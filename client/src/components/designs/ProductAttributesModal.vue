<script setup>
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
        sizes: {
            type: [Array, null],
        },
        color: {
            type: String,
            required: true,
        },
        solo_quantity: {
            type: [Number, null],
        },
    })

    const isOpen = ref(false)

    function closeModal() {
        isOpen.value = false
    }
    function openModal() {
        isOpen.value = true
    }
</script>

<template>
    <div class="flex items-center justify-center">
        <button
            type="button"
            @click="openModal"
            class="flex items-center gap-2 rounded-md bg-gray-900 hover:cursor-pointer px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-500"
        >
            <EyeIcon class="size-5" />
            View
        </button>
    </div>

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
                            class="w-full max-w-md transform overflow-hidden rounded-2xl bg-white text-left align-middle shadow-xl transition-all"
                        >
                            <!-- Header -->
                            <div class="bg-gray-900 px-6 py-4">
                                <DialogTitle as="h3" class="text-lg font-semibold text-white">
                                    Product Attributes
                                </DialogTitle>
                            </div>

                            <!-- Content -->
                            <div class="px-6 py-4 space-y-4">
                                <!-- Color -->
                                <div
                                    class="flex items-center justify-between py-3 border-b border-gray-200"
                                >
                                    <span class="text-sm font-medium text-gray-900">Color</span>
                                    <span class="text-sm text-gray-600">{{ color }}</span>
                                </div>

                                <!-- Sizes (if available) -->
                                <div
                                    v-if="sizes && sizes.length > 0"
                                    class="py-3 border-b border-gray-200"
                                >
                                    <div class="text-sm font-medium text-gray-900 mb-2">Sizes</div>
                                    <div class="flex flex-wrap gap-2">
                                        <span
                                            v-for="size in sizes"
                                            :key="size.id"
                                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-900 text-white"
                                        >
                                            {{ size.name }}
                                            <span
                                                v-if="size.pivot && size.pivot.quantity"
                                                class="ml-1.5 text-gray-300"
                                            >
                                                × {{ size.pivot.quantity }}
                                            </span>
                                        </span>
                                    </div>
                                </div>

                                <!-- Solo Quantity (if available) -->
                                <div
                                    v-if="solo_quantity !== null"
                                    class="flex items-center justify-between py-3 border-b border-gray-200"
                                >
                                    <span class="text-sm font-medium text-gray-900">Quantity</span>
                                    <span
                                        class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-900 text-white"
                                    >
                                        {{ solo_quantity }}
                                    </span>
                                </div>

                                <!-- Summary -->
                                <div class="pt-2">
                                    <div class="text-xs text-gray-500">
                                        <span v-if="sizes && sizes.length > 0">
                                            Total items:
                                            {{
                                                sizes.reduce(
                                                    (sum, size) =>
                                                        sum + (size.pivot?.quantity || 0),
                                                    0,
                                                )
                                            }}
                                        </span>
                                        <span v-else-if="solo_quantity !== null">
                                            Total items: {{ solo_quantity }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer -->
                            <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3">
                                <button
                                    type="button"
                                    class="inline-flex justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-500 focus-visible:ring-offset-2"
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
</template>
