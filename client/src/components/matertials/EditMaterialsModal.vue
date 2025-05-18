<script lang="ts" setup>
    import Toast from 'primevue/toast'

    import { Dialog, DialogPanel, DialogTitle, DialogDescription } from '@headlessui/vue'
    import { defineProps, defineEmits, watch, onMounted, onUpdated, nextTick } from 'vue'
    import { useForm, useField } from 'vee-validate'
    import * as yup from 'yup'
    import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
    import { useToast } from 'primevue/usetoast'
    import { apiService } from '@/api/axios'
    import Loader from '../Loader.vue'
    import type { MaterialsCategory, MaterialFormValues, Material } from '@/types/materials'
    import { InformationCircleIcon } from '@heroicons/vue/20/solid'
    import { initFlowbite } from 'flowbite'

    // MODAL TOGGLING HANDLERS
    const props = defineProps<{
        open: boolean
        material: Material
    }>()

    const emit = defineEmits(['close'])
    const handleCloseModal = () => emit('close')

    // Yup validation schema
    const materialSchema = yup.object({
        material_name: yup.string().required('Material name is required'),
        unit: yup.string().required('Unit is required'),
        quantity: yup
            .number()
            .required('Stock quantity is required')
            .min(0, 'Quantity cannot be negative'),
        reorder_level: yup
            .number()
            .required('Reorder level is required')
            .min(0, 'Reorder level cannot be negative'),
        category: yup.string().required('Category is required'),
    })

    // PRIMVUE TOAST
    const toast = useToast()

    // VUE QUERY CLIENT
    const queryClient = useQueryClient()

    // FORM INITIALIZATION
    const { handleSubmit, handleReset, setFieldValue } = useForm<MaterialFormValues>({
        validationSchema: materialSchema,
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
            const respData = await apiService.post('/api/edit/material', data)
            return respData
        },
        onSuccess: (response) => {
            console.log('response editMaterial: ', response)
            toast.add({
                severity: 'success',
                summary: 'Material Edited Successfully',
                life: 3000,
            })

            queryClient.invalidateQueries({ queryKey: ['materials'] })
            handleReset()
            handleCloseModal()
        },

        onError: (error) => {
            console.error('Error editting material:', error)
        },
    })

    // EDIT MATERTIAL SUBMISSION HANDLER
    const onSubmit = handleSubmit((values) => {
        console.log('Submitted:', values)
        materialsMutation.mutate(values)
    })


    // GET MATERIALS CATEGORY QUERY
    const { isPending, data } = useQuery({
        queryKey: ['materials_categories'],
        queryFn: async () => {
            const respData = await apiService.get<MaterialsCategory[]>(
                '/api/get/material/categories',
            )
            console.log('respData: ', respData)

            return respData
        },
    })


    // WATCH EVERY TIME ADMIN OPEN EDIT MODAL THE SELECTED FIELDS GET FILLED
    watch(
        () => props.open,
        (isOpen) => {
            if (isOpen && props.material) {
                nextTick(() => {
                    setFieldValue('id', props.material.id)
                    setFieldValue('material_name', props.material.name)
                    setFieldValue('unit', props.material.unit)
                    setFieldValue('quantity', props.material.quantity)
                    setFieldValue('reorder_level', props.material.reorder_level)
                    setFieldValue('category', props.material.category_id)
                })
            }
        },
        { immediate: true },
    )

    onMounted(() => {
        initFlowbite()

        console.log('material 123113: ', props.material)
    })

    // RE-INITIALIZED THE FLOWBITE CAUSE THE DIALOG GETS MOUNTED IN THE DOM DYNAMICALLY
    onUpdated(() => {
        nextTick(() => {
            initFlowbite()
        })
    })
</script>

