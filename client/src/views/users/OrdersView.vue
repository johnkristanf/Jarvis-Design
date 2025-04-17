<script lang="ts" setup>

import { getAllUploadedDesigns } from '@/api/get/designs';
import { formatCurrency  } from '@/helper/designs';
import { useAuthStore } from '@/stores/user';
import { UserRole } from '@/types/user';
import { computed, onMounted, ref, watch } from 'vue';
import { useQuery } from '@tanstack/vue-query';
import type { DesignAttribute, ProceedPaymentData } from '@/types/payment';

import { initFlowbite } from 'flowbite'
import { ArrowRightIcon, WalletIcon } from '@heroicons/vue/20/solid';
import { Button, Drawer } from 'primevue';
import { OrderStatus } from '@/types/order';


const authStore = useAuthStore();
const openPaymentModal = ref<boolean>(false);
const visibleRight = ref(false);


const designAttributeData = ref<DesignAttribute>({ 
    color: -1, 
    size: -1,
    quantity: 1
});

const paymentData = ref<ProceedPaymentData>({
    price: -1,
    name: ''
});


const testOrdersData = ref([
    {
        id: 1,
        order_id: `ORD-${Date.now()}-${Math.floor(Math.random() * 10000)}`,
        temp_url: "/jarvis-logo-circle.png",
        quantity: 5,
        paid_amount: 250,
        status: "in_progress",
        created_at: "January 23, 2003 10:30PM",
    },

    {
        id: 2,
        order_id: `ORD-${Date.now()}-${Math.floor(Math.random() * 10000)}`,
        temp_url: "/jarvis-logo-circle.png",
        quantity: 15,
        paid_amount: 500,
        status: "pickup",
        created_at: "January 23, 2003 10:30PM",
    },

    {
        id: 3,
        order_id: `ORD-${Date.now()}-${Math.floor(Math.random() * 10000)}`,
        temp_url: "/jarvis-logo-circle.png",
        quantity: 20,
        paid_amount: 800,
        status: "delivery",
        created_at: "January 23, 2003 10:30PM",
    },

    {
        id: 4,
        order_id: `ORD-${Date.now()}-${Math.floor(Math.random() * 10000)}`,
        temp_url: "/jarvis-logo-circle.png",
        quantity: 80,
        paid_amount: 10000,
        status: "completed",
        created_at: "January 23, 2003 10:30PM",
    },
])


// const isAdminActions = computed(() => {
//   const role = authStore.currentUser?.role?.name;
//   return !!role && role === UserRole.ADMIN;
// });

// const isUserActions = computed(() => {
//   const role = authStore.currentUser?.role?.name;
//   return !!role && role === UserRole.USER;
// });


const { data, isLoading, refetch } = useQuery({
  queryKey: ['uploaded'],
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

onMounted(() => {
    initFlowbite();
})


const randomNum = ``;

</script>

<template>
    <div class="card mt-5 p-8">

        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-bold tracking-tight text-gray-900">Orders & Shipping Details</h2>

            <!-- SEARCH INPUT MUST AUTO FETCH ONCHANGE -->
            <div class="flex items-center hover:cursor-pointer hover:opacity-75">
                <label for="default-search" class="mb-2 text-sm font-medium text-gray-900 sr-only dark:text-white">Search</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                            <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                            </svg>
                        </div>
                        <input type="search" id="default-search" class="block w-full p-4 ps-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Search Order ID, Status..." required />
                </div>
            </div>

            <!-- DRAWER CONTAINER -->
            <!-- <div v-if="visibleRight" class="absolute card flex justify-center">
                <Drawer v-model:visible="visibleRight"  position="right" class="!w-full md:!w-80 lg:!w-[30rem]">
                    <template #header>
                        <div class="flex items-center gap-2">
                            <WalletIcon class="size-5"/>
                            <span class="font-bold">Payment History</span>
                        </div>
                    </template>

                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>
                </Drawer>
            </div> -->
        </div>

        <div class="relative overflow-x-auto shadow-md sm:rounded-lg mt-5">
            <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>

                        <th scope="col" class="px-16 py-3">
                            <span>Order ID</span>
                        </th>

                        <th scope="col" class="px-16 py-3">
                            <span>Image Design</span>
                        </th>
                        
                        <th scope="col" class="px-6 py-3">
                            Quantity
                        </th>

                        <th scope="col" class="px-6 py-3">
                            Total Paid Price
                        </th>

                        <th scope="col" class="px-6 py-3 flex items-center">
                            Order Status
                        </th>

                        <th scope="col" class="px-6 py-3">
                            Order Date
                        </th>
                       
                       
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="order in testOrdersData" :key="order.id" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 border-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600">
                        
                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                            {{ order.order_id }}
                        </td>

                        <td class="p-4">
                            <img :src="order.temp_url" class="w-24 max-w-full max-h-full rounded" :alt="order.temp_url">
                        </td>

                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                            {{ order.quantity }}
                        </td>

                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                            {{ formatCurrency(order.paid_amount.toString()) }}
                        </td>

                       

                        <td class="px-6 py-4">
                            <span :class="{
                                    'bg-yellow-100 text-yellow-800 px-2 py-1 rounded': order.status === OrderStatus.IN_PROGRESS,
                                    'bg-sky-100 text-sky-800 px-2 py-1 rounded': order.status === OrderStatus.DELIVERY,
                                    'bg-indigo-100 text-indigo-800 px-2 py-1 rounded': order.status === OrderStatus.PICKUP,
                                    'bg-green-100 text-green-800 px-2 py-1 rounded': order.status === OrderStatus.COMPLETED,
                                }">

                                {{ order.status.toUpperCase() }}
                            </span>
                        </td>

                        <!-- YOU FORMATTER HELPER WITH THIS WHEN FETCHING REAL CREATED AT DATA -->
                        <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                            {{ order.created_at }}
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

    </div>
</template>
