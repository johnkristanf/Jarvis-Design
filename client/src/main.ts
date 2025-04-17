import './assets/main.css';

import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import { createPinia } from 'pinia'

import PrimeVue from 'primevue/config'
import ToastService from 'primevue/toastservice';
import Aura from '@primevue/themes/aura'
import { RouterLink, RouterView } from 'vue-router'
import { VueQueryPlugin } from '@tanstack/vue-query'

const app = createApp(App)
const pinia = createPinia()

app.use(PrimeVue, {
    theme: {
        preset: Aura
    }

})


app.use(ToastService);
app.use(pinia)
app.use(router)
app.use(VueQueryPlugin) 
app.component('RouterLink', RouterLink)
app.component('RouterView', RouterView)
app.mount('#app')
