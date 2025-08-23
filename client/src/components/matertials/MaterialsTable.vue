<script lang="ts" setup>
    import { reactive, ref, watch } from 'vue'
    import AddMaterialsModal from './AddMaterialsModal.vue'
    import { PlusIcon } from '@heroicons/vue/20/solid'
    import { useQuery } from '@tanstack/vue-query'
    import { apiService } from '@/api/axios'
    import type { Material } from '@/types/materials'
    import Loader from '../Loader.vue'
    import { useToast } from 'primevue/usetoast'
    import Toast from 'primevue/toast'

    import EditMaterialsModal from './EditMaterialsModal.vue'
    import DeleteDialog from '../DeleteDialog..vue'

    // REF TOGGLER OF ADD NEW MATERIALS MODAL
    const modals = reactive({
        show_add_materials_modal: false,
        show_edit_materials_modal: false,
    })

    // GET MATERIALS DATA QUERY
    const { isPending, data: designMaterials } = useQuery({
        queryKey: ['materials'],
        queryFn: async () => {
            const respData = await apiService.get<Material[]>('/api/get/materials')
            console.log('designMaterials: ', respData)

            return respData
        },
    })

    // PRIMEVUE TOAST FOR ALERT
    const toast = useToast()

    // REFERENCE FOR SELECT MATERIAL FOR EDIT
    const selectedMaterial = ref<Material>()

    // ALERT RESTOCK SOUND
    // const userHasInteracted = ref(false)
    // const alertSound = new Audio('/sounds/alarm.mp3')

    // CHECKS FOR REAL TIME UPDATE IF THERE ARE USER ORDERS THAT PAYS SUCCESSFULLY
    // onMounted(() => {
    //     const enableAudio = () => {
    //         userHasInteracted.value = true
    //         alertSound.play().then(() => alertSound.pause())
    //         window.removeEventListener('click', enableAudio)
    //     }

    //     window.addEventListener('click', enableAudio)

    //     echo.connector.pusher.connection.bind('connected', () => {
    //         console.log('✅ Echo is connected to Reverb!')
    //     })

    //     echo.channel('payments').listen('.payment.successful', async (e: any) => {
    //         console.log('Payment completed!', e)

    //         // REFETCH MATERIALS EVERYTIME SOMEONE SUCCESSFULLY ORDERS
    //         refetch()
    //     })
    // })

    const onShowEditMaterial = (material: Material) => {
        selectedMaterial.value = material
        modals.show_edit_materials_modal = true
    }

    // WATCH THE MATERIALS EVERY REFETCH TO CHECK IF THERE ARE ANY LOW STOCK MATERIALS
    watch(designMaterials, (newMaterials) => {
        console.log('refetcheddd')

        if (newMaterials) {
            const lowStockItems = newMaterials.filter(
                (material) => material.quantity <= material.reorder_level,
            )

            if (lowStockItems.length > 0) {
                const materialNames = lowStockItems.map((m) => m.name).join(', ')
                toast.add({
                    severity: 'warn',
                    summary: 'Low Stock Alert',
                    detail: `The following fabric are low on stock: ${materialNames}`,
                    closable: true,
                })
            }
        }
    })
</script>

<template>
    <div class="relative overflow-x-auto">
        <div class="flex justify-end gap-3 pb-3">
            <button
                @click="modals.show_add_materials_modal = true"
                type="button"
                class="w-[20%] flex items-center justify-center gap-1 px-3 py-2 text-sm font-medium text-center text-white bg-gray-900 rounded-lg hover:opacity-75 hover:cursor-pointer"
            >
                <PlusIcon class="size-6" />
                Add New Fabric
            </button>

            <!-- SEARCH FIELD -->
            <div class="dark:bg-gray-900">
                <label for="table-search" class="sr-only">Search</label>
                <div class="relative mt-1">
                    <div
                        class="absolute inset-y-0 rtl:inset-r-0 start-0 flex items-center ps-3 pointer-events-none"
                    >
                        <svg
                            class="w-4 h-4 text-gray-500 dark:text-gray-400"
                            aria-hidden="true"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 20 20"
                        >
                            <path
                                stroke="currentColor"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"
                            />
                        </svg>
                    </div>
                    <input
                        type="text"
                        id="table-search"
                        class="block pt-2 ps-10 text-sm text-gray-900 border border-gray-300 rounded-lg w-80 bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                        placeholder="Search for fabrics"
                    />
                </div>
            </div>
        </div>

        <table
            class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400 mt-6"
        >
            <thead
                class="text-xs uppercase bg-gray-800 text-white dark:bg-gray-700 dark:text-gray-400"
            >
                <tr>
                    <!-- <th scope="col" class="px-6 py-3">Material Category</th> -->
                    <th scope="col" class="px-6 py-3">Fabric Name</th>
                    <th scope="col" class="px-6 py-3">Unit</th>
                    <th scope="col" class="px-6 py-3">Stock Quantity</th>
                    <th scope="col" class="px-6 py-3">Reorder Level</th>
                    <th scope="col" class="px-6 py-3">Action</th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="material in designMaterials"
                    :key="material.id"
                    class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 border-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600"
                >
                    <!-- <td class="px-6 py-4">{{ material.category?.name }}</td> -->
                    <td class="px-6 py-4">{{ material.name }}</td>
                    <td class="px-6 py-4">{{ material.unit }}</td>

                    <td
                        :class="[
                            material.quantity <= material.reorder_level ? 'text-red-900' : '',
                            'px-6 py-4',
                        ]"
                    >
                        {{ material.quantity }}
                    </td>

                    <td class="px-6 py-4">{{ material.reorder_level }}</td>
                    
                    <td class="px-6 py-6 flex gap-2">
                        <button
                            class="font-medium text-blue-600 dark:text-blue-500 hover:underline"
                            @click="onShowEditMaterial(material)"
                        >
                            Edit
                        </button>
                        <DeleteDialog
                            :selectedID="material.id"
                            endpoint_url="/api/delete/material"
                            query_key="materials"
                        />
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- ADD MATERIAL MODAL -->
    <AddMaterialsModal
        v-if="modals.show_add_materials_modal"
        :open="modals.show_add_materials_modal"
        @close="modals.show_add_materials_modal = false"
    />

    <!-- EDIT MATERIAL MODAL -->
    <EditMaterialsModal
        v-if="modals.show_edit_materials_modal && selectedMaterial"
        :open="modals.show_edit_materials_modal"
        :material="selectedMaterial"
        @close="modals.show_edit_materials_modal = false"
    />

    <div v-if="isPending">
        <Loader msg="Loading Materials..." />
    </div>

    <Toast />
</template>
