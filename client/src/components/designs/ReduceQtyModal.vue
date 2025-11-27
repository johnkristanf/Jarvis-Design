<script setup>
    import { ref } from 'vue'
    import {
        TransitionRoot,
        TransitionChild,
        Dialog,
        DialogPanel,
        DialogTitle,
    } from '@headlessui/vue'
    import { useToast } from 'primevue/usetoast'
    import { useMutation, useQueryClient } from '@tanstack/vue-query'
    import { apiService } from '@/api/axios'

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

    const reduceQtyForm = ref({
        quantity: 0,
        reason: '',
    })

    const toast = useToast()
    const queryClient = useQueryClient()

    const mutation = useMutation({
        mutationFn: async (data) => {
            const respData = await apiService.put(
                `/api/reduce/fabric/quantity/${props.fabricId}`,
                data,
            )
            return respData
        },
        onSuccess: (response) => {
            toast.add({
                severity: 'success',
                summary: 'Fabric quantity reduced successfully',
                life: 3000,
            })

            queryClient.invalidateQueries({ queryKey: ['materials'] })
            closeModal()
        },

        onError: (error) => {
            console.error('Error adding new material:', error)
        },
    })

    async function handleReduceQuantity() {
        if (!reduceQtyForm.value.quantity || reduceQtyForm.value.quantity < 1) {
            toast.add({
                severity: 'error',
                summary: 'Please enter a valid quantity',
                life: 3000,
            })
            return
        }

        mutation.mutate({
            quantity: reduceQtyForm.value.quantity,
            reason: reduceQtyForm.value.reason,
        })
    }
</script>

<template>
    <div class="flex items-center justify-center">
        <button
            class="flex items-center gap-1 px-2 py-1 bg-yellow-600 hover:cursor-pointer hover:opacity-75 text-white rounded hover:bg-yellow-700 transition"
            @click="openModal"
            title="Reduce Quantity"
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
                    d="M6 12h12"
                />
            </svg>
            <span class="text-xs">Reduce Qty</span>
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
                                class="text-lg leading-6 text-gray-900 font-semibold"
                            >
                                {{ fabricName }}
                            </DialogTitle>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    Enter units to reduce and optional reason.
                                </p>
                            </div>

                            <form @submit.prevent="handleReduceQuantity">
                                <div class="mt-4">
                                    <label
                                        for="quantity"
                                        class="block text-sm font-medium text-gray-700"
                                    >
                                        Quantity
                                    </label>
                                    <input
                                        id="quantity"
                                        v-model.number="reduceQtyForm.quantity"
                                        type="number"
                                        min="1"
                                        :max="currentQty"
                                        required
                                        class="mt-1 font-medium block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                        placeholder="Enter quantity to reduce"
                                    />
                                    <p class="mt-1 text-xs text-gray-400">
                                        Current Stock: {{ currentQty }}
                                    </p>
                                </div>
                                <div class="mt-4">
                                    <label
                                        for="reason"
                                        class="block text-sm font-medium text-gray-700"
                                    >
                                        Reason (optional)
                                    </label>
                                    <input
                                        id="reason"
                                        v-model.trim="reduceQtyForm.reason"
                                        type="text"
                                        class="mt-1 font-medium block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                        placeholder="Reason for reduction"
                                    />
                                </div>
                                <div class="mt-6 flex justify-end space-x-2">
                                    <button
                                        type="button"
                                        @click="closeModal"
                                        class="inline-flex justify-center rounded-md border border-gray-300 px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        type="submit"
                                        :disabled="mutation.isPending.value"
                                        class="inline-flex justify-center rounded-md border border-transparent bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:cursor-pointer hover:opacity-75 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 transition"
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
