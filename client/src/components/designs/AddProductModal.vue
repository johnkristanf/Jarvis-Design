<script lang="ts" setup>
    import { computed, ref, watch } from 'vue'
    import { useForm, useField } from 'vee-validate'
    import * as yup from 'yup'
    import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
    import { apiService } from '@/api/axios'
    import { useToast } from 'primevue/usetoast'
    import Toast from 'primevue/toast'

    import Loader from '../Loader.vue'
    import {
        sublimationProductCategories,
        type DesignCategory,
        type FabricTypes,
    } from '@/types/design'

    const emit = defineEmits(['close'])
    const selectedFabricUnit = ref<string | null>(null)

    const toast = useToast()
    const queryClient = useQueryClient()

    // FORM VALIDATION
    const schema = yup.object({
        category: yup.number().required('Category is required'),
        productName: yup.string().required('Design name is required'),
        price: yup.number().required('Price is required').min(0, 'Invalid price'),
        fabricType: yup.number().nullable(),
        fabricQuantity: yup.number().nullable(),
    })

    const { handleSubmit, resetForm } = useForm({ validationSchema: schema })

    // INITIALIZE FORM INPUT FIELDS
    const { value: category, errorMessage: categoryError } = useField<number>('category')
    const { value: productName, errorMessage: productNameError } = useField<string>('productName')
    const { value: price, errorMessage: priceError } = useField<number>('price')
    const { value: fabricType, errorMessage: fabricTypeError } = useField<number | null>(
        'fabricType',
    )
    const { value: fabricQuantity, errorMessage: fabricQuantityError } =
        useField<number>('fabricQuantity')

    // ADD NEW PRODUCT MUTATION
    const mutation = useMutation({
        mutationFn: async (formData: FormData) => {
            return await apiService.post('/api/add/product', formData)
        },
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Product Added!', life: 3000 })
            queryClient.invalidateQueries({ queryKey: ['products'] })
            resetForm()
            emit('close')
        },
        onError: (err) => {
            console.error('Upload error', err)
            toast.add({
                severity: 'error',
                summary: 'Add product error, please try again',
                life: 3000,
            })
        },
    })

    const selectedCategory = ref<DesignCategory | null>(null)

    // USE QUERY FOR FETCHING DESIGN CATEGORIES
    const { data: designCategories } = useQuery({
        queryKey: ['design-categories'],
        queryFn: async () => {
            const respData = await apiService.get<DesignCategory[]>('/api/get/design/categories')
            console.log('respData categ: ', respData)
            return respData
        },
    })

    // USE QUERY FOR FETCHING FABRIC TYPES
    const { data: fabricTypes } = useQuery({
        queryKey: ['fabric-types'],
        queryFn: async () => {
            const respData = await apiService.get<FabricTypes[]>('/api/get/fabric/types')
            console.log('respData fabric: ', respData)
            return respData
        },
    })

    // FOR SUBMISSION HANDLER
    const onSubmit = handleSubmit((values) => {
        console.log('values: ', values)

        const formData = new FormData()
        formData.append('category_id', values.category.toString())

        // NULLABLE FABRIC TYPE CAUSE OTHER PRODUCTS LIKE MUGS DONT HAVE FABRICS
        if (
            values.fabricType !== null &&
            values.fabricType !== undefined &&
            values.fabricType !== ''
        ) {
            formData.append('fabric_type_id', values.fabricType.toString())
        }

        // NULLABLE FABRIC QUANTITY CAUSE OTHER PRODUCTS LIKE MUGS DONT HAVE FABRICS
        if (
            values.fabricQuantity !== null &&
            values.fabricQuantity !== undefined &&
            values.fabricQuantity !== ''
        ) {
            formData.append('fabric_quantity', values.fabricQuantity.toString())
        }

        formData.append('product_name', values.productName)
        formData.append('unit_price', values.price.toString())

        mutation.mutate(formData)
    })

    // WATCH THE CATEGORY CHANGES TO CHECK IF SELECTED HAS FABRIC REQUIRED
    watch(
        () => category.value,
        (catId) => {
            const selected = designCategories.value?.find((c) => c.id === catId) ?? null
            selectedCategory.value = selected

            if (selected?.is_fixed_priced && selected.fixed_price) {
                price.value = Number(selected.fixed_price) // set the fixed price
            } else {
                price.value = 0
            }
        },
    )

    // PRE-SELECT THE FIRST DESIGN CATEGORY
    watch(
        () => designCategories.value,
        (design_categories) => {
            if (design_categories && design_categories.length > 0) {
                category.value = design_categories[0].id
            }
        },
    )

    // WATCH SELECTED FABRIC AND GET ITS UNIT (rolls, yards, etc...)
    watch(
        () => fabricType.value,
        (fabricTypeId) => {
            const matched = fabricTypes.value?.find((fab) => fab.id === fabricTypeId)
            selectedFabricUnit.value = matched?.unit ?? null
        }
    )

    // CHECK IF THE SELECTED CATEGORY HAS FABRIC REQUIRED
    const isFabricRequired = computed(() =>
        selectedCategory.value
            ? sublimationProductCategories.includes(selectedCategory.value.name)
            : false,
    )
