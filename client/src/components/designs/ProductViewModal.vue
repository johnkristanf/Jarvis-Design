<script lang="ts" setup>
  import { ref, computed } from 'vue';
  import { Dialog, DialogPanel, TransitionChild, TransitionRoot, RadioGroup, RadioGroupOption} from '@headlessui/vue';
  import { XMarkIcon } from '@heroicons/vue/20/solid';
  import type { Designs } from '@/types/design'
  import { Select } from 'primevue';

  const props = defineProps<{
    design: Designs;
    isOpen: boolean;
    onClose: () => void;
  }>();

  const emit = defineEmits(['close']);

  const open = computed(() => props.isOpen);
  const selectedColor = ref(props.design.colors[0]);
  const selectedSize = ref(props.design.sizes[2]);

  const handleClose = () => {
    emit('close');
  };


  const handleSubmitOrder = () => {
    console.log("Selected Color Name: ", selectedColor.value.name);
    console.log("Selected Size on Submit: ", selectedSize.value);
    // ... your order submission logic
  };

  console.log("Initial selectedColor: ", selectedColor.value);
  console.log("Initial selectedSize: ", selectedSize.value);
</script>

<template>
  <TransitionRoot as="template" :show="open">
    <Dialog class="relative z-10" @close="handleClose">
      <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
        <div class="fixed inset-0 hidden bg-gray-500/75 transition-opacity md:block" />
      </TransitionChild>

      <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
        <div class="flex min-h-full items-stretch justify-center text-center md:items-center md:px-2 lg:px-4">
          <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 md:translate-y-0 md:scale-95" enter-to="opacity-100 translate-y-0 md:scale-100" leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 md:scale-100" leave-to="opacity-0 translate-y-4 md:translate-y-0 md:scale-95">
            <DialogPanel class="flex w-full transform text-left text-base transition md:my-8 md:max-w-2xl md:px-4 lg:max-w-4xl">
              <div class="relative flex w-full items-center overflow-hidden mb-16 bg-white px-4 pt-14 pb-8 shadow-2xl sm:px-6 sm:pt-8 md:p-6 lg:p-8">
                <button type="button" class="absolute top-4 right-4 text-gray-400 hover:text-gray-500 sm:top-8 sm:right-6 md:top-6 md:right-6 lg:top-8 lg:right-8" @click="handleClose">
                  <span class="sr-only">Close</span>
                  <XMarkIcon class="size-6" aria-hidden="true" />
                </button>

                <div class="flex justify-between w-full">
                  <img :src="props.design.imageSrc" class="aspect-2/3 w-1/2 rounded-lg bg-gray-100 object-cover sm:col-span-4 lg:col-span-5" />
                  <div class="sm:col-span-8 lg:col-span-7">
                    <h2 class="text-2xl font-bold text-gray-900 sm:pr-12">{{ props.design.name }}</h2>

                    <section aria-labelledby="information-heading" class="mt-2">
                      <h3 id="information-heading" class="sr-only">Design Information</h3>
                      <p class="text-2xl text-gray-900">{{ props.design.price }}</p>
                    </section>

                    <section aria-labelledby="options-heading" class="mt-10">
                      <h3 id="options-heading" class="sr-only">design options</h3>

                      <form @submit.prevent="handleSubmitOrder">
                        <fieldset aria-label="Choose a color">
                            <legend class="text-sm font-medium text-gray-900 mb-3">Select Color</legend>
                            <Select
                                v-model="selectedColor"
                                :options="props.design.colors"
                                optionLabel="name"
                                placeholder="Select a Color"
                                checkmark
                                :highlightOnSelect="false"
                                class="w-full md:w-56 no-focus-outline"
                            />
                        </fieldset>

                        <fieldset class="mt-10" aria-label="Choose a size">
                          <div class="flex items-center justify-between">
                            <div class="text-sm font-medium text-gray-900">Size</div>
                          </div>

                          <RadioGroup v-model="selectedSize" class="mt-4 grid grid-cols-4 gap-4">
                            <RadioGroupOption
                              as="template"
                              v-for="size in props.design.sizes"
                              :key="size.name"
                              :value="size"
                              :disabled="!size.inStock"
                              v-slot="{ active, checked }"
                            >
                              <div
                                :class="[
                                  size.inStock ? 'cursor-pointer bg-white text-gray-900 shadow-xs' : 'cursor-not-allowed bg-gray-50 text-gray-200',
                                  active ? 'ring-2 ring-indigo-500' : '',
                                  'group relative flex items-center justify-center rounded-md border px-4 py-3 text-sm font-medium uppercase hover:bg-gray-50 focus:outline-hidden sm:flex-1',
                                ]"
                              >
                                <span>{{ size.name }}</span>
                                <span
                                  v-if="size.inStock"
                                  :class="[
                                    active ? 'border' : 'border-2',
                                    checked ? 'border-indigo-500' : 'border-transparent',
                                    'pointer-events-none absolute -inset-px rounded-md',
                                  ]"
                                  aria-hidden="true"
                                />
                                <span v-else aria-hidden="true" class="pointer-events-none absolute -inset-px rounded-md border-2 border-gray-200">
                                  <svg class="absolute inset-0 size-full stroke-2 text-gray-200" viewBox="0 0 100 100" preserveAspectRatio="none" stroke="currentColor">
                                    <line x1="0" y1="100" x2="100" y2="0" vector-effect="non-scaling-stroke" />
                                  </svg>
                                </span>
                              </div>
                            </RadioGroupOption>
                          </RadioGroup>
                        </fieldset>

                        <button
                          type="submit"
                          class="mt-6 flex w-full items-center justify-center rounded-md border border-transparent bg-indigo-600 px-8 py-3 text-base font-medium text-white hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-hidden"
                        >
                          Add to bag
                        </button>
                      </form>
                    </section>
                  </div>
                </div>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

