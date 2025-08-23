<script lang="ts" setup>
    import { ref } from 'vue'
    import { Dialog, DialogPanel } from '@headlessui/vue'
    import { useToast } from 'primevue'
    import Toast from 'primevue/toast'
    import { apiService } from '@/api/axios'
    import { useMutation, useQueryClient } from '@tanstack/vue-query'
    import Loader from '../Loader.vue'

    const props = defineProps<{
        selectedProductCategory: string | undefined
        selectedProductID: number
        selectedProductName: string | undefined
        designImages: string[] | undefined
    }>()

    const emit = defineEmits(['close'])
    const handleCloseModal = () => emit('close')

    const toast = useToast()

    // Upload state
    const selectedFile = ref<File | null>(null)
    const fileInput = ref<HTMLInputElement | null>(null)
    const previewUrl = ref<string | null>(null)

    const queryClient = useQueryClient()

    const handleFileChange = (event: Event) => {
        const file = (event.target as HTMLInputElement)?.files?.[0]
        if (file) {
            selectedFile.value = file
            previewUrl.value = URL.createObjectURL(file)
        }
    }

    // UPLOAD DESIGN MUTATION
    // UPLOAD NEW PRE MADE DESIGN MUTATION
    const mutation = useMutation({
        mutationFn: async (formData: FormData) => {
            return await apiService.post('/api/add/product/design', formData)
        },
        onSuccess: (response) => {
            console.log('add design response: ', response)

            queryClient.invalidateQueries({ queryKey: ['products'] })

            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Design uploaded successfully.',
                life: 1000,
            })

            setTimeout(() => {
                // Reset
                selectedFile.value = null
                previewUrl.value = null
                handleCloseModal()
            }, 1500)
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

    const handleUpload = () => {
        console.log('selectedFile: ', selectedFile.value)

        if (!selectedFile.value) {
            toast.add({
                severity: 'warn',
                summary: 'No File',
                detail: 'Please select a design image to upload.',
                life: 3000,
            })
            return
        }

        const formData = new FormData()
        formData.append('design', selectedFile.value)
        formData.append('product_id', props.selectedProductID.toString())
        formData.append('product_name', props.selectedProductName || '')
        formData.append('category_name', props.selectedProductCategory || '')

        mutation.mutate(formData)
    }
</script>

<template>
    <Dialog
        :open="true"
        @close="handleCloseModal"
        class="fixed inset-0 z-[999] flex items-center justify-center bg-black/50"
    >
        <DialogPanel
            class="bg-white h-[400px] overflow-y-auto shadow-xl w-full max-w-xl p-6 space-y-6"
        >
            <div class="space-y-1">
                <h2 class="text-xl font-semibold text-gray-800">Upload Design</h2>
                <p class="text-sm text-gray-500">
                    Category:
                    <strong>{{ props.selectedProductCategory }}</strong>
                </p>

                <p class="text-sm text-gray-500">
                    Product:
                    <strong>{{ props.selectedProductName }}</strong>
                </p>
            </div>

            <div class="flex flex-col">
                <h2 class="font-semibold text-gray-800 mb-2">Uploaded Designs:</h2>

                <div
                    v-if="props.designImages && props.designImages.length > 0"
                    class="grid grid-cols-1 sm:grid-cols-2 gap-4"
                >
                    <div v-for="(image, index) in props.designImages" :key="index" class="relative">
                        <img
                            :src="image"
                            :alt="`Design ${index + 1}`"
                            class="w-full max-h-64 object-contain rounded-md border border-gray-300 p-3"
                        />
                    </div>
                </div>

                <p v-else class="text-sm text-gray-500 mt-2">No uploaded designs yet.</p>
            </div>

            <div class="space-y-3 mt-10">
                <!-- DESIGN IMAGE UPLOAD -->
                <h2 class="font-semibold text-gray-800">Choose the Design to Upload:</h2>

                <input
                    ref="fileInput"
                    type="file"
                    accept="image/*"
                    multiple
                    class="w-full"
                    @change="handleFileChange"
                />

                <!-- PREVIEW SELECTED FILE -->
                <div v-if="previewUrl" class="mt-8">
                    <p class="text-xs text-gray-500 mb-1">Preview:</p>
                    <img
                        :src="previewUrl"
                        alt="Preview"
                        class="w-full max-h-64 object-contain rounded-md p-3 border-2 border-dashed border-gray-300"
                    />
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4">
                <button
                    @click="handleCloseModal"
                    class="px-4 py-2 text-sm font-medium bg-gray-500 text-white hover:opacity-75 hover:cursor-pointer"
                >
                    Cancel
                </button>
                <button
                    @click="handleUpload"
                    class="px-4 py-2 text-sm font-semibold bg-gray-900 text-white hover:cursor-pointer hover:bg-gray-700"
                >
                    Upload
                </button>
            </div>
        </DialogPanel>
    </Dialog>

    <!-- LOADER -->
    <Loader v-if="mutation.isPending.value" msg="Uploading Design..." />

    <!-- TOAST -->
    <Toast />
</template>