<template>
    <Dialog
        :open="open"
        @close="() => emit('close')"
        class="fixed inset-0 z-[99999] flex items-center justify-center bg-gray-900/80"
    >
        <DialogPanel class="w-full max-w-xl bg-white h-[70%] p-6 overflow-y-auto">
            <DialogTitle class="text-lg font-bold">Edit Material</DialogTitle>
            <DialogDescription class="text-sm text-gray-600 mb-4">
                Enter the material details below.
            </DialogDescription>

            <form @submit.prevent="onSubmit" class="mt-5">
                <!-- Category -->
                <div class="mb-4">
                    <label class="block text-sm">Category</label>
                    <select
                        v-model="category"
                        class="font-medium w-full border px-3 py-2 rounded mt-1"
                    >
                        <option v-for="cat in data" :key="cat.id" :value="cat.id">
                            {{ cat.name }}
                        </option>
                    </select>
                    <span class="text-sm text-red-600 mt-1 block">{{ categoryError }}</span>
                </div>

                <!-- Material Name -->
                <div class="mb-4">
                    <label class="block text-sm">Material Name</label>
                    <input
                        v-model="material_name"
                        type="text"
                        class="font-medium w-full px-3 py-2 rounded mt-1 border"
                    />
                    <span class="text-sm text-red-600 mt-1 block">{{ nameError }}</span>
                </div>

                <!-- Unit -->
                <div class="mb-4">
                    <!-- Tooltip Target (Icon) -->
                    <div class="flex items-center gap-1 relative">
                        <label class="block text-sm">Unit</label>

                        <!-- Tooltip Trigger -->
                        <button
                            type="button"
                            data-tooltip-target="unit-tooltip"
                            data-tooltip-placement="right"
                            class="hover:text-gray-700"
                        >
                            <InformationCircleIcon class="w-4 h-4" />
                        </button>

                        <!-- Tooltip Content -->
                        <div
                            id="unit-tooltip"
                            role="tooltip"
                            class="absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white bg-gray-900 rounded-lg shadow-sm opacity-0 tooltip dark:bg-gray-700"
                        >
                            (kg, ml, pcs, etc...)
                            <div class="tooltip-arrow" data-popper-arrow></div>
                        </div>
                    </div>

                    <input
                        v-model="unit"
                        type="text"
                        class="font-medium w-full px-3 py-2 rounded mt-1 border"
                    />
                    <span class="text-sm text-red-600 mt-1 block">{{ unitError }}</span>
                </div>

                <!-- Quantity -->
                <div class="mb-4">
                    <!-- Tooltip Target (Icon) -->
                    <div class="flex items-center gap-1 relative">
                        <label class="block text-sm">Stock Quantity</label>

                        <!-- Tooltip Trigger -->
                        <button
                            type="button"
                            data-tooltip-target="quantity-tooltip"
                            data-tooltip-placement="right"
                            class="hover:text-gray-700"
                        >
                            <InformationCircleIcon class="w-4 h-4" />
                        </button>

                        <!-- Tooltip Content -->
                        <div
                            id="quantity-tooltip"
                            role="tooltip"
                            class="absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white bg-gray-900 rounded-lg shadow-sm opacity-0 tooltip dark:bg-gray-700"
                        >
                            If the unit is in mililiter specify quantity in mililiter quantity,
                            <div class="tooltip-arrow" data-popper-arrow></div>
                        </div>
                    </div>

                    <input
                        v-model="quantity"
                        type="number"
                        class="font-medium w-full px-3 py-2 rounded mt-1 border"
                    />
                    <span class="text-sm text-red-600 mt-1 block">{{ quantityError }}</span>
                </div>

                <!-- Reorder Level -->
                <div class="mb-4">
                    <label class="block text-sm">Reorder Level</label>
                    <input
                        v-model="reorder_level"
                        type="number"
                        class="font-medium w-full px-3 py-2 rounded mt-1 border"
                    />
                    <span class="text-sm text-red-600 mt-1 block">{{ reorderError }}</span>
                </div>

                <!-- Buttons -->
                <div class="flex justify-end gap-2 mt-6">
                    <button
                        type="button"
                        @click="() => emit('close')"
                        class="text-white font-medium px-4 py-2 bg-gray-500 rounded hover:opacity-75 hover:cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="font-medium px-4 py-2 bg-gray-900 text-white rounded hover:opacity-75 hover:cursor-pointer"
                    >
                        Save
                    </button>
                </div>
            </form>
        </DialogPanel>
    </Dialog>

    <div v-if="materialsMutation.isPending.value">
        <Loader msg="Editing New Material..." />
    </div>

    <div v-if="isPending">
        <Loader msg="Getting Material Categories..." />
    </div>

    <Toast />
</template>
