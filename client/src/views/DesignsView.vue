<script lang="ts" setup>
    import { generateImageDesign } from '@/api/post/generate';
    import ProductViewModal from '@/components/designs/ProductViewModal.vue';
import Loader from '@/components/Loader.vue';
    import type { DesignGenerate, Designs } from '@/types/design';
    import { useMutation } from '@tanstack/vue-query';
    import { Select } from 'primevue';
    import { useField, useForm } from 'vee-validate';
    import { ref } from 'vue';


    const designs: Designs[] = [
        {
            id: 1,
            name: 'Basic Tee 6-Pack ',
            price: '$192',
            imageSrc: 'https://tailwindcss.com/plus-assets/img/ecommerce-images/product-quick-preview-02-detail.jpg',
            
            colors: [
                { name: 'White' },
                { name: 'Gray'  },
                { name: 'Black' },
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
        },


        {
            id: 2,
            name: 'Basic Tee 6-Pack ',
            price: '$192',
            imageSrc: 'https://tailwindcss.com/plus-assets/img/ecommerce-images/product-quick-preview-02-detail.jpg',
            
            colors: [
                { name: 'White' },
                { name: 'Gray'  },
                { name: 'Black' },
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

    
    const selectedDesign = ref<Designs>(); 
    const openModal = ref(false);
    const imageUrls = ref([]);
    const isLoadingMutation = ref(false);

    const handleSelectDesign = (product: Designs) => {
        openModal.value = true
        selectedDesign.value = product; 

        console.log("ref product (initial): ", selectedDesign.value);

    };

    const { handleSubmit } = useForm();

    const mutation = useMutation({
        mutationFn: generateImageDesign,
        onSuccess: (response) => {
            isLoadingMutation.value = false;
            console.log("response: ", response)

            if(response && response.data.image_urls){
                imageUrls.value = response.data.image_urls
            }
        },

        onError: (error) => {
            isLoadingMutation.value = false; 
            console.error("Error generating image:", error);
        },

        onMutate: () => {
            isLoadingMutation.value = true; 
        },
    });


    const preferences = ref([
        {name: 'realistic'},
        {name: 'cartoon'},
    ])

    const { value: prompt, } = useField('prompt');
    const { value: style_preference, } = useField('style_preference');

    const onImageGenerate = handleSubmit(values => {
        const designGengerateData: DesignGenerate = {
            prompt: values.prompt,
            style_preference: values.style_preference.name,
        }

        console.log("isSubmitting: ", isLoadingMutation.value);

        mutation.mutate(designGengerateData)
        
    });

    const aiAPIURL = import.meta.env.VITE_AI_API_URL;
    console.log("imageUrls: ", imageUrls.value.length);
    
    

</script>

<template>
    <div class="bg-white">
      <div class="mx-auto max-w-2xl px-4 pt-10 pb-18 sm:px-6 sm:py-24 lg:max-w-7xl lg:px-8">

        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-bold tracking-tight text-gray-900">Explore our designs</h2>


            <div class="card flex justify-center">
                <form @submit.prevent="onImageGenerate" class="flex gap-2 w-full sm:w-56">
                    <div class="flex gap-1">
                        <input
                            type="text"
                            id="prompt"
                            v-model="prompt"
                            placeholder="AI Prompt for Design"
                            class="font-medium block w-full rounded-md bg-white px-3 py-1.5 text-base text-black placeholder:text-gray-400 focus:outline-none sm:text-sm/6"
                        />

                        <Select v-model="style_preference" :options="preferences" optionLabel="name" placeholder="Style Preference" class="w-full md:w-56" />

                    </div>

                    <button 
                        type="submit" 
                        class="p-1 rounded-md bg-black text-white hover:cursor-pointer hover:opacity-75"
                    >
                        Search
                    </button>
                </form>
            </div>
        </div>
  
        <div class="mt-6">

            <div v-if="designs && imageUrls.length == 0" class="mt-12 flex flex-wrap items-center gap-12">
                <div
                    v-for="design in designs"
                    :key="design.id"
                    class="group relative"
                    @click="handleSelectDesign(design)"
                >
                    <img
                        :src="design.imageSrc"
                        class="aspect-square w-full rounded-md bg-gray-200 object-cover group-hover:opacity-75 lg:aspect-auto lg:h-80"
                    />

                    <div class="mt-4 flex justify-between">
                        <div>
                            <h3 class="text-sm text-gray-700">
                                <h1>
                                <span aria-hidden="true" class="absolute inset-0" />
                                {{ design.name }}
                                </h1>
                            </h3>
                        </div>

                        <p class="text-sm font-medium text-gray-900">{{ design.price }}</p>
                    </div>
                </div>
            </div>


            <div v-else-if="imageUrls && imageUrls.length > 0"  class="mt-12 flex justify-center flex-wrap items-center gap-12">
                <div v-for="(imageUrl, index) in imageUrls" :key="'generated-' + index" class="group relative">
                
                <img
                    :src="`${aiAPIURL}/generated/image/${imageUrl}`"
                    class="aspect-square w-full rounded-md bg-gray-200 object-cover group-hover:opacity-75 lg:aspect-auto lg:h-80"
                />

                <div class="mt-4 flex justify-between">
                    <div>
                    <h3 class="text-sm text-gray-700">
                        <h1>
                        <span aria-hidden="true" class="absolute inset-0" />
                            Generated Design {{ index + 1 }}
                        </h1>
                    </h3>
                    </div>
                </div>
                </div>
            </div>

            <div v-else>
                <p>No designs or generated images available.</p>
            </div>

        </div>
      </div>
    </div>

    <div v-if="isLoadingMutation">
        <Loader msg="Generating Images..."/>
    </div>

    <ProductViewModal
        v-if="selectedDesign"
        :design="selectedDesign"
        :isOpen="openModal"
        @close="openModal = false"
    />

</template>
  
  