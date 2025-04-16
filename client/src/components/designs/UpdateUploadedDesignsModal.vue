<script setup lang="ts">
  import { DesignStatus, type PreferredDesign } from '@/types/design';
  import { ref } from 'vue'

  import {
    TransitionRoot,
    TransitionChild,
    Dialog,
    DialogPanel,
    DialogTitle,
  } from '@headlessui/vue';

  import {
    Listbox,
    ListboxButton,
    ListboxOptions,
    ListboxOption,
  } from '@headlessui/vue'

  import { CheckIcon, ChevronUpDownIcon } from '@heroicons/vue/20/solid'
  import { useMutation, useQueryClient } from '@tanstack/vue-query';
  import { updateUploadedDesign } from '@/api/put/design';
  import Loader from '../Loader.vue';


  const props = defineProps<{
    isOpen: boolean;
    design: PreferredDesign
  }>();

  const emit = defineEmits(['close']);
  const handleClose = () => emit('close');
  const price = ref(props.design.price);
  const isUpdatingDesigns = ref<boolean>(false);

  const statuses = [
    { name: DesignStatus.PENDING },
    { name: DesignStatus.ACKNOWLEDGE },
    { name: DesignStatus.TAGGED },
  ];
  
  const selectedStatus = ref(
    statuses.find(status => status.name === props.design.status) || statuses[0]
  );
  
  const queryClient = useQueryClient();


  console.log("Selected Design: ", props.design);

  const mutation = useMutation({
    mutationFn: updateUploadedDesign,
    onSuccess: () => {
      // IMPLEMENT THE USEQUERY LATER AFTER UPDATE
      queryClient.invalidateQueries({ queryKey: ['uploaded-designs'] });
      handleClose();
      isUpdatingDesigns.value = false;
    },

    onError: (error) => {
      console.error('Mutation error:', error);
    }

  });

  // Submit handler
  const handleSubmit = () => {
    console.log("status: ", selectedStatus.value.name);
    console.log("price: ", price.value);

    isUpdatingDesigns.value = true

    mutation.mutate({
      status: selectedStatus.value.name,
      price: price.value,
      design_id: props.design.id
    });
  }



</script>

<template>
    <TransitionRoot appear :show="isOpen" as="template">
      <Dialog as="div" @close="handleClose" class="relative z-10 ">
        <TransitionChild
          as="template"
          enter="duration-300 ease-out"
          enter-from="opacity-0"
          enter-to="opacity-100"
          leave="duration-200 ease-in"
          leave-from="opacity-100"
          leave-to="opacity-0"
        >
          <div class="fixed inset-0 bg-black/25" />
        </TransitionChild>
  
        <div class="fixed inset-0 overflow-y-auto">
          <div
            class="flex min-h-full items-center justify-center p-4 text-center"
          >
            <TransitionChild
              as="template"
              enter="duration-300 ease-out"
              enter-from="opacity-0 scale-95"
              enter-to="opacity-100 scale-100"
              leave="duration-200 ease-in"
              leave-from="opacity-100 scale-100"
              leave-to="opacity-0 scale-95"
            >
              <DialogPanel
                class="w-full h-[18.5rem] max-w-md transform overflow-hidden bg-white p-6 text-left align-middle shadow-xl transition-all"
              >
                <DialogTitle
                  as="h3"
                  class="text-lg  leading-6 text-gray-900"
                >
                  Update Customer Uploaded Design
                </DialogTitle>

                

                <form @submit.prevent="handleSubmit" class="max-w-sm mx-auto">

                    <!-- DESIGN STATUS SELECT ELEMENT -->

                    <div class="mt-4 w-full">

                      <h1 class="font-medium">Pricing Status:</h1>

                      <Listbox v-model="selectedStatus">
                        <div class="relative mt-1">
                          <ListboxButton
                            class="relative w-full cursor-default rounded-lg  py-2 pl-3 pr-10 text-left border-1 border-gray-300 focus:outline-none focus-visible:border-indigo-500 focus-visible:ring-2 focus-visible:ring-white/75 focus-visible:ring-offset-2 focus-visible:ring-offset-orange-300 sm:text-sm"
                          >
                            <span class="block font-medium truncate">{{ selectedStatus.name.toUpperCase() }}</span>
                            <span
                              class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2"
                            >
                              <ChevronUpDownIcon
                                class="h-5 w-5 text-gray-400"
                                aria-hidden="true"
                              />
                            </span>
                          </ListboxButton>

                          <transition
                            leave-active-class="transition duration-100 ease-in"
                            leave-from-class="opacity-100"
                            leave-to-class="opacity-0"
                          >
                            <ListboxOptions
                              class="absolute mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black/5 focus:outline-none sm:text-sm"
                            >
                              <ListboxOption
                                v-slot="{ active, selected }"
                                v-for="status in statuses"
                                :key="status.name"
                                :value="status"
                                as="template"
                              >
                                <li
                                  :class="[
                                    active ? 'bg-blue-100 text-blue-900' : 'text-gray-900',
                                    'relative cursor-default select-none py-2 pl-10 pr-4',
                                  ]"
                                >
                                  <span
                                    :class="[
                                      selected ? 'font-medium' : 'font-normal',
                                      'block truncate',
                                    ]"
                                    >{{ status.name.toUpperCase() }}</span
                                  >
                                  <span
                                    v-if="selected"
                                    class="absolute inset-y-0 left-0 flex items-center pl-3 text-blue-600"
                                  >
                                    <CheckIcon class="h-5 w-5" aria-hidden="true" />
                                  </span>
                                </li>
                              </ListboxOption>
                            </ListboxOptions>
                          </transition>
                        </div>
                      </Listbox>
                    </div>

                    <!-- END OF DESIGN STATUS SELECT ELEMENT -->

                    <div class="mt-5">
                      <label for="price" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Price (₱):</label>
                      <input
                        type="text"
                        id="price"
                        v-model="price"
                        class="font-medium shadow-xs bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 dark:shadow-xs-light"
                        required
                      />
                    </div>
                 

                    <button type="submit" class="mt-4 text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 hover:cursor-pointer focus:outline-none focus:ring-blue-300 rounded-md text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                      Submit
                    </button>
                </form>


  
              </DialogPanel>
            </TransitionChild>
          </div>
        </div>
      </Dialog>
    </TransitionRoot>

    <!-- LOADER -->
    <div v-if="isUpdatingDesigns">
        <Loader msg="Updating Customer Uploaded Designs..."/>
      </div>

  </template>
  

  