import './assets/main.css'

import { createApp } from 'vue'
import App from './App.vue'
import router from './router'

import PrimeVue from 'primevue/config'
import Aura from '@primevue/themes/aura'
import { RouterLink, RouterView } from 'vue-router'
 
const app = createApp(App)

app.use(PrimeVue, {
    theme: {
        preset: Aura
    }
})

app.use(router)
app.component('RouterLink', RouterLink)
app.component('RouterView', RouterView)
app.mount('#app')
