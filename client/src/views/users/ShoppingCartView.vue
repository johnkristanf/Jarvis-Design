<script lang="ts" setup>
    import { ShoppingCartIcon, TrashIcon } from '@heroicons/vue/20/solid'
    import { computed, ref, watch, reactive } from 'vue'
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
            // Don't mutate TS inferred types, rely on runtime output for price
            return respData
        },
    })

    const showCheckoutModal = ref<boolean>(false)
    const selectedProductRef = ref<ProductDetails[]>() // now includes quantity

    // Selection for checkboxes
    const selectedCartIds = ref<number[]>([])

    // Quantities for each cart id
    const cartQuantities = reactive<{ [id: number]: number }>({})

    // (Re)set all cart items as selected by default whenever they change and initialize quantities
    watch(
        cartItems,
        (items) => {
            if (Array.isArray(items)) {
                // If none are selected, default to all selected on load
                if (selectedCartIds.value.length === 0 && items.length > 0) {
                    selectedCartIds.value = items.map((item) => item.id)
                }
                // Remove IDs that no longer exist
                else {
                    const ids = items.map((item) => item.id)
                    selectedCartIds.value = selectedCartIds.value.filter((id) => ids.includes(id))
                }
                // Set initial quantities for each item in cart (if not already set)
                items.forEach((item) => {
                    if (cartQuantities[item.id] === undefined || cartQuantities[item.id] < 1) {
                        cartQuantities[item.id] = item.quantity ?? 1
                    }
                })
                // Remove quantities for items that no longer exist
                for (const idStr of Object.keys(cartQuantities)) {
                    const id = Number(idStr)
                    if (!items.find((itm) => itm.id === id)) {
                        delete cartQuantities[id]
                    }
                }
            }
        },
        { immediate: true },
    )

    function toggleSelectCart(id: number) {
        if (selectedCartIds.value.includes(id)) {
            selectedCartIds.value = selectedCartIds.value.filter((cartId) => cartId !== id)
        } else {
            selectedCartIds.value.push(id)
        }
    }

    function selectAllCarts() {
        if (cartItems.value) {
            selectedCartIds.value = cartItems.value.map((item) => item.id)
        }
    }

    function clearAllCarts() {
        selectedCartIds.value = []
    }

    function incrementQuantity(id: number) {
        if (cartQuantities[id] === undefined) {
            cartQuantities[id] = 1
        } else {
            cartQuantities[id]++
        }
    }

    function decrementQuantity(id: number) {
        if (cartQuantities[id] === undefined) return
        if (cartQuantities[id] > 1) {
            cartQuantities[id]--
        }
    }

    // Total price is only from selected cart items, taking quantity into account
    const totalPrice = computed(() => {
        if (!cartItems?.value) return 0
        return cartItems.value
            .filter((item) => selectedCartIds.value.includes(item.id))
            .reduce((sum, item) => {
                let priceRaw = item.product?.unit_price
                let price: number
                if (typeof priceRaw === 'string') {
                    price = parseFloat(priceRaw)
                    if (Number.isNaN(price)) price = 0
                } else if (typeof priceRaw === 'number') {
                    price = priceRaw
                } else {
                    price = 0
                }
                const qty = cartQuantities[item.id] ?? item.quantity ?? 1
                return sum + (typeof price === 'number' ? price * qty : 0)
            }, 0)
    })

    // Handler for overall checkout (for selected items in cart)
    const handleFullCheckout = () => {
        const selectedItems = cartItems.value?.filter((item) =>
            selectedCartIds.value.includes(item.id),
        )

        console.log("selectedItems: ", selectedItems);
        

        if (selectedItems && selectedItems.length > 0) {
            // Build an array containing each selected product with its quantity
            const productsWithQuantities: ProductDetails[] = selectedItems.map((item) => ({
                ...item.product,
                size: item.size,
                desired_quantity: cartQuantities[item.id] ?? item.quantity ?? 1,
            }))

            console.log('productsWithQuantities: ', productsWithQuantities)

            handleShowCheckoutModal(productsWithQuantities)
        }
    }

    const handleShowCheckoutModal = (productWithQuantity: ProductDetails[]) => {
        showCheckoutModal.value = true
        selectedProductRef.value = productWithQuantity
    }
</script>

