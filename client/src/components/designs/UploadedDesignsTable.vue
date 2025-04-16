<script lang="ts" setup>
import { getAllUploadedDesigns } from '@/api/get/designs';
import { formatCurrency, formatDate, getStatusTag } from '@/helper/designs';
import { useAuthStore } from '@/stores/user';
import { DesignStatus, type PreferredDesign } from '@/types/design';
import { UserRole } from '@/types/user';
import { computed, onMounted, ref, watch } from 'vue';
import Loader from '../Loader.vue';
import { useQuery } from '@tanstack/vue-query';


const emit = defineEmits<{
  (e: 'openUpdateModal', design: PreferredDesign): void;
}>();

const handleOpenUpdateModal = (design: PreferredDesign) => {
  emit('openUpdateModal', design);
};

const authStore = useAuthStore();
const designs = ref<PreferredDesign[]>([]);


const isAdminActions = computed(() => {
  const role = authStore.currentUser?.role?.name;
  return !!role && role === UserRole.ADMIN;
});

const isUserActions = computed(() => {
  const role = authStore.currentUser?.role?.name;
  return !!role && role === UserRole.USER;
});


const { data, isLoading, refetch } = useQuery({
  queryKey: ['uploaded-designs'],
  queryFn: getAllUploadedDesigns,
  enabled: true, 
  
});


watch(
  () => authStore.isAuthenticated,
  (isAuthenticated) => {
    if (isAuthenticated) {
      refetch();
    }
  }
);

console.log("uploaded designs data: ", data.value);


</script>

<template>
    <div class="card mt-5">
        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
            <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-16 py-3">
                            <span>Image</span>
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Price
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Color
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Size
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Pricing Status
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Upload Date
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="design in data" :key="design.id" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 border-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600">
                        
                        <td class="p-4">
                            <img :src="design.path" class="w-24 max-w-full max-h-full rounded" :alt="design.path">
                        </td>

                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                            {{ formatCurrency(design.price.toString()) }}
                        </td>

                        <td class="px-6 py-4 text-gray-900 dark:text-white">
                            {{ design.color_name }}
                        </td>

                        <td class="px-6 py-4 text-gray-900 dark:text-white">
                            {{ design.size_name }}
                        </td>


                        <td class="px-6 py-4">
                            <span :class="{
                                'bg-yellow-100 text-yellow-800 px-2 py-1 rounded': getStatusTag(design.status) == 'info',
                                'bg-blue-100 text-blue-800 px-2 py-1 rounded': getStatusTag(design.status) == 'warn',
                                'bg-green-100 text-green-800 px-2 py-1 rounded': getStatusTag(design.status) == 'success'
                            }">
                                {{ design.status.toUpperCase() }}
                            </span>
                        </td>

                        <td class="px-6 py-4 text-gray-900 dark:text-white">
                            {{ formatDate(design.created_at) }}
                        </td>

                        <td class="px-6 py-4">
                            <button 
                                v-if="isAdminActions"
                                @click="handleOpenUpdateModal(design)" 
                                class="bg-blue-600 text-white rounded-md p-2 hover:opacity-75 hover:cursor-pointer"
                            >
                                Update
                            </button>
                        </td>
                    </tr>
                    
                    <!-- Empty state message -->
                    <tr v-if="data && data.length === 0 && !isLoading">
                        <td colspan="7" class="px-6 py-4 text-center">
                            No designs found.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- LOADER -->
        <div v-if="isLoading">
            <Loader msg="Loading Customer Uploaded Designs..."/>
        </div>
    </div>
</template>
