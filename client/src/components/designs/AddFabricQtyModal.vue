<script setup>
    import { ref } from 'vue'
    import { useMutation, useQueryClient } from '@tanstack/vue-query'
    import { apiService } from '@/api/axios'
    import { useToast } from 'primevue/usetoast'
    import FormModal from '../FormModal.vue'
    import FormInput from '../FormInput.vue'

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
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Fabric quantity added successfully',
                life: 3000,
            })

            queryClient.invalidateQueries({ queryKey: ['materials'] })
            closeModal()
            form.value = {
                delivery_date: '',
                quantity: null,
            }
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
            <span class="text-xs">Add</span>
        </button>
    </div>

    <FormModal
        :is-open="isOpen"
        :title="fabricName"
        mode="add"
        :is-submitting="mutation.isPending.value"
        submit-text="Submit"
        @close="closeModal"
        @submit="handleSubmit"
    >
        <p class="text-sm text-gray-500 dark:text-gray-300 -mt-4 mb-5">
            Add fabric quantities and delivery date for the new stock.
        </p>

        <FormInput
            v-model="form.delivery_date"
            label="Delivery Date"
            type="date"
            required
        />

        <FormInput
            v-model.number="form.quantity"
            label="Quantity"
            type="number"
            min="1"
            required
        />
    </FormModal>
</template>