</script>

<template>
    <div class="fixed inset-0 bg-black/30 flex justify-center items-center z-50">
        <div class="bg-white p-6 w-full h-[450px] overflow-y-auto max-w-lg rounded shadow">
            <h2 class="text-xl font-semibold mb-2">Add Product</h2>
            <p class="text-sm text-gray-600 mb-6">Enter the product details below.</p>

            <form @submit.prevent="onSubmit" class="space-y-4">
                <!-- PRODUCT CATEGORY -->
                <div>
                    <label class="block text-sm mb-3">Category</label>
                    <select
                        v-model="category"
                        class="font-medium w-full border px-3 py-2 rounded mt-1"
                    >
                        <option v-for="cat in designCategories" :key="cat.id" :value="cat.id">
                            {{ cat.name }}
                        </option>
                    </select>
                    <p class="text-sm text-red-500 mt-1">{{ categoryError }}</p>
                </div>

                <!-- PRODUCT NAME -->
                <div>
                    <label class="block text-sm mb-3">Product Name</label>
                    <input
                        v-model="productName"
                        type="text"
                        class="font-medium w-full border p-2 rounded"
                    />
                    <p class="text-sm text-red-500 mt-1">{{ productNameError }}</p>
                </div>

                <!-- PRODUCT FABRIC TYPE (OPTIONAL) -->
                <div v-if="isFabricRequired">
                    <label class="block text-sm mb-3">Fabric Type</label>
                    <select
                        v-model="fabricType"
                        class="font-medium w-full border px-3 py-2 rounded mt-1"
                    >
                        <option :value="null" disabled>Select fabric type</option>
                        <option v-for="fab in fabricTypes" :key="fab.id" :value="fab.id">
                            {{ fab.name }}
                        </option>
                    </select>
                    <p class="text-sm text-red-500 mt-1">{{ fabricTypeError }}</p>
                </div>

                <!-- PRODUCT FABRIC TYPE (OPTIONAL) -->

                <div v-if="isFabricRequired">
                    <label class="block text-sm mb-3">
                        Unit Fabric Quantity Used ({{ selectedFabricUnit }})
                    </label>
                    <input
                        v-model="fabricQuantity"
                        type="number"
                        class="font-medium w-full border p-2 rounded"
                    />
                    <p class="text-sm text-red-500 mt-1">{{ fabricQuantityError }}</p>
                </div>

                <!-- PRODUCT UNIT PRICE -->
                <div>
                    <label class="block text-sm mb-3">Unit Price</label>
                    <input
                        v-model="price"
                        type="number"
                        class="font-medium w-full border p-2 rounded"
                    />
                    <p class="text-sm text-red-500 mt-1">{{ priceError }}</p>
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
                        Add Product
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div v-if="mutation.isPending.value">
        <Loader msg="Adding Product..." />
    </div>

    <Toast />
</template>
