<script lang="ts" setup>
    import { apiService } from '@/api/axios'
    import type { Designs } from '@/types/design'
    import { useQuery } from '@tanstack/vue-query'
    import Loader from '../Loader.vue'
    import AttachMaterialsModal from './AttachMaterialsModal.vue'
    import { reactive, ref } from 'vue'
import { OrderTypes } from '@/types/order'

    const modals = reactive({
        show_attach_materials: false,
    })

    const selectedDesignID = ref<number>();

    // GET ALL PRE - MADE DESIGNS DATA QUERY
    const { isPending, isError, data, error } = useQuery({
        queryKey: ['pre-made-designs'],
        queryFn: async () => {
            const respData = await apiService.get<Designs[]>('/api/get/all/designs')
            return respData
        },
    });


    const handleAttachMaterialsOnDesign = (design_id: number) => {
        selectedDesignID.value = design_id
        modals.show_attach_materials = true
    }
</script>

<template>
    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
            <thead
                class="text-xs text-white uppercase bg-gray-900 dark:bg-gray-700 dark:text-gray-400"
            >
                <tr>
                    <th scope="col" class="px-8 py-3">Image</th>
                    <th scope="col" class="px-6 py-3">Design Name</th>
                    <th scope="col" class="px-6 py-3">Price</th>
                    <th scope="col" class="px-6 py-3">Action</th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="design in data"
                    :key="design.id"
                    class="bg-white border-b dark:bg-gray-800 dark:border-gray-700"
                >
                    <!-- Image -->
                    <td class="px-6 py-4">
                        <img
                            :src="design.image_path"
                            alt="design preview"
                            class="w-20 h-20 object-cover rounded-md"
                        />
                    </td>

                    <!-- Product Name -->
                    <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                        {{ design.name }}
                    </td>

                   
                    <!-- Price -->
                    <td class="px-6 py-4">₱ {{ design.price }}</td>

                    <!-- Actions -->
                    <td class="font-medium px-6 py-12 flex items-center gap-3">
                        <button
                            @click="handleAttachMaterialsOnDesign(design.id)"
                            class="text-gray-900 hover:underline"
                        >
                            Attach Materials
                        </button>
                        <button class="text-blue-600 hover:underline">Edit</button>
                        <button class="text-red-800 hover:underline">Delete</button>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- ATTACH MATERIALS MODAL -->
        <AttachMaterialsModal
            v-if="modals.show_attach_materials && selectedDesignID"
            :selectedDesignID="selectedDesignID"
            :designType="OrderTypes.PRE_MADE"
            @close="modals.show_attach_materials = false"
        />

        <!-- LOADER -->
        <div v-if="isPending">
            <Loader msg="Loading Designs..." />
        </div>

        <!-- ERROR -->
        <div v-if="isError" class="text-red-500 p-4">
            Error loading designs: {{ error?.message }}
        </div>
    </div>
</template>
