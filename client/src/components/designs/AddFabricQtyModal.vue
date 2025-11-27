<script setup>
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
    import { useToast } from 'primevue/usetoast'

    const props = defineProps({
        fabricId: String,
        fabricName: String,
    })

    const isOpen = ref(false)

    function closeModal() {
        isOpen.value = false
    }
    function openModal() {
        isOpen.value = true
    }

    const form = ref({
        delivery_date: '',
        quantity: null,
    })

    const isSubmitting = ref(false)
    const queryClient = useQueryClient()
    const toast = useToast()

    const mutation = useMutation({
        mutationFn: async (data) => {
            const respData = await apiService.put(
                `/api/add/fabric/quantity/${props.fabricId}`,
                data,
            )
            return respData
        },
        onSuccess: (response) => {
            toast.add({
                severity: 'success',
                summary: 'Fabric quantity added successfully',
                life: 3000,
            })

            queryClient.invalidateQueries({ queryKey: ['materials'] })
            closeModal()
        },

        onError: (error) => {
            console.error('Error adding new material:', error)
        },
    })

    function handleSubmit() {
        mutation.mutate({
            ...form.value,
            fabricName: props.fabricName,
        })
    }
</script>

<template>
    <div class="flex items-center justify-center">
        <button
            class="flex items-center gap-1 px-2 py-1 bg-teal-600 text-white hover:cursor-pointer hover:opacity-75 rounded hover:bg-green-700 transition"
            @click="openModal"
            title="Add Quantity"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-4 w-4"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 6v12m6-6H6"
                />
            </svg>
            <span class="text-xs">Add Qty</span>
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
                            class="w-full max-w-md transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all"
                        >
                            <DialogTitle
                                as="h3"
                                class="text-lg font-semibold leading-6 text-gray-900"
                            >
                                {{ fabricName }}
                            </DialogTitle>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    Add fabric quantities and delivery date for the new stock.
                                </p>
                            </div>

                            <form @submit.prevent="handleSubmit">
                                <div class="mt-4">
                                    <label
                                        for="delivery_date"
                                        class="block text-sm font-medium text-gray-700 mb-1"
                                    >
                                        Delivery Date
                                    </label>
                                    <input
                                        id="delivery_date"
                                        v-model="form.delivery_date"
                                        type="date"
                                        class="w-full rounded-md font-medium border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm p-2"
                                        required
                                    />
                                </div>
                                <div class="mt-4">
                                    <label
                                        for="quantity"
                                        class="block text-sm font-medium text-gray-700 mb-1"
                                    >
                                        Quantity
                                    </label>
                                    <input
                                        id="quantity"
                                        v-model.number="form.quantity"
                                        type="number"
                                        min="1"
                                        class="w-full rounded-md font-medium border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm p-2"
                                        required
                                    />
                                </div>
                                <div class="mt-6 flex justify-end">
                                    <button
                                        type="button"
                                        @click="closeModal"
                                        class="inline-flex justify-center rounded-md border border-gray-300 px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        type="submit"
                                        class="inline-flex justify-center rounded-md border border-transparent bg-gray-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:cursor-pointer hover:opacity-75 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                        :disabled="mutation.isPending.value"
                                    >
                                        <span v-if="mutation.isPending.value">Submitting...</span>
                                        <span v-else>Submit</span>
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
