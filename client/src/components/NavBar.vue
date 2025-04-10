
<script lang="ts" setup>
    import { COLOR } from "@/themes/color";
    import { Menubar, Avatar, Button } from "primevue";
    import { ref } from "vue";
    import { useRouter } from 'vue-router';
    import { PrimeIcons } from '@primevue/core/api';


    const router = useRouter();

    const items = ref([
        // {
        //     label: 'Router',
        //     icon: 'pi pi-palette',
        //     items: [
        //         {
        //             label: 'Styled',
        //             route: '/theming/styled'
        //         },
        //         {
        //             label: 'Unstyled',
        //             route: '/theming/unstyled'
        //         }
        //     ]
        // },
        {
            label: 'Home',
            icon: PrimeIcons.HOME,
            command: () => {
                router.push('/home');
            }
        },

        {
            label: 'Design',
            icon: PrimeIcons.PALETTE,
            command: () => {
                router.push('/design');
            }
        },


        // {
        //     label: 'External',
        //     icon: 'pi pi-home',
        //     items: [
        //         {
        //             label: 'Vue.js',
        //             url: 'https://vuejs.org/'
        //         },
        //         {
        //             label: 'Vite.js',
        //             url: 'https://vitejs.dev/'
        //         }
        //     ]
        // }
    ]);


    const authLink = ref([
        {name: "Login", to: '/auth/login'},
        {name: "Register", to: '/auth/register'},
    ])
</script>


<template>
    <nav >
        <Menubar 
            :model="items" 
            :pt="{
                root: { 
                    style: {
                        backgroundColor: 'transparent',
                        border: 'none',
                        paddingLeft: '4rem',   /* Add left padding */
                        paddingRight: '4rem',
                        paddingTop: '1rem'
                    }
                },

            }"
        >
            <template #start>
                <img src="/jarvis-logo-circle.png" width=100 class="mr-3" />
            </template>

            <template #item="{ item, props, hasSubmenu }">
                <router-link v-if="item.route" v-slot="{ href, navigate }" :to="item.route" custom>
                    <a v-ripple :href="href" v-bind="props.action" @click="navigate">
                        <span :class="item.icon" />
                        <span>{{ item.label }}</span>
                    </a>
                </router-link>

                <a v-else v-ripple :href="item.url" :target="item.target" v-bind="props.action">
                    <span :style="{color: COLOR.secondary}" :class="item.icon" />
                    <span :style="{color: COLOR.secondary}">{{ item.label }}</span>
                    <span v-if="hasSubmenu" class="pi pi-fw pi-angle-down" />
                </a>
            </template>


            <template #end>
                <div class="flex items-center gap-2 ">
                    <router-link 
                        v-for="link in authLink" 
                        :to="link.to" 
                        class="text-white mr-3 hover:bg-white hover:text-black p-2 rounded-md"
                    >
                        {{ link.name }}
                    </router-link>

                    <Avatar image="/jarvis-logo.jpg" shape="circle" />
                </div>
            </template>
        </Menubar>
    </nav>
</template>


<style scoped>
.menubar-link:hover {
    background-color: rgba(255, 255, 255, 0.1); 
}
</style>