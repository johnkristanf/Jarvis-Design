<script lang="ts" setup>
    import { apiService } from '@/api/axios'
    import type { UploadedDesignsByID } from '@/types/design'
    import { Dialog, DialogPanel } from '@headlessui/vue'
    import { useQuery } from '@tanstack/vue-query'
    import Loader from '../Loader.vue'

    // DESIGN RELATED PROPS
    const props = defineProps<{
        selectedDesignID: number
        isAdmin: boolean
    }>()

    // MODAL CLOSING EMITS
    const emit = defineEmits(['close'])
    const handleCloseModal = () => emit('close')

    // GET ALL SELECTED UPLOADED DESIGN IMAGES
    const { isLoading, data: uploadedImages } = useQuery({
        queryKey: ['uploaded-designs', props.selectedDesignID],
        queryFn: async () => {
            console.log('selectedDesignID: ', props.selectedDesignID)

            const respData = await apiService.get<UploadedDesignsByID[]>(
                `/api/uploaded/${props.selectedDesignID}/design`,
            )
            console.log('respData uplaoded image by ID: ', respData)
            return respData
        },
    })
</script>

<template>
    <Dialog
        :open="true"
        @close="handleCloseModal"
        class="fixed inset-0 z-[999] flex items-center justify-center bg-gray-900/70 bg-opacity-50"
    >
        <DialogPanel>
            <div class="w-[560px] bg-white max-h-[90vh] overflow-y-auto p-6 rounded-2xl shadow-xl">
                <div class="mb-4">
                    <h1 class="text-lg font-semibold text-gray-900">Uploaded Designs</h1>
                    <p class="text-sm text-gray-600 mt-1" v-if="!props.isAdmin">
                        Below are the designs you’ve uploaded for this order. These files will be
                        used by our team to prepare your customized product. Please ensure the
                        designs are clear and accurate.
                    </p>
                    <p class="text-sm text-gray-600 mt-1" v-else>
                        These are the customer-uploaded designs linked to this order. Use this
                        section to review and validate the files before processing. Ensure all
                        required materials are accounted for.
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div
                        v-for="(item, index) in uploadedImages"
                        :key="index"
                        class="rounded-lg border p-2 shadow-sm hover:shadow-md transition"
                    >
                        <img
                            :src="item.temporary_url"
                            alt="Uploaded Design"
                            class="w-full h-40 object-cover rounded-md"
                        />
                    </div>
                </div>
            </div>
        </DialogPanel>
    </Dialog>

    <!-- DESIGNS LOADER -->
    <Loader v-if="isLoading" msg="Loading Designs..." />
</template>
