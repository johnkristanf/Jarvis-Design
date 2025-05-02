<script lang="ts" setup>
    import { ref, watch } from 'vue'
    import { useForm, useField } from 'vee-validate'
    import * as yup from 'yup'
    import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
    import { apiService } from '@/api/axios'
    import { useToast } from 'primevue/usetoast'
    import Toast from 'primevue/toast'

    import Loader from '../Loader.vue'
    import type { DesignCategory } from '@/types/design'

    const emit = defineEmits(['close'])

    const toast = useToast()
    const queryClient = useQueryClient()

    // FORM VALIDATION
    const schema = yup.object({
        name: yup.string().required('Design name is required'),
        price: yup.number().required('Price is required').min(0, 'Invalid price'),
        // quantity: yup.number().required('Quantity is required').min(0, 'Invalid quantity'),
        file: yup.mixed().required('Image is required'),
    })

    const { handleSubmit, resetForm } = useForm({ validationSchema: schema })

    // INITIALIZE FORM INPUT FIELDS
    const { value: category, errorMessage: categoryError } = useField<number>('category')
    const { value: name, errorMessage: nameError } = useField<string>('name')
    const { value: price, errorMessage: priceError } = useField<number>('price')
    // const { value: quantity, errorMessage: quantityError } = useField<number>('quantity')
    const { value: file, errorMessage: fileError } = useField<File>('file')

    // ADD NEW PRE MADE DESIGN MUTATION
    const mutation = useMutation({
        mutationFn: async (formData: FormData) => {
            return await apiService.post('/api/add/pre_made/design', formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            })
        },
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Design added!', life: 3000 })
            queryClient.invalidateQueries({ queryKey: ['pre-made-designs'] })
            resetForm()
            emit('close')
        },
        onError: (err) => {
            console.error('Upload error', err)
            toast.add({ severity: 'error', summary: 'Upload failed', life: 3000 })
        },
    })
    

    // USE QUERY FOR FETCHING DESIGN CATEGORIES
    const { data, isLoading } = useQuery({
        queryKey: ['design-categories'],
        queryFn: async () => {
            const respData = await apiService.get<DesignCategory[]>('/api/get/design/categories')
            console.log('respData categ: ', respData)
            return respData
        },
    })


    // FOR SUBMISSION HANDLER
    const onSubmit = handleSubmit((values) => {
        console.log("values: ", values);
        
        const formData = new FormData()
        formData.append('category_id', values.category.toString())
        formData.append('name', values.name)
        formData.append('price', values.price.toString())
        formData.append('file', values.file as File)

        mutation.mutate(formData)
    })

    // PRE-SELECT THE FIRST DESIGN CATEGORY
    watch(
        () => data.value,
        (design_categories) => {
            if (design_categories && design_categories.length > 0) {
                category.value = design_categories[0].id
            }
        },
    )
</script>

<template>
    <div class="fixed inset-0 bg-black/30 flex justify-center items-center z-50">
        <div class="bg-white p-6 w-full max-w-lg rounded shadow">
            <h2 class="text-xl font-semibold mb-2">Add Pre-made Design</h2>
            <p class="text-sm text-gray-600 mb-6">Enter the material design below.</p>

            <form @submit.prevent="onSubmit" class="space-y-4">
                <div>
                    <label class="block text-sm mb-3">Category</label>
                    <select
                        v-model="category"
                        class="font-medium w-full border px-3 py-2 rounded mt-1"
                    >
                        <option v-for="cat in data" :key="cat.id" :value="cat.id">
                            {{ cat.name }}
                        </option>
                    </select>
                    <p class="text-sm text-red-500 mt-1">{{ categoryError }}</p>
                </div>

                <div>
                    <label class="block text-sm mb-3">Design Name</label>
                    <input
                        v-model="name"
                        type="text"
                        class="font-medium w-full border p-2 rounded"
                    />
                    <p class="text-sm text-red-500 mt-1">{{ nameError }}</p>
                </div>

                <div>
                    <label class="block text-sm mb-3">Price</label>
                    <input
                        v-model="price"
                        type="number"
                        class="font-medium w-full border p-2 rounded"
                    />
                    <p class="text-sm text-red-500 mt-1">{{ priceError }}</p>
                </div>

                <!-- <div>
                    <label class="block text-sm mb-3">Quantity</label>
                    <input
                        v-model="quantity"
                        type="number"
                        class="font-medium w-full border p-2 rounded"
                    />
                    <p class="text-sm text-red-500 mt-1">{{ quantityError }}</p>
                </div> -->

                <div>
                    <label class="block text-sm mb-3">Design Image</label>
                    <input
                        type="file"
                        class="font-medium w-full"
                        accept="image/*"
                        @change="(e) => (file = e.target?.files?.[0] || null)"
                    />
                    <p class="text-sm text-red-500 mt-1">{{ fileError }}</p>
                </div>

                <div class="font-medium flex justify-end space-x-2 pt-4">
                    <button
                        type="button"
                        class="bg-gray-400 px-4 py-2 rounded text-white hover:opacity-75 hover:cursor-pointer"
                        @click="$emit('close')"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="bg-gray-900 px-4 py-2 rounded text-white hover:opacity-75 hover:cursor-pointer"
                    >
                        Add Design
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div v-if="mutation.isPending.value">
        <Loader msg="Adding Design..." />
    </div>

    <Toast />
</template>
