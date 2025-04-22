<script lang="ts" setup>
    import { uploadDesign } from '@/api/post/generate'
    import { Dialog, DialogPanel, TransitionChild, TransitionRoot, RadioGroup, RadioGroupOption } from '@headlessui/vue'
    import { useMutation, useQueryClient } from '@tanstack/vue-query'
    import { ProgressSpinner } from 'primevue'
    import { useToast } from 'primevue/usetoast';

    import Toast from 'primevue/toast';

    import { ref } from 'vue'
    import Loader from '../Loader.vue'
    import { useProductAttributes } from '@/composables/useProductAttribute'

    import ListSelectBox from '../ListSelectBox.vue'
    import { OrderOptions } from '@/types/order'


    // COMPONENT PROPS
    const props = defineProps<{
        isOpen: boolean
        onClose: () => void
    }>()

    // MODAL TOGGLING HANDLERS
    const emit = defineEmits(['close'])
    const handleCloseModal = () => emit('close')


    // LOADER TOAST VARIABLES
    const toast = useToast()
    const loaderMsg = ref<string>('')
    const quantity = ref<number>(0)

    const queryClient = useQueryClient()


    // SELECTION OF ORDER OPTIONS
    const orderOptions = ref([
        { id: 1, name: OrderOptions.DELIVERY },
        { id: 2, name: OrderOptions.PICK_UP }
    ])

    const uploadPreferredDesignMutation = useMutation({
        mutationFn: uploadDesign,
        onSuccess: (response) => {
            console.log('response uploadPreferredDesignMutation: ', response)
            toast.add({
                severity: 'success',
                summary: 'Design Uploaded Successfully',
                detail: 'Please wait for 1-2 bussiness days before your design get tagged',
                life: 3000
            })

            queryClient.invalidateQueries({ queryKey: ['uploaded-designs'] })
            handleCloseModal()
        },

        onError: (error) => {
            console.error('Error uploading preferred image:', error)
        },

        onMutate: () => {
            loaderMsg.value = 'Uploading Preferred Image...'
        }
    })

    const formData = new FormData()

    // GET DATABASE CREATED COLORS AND SIZES
    const { colors, sizes, loadingColors, loadingSizes } = useProductAttributes()

    const selectedColor = ref(colors.value && colors.value[0])
    const selectedSize = ref(sizes.value && sizes.value[2])
    const selectedOrderOptions = ref(orderOptions.value[0])

    const selectedFile = ref<File | null>(null)
    const fileInput = ref<HTMLInputElement | null>(null)
    const imagePreview = ref<string | null>(null)

    // FILE CHANGE HANDLER

    const handleFileChange = (event: Event) => {
        const input = event.target as HTMLInputElement

        if (input.files && input.files.length > 0) {
            selectedFile.value = input.files[0]

            // Update formData with the selected file
            formData.delete('file')
            formData.append('file', selectedFile.value)

            console.log('Selected file:', selectedFile.value.name)

            // UPLOADED IMAGE PREVIEWER
            const reader = new FileReader()
            reader.onload = (e) => {
                imagePreview.value = e.target?.result as string
            }
            reader.readAsDataURL(selectedFile.value)
        }
    }

    // UPLOADING CUSTOMER DESIGN HANDLER
    const handleUploadDesign = () => {
        console.log('selectedOrderOptions: ', selectedOrderOptions.value)
        console.log('selectedColor: ', selectedColor.value)
        console.log('selectedSize: ', selectedSize.value)
        console.log('quantity: ', quantity.value)

        if (!selectedFile.value) {
            toast.add({ severity: 'error', summary: 'Error', detail: 'Please select a file first', life: 3000 })
            return
        }

        formData.append('order_option', selectedOrderOptions.value.name)
        formData.append('color', selectedColor.value.id.toString())
        formData.append('size', selectedSize.value.id.toString())
        formData.append('quantity', quantity.value.toString())

        console.log('file: ', formData.get('file'))

        uploadPreferredDesignMutation.mutate(formData)
    }

    console.log('colors: ', colors.value)
    console.log('sizes: ', sizes.value)
</script>

