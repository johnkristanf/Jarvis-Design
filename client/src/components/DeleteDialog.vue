<script lang="ts" setup>
    import { onMounted, ref } from 'vue'
    import {
        TransitionRoot,
        TransitionChild,
        Dialog,
        DialogPanel,
        DialogTitle,
    } from '@headlessui/vue'
    import { useMutation, useQueryClient } from '@tanstack/vue-query'
    import { apiService } from '@/api/axios'
    import { useToast } from 'primevue'

    // Props
    const props = defineProps<{
        selectedID: number | string
        endpoint_url: string
        query_key: string
        success_message: string
        refresh_url?: string
    }>()

    const isOpen = ref(false)

    // Vue Query: Setup Query Client
    const queryClient = useQueryClient()
    const toast = useToast()

    // Define the mutation for deleting a record
    const deleteMutation = useMutation({
        mutationFn: async (id: number | string) => {
            return await apiService.delete(`${props.endpoint_url}/${encodeURIComponent(id)}`)
        },
        onSuccess: () => {
            // Invalidate queries to refresh data after deletion
            queryClient.invalidateQueries({ queryKey: [props.query_key] })

            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: props.success_message,
                life: 1500,
            })

            setTimeout(() => {
                if (props.refresh_url) {
                    window.location.href = props.refresh_url
                }
                closeModal()
            }, 1500)
        },
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        onError: (error: any) => {
            console.error('Failed to delete:', error)
        },
    })

    function closeModal() {
        isOpen.value = false
    }

    function openModal() {
        isOpen.value = true
    }

    function handleDelete() {
        deleteMutation.mutate(props.selectedID)
    }
</script>

<template>
    <div class="flex items-center justify-center">
        <slot :openModal="openModal">
            <button
                @click="openModal"
                class="flex items-center gap-1 px-2 py-1 bg-red-600 text-white rounded text-xs hover:cursor-pointer hover:opacity-75 transition"
                title="Delete"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-3 w-3"
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
                <span>Delete</span>
            </button>
        </slot>
    </div>

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
                <div class="fixed inset-0 bg-black/25" />
            </TransitionChild>

            <!-- Dialog Content -->
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
                            class="w-full max-w-md transform overflow-hidden rounded-2xl bg-white dark:bg-gray-900 border dark:border-gray-700 p-6 text-left align-middle shadow-xl transition-all"
                        >
                            <DialogTitle
                                as="h3"
                                class="text-lg font-medium leading-6 text-gray-900 dark:text-white"
                            >
                                Confirm Deletion
                            </DialogTitle>

                            <div class="mt-2">
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Are you sure you want to delete this record?
                                </p>
                            </div>

                            <div class="mt-4 flex justify-end space-x-3">
                                <!-- Cancel Button -->
                                <button
                                    type="button"
                                    class="rounded-md border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm font-medium text-gray-700 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800"
                                    @click="closeModal"
                                >
                                    Cancel
                                </button>

                                <!-- Delete Button -->
                                <button
                                    type="button"
                                    class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50"
                                    @click="handleDelete"
                                    :disabled="deleteMutation.isPending.value"
                                >
                                    <span v-if="deleteMutation.isPending.value">Deleting...</span>
                                    <span v-else>Delete</span>
                                </button>
                            </div>

                            <!-- Error Message -->
                            <p
                                v-if="deleteMutation.isError.value"
                                class="mt-3 text-sm text-red-500"
                            >
                                Failed to delete. Please try again.
                            </p>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>
