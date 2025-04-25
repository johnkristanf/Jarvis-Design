<script lang="ts" setup>
    import Footer from '@/components/home/Footer.vue'
    import { RouterView } from 'vue-router'
    import NavBar from '../components/NavBar.vue'
</script>

<template>
    <div>
        <header>
            <NavBar />
        </header>

        <main class="content">
            <RouterView />
        </main>

        <Footer />
    </div>
</template>
