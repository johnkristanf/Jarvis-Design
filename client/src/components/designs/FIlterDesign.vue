<script lang="ts" setup>
    import { nextTick, ref } from 'vue'
    import { Popover, PopoverButton, PopoverPanel } from '@headlessui/vue'
    import { ChevronDownIcon } from '@heroicons/vue/20/solid'
    import { useQuery } from '@tanstack/vue-query'
    import { apiService } from '@/api/axios'
    import { useDesignFilterStore } from '@/stores/filter'
    import type { DesignCategory } from '@/types/design'


    // FILTER DESIGN STATE STORE
    const filterStore = useDesignFilterStore()

    // REMOVING PANEL WHEN USER SELECT A SORT
    const popoverVisible = ref(true)

    const handleSelectSort = async (option: {name: string; tag: string} ) => {
        filterStore.setSort(option)
        popoverVisible.value = false // Force close
        await nextTick()
        popoverVisible.value = true // Re-enable for future clicks

    }


    // USE QUERY FOR FETCHING DESIGN CATEGORIES
    const { data: designCatergories, isLoading } = useQuery({
        queryKey: ['design-categories'],
        queryFn: async () => {
            const respData = await apiService.get<DesignCategory[]>('/api/get/design/categories')
            return respData
        },
    })


    // CATEGORIES SELECT TOGGLER
    function toggleCategory(category_id: number) {
        if (filterStore.selectedCategories.includes(category_id)) {
            filterStore.selectedCategories = filterStore.selectedCategories.filter((c) => c !== category_id)
        } else {
            filterStore.selectedCategories.push(category_id)
        }

    }
</script>

<template>
    <div class="w-full flex justify-end items-center px-4 gap-4">
        <!-- SORT DROPDOWN -->
        <Popover v-if="popoverVisible" v-slot="{ open }" class="relative">
            <PopoverButton
                class="flex items-center gap-2 px-4 py-2 rounded-md text-sm hover:cursor-pointer hover:opacity-75"
            >
                {{ filterStore.selectedSort.name }}
                <ChevronDownIcon class="w-5 h-5" />
            </PopoverButton>

            <transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="translate-y-1 opacity-0"
                enter-to-class="translate-y-0 opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="translate-y-0 opacity-100"
                leave-to-class="translate-y-1 opacity-0"
            >
                <PopoverPanel
                    class="absolute left-0 z-10 mt-2 w-60 bg-white rounded-lg shadow-lg ring-1 ring-black/5"
                >
                    <div v-for="option in filterStore.sortOptions" :key="option.name" class="px-1 py-1">
                        <button
                            @click="handleSelectSort(option)"
                            :class="[
                                'hover:bg-gray-900 hover:text-white hover:cursor-pointer group flex w-full items-center rounded-md px-2 py-2 text-sm',
                            ]"
                        >
                            {{ option.name }}
                        </button>
                    </div>
                </PopoverPanel>
            </transition>
        </Popover>

        <!-- Category Filter -->
        <Popover v-slot="{ open }" class="relative">
            <PopoverButton
                class="flex items-center gap-2 px-4 py-2 rounded-md text-sm hover:cursor-pointer hover:opacity-75"
            >
                Categories
                <ChevronDownIcon class="w-5 h-5" />
            </PopoverButton>

            <transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="translate-y-1 opacity-0"
                enter-to-class="translate-y-0 opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="translate-y-0 opacity-100"
                leave-to-class="translate-y-1 opacity-0"
            >
                <PopoverPanel
                    class="absolute left-0 z-10 mt-2 w-60 bg-white rounded-lg shadow-lg ring-1 ring-black/5"
                >
                    <ul class="divide-y divide-gray-100 p-2">
                        <li
                            v-for="category in designCatergories"
                            :key="category.id"
                            class="flex items-center space-x-2 p-2"
                        >
                            <input
                                type="checkbox"
                                :id="category.name"
                                :checked="filterStore.selectedCategories.includes(category.id)"
                                @change="toggleCategory(category.id)"
                            />
                            <label :for="category.name" class="text-sm text-gray-700">
                                {{ category.name }}
                            </label>
                        </li>
                    </ul>
                </PopoverPanel>
            </transition>
        </Popover>
    </div>
</template>
