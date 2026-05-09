<script lang="ts" setup>
    import {
        TransitionRoot,
        TransitionChild,
        Dialog,
        DialogPanel,
        DialogTitle,
    } from '@headlessui/vue'
    import { ref, onMounted } from 'vue'

    const props = defineProps<{
        isOpen: boolean
        title: string
        mode?: 'add' | 'edit'
        isSubmitting?: boolean
        submitText?: string
        cancelText?: string
    }>()

    const emit = defineEmits<{
        (e: 'update:isOpen', value: boolean): void
        (e: 'close'): void
        (e: 'submit'): void
    }>()

    const isDark = ref(false)
    onMounted(() => {
        if (typeof window !== 'undefined') {
            isDark.value =
                window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches

            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                isDark.value = e.matches
            })
        }
    })

    function closeModal() {
        emit('update:isOpen', false)
        emit('close')
    }

    function handleSubmit() {
        emit('submit')
    }
</script>

<template>
    <TransitionRoot appear :show="isOpen" as="template">
        <Dialog as="div" @close="closeModal" class="relative z-[9999]">
            <!-- Backdrop -->
            <TransitionChild
                as="template"
                enter="duration-300 ease-out"
                enter-from="opacity-0"
                enter-to="opacity-100"
                leave="duration-200 ease-in"
                leave-from="opacity-100"
                leave-to="opacity-0"
            >
                <div class="fixed inset-0 bg-black/50 transition-opacity" />
            </TransitionChild>

            <!-- Dialog Content -->
            <div class="fixed inset-0 overflow-y-auto">
                <div
                    class="flex min-h-full items-center justify-center p-4 text-center sm:p-6 lg:p-8"
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
                            class="w-full max-w-lg transform overflow-visible rounded-md border p-6 text-left align-middle shadow-xl transition-all"
                            :class="[
                                isDark ? 'bg-zinc-900 border-zinc-700' : 'bg-white border-gray-200',
                            ]"
                        >
                            <!-- Header -->
                            <div class="flex items-center justify-between mb-5">
                                <DialogTitle
                                    as="h3"
                                    class="text-xl font-semibold leading-6"
                                    :class="isDark ? 'text-gray-100' : 'text-gray-900'"
                                >
                                    {{ title }}
                                </DialogTitle>
                                <button
                                    type="button"
                                    @click="closeModal"
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

                            <!-- Form -->
                            <form @submit.prevent="handleSubmit">
                                <div class="mt-2 space-y-4">
                                    <slot></slot>
                                </div>

                                <div class="mt-6 flex justify-end space-x-3">
                                    <!-- Cancel Button -->
                                    <button
                                        type="button"
                                        class="rounded-md border px-4 py-2 text-sm font-medium transition-colors hover:cursor-pointer hover:opacity-75"
                                        :class="
                                            isDark
                                                ? 'border-gray-600 text-gray-200 hover:bg-gray-800'
                                                : 'border-gray-300 text-gray-700 hover:bg-gray-50'
                                        "
                                        @click="closeModal"
                                    >
                                        {{ cancelText || 'Cancel' }}
                                    </button>

                                    <!-- Submit Button -->
                                    <button
                                        type="submit"
                                        class="inline-flex justify-center rounded-md border border-transparent bg-gray-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:opacity-75 hover:cursor-pointer focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                                        :disabled="isSubmitting"
                                    >
                                        <span v-if="isSubmitting">Saving...</span>
                                        <span v-else>
                                            {{
                                                submitText || (mode === 'edit' ? 'Update' : 'Save')
                                            }}
                                        </span>
                                    </button>
                                </div>
                            </form>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>
