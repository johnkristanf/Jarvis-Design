<script lang="ts" setup>
    import SideBar from '@/components/SideBar.vue'
    import { RouterView } from 'vue-router'
</script>

<template>
    <aside>
        <SideBar />
    </aside>

    <div class="p-4  sm:ml-64">
        <div class="p-4  rounded-lg dark:border-gray-700 mt-14">
            <main >
                <RouterView />
            </main>
        </div>
    </div>

</template>
