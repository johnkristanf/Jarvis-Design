<script setup lang="ts">
    import { defineProps, defineEmits } from 'vue'

    const props = defineProps({
        currentPage: { type: Number, required: true },
        lastPage: { type: Number, required: true },
    })

    const emit = defineEmits(['changePage'])

    const changePage = (page: number) => {
        if (page >= 1 && page <= props.lastPage) {
            emit('changePage', page)
        }
    }

</script>

<template>
    <div class="flex items-center space-x-2 mt-4 p-2">
        <button
            :disabled="props.currentPage === 1"
            @click="changePage(props.currentPage - 1)"
            class="px-3 py-1 bg-gray-200 rounded disabled:opacity-50 hover:opacity-75 hover:cursor-pointer"
        >
            Prev
        </button>

        <span>Page {{ props.currentPage }} of {{ props.lastPage }}</span>

        <button
            :disabled="props.currentPage === props.lastPage"
            @click="changePage(props.currentPage + 1)"
            class="px-3 py-1 bg-gray-200 rounded disabled:opacity-50 hover:opacity-75 hover:cursor-pointer"
        >
            Next
        </button>
    </div>
</template>
