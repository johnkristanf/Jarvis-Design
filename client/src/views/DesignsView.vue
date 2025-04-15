<script lang="ts" setup>
    import { getAllDesigns } from '@/api/get/designs';
    import { generateImageDesign, uploadPreferredDesign } from '@/api/post/generate';
    import ProductViewModal from '@/components/designs/ProductViewModal.vue';
    import Loader from '@/components/Loader.vue';
    import type { DesignGenerate, Designs } from '@/types/design';
    import { ArrowRightIcon, ArrowUpOnSquareIcon } from '@heroicons/vue/20/solid';
    import { useMutation, useQuery } from '@tanstack/vue-query';
    import { Select } from 'primevue';
    import { useField, useForm } from 'vee-validate';
    import { onMounted, ref } from 'vue';
    import { useToast } from 'primevue/usetoast';
import UploadDesignModal from '@/components/designs/UploadDesignModal.vue';
import UploadedDesignsTable from '@/components/designs/UploadedDesignsTable.vue';


    const aiAPIURL = import.meta.env.VITE_AI_API_URL;
    const designs = ref();

       
    const selectedDesign = ref<Designs>(); 
    const openDesignModal = ref(false);
    const openUploadDesignModal = ref(false);

    const imageUrls = ref([]);
    const isLoadingMutation = ref(false);
    const loaderMsg = ref<string>('');


    const renderAllDesigns = async () => {
        isLoadingMutation.value = true; 
        loaderMsg.value = "Loading Designs...";

        const res = await getAllDesigns();

        designs.value = res
        isLoadingMutation.value = false; 
    }

    const handleSelectDesign = (design: Designs) => {
        openDesignModal.value = true
        selectedDesign.value = design; 
    };

    const { handleSubmit, handleReset } = useForm();

    const generateImageMutation = useMutation({
        mutationFn: generateImageDesign,
        onSuccess: (response) => {
            isLoadingMutation.value = false;
            console.log("response: ", response)

            if(response && response.data.image_urls){
                imageUrls.value = response.data.image_urls;
                handleReset();
            }
        },

        onError: (error) => {
            isLoadingMutation.value = false; 
            console.error("Error generating image:", error);
        },

        onMutate: () => {
            loaderMsg.value = "Generating Images..."
            isLoadingMutation.value = true; 
        },
    });


    const preferences = ref([
        {name: 'realistic'},
        {name: 'cartoon'},
        {name: 'anime'},
        {name: 'painting'},
        {name: 'sketch'},
    ])

    const { value: prompt, } = useField('prompt');
    const { value: style_preference, } = useField('style_preference');


    const onImageGenerate = handleSubmit(values => {
        const designGengerateData: DesignGenerate = {
            prompt: values.prompt,
            style_preference: values.style_preference.name,
        }

        console.log("isSubmitting: ", isLoadingMutation.value);

        generateImageMutation.mutate(designGengerateData)
        
    });

    const handleOpenUploadModal = () => openUploadDesignModal.value = true;





    onMounted(() => {
        renderAllDesigns();
    });


</script>

<template>
    <div class="bg-white">
      <div class="mx-auto max-w-2xl px-4 pt-10 pb-18 sm:px-6 sm:py-24 lg:max-w-7xl lg:px-8">

        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-bold tracking-tight text-gray-900">Explore our designs</h2>


            <div class="card flex justify-center gap-3">

                <button
                    @click="handleOpenUploadModal"
                    class="flex items-center justify-center gap-1 font-medium bg-gray-900 p-3 text-white w-[40%] rounded-md text-base focus:outline-none hover:bg-gray-700 cursor-pointer sm:text-sm/6"
                    >
                    <ArrowUpOnSquareIcon class="size-5"/>
                    Upload Design
                </button>
                
                
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

            <div class="mt-12 w-full flex justify-end">
                <h1 class="text-gray-600 hover:cursor-pointer hover:opacity-75 flex items-center">
                    View Uploaded Designs
                    <ArrowRightIcon class="size-6"/>
                </h1>
            </div>


            <!-- UPLOADED DESIGNS -->
            <UploadedDesignsTable />


            <!-- PRE-MADE DESIGNS -->

            <div v-if="designs && imageUrls.length == 0" class=" flex flex-wrap items-center gap-12">
                <div
                    v-for="design in designs"
                    :key="design.id"
                    class="group relative"
                    @click="handleSelectDesign(design)"
                >
                    <img
                        :src="design.image_path"
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

                        <p class="text-sm font-medium text-gray-900">₱ {{ design.price }}</p>
                    </div>
                </div>
            </div>


            <!-- AI GENERATED IMAGES -->
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

            <div v-if="!designs && !imageUrls" >
                <p>No designs or generated images available.</p>
            </div>

        </div>
      </div>
    </div>

    <div v-if="isLoadingMutation">
        <Loader :msg="loaderMsg"/>
    </div>

    <ProductViewModal
        v-if="selectedDesign"
        :design="selectedDesign"
        :isOpen="openDesignModal"
        @close="openDesignModal = false"
    />

    <UploadDesignModal
        v-if="openUploadDesignModal"
        :isOpen="openUploadDesignModal"
        @close="openUploadDesignModal = false"
    />

</template>
  
  