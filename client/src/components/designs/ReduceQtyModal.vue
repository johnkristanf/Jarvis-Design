<script setup>
    import { ref } from 'vue'
    import { useToast } from 'primevue/usetoast'
    import { useMutation, useQueryClient } from '@tanstack/vue-query'
    import { apiService } from '@/api/axios'
    import FormModal from '../FormModal.vue'
    import FormInput from '../FormInput.vue'
    import { truncateNonDecimal } from '@/helper/designs'

    const props = defineProps({
        fabricId: String,
        fabricName: String,
        currentQty: [String, Number],
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
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Fabric quantity reduced successfully',
                life: 3000,
            })

            queryClient.invalidateQueries({ queryKey: ['materials'] })
            closeModal()
            reduceQtyForm.value = {
                quantity: 0,
                reason: '',
            }
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
            <span class="text-xs">Reduce</span>
        </button>
    </div>

    <FormModal
        :is-open="isOpen"
        :title="fabricName"
        mode="edit"
        :is-submitting="mutation.isPending.value"
        submit-text="Submit"
        @close="closeModal"
        @submit="handleReduceQuantity"
    >
        <p class="text-sm text-gray-500 dark:text-gray-300 -mt-4 mb-5">
            Enter units to reduce and optional reason.
        </p>

        <FormInput
            v-model.number="reduceQtyForm.quantity"
            label="Quantity"
            type="number"
            min="1"
            :max="currentQty"
            required
            placeholder="Enter quantity to reduce"
        />
        <p class="mt-1 text-xs text-gray-400 -mt-3 mb-4">
            Current Stock: {{ truncateNonDecimal(currentQty) }}
        </p>

        <FormInput
            v-model.trim="reduceQtyForm.reason"
            label="Reason (optional)"
            type="text"
            placeholder="Reason for reduction"
        />
    </FormModal>
</template>
