<script lang="ts" setup>
    import { onMounted, onUpdated, nextTick } from 'vue'
    import { useForm, useField } from 'vee-validate'
    import * as yup from 'yup'
    import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
    import { useToast } from 'primevue/usetoast'
    import Toast from 'primevue/toast'
    import { apiService } from '@/api/axios'
    import Loader from '../Loader.vue'
    import FormInput from '../FormInput.vue'
    import FormModal from '../FormModal.vue'
    import { type MaterialsCategory, type MaterialFormValues } from '@/types/materials'
    import { initFlowbite } from 'flowbite'

    // MODAL TOGGLING HANDLERS
    defineProps<{ isOpen: boolean }>()
    const emit = defineEmits(['close', 'update:isOpen'])

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

    // ADD NEW MATERIALS MUTATION
    const materialsMutation = useMutation({
        mutationFn: async (data: MaterialFormValues) => {
            return await apiService.post('/api/add/material', data)
        },
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Fabric Added Successfully',
                life: 3000,
            })

            queryClient.invalidateQueries({ queryKey: ['materials'] })
            handleReset()
            emit('close')
        },
        onError: (error) => {
            console.error('Error adding new material:', error)
        },
    })

    // ADD NEW MATERTIAL SUBMISSION HANDLER
    const onSubmit = handleSubmit((values) => {
        materialsMutation.mutate(values)
    })

    // GET MATERIALS CATEGORY QUERY
    const { isPending } = useQuery({
        queryKey: ['materials_categories'],
        queryFn: async () => {
            return await apiService.get<MaterialsCategory[]>('/api/get/material/categories')
        },
    })

    onMounted(() => {
        initFlowbite()
    })

    onUpdated(() => {
        nextTick(() => {
            initFlowbite()
        })
    })
</script>

<template>
    <FormModal
        :is-open="isOpen"
        title="New Fabric"
        mode="add"
        :is-submitting="materialsMutation.isPending.value"
        submit-text="Save"
        @close="$emit('close')"
        @submit="onSubmit"
    >
        <p class="text-sm text-gray-600 dark:text-gray-300 -mt-2 mb-4">
            Enter the fabric details below.
        </p>

        <!-- Fabric Name -->
        <FormInput
            v-model="material_name"
            label="Fabric Name"
            placeholder="(Semi-Cooltech, Microstepline, etc...)"
            :error="nameError"
        />

        <!-- Unit -->
        <div>
            <label class="block text-sm mb-2">Unit</label>
            <select
                v-model="unit"
                class="font-medium w-full px-3 py-2 rounded border border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500"
            >
                <option value="" disabled>Select unit</option>
                <option value="rolls">rolls</option>
            </select>
            <span class="text-sm text-red-600 mt-1 block">{{ unitError }}</span>
        </div>

        <!-- Quantity -->
        <FormInput
            v-model="quantity"
            label="Stock Quantity"
            type="number"
            :error="quantityError"
        />

        <!-- Reorder Level -->
        <FormInput
            v-model="reorder_level"
            label="Stock Reorder Level"
            type="number"
            :error="reorderError"
        />
    </FormModal>

    <div v-if="materialsMutation.isPending.value">
        <Loader msg="Adding New Fabric..." />
    </div>
    <div v-if="isPending">
        <Loader msg="Getting Material Categories..." />
    </div>

    <Toast />
</template>
