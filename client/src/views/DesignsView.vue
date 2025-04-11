<script lang="ts" setup>
    import ProductViewModal from '@/components/designs/ProductViewModal.vue';
    import type { Product } from '@/types/product';
    import { ref } from 'vue';


    const products: Product[] = [
        {
            id: 1,
            name: 'Basic Tee 6-Pack ',
            price: '$192',
            imageSrc: 'https://tailwindcss.com/plus-assets/img/ecommerce-images/product-quick-preview-02-detail.jpg',
            
            colors: [
                { name: 'White', class: 'bg-white', selectedClass: 'ring-gray-400' },
                { name: 'Gray', class: 'bg-gray-200', selectedClass: 'ring-gray-400' },
                { name: 'Black', class: 'bg-gray-900', selectedClass: 'ring-gray-900' },
            ],

            sizes: [
                { name: 'XXS', inStock: true },
                { name: 'XS', inStock: true },
                { name: 'S', inStock: true },
                { name: 'M', inStock: true },
                { name: 'L', inStock: true },
                { name: 'XL', inStock: true },
                { name: 'XXL', inStock: true },
                { name: 'XXXL', inStock: false },
            ],
        }

    ]

    
    const selectedProduct = ref<Product>(); 
    const openModal = ref(false);

    const handleSelectProduct = (product: Product) => {
        openModal.value = true
        selectedProduct.value = product; 

        console.log("ref product (initial): ", selectedProduct.value);

    };

    


</script>

<template>
    <div class="bg-white">
      <div class="mx-auto max-w-2xl px-4 pt-10 pb-18 sm:px-6 sm:py-24 lg:max-w-7xl lg:px-8">
        <h2 class="text-2xl font-bold tracking-tight text-gray-900">Explore our designs</h2>
  
        <div class="mt-6 grid grid-cols-1 gap-x-6 gap-y-10 sm:grid-cols-2 lg:grid-cols-4 xl:gap-x-8">
            <div v-for="product in products" @click="handleSelectProduct(product)" :key="product.id" class="group relative">
                <img :src="product.imageSrc" class="aspect-square w-full rounded-md bg-gray-200 object-cover group-hover:opacity-75 lg:aspect-auto lg:h-80" />
                <div class="mt-4 flex justify-between">
                    <div>
                        <h3 class="text-sm text-gray-700">
                            <h1>
                                <span aria-hidden="true" class="absolute inset-0" />
                                {{ product.name }}
                            </h1>
                        </h3>
                        <!-- <p class="mt-1 text-sm text-gray-500">{{ product.color }}</p> -->
                    </div>
                    <p class="text-sm font-medium text-gray-900">{{ product.price }}</p>
                </div>
            </div>
        </div>
      </div>
    </div>

    <ProductViewModal
        v-if="selectedProduct"
        :product="selectedProduct"
        :isOpen="openModal"
        @close="openModal = false"
    />

</template>
  
  