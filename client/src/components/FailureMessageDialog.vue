<script lang="ts" setup>
    import {
        TransitionRoot,
        TransitionChild,
        Dialog,
        DialogPanel,
        DialogTitle,
    } from '@headlessui/vue'
    import { ExclamationTriangleIcon } from '@heroicons/vue/20/solid'
    import { useRouter } from 'vue-router'

    const props = defineProps<{
        title: String
        message: String
        isUnauthenticated: boolean
        isOpen: boolean
        onClose: () => void
    }>()

    const router = useRouter()
    const emit = defineEmits(['close'])
    const handleClose = () => emit('close')

    const goToLoginPage = () => router.push('/auth/login')
</script>

<template>
    <TransitionRoot appear :show="isOpen" as="template">
        <Dialog as="div" @close="handleClose" class="relative z-10">
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
                            class="w-full max-w-md transform overflow-hidden rounded-2xl bg-white p-6 mb-8 text-left align-middle shadow-xl transition-all"
                        >
                            <DialogTitle
                                as="h3"
                                class="text-xl flex items-center font-medium leading-6 text-gray-900 gap-1"
                            >
                                <ExclamationTriangleIcon class="text-red-800 size-8" />
                                {{ props.title }}
                            </DialogTitle>

                            <div class="mt-2">
                                <p class="text-md text-gray-500">{{ props.message }}</p>
                            </div>

                            <div v-if="isUnauthenticated" class="mt-4">
                                <button
                                    @click="goToLoginPage"
                                    class="inline-flex justify-center rounded-md border border-transparent bg-gray-900 text-white px-4 py-2 text-sm font-medium text-blue-900 hover:opacity-75"
                                >
                                    Go to Login Page
                                </button>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>
