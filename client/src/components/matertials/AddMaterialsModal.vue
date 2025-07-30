<script lang="ts" setup>
    import Toast from 'primevue/toast'

    import { Dialog, DialogPanel, DialogTitle, DialogDescription } from '@headlessui/vue'
    import { defineProps, defineEmits, watch, onMounted, onUpdated, nextTick, ref } from 'vue'
    import { useForm, useField } from 'vee-validate'
    import * as yup from 'yup'
    import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
    import { useToast } from 'primevue/usetoast'
    import { apiService } from '@/api/axios'
    import Loader from '../Loader.vue'
    import { type MaterialsCategory, type MaterialFormValues } from '@/types/materials'
    import { InformationCircleIcon } from '@heroicons/vue/20/solid'
    import { initFlowbite } from 'flowbite'

    // MODAL TOGGLING HANDLERS
    defineProps<{ open: boolean }>()
    const emit = defineEmits(['close'])
    const handleCloseModal = () => emit('close')

    // Yup validation schema
    const materialSchema = yup.object({
        material_name: yup.string().required('Fabric name is required'),
        unit: yup.string().required('Unit is required'),
        quantity: yup
            .number()
            .required('Stock quantity is required')
            .min(0, 'Quantity cannot be negative'),
        reorder_level: yup
            .number()
            .required('Reorder level is required')
            .min(0, 'Reorder level cannot be negative'),
        // category: yup.string().required('Category is required'),
    })



    // PRIMVUE TOAST
    const toast = useToast()

    // VUE QUERY CLIENT
    const queryClient = useQueryClient()

    // FORM INITIALIZATION
    const { handleSubmit, handleReset } = useForm<MaterialFormValues>({
        validationSchema: materialSchema,
    })

    // FORM FIELDS
    const { value: material_name, errorMessage: nameError } = useField<string>('material_name')
    const { value: unit, errorMessage: unitError } = useField<string>('unit')
    const { value: quantity, errorMessage: quantityError } = useField<number>('quantity')
    const { value: reorder_level, errorMessage: reorderError } = useField<number>('reorder_level')
    // const { value: category, errorMessage: categoryError } = useField<number>('category')

    // ADD NEW MATERIALS MUTATION
    const materialsMutation = useMutation({
        mutationFn: async (data: MaterialFormValues) => {
            const respData = await apiService.post('/api/add/material', data)
            return respData
        },
        onSuccess: (response) => {
            console.log('response addNewMaterial: ', response)
            toast.add({
                severity: 'success',
                summary: 'Fabric Added Successfully',
                life: 3000,
            })

            queryClient.invalidateQueries({ queryKey: ['materials'] })
            handleReset()
            handleCloseModal()
        },

        onError: (error) => {
            console.error('Error adding new material:', error)
        },
    })

    // ADD NEW MATERTIAL SUBMISSION HANDLER
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

    // SET THE FIRST MATERIALS CATEGORY AS PRE-SELECTED
    // watch(
    //     () => data.value,
    //     (categories) => {
    //         if (categories && categories.length > 0 && !category.value) {
    //             category.value = categories[0].id
    //         }
    //     },
    // )

    onMounted(() => {
        initFlowbite()
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
            <DialogTitle class="text-lg font-bold">New Fabric</DialogTitle>
            <DialogDescription class="text-sm text-gray-600 mb-4">
                Enter the fabric details below.
            </DialogDescription>

            <form @submit.prevent="onSubmit" class="mt-5">
                <!-- Category -->
                <!-- <div class="mb-4">
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
                </div> -->

                <!-- Fabric Name -->
                <div class="mb-4">
                    <label class="block text-sm">Fabric Name</label>
                    <input
                        v-model="material_name"
                        type="text"
                        class="font-medium w-full px-3 py-2 rounded mt-1 border"
                        placeholder="(Semi-Cooltech, Microstepline, etc...)"
                    />
                    <span class="text-sm text-red-600 mt-1 block">{{ nameError }}</span>
                </div>

                <!-- Unit -->
                <div class="mb-4">
                    <!-- Tooltip Target (Icon) -->
                    <div class="flex items-center gap-1 relative">
                        <label class="block text-sm">Unit</label>

                    </div>

                    <input
                        v-model="unit"
                        type="text"
                        class="font-medium w-full px-3 py-2 rounded mt-1 border"
                        placeholder="rolls, meters, yards, etc..."
                    />
                    <span class="text-sm text-red-600 mt-1 block">{{ unitError }}</span>
                </div>

                <!-- Quantity -->
                <div class="mb-4">
                    <!-- Tooltip Target (Icon) -->
                    <div class="flex items-center gap-1 relative">
                        <label class="block text-sm">Stock Quantity</label>

                        <!-- Tooltip Trigger -->
                        <!-- <button
                            type="button"
                            data-tooltip-target="quantity-tooltip"
                            data-tooltip-placement="right"
                            class="hover:text-gray-700"
                        >
                            <InformationCircleIcon class="w-4 h-4" />
                        </button> -->

                        <!-- Tooltip Content -->
                        <!-- <div
                            id="quantity-tooltip"
                            role="tooltip"
                            class="absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white bg-gray-900 rounded-lg shadow-sm opacity-0 tooltip dark:bg-gray-700"
                        >
                            If the unit is in mililiter specify quantity in mililiter quantity,
                            <div class="tooltip-arrow" data-popper-arrow></div>
                        </div> -->
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
                    <label class="block text-sm">Stock Reorder Level</label>
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
        <Loader msg="Adding New Material..." />
    </div>

    <div v-if="isPending">
        <Loader msg="Getting Material Categories..." />
    </div>

    <Toast />
</template>
