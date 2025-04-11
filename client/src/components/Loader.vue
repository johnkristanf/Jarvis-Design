<script lang="ts" setup>
    import { ProgressSpinner } from 'primevue';

    const props = defineProps<{
        msg: String
    }>();

</script>

<template>
    <div class="fixed top-0 bottom-0 h-screen w-full bg-gray-600 opacity-75 z-[999999]"></div>
    <div class="fixed top-0 bottom-0 flex flex-col gap-3 justify-center items-center h-screen w-full z-[9999999]">
        <ProgressSpinner />
        <h1 class="text-white">{{ props.msg }}</h1>
    </div>

</template>