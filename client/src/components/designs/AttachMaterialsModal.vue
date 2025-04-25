<script lang="ts" setup>
    import { ref, watch } from 'vue'
    import { apiService } from '@/api/axios'
    import { Dialog, DialogPanel } from '@headlessui/vue'
    import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
    import { useToast } from 'primevue'
    import { InformationCircleIcon } from '@heroicons/vue/20/solid'
    import Loader from '../Loader.vue'
    import Toast from 'primevue/toast'
import type { DesignMaterialAttachmentData } from '@/types/design'


    // DESIGN RELATED PROPS
    const props = defineProps<{
        selectedDesignID: number
    }>()


    // API RESPONSE HELPERS
    const toast = useToast();
    const queryClient = useQueryClient()


    // MODAL CLOSING EMITS
    const emit = defineEmits(['close'])
    const handleCloseModal = () => emit('close')

    // SELECTED MATERIALS IN ID VALUE ARRAY
    const selectedMaterials = ref<number[]>([])

    // QUANTITY USED EACH MATERIALS
    const materialsUsedQuantities = ref<Record<number, number>>({})

    // GET ALL PRE - MADE DESIGNS DATA QUERY
    const { isPending, isError, data, error } = useQuery({
        queryKey: ['attach-materials'],
        queryFn: async () => {
            const respData = await apiService.get('/api/get/grouped/materials')
            return respData
        },
    })

    // ATTACHMENT NOTE
    const attachementNote = `When assigning specific materials to a product design, please consider each configuration as representing one (1) unit of an order.`

    // Watch for checkbox changes and set default quantity if newly selected
    watch(selectedMaterials, (newVal) => {
        newVal.forEach((id) => {
            if (!materialsUsedQuantities.value[id]) {
                materialsUsedQuantities.value[id] = 1 // default quantity
            }
        })

        // Remove quantities for unchecked items
        for (const id in materialsUsedQuantities.value) {
            if (!newVal.includes(Number(id))) {
                delete materialsUsedQuantities.value[Number(id)]
            }
        }
    })

    // ADD NEW MATERIALS MUTATION
    const attachMutation = useMutation({
        mutationFn: async (data: DesignMaterialAttachmentData) => {
            const respData = await apiService.post('/api/attach/design/material', data)
            return respData
        },
        onSuccess: (response) => {
            console.log('response attach material: ', response)
            toast.add({
                severity: 'success',
                summary: 'Design Material Attached Successfully',
                life: 2000,
            })

            queryClient.invalidateQueries({ queryKey: ['materials'] })

            setTimeout(() => {
                handleCloseModal()
            }, 2300)
        },

        onError: (error) => {
            console.error('Error uploading preferred image:', error)
        },
    })

    // ON SUBMISSION FINAL VALUE: quantity used, design_id and material_id
    const handleSubmission = () => {

        // MAP THE CORRECT MATERIAL WITH ITS RESPECTIVE INPUTTED QUANTITY
        const mappedMaterialsAndQuantity = selectedMaterials.value.map((id) => ({
            material_id: id,
            quantity_used: materialsUsedQuantities.value[id],
        }))

        console.log('selectedDesignID: ', props.selectedDesignID)
        console.log('mappedMaterialsAndQuantity:', mappedMaterialsAndQuantity)

        const data: DesignMaterialAttachmentData = {
            design_id: props.selectedDesignID,
            material_quantity_arr: mappedMaterialsAndQuantity
        }

        attachMutation.mutate(data)
    }


</script>

<template>
    <Dialog
        v-if="!isPending"
        :open="true"
        @close="handleCloseModal"
        class="fixed inset-0 z-[999] flex items-center justify-center bg-gray-900/70 bg-opacity-50"
    >
        <DialogPanel>
            <div class="w-140 bg-white h-100 overflow-y-auto p-8">
                <div class="flex items-center gap-2">
                    <h1 class="text-lg font-medium leading-6 text-gray-900">Attach Materials</h1>

                    <span
                        v-tooltip.bottom="{
                            value: attachementNote,
                            pt: {
                                root: {
                                    style: {
                                        width: '250px',
                                    },
                                },

                                arrow: {
                                    style: {
                                        borderBottomColor: 'var(--p-primary-color)',
                                    },
                                },
                                text: '!bg-primary !text-hite !font-medium',
                            },
                        }"
                        class="cursor-pointer text-gray-500 hover:text-primary transition-colors"
                    >
                        <InformationCircleIcon class="w-5 h-5" />
                    </span>
                </div>

                <p class="text-sm font-medium leading-6 text-gray-400 mb-5">
                    Select the materials that have been used to create the selected design
                </p>

                <div v-if="data">
                    <div v-for="(materials, category) in data" :key="category" class="mb-6">
                        <h3 class="mb-2 font-semibold text-gray-900">{{ category }}</h3>
                        <ul
                            class="w-full text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg"
                        >
                            <li
                                v-for="material in materials"
                                :key="material.id"
                                class="w-full border-b border-gray-200"
                            >
                                <div class="flex items-center ps-3">
                                    <input
                                        type="checkbox"
                                        :id="'material-' + material.id"
                                        :value="material.id"
                                        v-model="selectedMaterials"
                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded-sm focus:ring-blue-500"
                                    />
                                    <label
                                        :for="'material-' + material.id"
                                        class="w-full py-3 ms-2 text-sm font-medium text-gray-900"
                                    >
                                        {{ material.name }}
                                    </label>
                                </div>

                                <div
                                    v-if="selectedMaterials.includes(material.id)"
                                    class="ps-10 pb-3"
                                >
                                    <label class="text-sm text-gray-600">
                                        Quantity used ({{ material.unit }}):
                                    </label>
                                    <input
                                        type="number"
                                        min="1"
                                        v-model.number="materialsUsedQuantities[material.id]"
                                        class="w-24 ml-2 border border-gray-300 rounded px-2 py-1 text-sm"
                                    />
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="font-medium w-full flex justify-end items-center gap-3 mt-4">
                    <button
                        type="button"
                        @click="handleCloseModal"
                        class="text-white font-medium px-4 py-2 bg-gray-500 rounded hover:opacity-75 hover:cursor-pointer"
                    >
                        Cancel
                    </button>

                    <button
                        @click="handleSubmission"
                        class="font-medium px-4 py-2 bg-gray-900 text-white rounded hover:opacity-75 hover:cursor-pointer"
                    >
                        Save
                    </button>
                </div>
            </div>
        </DialogPanel>
    </Dialog>

    <div v-if="isPending">
        <Loader msg="Loading Materials..." />
    </div>


    <div v-if="attachMutation.isPending.value">
        <Loader msg="Attaching Design Materials..." />
    </div>

    <Toast />
</template>
