<script lang="ts" setup>
    import { ShoppingCartIcon, TrashIcon } from '@heroicons/vue/20/solid'
    import { computed, ref } from 'vue'
    import { useQuery } from '@tanstack/vue-query'
    import { apiService } from '@/api/axios'
    import type { CartItem, ProductDetails } from '@/types/order'
    import OrderProductModal from '@/components/designs/OrderProductModal.vue'

    // TanStack Query to fetch cart items
    const {
        data: cartItems,
        isLoading,
        isError,
    } = useQuery({
        queryKey: ['cart_items'],
        queryFn: async () => {
            const respData = await apiService.get<CartItem[]>(`/api/get/all/cart`)
            console.log('respData: ', respData)
            return respData
        },
    })

    const showCheckoutModal = ref<boolean>(false)
    const selectedProductRef = ref<ProductDetails>()

    const handleShowCheckoutModal = (product: ProductDetails) => {
        showCheckoutModal.value = true;
        selectedProductRef.value = product;
    }
</script>

<template>
    <div
        class="mx-auto min-h-screen px-4 pt-10 pb-18 bg-gradient-to-b from-black to-gray-900 text-white"
    >
        <!-- Header -->

        <div class="flex justify-between items-center">
            <div class="flex items-center justify-between">
                <h2 class="text-2xl font-bold tracking-tight text-gray-900">Shopping Cart</h2>
            </div>
            <!-- <div class="bg-red-600 px-3 py-1 rounded-full text-sm font-semibold">
                {{ itemCount }} items
            </div> -->
        </div>

        <!-- Cart Items -->
        <div v-if="cartItems && cartItems.length > 0" class="p-4 pb-64">
            <div
                v-for="item in cartItems"
                :key="item.id"
                class="bg-white rounded-2xl p-4 mb-3 border border-white/10 slide-in"
            >
                <div class="flex items-center gap-3">
                    <!-- Image -->
                    <img
                        :src="item.product.designs[0].image_url"
                        :alt="item.product.name"
                        class="w-24 h-24 rounded-xl object-cover bg-white/10"
                    />

                    <!-- Details -->
                    <div class="flex-1 flex flex-col gap-2">
                        <h3 class="font-semibold text-gray-900 leading-tight">
                            {{ item.product.name }}
                        </h3>
                        <p class="text-lg font-bold text-gray-400">
                            ₱ {{ item.product.unit_price }}
                        </p>
                    </div>

                    <!-- Quantity Control -->
                    <div class="flex items-center gap-3">
                        <button
                            class="p-2 text-xs rounded-md bg-red-500/20 text-red-500 hover:opacity-75 hover:cursor-pointer active:scale-95 transition-all flex items-center justify-center"
                        >
                            <TrashIcon class="size-3" />
                            Remove
                        </button>

                        <button
                            @click="handleShowCheckoutModal(item.product)"
                            class="p-2 text-xs rounded-md bg-gray-700/20 text-gray-800 hover:opacity-75 hover:cursor-pointer active:scale-95 transition-all flex items-center justify-center"
                        >
                            <ShoppingCartIcon class="size-3" />
                            Checkout
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Empty State -->
        <div v-else class="text-center px-5 py-20">
            <div class="text-8xl mb-5 opacity-50">🛒</div>
            <p class="text-xl text-white/60 mb-2">Your cart is empty</p>
            <p class="text-sm text-white/40">Add items to get started</p>
        </div>

        <OrderProductModal
            v-if="showCheckoutModal && selectedProductRef"
            categoryName="TEST"
            :product="selectedProductRef"
            @close="showCheckoutModal = false"
        />
    </div>
</template>