<template>
    <div
        class="mx-auto min-h-screen px-4 pt-10 pb-18 bg-gradient-to-b from-black to-gray-900 text-white relative"
    >
        <!-- Header -->
        <div class="flex justify-between items-center">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-2xl font-bold tracking-tight text-gray-900">Shopping Cart</h2>
                <template v-if="cartItems && cartItems.length > 0">
                    <button
                        @click="selectAllCarts"
                        class="text-xs ml-5 font-semibold underline text-blue-500 hover:text-blue-700"
                    >
                        Select All
                    </button>
                    <button
                        @click="clearAllCarts"
                        class="text-xs font-semibold underline text-red-500 hover:text-red-700"
                    >
                        Clear All
                    </button>
                </template>
            </div>
        </div>

        <!-- Cart Items -->
        <div v-if="cartItems && cartItems.length > 0" class="p-4 pb-64">
            <div
                v-for="item in cartItems"
                :key="item.id"
                class="bg-white rounded-2xl p-4 mb-3 border border-white/10 slide-in flex items-center gap-3"
            >
                <!-- Checkbox for selection -->
                <input
                    type="checkbox"
                    :checked="selectedCartIds.includes(item.id)"
                    @change="toggleSelectCart(item.id)"
                    class="h-5 w-5 accent-blue-600 border-gray-300 mr-2"
                />

                <!-- Image -->
                <img
                    :src="
                        item.product.designs &&
                        item.product.designs[0] &&
                        typeof item.product.designs[0] === 'object' &&
                        'temp_url' in item.product.designs[0]
                            ? (item.product.designs[0] as { temp_url?: string }).temp_url || ''
                            : ''
                    "
                    :alt="item.product.name"
                    class="w-24 h-24 rounded-xl object-cover bg-white/10"
                />

                <!-- Details -->
                <div class="flex-1 flex flex-col gap-2">
                    <h3 class="font-semibold text-gray-900 leading-tight">
                        {{ item.product.name }}
                    </h3>
                    <div class="flex items-center gap-2">
                        <p class="text-lg font-bold text-gray-400">
                            ₱
                            {{
                                (() => {
                                    const price = item.product.unit_price
                                    if (typeof price === 'number') return price.toLocaleString()
                                    if (typeof price === 'string') {
                                        const parsed = parseFloat(price)
                                        return !Number.isNaN(parsed) ? parsed.toLocaleString() : '-'
                                    }
                                    return '-'
                                })()
                            }}
                        </p>
                        <!-- Quantity Increment/Decrement - display only -->
                        <div class="flex items-center ml-3 border rounded px-1 bg-gray-100">
                            <button
                                @click="decrementQuantity(item.id)"
                                class="px-2 py-1 text-gray-600 hover:text-black font-bold disabled:opacity-30"
                                :disabled="cartQuantities[item.id] <= 1"
                                aria-label="Decrease quantity"
                                type="button"
                            >
                                -
                            </button>
                            <span
                                class="mx-2 min-w-[2ch] text-center select-none text-lg text-gray-900 font-medium"
                            >
                                {{ cartQuantities[item.id] ?? 1 }}
                            </span>
                            <button
                                @click="incrementQuantity(item.id)"
                                class="px-2 py-1 text-gray-600 hover:text-black font-bold"
                                aria-label="Increase quantity"
                                type="button"
                            >
                                +
                            </button>
                        </div>
                    </div>
                    <span
                        class="w-[4%] inline-block bg-blue-100 text-blue-800 text-xs text-center font-semibold px-2 py-1 rounded-md"
                    >
                        {{ item.size ? item.size.name : 'No size' }}
                    </span>
                </div>

                <!-- Quantity Control -->
                <div class="flex items-center gap-3">
                    <button
                        class="p-2 text-xs rounded-md bg-red-500/20 text-red-500 hover:opacity-75 hover:cursor-pointer active:scale-95 transition-all flex items-center justify-center"
                    >
                        <TrashIcon class="size-3" />
                        Remove
                    </button>
                    <!-- Optional: Individual checkout button, currently commented out. -->
                    <!--
                    <button
                        @click="handleShowCheckoutModal({ ...item.product, quantity: cartQuantities[item.id] ?? item.quantity ?? 1 })"
                        class="p-2 text-xs rounded-md bg-gray-700/20 text-gray-800 hover:opacity-75 hover:cursor-pointer active:scale-95 transition-all flex items-center justify-center"
                    >
                        <ShoppingCartIcon class="size-3" />
                        Checkout
                    </button>
                    -->
                </div>
            </div>

            <!-- Footer Cart Summary -->
            <div class="w-full mt-5 z-40" style="pointer-events: auto">
                <div
                    class="mx-auto px-4 py-4 bg-gray-900/98 border-t border-white/10 flex flex-col md:flex-row items-center md:justify-between gap-3"
                >
                    <div class="flex items-center gap-3 w-full md:w-auto">
                        <span class="font-semibold text-lg text-white">Total Price:</span>
                        <span class="font-bold text-2xl text-blue-300 tracking-wide">
                            ₱ {{ totalPrice.toLocaleString() }}
                        </span>
                        <span v-if="selectedCartIds.length === 0" class="text-sm text-red-400 ml-2">
                            No items selected
                        </span>
                    </div>
                    <button
                        @click="handleFullCheckout"
                        :disabled="selectedCartIds.length === 0"
                        class="mt-2 md:mt-0 inline-flex items-center gap-2 px-8 py-2 rounded-full font-semibold bg-blue-700 hover:bg-blue-800 transition-colors text-white text-md shadow-lg active:scale-[.98] disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <ShoppingCartIcon class="size-5" />
                        Checkout
                    </button>
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
