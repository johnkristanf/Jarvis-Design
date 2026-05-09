<script lang="ts" setup>
    import { computed, ref, watch } from 'vue'
    import { useForm, useField } from 'vee-validate'
    import * as yup from 'yup'
    import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
    import { apiService } from '@/api/axios'
    import { useToast } from 'primevue/usetoast'
    import Toast from 'primevue/toast'

    import Loader from '../Loader.vue'
    import FormInput from '../FormInput.vue'
    import FormModal from '../FormModal.vue'
    import {
        sublimationProductCategories,
        type DesignCategory,
        type FabricTypes,
    } from '@/types/design'

    defineProps<{
        isOpen: boolean
    }>()

    const emit = defineEmits(['close', 'update:isOpen'])
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
        isPocketIncluded: yup.boolean().default(false),
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
    const { value: isPocketIncluded } = useField<boolean>('isPocketIncluded', undefined, {
        initialValue: false,
    })

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
            return respData
        },
    })

    // FOR SUBMISSION HANDLER
    const onSubmit = handleSubmit((values) => {
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
        formData.append('is_pocket_included', values.isPocketIncluded ? '1' : '0')

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
        },
    )

    // CHECK IF THE SELECTED CATEGORY HAS FABRIC REQUIRED
    const isFabricRequired = computed(() =>
        selectedCategory.value
            ? sublimationProductCategories.includes(selectedCategory.value.name)
            : false,
    )
</script>

<template>
    <FormModal
        :is-open="isOpen"
        title="Add Product"
        mode="add"
        :is-submitting="mutation.isPending.value"
        submit-text="Add Product"
        @close="$emit('close')"
        @submit="onSubmit"
    >
        <p class="text-sm text-gray-600 dark:text-gray-400 -mt-2 mb-2">
            Enter the product details below.
        </p>

        <!-- PRODUCT CATEGORY -->
        <div>
            <label class="block text-sm mb-3">Category</label>
            <select
                v-model="category"
                class="font-medium w-full border border-gray-300 dark:border-gray-600 px-3 py-2 rounded mt-1 bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
            >
                <option v-for="cat in designCategories" :key="cat.id" :value="cat.id">
                    {{ cat.name }}
                </option>
            </select>
            <p class="text-sm text-red-500 mt-1">{{ categoryError }}</p>
        </div>

        <!-- PRODUCT NAME -->
        <FormInput
            v-model="productName"
            label="Product Name"
            placeholder="Enter product name"
            :error="productNameError"
        />

        <!-- PRODUCT FABRIC TYPE (OPTIONAL) -->
        <div v-if="isFabricRequired">
            <label class="block text-sm mb-3">Fabric Type</label>
            <select
                v-model="fabricType"
                class="font-medium w-full border border-gray-300 dark:border-gray-600 px-3 py-2 rounded mt-1 bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
            >
                <option :value="null" disabled>Select fabric type</option>
                <option v-for="fab in fabricTypes" :key="fab.id" :value="fab.id">
                    {{ fab.name }}
                </option>
            </select>
            <p class="text-sm text-red-500 mt-1">{{ fabricTypeError }}</p>
        </div>

        <!-- INCLUDE POCKET OPTION -->
        <div>
            <label class="block text-sm mb-3">Include pocket option</label>
            <div class="flex items-center space-x-2">
                <button
                    type="button"
                    @click="isPocketIncluded = true"
                    :class="
                        isPocketIncluded
                            ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-900 border-transparent'
                            : 'bg-white text-gray-700 border-gray-300 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'
                    "
                    class="px-4 py-2 border rounded font-medium text-sm transition-colors"
                >
                    Yes
                </button>
                <button
                    type="button"
                    @click="isPocketIncluded = false"
                    :class="
                        !isPocketIncluded
                            ? 'bg-gray-900 text-white dark:bg-white dark:text-gray-900 border-transparent'
                            : 'bg-white text-gray-700 border-gray-300 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'
                    "
                    class="px-4 py-2 border rounded font-medium text-sm transition-colors"
                >
                    No
                </button>
            </div>
        </div>

        <!-- PRODUCT UNIT PRICE -->
        <FormInput
            v-model="price"
            label="Unit Price"
            type="number"
            :required="true"
            :error="priceError"
        />
    </FormModal>

    <div v-if="mutation.isPending.value">
        <Loader msg="Adding Product..." />
    </div>

    <Toast />
</template>
