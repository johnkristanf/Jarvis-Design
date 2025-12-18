<script lang="ts" setup>
    import { ref, computed, watch, onMounted } from 'vue'
    import {
        Dialog,
        DialogPanel,
        DialogTitle,
        TransitionChild,
        TransitionRoot,
    } from '@headlessui/vue'

    import {
        sublimationProductCategories,
        type BusinessProductDesign,
        type FabricTypes,
        type Product,
    } from '@/types/design'

    import type { PropType } from 'vue'
    import { useProductAttributes } from '@/composables/useProductAttribute'
    import { apiService } from '@/api/axios'
    import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'

    import { useToast } from 'primevue/usetoast'
    import Toast from 'primevue/toast'
    import Loader from '../Loader.vue'
    import { ShoppingCartIcon } from '@heroicons/vue/20/solid'

    // vee-validate
    import { useForm, useField } from 'vee-validate'
    import * as yup from 'yup'

    const props = defineProps({
        product: {
            type: Object as PropType<Product>,
            required: true,
        },
    })

    const emit = defineEmits(['close'])
    const handleClose = () => emit('close')

    const { sizes, loadingSizes } = useProductAttributes()
    const queryClient = useQueryClient()
    const toast = useToast()

    // Load fabric types for select
    const { data: fabricTypes } = useQuery({
        queryKey: ['fabric-types'],
        queryFn: async () => {
            const respData = await apiService.get<FabricTypes[]>('/api/get/fabric/types')
            return respData
        },
    })

    // Form validation schema
    const schema = yup.object({
        fabricTypeId: yup.number().required('Fabric type is required'),
        sizeId: yup.number().required('Size is required'),
    })

    const { handleSubmit, resetForm } = useForm({ validationSchema: schema })

    // Field bindings
    const { value: fabricTypeId, errorMessage: fabricTypeError } = useField<number>('fabricTypeId')
    const { value: sizeId, errorMessage: sizeIdError } = useField<number>('sizeId')

    // Optional: Pre-select first item as available
    watch(
        () => fabricTypes.value,
        (ftypes) => {
            if (ftypes && ftypes.length > 0 && fabricTypeId.value == null) {
                fabricTypeId.value = ftypes[0].id
            }
        },
        { immediate: true }
    )
    watch(
        () => sizes.value,
        (sizeVals) => {
            if (sizeVals && sizeVals.length > 0 && sizeId.value == null) {
                sizeId.value = sizeVals[0].id
            }
        },
        { immediate: true }
    )

    // Place order mutation
    const mutation = useMutation({
        mutationFn: async (formData: FormData) => {
            const respData = await apiService.post('/api/add/cart', formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            })
            return respData
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['order_notifications'] })
            toast.add({
                severity: 'success',
                summary: 'Added to cart successfully!',
                life: 1000,
            })
            setTimeout(() => {
                handleClose()
            }, 1500)
        },
        onError: (err) => {
            console.error('Place order error', err)
            // @ts-expect-error custom payload
            if (err.statusCode === 401) {
                toast.add({
                    severity: 'error',
                    summary: 'Please login your account to proceed the order.',
                    life: 3000,
                })
                return
            }
            if (err.message == 'Not enough material in stock.') {
                toast.add({
                    severity: 'error',
                    summary: err.message,
                    life: 3000,
                })
                return
            }
            if (err.message == 'Network Error') {
                toast.add({
                    severity: 'error',
                    summary: 'Check your internet connection and try again',
                    life: 3000,
                })
                return
            }
            toast.add({
                severity: 'error',
                summary: 'Placing order error, please try again',
                life: 3000,
            })
        },
    })

    // Submit handler
    const onSubmit = handleSubmit((values) => {
        const formData = new FormData()
        formData.append('product_id', props.product.id.toString())
        formData.append('fabric_type_id', values.fabricTypeId.toString())
        formData.append('size_id', values.sizeId.toString())

        // Peek FormData key-values for debugging
        for (const pair of formData.entries()) {
            console.log(pair[0]+ ': ' + pair[1]);
        }
        mutation.mutate(formData)
    })

    onMounted(() => {
        // console.log('DesignOptionModal props:', props)
    })
