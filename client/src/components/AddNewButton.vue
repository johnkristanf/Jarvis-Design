<script lang="ts" setup>
    import { PlusIcon } from '@heroicons/vue/20/solid'

    defineProps<{
        message: string
        widthClass?: string
    }>()

    const emit = defineEmits(['action']);
    const handleAction = () => emit('action');
</script>

<template>
    <button
        type="button"
        @click="handleAction"
        :class="[
            widthClass || 'w-36',
            'flex items-center justify-center gap-1 px-3 py-2 text-sm font-medium text-center text-white bg-gray-900 rounded-lg hover:opacity-75 hover:cursor-pointer',
        ]"
    >
        <PlusIcon class="size-5" />
        {{ message }}
    </button>
</template>
