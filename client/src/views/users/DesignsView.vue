<script lang="ts" setup>
    import { getAllDesigns } from '@/api/get/designs';
    import PreviewDesignModal from '@/components/designs/PreviewDesignModal.vue';
    import Loader from '@/components/Loader.vue';
    import type { Designs } from '@/types/design';
    import { ArrowLeftIcon, ArrowRightIcon, DocumentArrowUpIcon, WrenchScrewdriverIcon } from '@heroicons/vue/20/solid';
    import { onMounted, ref } from 'vue';
    import UploadDesignModal from '@/components/designs/UploadDesignModal.vue';
    import UploadedDesignsTable from '@/components/designs/UploadedDesignsTable.vue';
    import GenerateAIDesignsModal from '@/components/designs/GenerateAIDesignsModal.vue';


    const designs = ref();

       
    const selectedDesign = ref<Designs>(); 
    const openDesignModal = ref(false);
    const openUploadDesignModal = ref(false);
    const openAIDesignGenerateModal = ref(false);

    const imageUrls = ref([]);
    const isLoadingMutation = ref(false);
    const loaderMsg = ref<string>('');

    const showUploadedDesignsTableRef = ref<boolean>(false);


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

    

    const handleOpenUploadModal = () => openUploadDesignModal.value = true;
    const handleOpenAIDesignModal = () => openAIDesignGenerateModal.value = true;

    onMounted(() => {
        renderAllDesigns();
    });


</script>

<template>
    <div class="bg-white">
      <div class="mx-auto px-4 pt-10 pb-18 sm:px-6 sm:py-24 lg:max-w-7xl lg:px-8">

        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-bold tracking-tight text-gray-900">Explore our designs</h2>


            <div class="w-1/2 flex justify-end gap-3 ">

                <button
                    @click="handleOpenUploadModal"
                    class="flex items-center justify-center gap-1 font-medium bg-gray-900 p-3 text-white w-[40%] rounded-md text-base focus:outline-none hover:bg-gray-700 cursor-pointer sm:text-sm/6"
                    >
                    <DocumentArrowUpIcon class="size-5"/>
                    Upload Design
                </button>


                <button
                    @click="handleOpenAIDesignModal"
                    class="flex items-center justify-center gap-1 font-medium bg-gray-900 p-3 text-white w-[40%] rounded-md text-base focus:outline-none hover:bg-gray-700 cursor-pointer sm:text-sm/6"
                    >
                    <WrenchScrewdriverIcon class="size-5"/>
                    Generate AI Designs
                </button>

                
              
            </div>
        </div>
        
  
        <div class="mt-6">

            <div class="mt-12 mb-5 w-full flex justify-end">
                <h1 v-if="!showUploadedDesignsTableRef" class="text-gray-600 hover:cursor-pointer hover:opacity-75 flex items-center" @click="showUploadedDesignsTableRef = true">
                    View Uploaded Designs
                    <ArrowRightIcon class="size-6"/>
                </h1>

                <h1 v-else class="text-gray-600 hover:cursor-pointer hover:opacity-75 flex items-center" @click="showUploadedDesignsTableRef = false">
                    <ArrowLeftIcon class="size-6"/>
                    View Made Designs
                </h1>
            </div>


            <!-- UPLOADED DESIGNS -->

            <div class="w-full" v-if="showUploadedDesignsTableRef">
                <UploadedDesignsTable />
            </div>


            <!-- PRE-MADE DESIGNS -->

            <div v-if="!showUploadedDesignsTableRef && designs && imageUrls.length == 0" class=" flex flex-wrap items-center gap-12">
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


            

            <div v-if="!designs && !imageUrls" >
                <p>No designs or generated images available.</p>
            </div>

        </div>
      </div>
    </div>

    <div v-if="isLoadingMutation">
        <Loader :msg="loaderMsg"/>
    </div>

    <PreviewDesignModal
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

    <GenerateAIDesignsModal
        v-if="openAIDesignGenerateModal"
        :isOpen="openAIDesignGenerateModal"
        @close="openAIDesignGenerateModal = false"
    />

</template>
  
  