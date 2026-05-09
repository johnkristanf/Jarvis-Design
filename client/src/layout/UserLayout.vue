<script lang="ts" setup>
    import Footer from '@/components/home/Footer.vue'
    import { RouterView } from 'vue-router'
    import NavBar from '../components/NavBar.vue'
    import Toast from 'primevue/toast';
</script>

<template>
    <div>
        <header>
            <NavBar />
        </header>

        <main class="content pt-28">
            <RouterView />
            <Toast />
        </main>

        <Footer />
    </div>
</template>
