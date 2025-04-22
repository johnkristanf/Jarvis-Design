<script lang="ts" setup>
    import Toast from 'primevue/toast'

    import { Dialog, DialogPanel, DialogTitle, DialogDescription } from '@headlessui/vue'
    import { defineProps, defineEmits } from 'vue'
    import { useForm, useField } from 'vee-validate'
    import * as yup from 'yup'
    import { useMutation, useQueryClient } from '@tanstack/vue-query'
    import { useToast } from 'primevue/usetoast'
    import { apiService } from '@/api/axios'
    import Loader from '../Loader.vue'
    import type { MaterialFormValues } from '@/types/materials'

    // MODAL TOGGLING HANDLERS
    defineProps<{ open: boolean }>()
    const emit = defineEmits(['close'])
    const handleCloseModal = () => emit('close')

    // Yup validation schema
    const materialSchema = yup.object({
        material_name: yup.string().required('Material name is required'),
        unit: yup.string().required('Unit is required'),
        quantity: yup.number().required('Stock quantity is required').min(0, 'Quantity cannot be negative'),
        reorder_level: yup.number().required('Reorder level is required').min(0, 'Reorder level cannot be negative'),
        category: yup.string().required('Category is required')
    })

    // PRIMVUE TOAST
    const toast = useToast()

    // VUE QUERY CLIENT
    const queryClient = useQueryClient()

    // FORM INITIALIZATION
    const { handleSubmit, values, handleReset } = useForm<MaterialFormValues>({
        validationSchema: materialSchema
    })

    // FORM FIELDS
    const { value: material_name, errorMessage: nameError } = useField<string>('material_name')
    const { value: unit, errorMessage: unitError } = useField<string>('unit')
    const { value: quantity, errorMessage: quantityError } = useField<number>('quantity')
    const { value: reorder_level, errorMessage: reorderError } = useField<number>('reorder_level')
    const { value: category, errorMessage: categoryError } = useField<number>('category')


    // ADD NEW MATERIALS MUTATION
    const materialsMutation = useMutation({
        mutationFn: async (data: MaterialFormValues) => {
            const respData = await apiService.post('/api/add/material', data)
            return respData
        },
        onSuccess: (response) => {
            console.log('response uploadPreferredDesignMutation: ', response)
            toast.add({
                severity: 'success',
                summary: 'Material Added Successfully',
                life: 3000
            })

            queryClient.invalidateQueries({ queryKey: ['materials'] })
            handleReset()
            handleCloseModal()
        },

        onError: (error) => {
            console.error('Error uploading preferred image:', error)
        }
    })

    // ADD NEW MATERTIAL SUBMISSION HANDLER
    const onSubmit = handleSubmit((values) => {
        console.log('Submitted:', values)
        materialsMutation.mutate(values)
    })
</script>

<template>
    <Dialog :open="open" @close="() => emit('close')" class="fixed inset-0 z-50 flex items-center justify-center bg-black/30">
        <DialogPanel class="w-full max-w-md bg-white h-[70%] p-6 overflow-y-auto">
            <DialogTitle class="text-lg font-bold">New Material</DialogTitle>
            <DialogDescription class="text-sm text-gray-600 mb-4">Enter the material details below.</DialogDescription>

            <form @submit.prevent="onSubmit" class="mt-5">
                <!-- Category -->
                <div class="mb-4 font-medium">
                    <label class="block text-sm">Category</label>
                    <select v-model="category" class="w-full border px-3 py-2 rounded mt-1">
                        <option value="" disabled>Select category</option>
                        <option value="1">Paper & Consumables</option>
                        <option value="2">Ink & Toner</option>
                        <option value="3">Cleaning Supplies</option>
                        <option value="4">Packaging</option>
                    </select>
                    <span class="text-sm text-red-600 mt-1 block">{{ categoryError }}</span>
                </div>

                <!-- Material Name -->
                <div class="mb-4 font-medium">
                    <label class="block text-sm">Material Name</label>
                    <input v-model="material_name" type="text" class="font-medium w-full px-3 py-2 rounded mt-1 border" />
                    <span class="text-sm text-red-600 mt-1 block">{{ nameError }}</span>
                </div>

                <!-- Unit -->
                <div class="mb-4 font-medium">
                    <label class="block text-sm">Unit</label>
                    <input v-model="unit" type="text" class="font-medium w-full px-3 py-2 rounded mt-1 border" />
                    <span class="text-sm text-red-600 mt-1 block">{{ unitError }}</span>
                </div>

                <!-- Quantity -->
                <div class="mb-4 font-medium">
                    <label class="block text-sm">Stock Quantity</label>
                    <input v-model="quantity" type="number" class="font-medium w-full px-3 py-2 rounded mt-1 border" />
                    <span class="text-sm text-red-600 mt-1 block">{{ quantityError }}</span>
                </div>

                <!-- Reorder Level -->
                <div class="mb-4 font-medium">
                    <label class="block text-sm">Reorder Level</label>
                    <input v-model="reorder_level" type="number" class="font-medium w-full px-3 py-2 rounded mt-1 border" />
                    <span class="text-sm text-red-600 mt-1 block">{{ reorderError }}</span>
                </div>

                <!-- Buttons -->
                <div class="flex justify-end gap-2 mt-6">
                    <button type="button" @click="() => emit('close')" class="text-white font-medium px-4 py-2 bg-gray-500 rounded hover:opacity-75">Cancel</button>
                    <button type="submit" class="font-medium px-4 py-2 bg-gray-900 text-white rounded hover:opacity-75 hover:cursor-pointer">Save</button>
                </div>
            </form>
        </DialogPanel>
    </Dialog>

    <div v-if="materialsMutation.isPending.value">
        <Loader msg="Adding New Material..." />
    </div>

    <Toast />
</template>