<template>
    <TransitionRoot as="template" :show="isOpen">
        <Dialog class="relative z-10" @close="handleCloseModal">
            <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                <div class="fixed inset-0 bg-gray-500/75 transition-opacity" />
            </TransitionChild>

            <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                <div class="flex min-h-full items-stretch justify-center text-center md:items-center md:px-2 lg:px-4">
                    <TransitionChild
                        as="template"
                        enter="ease-out duration-300"
                        enter-from="opacity-0 translate-y-4 md:translate-y-0 md:scale-95"
                        enter-to="opacity-100 translate-y-0 md:scale-100"
                        leave="ease-in duration-200"
                        leave-from="opacity-100 translate-y-0 md:scale-100"
                        leave-to="opacity-0 translate-y-4 md:translate-y-0 md:scale-95"
                    >
                        <DialogPanel class="flex w-[60%] transform text-left text-base transition md:my-8 md:max-w-2xl md:px-4 lg:max-w-4xl">
                            <div class="relative flex w-full items-center overflow-hidden mb-16 bg-white px-4 pt-14 pb-8 shadow-2xl sm:px-6 sm:pt-8 md:p-6 lg:p-8">
                                <button
                                    type="button"
                                    class="absolute top-4 right-4 text-gray-400 hover:text-gray-500 sm:top-8 sm:right-6 md:top-6 md:right-6 lg:top-8 lg:right-8"
                                    @click="handleCloseModal"
                                >
                                    <span class="sr-only">Close</span>
                                    <XMarkIcon class="size-6" aria-hidden="true" />
                                </button>

                                <div class="flex justify-between w-full">
                                    <div class="w-full sm:col-span-8 lg:col-span-7">
                                        <h2 class="text-2xl font-bold text-gray-900 sm:pr-12">Choose the attribute of your desired design</h2>

                                        <section aria-labelledby="options-heading" class="mt-10 w-full">
                                            <h3 id="options-heading" class="sr-only">design options</h3>

                                            <form @submit.prevent="handleUploadDesign" class="w-full">
                                                <!-- QUANTITY -->

                                                <fieldset class="mt-10 w-full" aria-label="Select quantity">
                                                    <div class="flex items-center justify-between">
                                                        <div class="text-md">Quantity</div>
                                                    </div>

                                                    <div class="mt-4 w-full">
                                                        <input
                                                            type="number"
                                                            min="1"
                                                            v-model="quantity"
                                                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                        />
                                                    </div>
                                                </fieldset>

                                                <!-- ORDER TYPES -->

                                                <fieldset v-if="orderOptions" class="mt-10" aria-label="Choose a color">
                                                    <div class="flex items-center justify-between">
                                                        <div class="text-md">Order Options</div>
                                                    </div>

                                                    <!-- DESIGN STATUS SELECT ELEMENT -->

                                                    <div class="mt-4 w-full">
                                                        <ListSelectBox v-model="selectedOrderOptions" :options="orderOptions" displayKey="name" />
                                                    </div>

                                                    <!-- END OF DESIGN STATUS SELECT ELEMENT -->
                                                </fieldset>

                                                <!-- COLORS -->

                                                <fieldset v-if="colors && !loadingColors" class="mt-10" aria-label="Choose a size">
                                                    <div class="flex items-center justify-between">
                                                        <div class="text-md">Color</div>
                                                    </div>

                                                    <!-- DESIGN STATUS SELECT ELEMENT -->
                                                    <div class="mt-5 w-full">
                                                        <ListSelectBox v-model="selectedColor" :options="colors" displayKey="name" />
                                                    </div>

                                                    <!-- END OF DESIGN STATUS SELECT ELEMENT -->
                                                </fieldset>

                                                <div class="w-full flex items-center" v-if="loadingColors">
                                                    <h1>Loading Colors...</h1>
                                                    <ProgressSpinner :pt="{ root: { style: { width: '40px' } } }" />
                                                </div>

                                                <!-- SIZES -->

                                                <fieldset v-if="sizes && !loadingSizes" class="mt-10" aria-label="Choose a size">
                                                    <div class="flex items-center justify-between">
                                                        <div class="text-md">Size</div>
                                                    </div>

                                                    <RadioGroup v-model="selectedSize" class="mt-4 grid grid-cols-4 gap-4">
                                                        <RadioGroupOption as="template" v-for="size in sizes" :key="size.name" :value="size" v-slot="{ active, checked }">
                                                            <div
                                                                :class="[
                                                                    active ? 'ring-2 ring-indigo-500' : '',
                                                                    'group relative hover:cursor-pointer flex items-center justify-center rounded-md border px-4 py-3 text-sm font-medium uppercase hover:bg-gray-50 focus:outline-hidden sm:flex-1'
                                                                ]"
                                                            >
                                                                <span>{{ size.name }}</span>
                                                            </div>
                                                        </RadioGroupOption>
                                                    </RadioGroup>
                                                </fieldset>

                                                <div class="w-full flex items-center" v-if="loadingSizes">
                                                    <h1>Loading Sizes...</h1>
                                                    <ProgressSpinner :pt="{ root: { style: { width: '40px' } } }" />
                                                </div>

                                                <!-- UPLOAD INPUT -->

                                                <div class="mt-10">
                                                    <div class="flex items-center justify-between">
                                                        <p>File Upload</p>
                                                    </div>

                                                    <div v-if="!imagePreview" class="mt-4 border-2 border-dashed border-gray-300 rounded-md p-6 flex flex-col items-center justify-center">
                                                        <div class="flex items-center justify-center">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path
                                                                    stroke-linecap="round"
                                                                    stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
                                                                />
                                                            </svg>
                                                        </div>

                                                        <div class="mt-4 text-center">
                                                            <p class="text-sm text-gray-600">Drag and drop files to here to upload</p>
                                                            <p class="text-xs text-gray-500 mt-1">or</p>
                                                        </div>

                                                        <input ref="fileInput" type="file" accept="image/*" class="hidden" @change="handleFileChange" />

                                                        <button
                                                            type="button"
                                                            class="mt-4 px-4 py-2 bg-white border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none"
                                                            @click="fileInput?.click()"
                                                        >
                                                            Select File
                                                        </button>
                                                    </div>

                                                    <!-- Image Preview Section -->
                                                    <div v-else class="mt-4 border-2 border-gray-300 rounded-md p-4">
                                                        <div class="flex flex-col items-center">
                                                            <img :src="imagePreview" alt="Design preview" class="max-h-48 object-contain rounded-md" />
                                                            <div class="mt-3 flex items-center justify-center">
                                                                <button
                                                                    type="button"
                                                                    class="text-sm text-indigo-600 hover:text-indigo-500"
                                                                    @click="
                                                                        () => {
                                                                            imagePreview = null
                                                                            selectedFile = null
                                                                        }
                                                                    "
                                                                >
                                                                    Change
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <button
                                                    type="submit"
                                                    class="mt-6 flex w-full items-center justify-center rounded-md border border-transparent bg-gray-900 px-8 py-3 text-base font-medium text-white hover:opacity-75 hover:cursor-pointer"
                                                >
                                                    Upload Design
                                                </button>
                                            </form>
                                        </section>
                                    </div>
                                </div>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>

    <div v-if="uploadPreferredDesignMutation.isPending">
        <Loader :msg="loaderMsg" />
    </div>

    <Toast />
</template>
