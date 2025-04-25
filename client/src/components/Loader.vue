<script lang="ts" setup>
    import { ProgressSpinner } from 'primevue'

    const props = defineProps<{
        msg: string
    }>()
</script>

<template>
    <div class="fixed-full h-screen w-full bg-gray-600/70 opacity-75 z-[999999]"></div>
    <div
        class="fixed-full flex flex-col gap-3 justify-center items-center h-screen w-full z-[9999999]"
    >
        <ProgressSpinner />
        <h1 class="text-white">{{ props.msg }}</h1>
    </div>
</template>
