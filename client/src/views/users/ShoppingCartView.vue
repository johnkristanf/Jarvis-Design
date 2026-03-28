<script lang="ts" setup>
    import { ShoppingCartIcon, TrashIcon } from '@heroicons/vue/20/solid'
    import { computed, ref, watch, reactive } from 'vue'
    import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
    import { apiService } from '@/api/axios'
    import type { CartItem, ProductDetails } from '@/types/order'
    import OrderProductModal from '@/components/designs/OrderProductModal.vue'
    import { colorPalette } from '@/utils/color'
    import { useFetchAuthenticatedUser } from '@/composables/useFetchAuthenticatedUser'
    import { getCartItemImageSrc } from '@/helper/designs'

    const { authStore } = useFetchAuthenticatedUser()
    const queryClient = useQueryClient()

    // TanStack Query to fetch cart items
    const {
        data: cartItems,
        isLoading,
        isError,
    } = useQuery({
        queryKey: ['cart_items'],
        queryFn: async () => {
            const respData = await apiService.get<CartItem[]>(`/api/get/all/cart`)
            return respData
        },
    })

    // Add mutation for deleting a cart item
    const deleteCartMutation = useMutation({
        mutationFn: async (cartId: number) => {
            return await apiService.delete(`/api/delete/cart/${cartId}`)
        },
        onSuccess: () => {
            // Refresh cart items after successful delete
            queryClient.invalidateQueries({ queryKey: ['cart_items'] })
            queryClient.invalidateQueries({ queryKey: ['cart_count'] })
        },
    })

    const showCheckoutModal = ref<boolean>(false)
    const checkoutProductDetailsRef = ref<ProductDetails[]>()

    // Selection for checkboxes
    const selectedCartIds = ref<number[]>([])

    // Quantities for each cart id
    const cartQuantities = reactive<{ [id: number]: number }>({})

    // (Re)set only the FIRST cart item as selected by default whenever cartItems change, and initialize quantities
    watch(
        cartItems,
        (items) => {
            if (Array.isArray(items)) {
                // Only preselect the first cart item if none are selected
                if (selectedCartIds.value.length === 0 && items.length > 0) {
                    selectedCartIds.value = [items[0].id]
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

    // Delete cart handler
    function handleDeleteCart(id: number) {
        if (deleteCartMutation.isPending.value) return
        deleteCartMutation.mutate(id)
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

    // Total pocket costs from selected items with pocket customizations
    const totalPocketCosts = computed(() => {
        if (!cartItems?.value) return 0
        return cartItems.value
            .filter((item) => selectedCartIds.value.includes(item.id))
            .reduce((sum, item) => {
                const pocketCost = item.customizations?.pocket_costs
                return sum + (pocketCost ? Number(pocketCost) : 0)
            }, 0)
    })

    // Handler for overall checkout (for selected items in cart)
    const handleFullCheckout = () => {
        const selectedItems = cartItems.value?.filter((item) =>
            selectedCartIds.value.includes(item.id),
        )

        if (selectedItems && selectedItems.length > 0) {
            // Build an array containing each selected product with its quantity
            const checkoutProductDetails: ProductDetails[] = selectedItems.map((item) => {
                const baseProduct = {
                    ...item.product,
                    color: item.color,
                    size: item.size,
                    desired_quantity: cartQuantities[item.id] ?? item.quantity ?? 1,
                }
                // Conditionally add own_design_url and own_design_temp_url if present
                if (item.own_design_url) {
                    baseProduct.own_design_url = item.own_design_url
                }
                if (item.own_design_temp_url) {
                    baseProduct.own_design_temp_url = item.own_design_temp_url
                }
                if (item.selected_styles) {
                    baseProduct.selected_styles = item.selected_styles
                }
                if (item.selected_product_styles) {
                    baseProduct.selected_product_styles = item.selected_product_styles
                }
                if (item.customizations) {
                    baseProduct.customizations = item.customizations
                }
                return baseProduct
            })

            handleShowCheckoutModal(checkoutProductDetails)
        }
    }

    const handleShowCheckoutModal = (checkoutProductDetails: ProductDetails[]) => {
        showCheckoutModal.value = true
        checkoutProductDetailsRef.value = checkoutProductDetails
    }
</script>

<template>
    <div
        class="mx-auto min-h-screen px-4 pt-10 pb-18 bg-gradient-to-b from-white to-gray-50 text-gray-900 relative dark:bg-gray-900 dark:text-white"
    >
        <!-- Header -->
        <div class="flex justify-between items-center">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                    Shopping Cart
                </h2>
                <template v-if="cartItems && cartItems.length > 0">
                    <button
                        @click="selectAllCarts"
                        class="text-xs ml-5 font-semibold underline text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                    >
                        Select All
                    </button>
                    <button
                        @click="clearAllCarts"
                        class="text-xs font-semibold underline text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
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
                class="bg-white rounded-2xl p-4 mb-3 border border-white/10 slide-in flex flex-row items-center gap-3 sm:gap-4 dark:bg-gray-800 dark:border-gray-700 w-full"
            >
                <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                    <!-- Checkbox for selection -->
                    <input
                        type="checkbox"
                        :checked="selectedCartIds.includes(item.id)"
                        @change="toggleSelectCart(item.id)"
                        class="h-4 w-4 sm:h-5 sm:w-5 accent-blue-600 border-gray-300 dark:border-gray-600"
                    />

                    <!-- Image -->
                    <img
                        :src="getCartItemImageSrc(item)"
                        :alt="item.product?.name || 'Design'"
                        class="w-16 h-16 sm:w-24 sm:h-24 rounded-lg sm:rounded-xl object-cover bg-white/10 dark:bg-gray-700"
                    />
                </div>

                <!-- Content (Details + Actions) -->
                <div
                    class="flex-1 flex flex-col sm:flex-row sm:items-center justify-between gap-2 sm:gap-4 w-full min-w-0"
                >
                    <!-- Details -->
                    <div class="flex flex-col gap-1 sm:gap-2 items-start text-left min-w-0 flex-1">
                        <h3
                            class="font-semibold text-sm sm:text-base text-gray-900 leading-tight dark:text-white truncate w-full"
                        >
                            {{ item.product.name }}
                        </h3>
                        <div
                            class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-4 w-full"
                        >
                            <p
                                class="text-sm sm:text-lg font-bold text-gray-400 dark:text-gray-200"
                            >
                                ₱
                                {{
                                    (() => {
                                        const price = item.product.unit_price
                                        if (typeof price === 'number') return price
                                        if (typeof price === 'string') {
                                            const parsed = parseFloat(price)
                                            return !Number.isNaN(parsed)
                                                ? parsed.toLocaleString()
                                                : '-'
                                        }
                                        return '-'
                                    })()
                                }}
                            </p>
                            <!-- Quantity Increment/Decrement - display only -->
                            <div
                                class="flex items-center w-max border rounded px-1 sm:px-1 bg-gray-100 border-gray-300 dark:bg-gray-900 dark:border-gray-700 scale-90 sm:scale-100 origin-left"
                            >
                                <button
                                    @click="decrementQuantity(item.id)"
                                    class="px-2 py-1 text-gray-600 hover:text-black font-bold disabled:opacity-30 dark:text-gray-200 dark:hover:text-white"
                                    :disabled="cartQuantities[item.id] <= 1"
                                    aria-label="Decrease quantity"
                                    type="button"
                                >
                                    -
                                </button>
                                <input
                                    v-model.number="cartQuantities[item.id]"
                                    type="number"
                                    min="1"
                                    class="mx-1 sm:mx-2 w-8 sm:w-12 text-center text-base sm:text-lg text-gray-900 font-medium bg-transparent border-none focus:outline-none focus:ring-0 dark:text-white p-0 appearance-none [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                    style="appearance: textfield; -moz-appearance: textfield"
                                    @blur="
                                        () => {
                                            if (
                                                !cartQuantities[item.id] ||
                                                cartQuantities[item.id] < 1
                                            )
                                                cartQuantities[item.id] = 1
                                        }
                                    "
                                />
                                <button
                                    @click="incrementQuantity(item.id)"
                                    class="px-2 py-1 text-gray-600 hover:text-black font-bold dark:text-gray-200 dark:hover:text-white"
                                    aria-label="Increase quantity"
                                    type="button"
                                >
                                    +
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center justify-start gap-1 sm:gap-2 mt-0">
                            <span
                                class="inline-block text-[10px] sm:text-xs text-center font-semibold px-1.5 sm:px-2 py-0.5 sm:py-1 rounded-md whitespace-nowrap"
                                :style="{
                                    backgroundColor: colorPalette[item.color] || '#2563eb',
                                    color: 'white',
                                }"
                            >
                                {{ item.color ? item.color : 'No color' }}
                            </span>

                            <span
                                v-if="item.size"
                                class="inline-block bg-blue-100 text-blue-800 text-[10px] sm:text-xs text-center font-semibold px-1.5 sm:px-2 py-0.5 sm:py-1 rounded-md dark:bg-blue-900 dark:text-blue-200 whitespace-nowrap"
                            >
                                {{ item.size.name }}
                            </span>
                        </div>

                        <!-- Selected Styles -->
                        <div
                            v-if="
                                item.selected_product_styles &&
                                item.selected_product_styles.length > 0
                            "
                            class="flex flex-wrap gap-1 mt-1"
                        >
                            <span
                                v-for="style in item.selected_product_styles"
                                :key="style.id"
                                class="inline-block bg-gray-100 text-gray-600 text-[10px] sm:text-[11px] px-1.5 py-0.5 rounded border border-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600"
                                :title="style.panel ? `Panel: ${style.panel}` : ''"
                            >
                                {{ style.name }}
                            </span>
                        </div>

                        <!-- Customizations -->
                        <div
                            v-if="item.customizations"
                            class="flex flex-col gap-0.5 mt-1.5 w-full text-[11px] sm:text-xs text-gray-600 dark:text-gray-400"
                        >
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                <span v-if="item.customizations.jersey_name">
                                    Name:
                                    <strong class="text-gray-900 dark:text-gray-200">
                                        {{ item.customizations.jersey_name }}
                                    </strong>
                                </span>
                                <span v-if="item.customizations.jersey_number">
                                    Number:
                                    <strong class="text-gray-900 dark:text-gray-200">
                                        {{ item.customizations.jersey_number }}
                                    </strong>
                                </span>
                                <span v-if="item.customizations.pocket_count">
                                    Pockets:
                                    <strong class="text-gray-900 dark:text-gray-200">
                                        {{ item.customizations.pocket_count }}
                                    </strong>
                                </span>
                            </div>
                            <div
                                v-if="item.customizations.additional_instruction"
                                class="italic mt-0.5 text-[10px] sm:text-[11px]"
                            >
                                <strong>Additional Instructions:</strong>
                                {{ item.customizations.additional_instruction }}
                            </div>
                        </div>
                    </div>

                    <!-- Quantity Control (Remove Button) -->
                    <div
                        class="flex items-center sm:self-auto justify-end sm:justify-start w-auto shrink-0 mt-0"
                    >
                        <button
                            class="p-1.5 sm:p-2 w-auto text-[10px] sm:text-xs rounded-md bg-red-500/20 text-red-500 hover:opacity-75 hover:cursor-pointer active:scale-95 transition-all flex items-center justify-center dark:bg-red-900/40 dark:text-red-300"
                            @click="handleDeleteCart(item.id)"
                            :disabled="deleteCartMutation.isPending.value"
                        >
                            <TrashIcon class="size-3 sm:size-3 mr-1" />
                            <span v-if="deleteCartMutation.isPending.value">Removing...</span>
                            <span v-else>Remove</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Footer Cart Summary -->
            <div class="w-full mt-5 z-40 sticky bottom-12" style="pointer-events: auto">
                <div
                    class="mx-auto px-4 py-4 bg-gray-900/98 border-t border-white/10 flex flex-col md:flex-row items-center md:justify-between gap-3 dark:bg-gray-950 dark:border-gray-700"
                >
                    <div class="flex items-center gap-3 w-full md:w-auto">
                        <span class="font-semibold text-lg text-white dark:text-blue-300">
                            Total Price:
                        </span>
                        <span
                            class="flex items-center gap-2 font-bold text-2xl text-blue-300 tracking-wide dark:text-blue-400"
                        >
                            ₱ {{ totalPrice.toLocaleString() }}

                            <span
                                v-if="totalPocketCosts > 0"
                                class="text-base text-yellow-400 dark:text-yellow-300 font-semibold"
                            >
                                + ₱{{ totalPocketCosts }} (pocket costs)
                            </span>

                            <span
                                v-if="
                                    authStore.currentUser &&
                                    typeof authStore.currentUser.prompt_credit === 'number' &&
                                    authStore.currentUser.prompt_credit > 0
                                "
                                class="text-base text-red-500 dark:text-red-400"
                            >
                                +{{ authStore.currentUser.prompt_credit }} (prompt credits)
                            </span>
                        </span>
                        <span
                            v-if="selectedCartIds.length === 0"
                            class="text-sm text-red-400 ml-2 dark:text-red-300"
                        >
                            No items selected
                        </span>
                    </div>
                    <button
                        @click="handleFullCheckout"
                        :disabled="selectedCartIds.length === 0"
                        class="mt-2 md:mt-0 inline-flex items-center gap-2 px-8 py-2 rounded-md font-semibold bg-blue-700 hover:bg-blue-800 transition-colors text-white text-md shadow-lg active:scale-[.98] disabled:opacity-50 disabled:cursor-not-allowed dark:bg-blue-600 dark:hover:bg-blue-700"
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
            <p class="text-xl text-gray-900/60 mb-2 dark:text-white/60">Your cart is empty</p>
            <p class="text-sm text-gray-900/60 dark:text-white/60">Add items to get started</p>
        </div>

        <OrderProductModal
            v-if="showCheckoutModal && checkoutProductDetailsRef"
            :product="checkoutProductDetailsRef"
            :selectedCartIds="selectedCartIds"
            @close="showCheckoutModal = false"
        />
    </div>
</template>
