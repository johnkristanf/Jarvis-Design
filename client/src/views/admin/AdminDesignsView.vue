<script lang="ts" setup>
import UpdateUploadedDesignsModal from '@/components/designs/UpdateUploadedDesignsModal.vue';
import UploadedDesignsTable from '@/components/designs/UploadedDesignsTable.vue';
import type { PreferredDesign } from '@/types/design';
import { ref } from 'vue';

const isUpdateModalOpen = ref<boolean>(false);
const selectedDesign = ref<PreferredDesign | null>(null);

const openUpdateModal = (design: PreferredDesign) => {
  selectedDesign.value = design;
  isUpdateModalOpen.value = true;
};

</script>

<template>
    
    <div class="w-full p-4 rounded-md bg-gray-100 border-1 border-gray-400">
        <h1>Customer Uploaded Designs</h1>
        <UploadedDesignsTable 
            @openUpdateModal="openUpdateModal"
        />
    </div>

    <UpdateUploadedDesignsModal 
        v-if="isUpdateModalOpen"
        :isOpen="isUpdateModalOpen"
        :design="selectedDesign"
        @close="isUpdateModalOpen = false"
    />
</template>