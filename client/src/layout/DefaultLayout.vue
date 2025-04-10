<script lang="ts" setup>
    import Footer from '@/components/Footer.vue';
    import NavBar from '../components/NavBar.vue';
import { RouterView } from 'vue-router';
</script>

<template>
    <div class="h-screen">
        <header>
            <NavBar />
        </header>

        <main class="content">
            <RouterView />
        </main>
        
        <Footer />

    </div>
</template>