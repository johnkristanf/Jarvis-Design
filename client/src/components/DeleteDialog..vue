<script lang="ts" setup>
    import { ref } from 'vue'
    import {
        TransitionRoot,
        TransitionChild,
        Dialog,
        DialogPanel,
        DialogTitle,
    } from '@headlessui/vue'
    import { useMutation, useQueryClient } from '@tanstack/vue-query'
    import { apiService } from '@/api/axios'

    // Props
    const props = defineProps<{
        selectedID: number
        endpoint_url: string
        query_key: string
    }>()

    const isOpen = ref(false)

    // Vue Query: Setup Query Client
    const queryClient = useQueryClient()

    // Define the mutation for deleting a record
    const deleteMutation = useMutation({
        mutationFn: async (id: number) => {
            return await apiService.delete(`${props.endpoint_url}/${id}`)
        },
        onSuccess: () => {
            // Invalidate queries to refresh data after deletion
            queryClient.invalidateQueries({ queryKey: [props.query_key] })
            closeModal()
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
        <button @click="openModal" class="text-red-600 hover:underline">Delete</button>
    </div>

    <TransitionRoot appear :show="isOpen" as="template">
        <Dialog as="div" @close="closeModal" class="relative z-10">
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
                            class="w-full max-w-md transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all"
                        >
                            <DialogTitle
                                as="h3"
                                class="text-lg font-medium leading-6 text-gray-900"
                            >
                                Confirm Deletion
                            </DialogTitle>

                            <div class="mt-2">
                                <p class="text-sm text-gray-600">
                                    Are you sure you want to delete this record?
                                </p>
                            </div>

                            <div class="mt-4 flex justify-end space-x-3">
                                <!-- Cancel Button -->
                                <button
                                    type="button"
                                    class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100"
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
                            <p v-if="deleteMutation.isError.value" class="mt-3 text-sm text-red-500">
                                Failed to delete. Please try again.
                            </p>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>
