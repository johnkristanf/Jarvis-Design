import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useDesignFilterStore = defineStore('designFilter', () => {
    const sortOptions = [
        { name: 'Newest', tag: 'newest' },
        { name: 'Price: Low to High', tag: 'low_high' },
        { name: 'Price: High to Low', tag: 'high_low' },
    ]

    const selectedSort = ref(sortOptions[0])

    function setSort(option: { name: string; tag: string }) {
        selectedSort.value = option
    }

    // Categories
    const selectedCategories = ref<number[]>([])

    function toggleCategory(categoryId: number) {
        const index = selectedCategories.value.indexOf(categoryId)

        // REMOVE IN THE CATEGORIES IF CLICKED AGAIN
        if (index > -1) {
            selectedCategories.value.splice(index, 1)
        } else {
            // PUSH IF NOT
            selectedCategories.value.push(categoryId)
        }
    }

    return {
        selectedSort,
        selectedCategories,
        sortOptions,
        setSort,
        toggleCategory,
    }
})
