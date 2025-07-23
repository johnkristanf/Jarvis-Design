<!-- components/BaseListbox.vue -->
<script lang="ts" setup>
    import { Listbox, ListboxButton, ListboxOptions, ListboxOption } from '@headlessui/vue'
    import { CheckIcon, ChevronUpDownIcon } from '@heroicons/vue/20/solid'

    defineProps<{
        options: Array<any>
        modelValue: any
        displayKey?: string
    }>()

    const emit = defineEmits(['update:modelValue'])

    const handleChange = (val: any) => {
        emit('update:modelValue', val)
    }
</script>

<template>
    <Listbox :modelValue="modelValue" @update:modelValue="handleChange">
        <div class="relative mt-1 w-full">
            <ListboxButton
                class="relative w-full cursor-default rounded-lg py-2 pl-3 pr-10 text-left border border-gray-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 sm:text-sm"
            >
                <span class="block font-medium truncate">
                    {{ modelValue?.[displayKey || 'name'].toUpperCase() || 'Select an option' }}
                </span>
                <span class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                    <ChevronUpDownIcon class="h-5 w-5 text-gray-400" />
                </span>
            </ListboxButton>

            <!-- DROPDOWN OPTIONS -->
            <transition
                leave-active-class="transition duration-100 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div class="absolute z-10 w-full">
                    <ListboxOptions
                        class="mt-1 max-h-60 overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black/5 focus:outline-none sm:text-sm"
                    >
                        <ListboxOption
                            v-for="option in options"
                            :key="option[displayKey || 'name']"
                            :value="option"
                            v-slot="{ active, selected }"
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
                                >
                                    {{ option[displayKey || 'name'].toUpperCase() }}
                                </span>
                                <span
                                    v-if="selected"
                                    class="absolute inset-y-0 left-0 flex items-center pl-3 text-blue-600"
                                >
                                    <CheckIcon class="h-5 w-5" />
                                </span>
                            </li>
                        </ListboxOption>
                    </ListboxOptions>
                </div>
            </transition>
        </div>
    </Listbox>
</template>