</script>

<template>
    <TransitionRoot appear :show="true">
        <Dialog as="div" static @close="() => {}" class="relative z-[999]">
            <div class="fixed inset-0 overflow-y-auto bg-gray-900/80 transition-opacity">
                <div
                    class="flex flex-col lg:flex-row items-start lg:items-center justify-center p-4 text-center gap-4 lg:gap-8 min-h-screen"
                >
                    <TransitionChild
                        enter="duration-300 ease-out"
                        enter-from="opacity-0 scale-95"
                        enter-to="opacity-100 scale-100"
                        leave="duration-200 ease-in"
                        leave-from="opacity-100 scale-100"
                        leave-to="opacity-0 scale-95"
                    >
                        <DialogPanel
                            class="w-[600px] max-w-7xl h-[30rem] transform overflow-y-auto bg-white p-6 text-left align-middle shadow-xl transition-all"
                        >
                            <DialogTitle as="h1" class="text-2xl text-gray-900">
                                Product Order Option
                            </DialogTitle>

                            <div class="space-y-7">
                                <!-- T-shirt Section -->
                                <div>
                                    <div class="flex flex-col mb-5 text-sm">
                                        <p class="font-medium text-gray-700">
                                            Product:
                                            <strong>{{ props.product.name }}</strong>
                                        </p>
                                        <p class="font-medium text-gray-700">
                                            Unit Price:
                                            <strong>₱{{ props.product.unit_price }}</strong>
                                        </p>
                                    </div>
                                    <div
                                        v-if="props.product.designs && props.product.designs.length"
                                    >
                                        <img
                                            :src="props.product.designs[0].temp_url"
                                            alt="Product Design"
                                            class="object-cover rounded shadow border border-gray-200 mb-4 max-w-full h-40"
                                        />
                                    </div>

                                    <form @submit.prevent="onSubmit" class="space-y-4">
                                        <!-- Fabric Type Input -->
                                        <div class="mb-5">
                                            <label class="block text-sm text-gray-600 mb-1">
                                                Fabric Type:
                                            </label>
                                            <div class="flex gap-2">
                                                <select
                                                    v-model="fabricTypeId"
                                                    class="font-medium w-full border px-3 py-2 rounded mt-1"
                                                >
                                                    <option :value="null" disabled>
                                                        Select fabric type
                                                    </option>
                                                    <option
                                                        v-for="fab in fabricTypes"
                                                        :key="fab.id"
                                                        :value="fab.id"
                                                    >
                                                        {{ fab.name }}
                                                    </option>
                                                </select>
                                            </div>
                                            <p class="text-sm text-red-500 mt-1">
                                                {{ fabricTypeError }}
                                            </p>
                                        </div>

                                        <!-- Size Selection -->
                                        <div class="mb-5">
                                            <label class="block text-sm text-gray-600 mb-1">
                                                Size:
                                            </label>
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <button
                                                    v-for="size in sizes"
                                                    :key="size.id"
                                                    class="inline-flex items-center px-2 py-0.5 rounded-md text-sm font-semibold border
                                                        transition-colors
                                                        "
                                                    :class="sizeId === size.id
                                                        ? 'bg-blue-600 text-white border-blue-600'
                                                        : 'bg-gray-100 text-gray-700 border-gray-300 hover:opacity-75'"
                                                    type="button"
                                                    @click="sizeId = size.id"
                                                >
                                                    {{ size.name }}
                                                </button>
                                            </div>
                                            <p class="text-sm text-red-500 mt-1">
                                                {{ sizeIdError }}
                                            </p>
                                        </div>

                                        <div class="flex justify-end items-center">
                                            <button
                                                type="submit"
                                                class="flex items-center gap-1 px-4 py-2 bg-blue-600 hover:opacity-75 hover:cursor-pointer text-white text-xs font-semibold rounded transition-colors"
                                                :disabled="mutation.isPending.value"
                                            >
                                                <ShoppingCartIcon class="size-4" />
                                                Add to Cart
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>

    <Loader v-if="mutation.isPending.value" msg="Adding to Cart..." />

    <Toast />
</template>
