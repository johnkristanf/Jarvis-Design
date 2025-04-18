<script lang="ts" setup>

import { getAllUploadedDesigns } from '@/api/get/designs';
import { formatCurrency, formatDate } from '@/helper/designs';
import { useAuthStore } from '@/stores/user';
import { DesignStatus, type UploadedDesign } from '@/types/design';
import { UserRole } from '@/types/user';
import { computed, onMounted, ref, watch } from 'vue';
import Loader from '../Loader.vue';
import { useQuery } from '@tanstack/vue-query';
import PaymentModal from './PaymentModal.vue';
import type { DesignAttribute, ProceedPaymentData } from '@/types/payment';

import { initFlowbite } from 'flowbite'
import { InformationCircleIcon } from '@heroicons/vue/20/solid';
import { OrderTypes } from '@/types/order';



const emit = defineEmits<{
  (e: 'openUpdateModal', design: UploadedDesign): void;
}>();

const handleOpenUpdateModal = (design: UploadedDesign) => {
  emit('openUpdateModal', design);
};

const authStore = useAuthStore();
const openPaymentModal = ref<boolean>(false);

const designAttributeData = ref<DesignAttribute>({ 
    design_id: -1,
    color: -1, 
    size: -1,
    quantity: 1
});

const paymentData = ref<ProceedPaymentData>({
    price: -1,
    name: ''
});



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

const handleOpenPaymentModal = (design: UploadedDesign) => {
    if(design){
        openPaymentModal.value = true;

        paymentData.value.name = `Uploaded Design ${design.id}`;
        paymentData.value.price = design.price;
        designAttributeData.value.design_id = design.id;

        designAttributeData.value.quantity = design.quantity;
        designAttributeData.value.color = design.color.id;
        designAttributeData.value.size = design.size.id;
    }
    
}


onMounted(() => {
    initFlowbite();
})


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
                            Original Price
                        </th>

                        <th scope="col" class="px-6 py-3">
                            Quantity
                        </th>

                        <th scope="col" class="px-6 py-3">
                            Total Price
                        </th>


                        <th scope="col" class="px-6 py-3">
                            Color
                        </th>
                        <th scope="col" class="px-6 py-3">
                            Size
                        </th>
                        <th scope="col" class="px-6 py-3 flex items-center">
                            Pricing Status

                            <button data-tooltip-target="tooltip-default" type="button" class="text-black focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-1 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                                <InformationCircleIcon class="size-5"/>
                            </button>

                            <div id="tooltip-default" role="tooltip" class="normal-case absolute z-10 px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-900 rounded-lg shadow-xs opacity-0 tooltip dark:bg-gray-700">
                                Indicates the price state of your uploaded design: (PENDING, ACKNOWLEDGE, TAGGED)
                                <div class="tooltip-arrow" data-popper-arrow></div>
                            </div>
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
                            <img :src="design.temp_url" class="w-24 max-w-full max-h-full rounded" :alt="design.path">
                        </td>

                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                            {{ formatCurrency(design.price.toString()) }}
                        </td>

                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                            {{ design.quantity }}
                        </td>

                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                            {{ formatCurrency( (design.quantity * design.price).toString() ) }}
                        </td>


                        <td class="px-6 py-4 text-gray-900 dark:text-white">
                            {{ design.color.name }}
                        </td>

                        <td class="px-6 py-4 text-gray-900 dark:text-white">
                            {{ design.size.name }}
                        </td>


                        <td class="px-6 py-4">
                            <span :class="{
                                'bg-yellow-100 text-yellow-800 px-2 py-1 rounded': design.status == DesignStatus.PENDING,
                                'bg-blue-100 text-blue-800 px-2 py-1 rounded': design.status == DesignStatus.ACKNOWLEDGE,
                                'bg-green-100 text-green-800 px-2 py-1 rounded': design.status == DesignStatus.TAGGED
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

                            <button 
                                v-else-if="isUserActions && design.status == DesignStatus.TAGGED"
                                @click="handleOpenPaymentModal(design)" 
                                class="bg-blue-600 text-white rounded-md p-2 hover:opacity-75 hover:cursor-pointer"
                            >
                                Place Order
                            </button>

                            <h1 v-else>
                                Once design is tagged you can proceed to order.
                            </h1>
                        </td>
                    </tr>
                    
                    <!-- Empty state message -->
                    <tr v-if="data && data.length === 0 && !isLoading">
                        <td colspan="12" class="px-6 py-4 text-center">
                            No designs found.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        

        <!-- LOADER -->
        <div v-if="isLoading">
            <Loader msg="Loading Uploaded Designs..."/>
        </div>


        <!-- PAYMENT MODAL -->
        <PaymentModal 
            v-if="designAttributeData && paymentData && openPaymentModal"
            :orderType="OrderTypes.UPLOADED"
            :paymentData="paymentData"
            :attributeData="designAttributeData"
            :isOpen="openPaymentModal"
            @close="openPaymentModal = false"
        />
    </div>
</template>
