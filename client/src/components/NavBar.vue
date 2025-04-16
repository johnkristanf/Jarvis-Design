<script lang="ts" setup>
    import { Disclosure, DisclosureButton, DisclosurePanel, Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'
    import { UserIcon } from '@heroicons/vue/20/solid';
    import { BellIcon } from '@heroicons/vue/24/outline'
    import { onMounted, ref } from 'vue';
    import { useRoute, useRouter } from 'vue-router';
    import Loader from './Loader.vue';
    import { fetchUserData } from '@/api/get/user-data';
    import { useFetchAuthenticatedUser } from '@/composables/useFetchAuthenticatedUser';

    const route = useRoute();
    const { authStore, isLoading } = useFetchAuthenticatedUser();


    const navigation = [
        { name: 'Home', to: '/home' },
        { name: 'Designs', to: '/designs'},
        { name: 'Orders', to: '/orders'},
        { name: 'FAQ', to: '/faq'},
    ]

    const userNavigation = [
        { name: 'Your Profile', href: '#' },
        { name: 'Settings', href: '#' },
        { name: 'Sign Out', onclick: async () => {

            // console.log("authStore.isAuthenticated before logout", authStore.isLogginedIn);
            // console.log("authStore.currentUser before logout", authStore.currentUser);

            await authStore.logout();

            // console.log("authStore.isAuthenticated after logout", authStore.isLogginedIn);
            // console.log("authStore.currentUser after logout", authStore.currentUser);

            window.location.href = '/';
            
        }},
    ]

    const authNavigation = [
        { name: 'Login', to: '/auth/login' },
        { name: 'Register', to: '/auth/register' },
    ]

    
</script>

<template>
    <div class="min-h-full">

        <Disclosure as="nav" class="bg-gray-900 p-4" v-slot="{ open }">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

                <div class="flex h-16 items-center ">
                    <div class="flex items-center justify-between w-full">

                        <h1 class="text-white text-3xl hover:cursor-pointer hover:opacity-75">
                            Jarvis <span class="text-gray-700">Designs</span>
                        </h1>

                        <div class="hidden md:block">
                            <div class="ml-6 flex items-baseline space-x-4">
                                <router-link
                                    v-for="item in navigation.filter(nav => nav.name !== 'Orders' || authStore.currentUser)"
                                    :key="item.name"
                                    :to="item.to"
                                    :class="[
                                        'px-3 py-2 text-white hover:border-b-2',
                                        route.path === item.to ? 'border-b-2' : '',
                                    ]"
                                    :aria-current="route.path === item.to ? 'page' : undefined"
                                >
                                    {{ item.name }}
                                </router-link>
                            </div>
                        </div>

                        <div class="hidden md:block">
                            <div class="ml-4 flex items-center md:ml-6">
                                <template v-if="!authStore.currentUser && !isLoading">
                                    <div class="flex items-center gap-3 mr-3">
                                        <router-link
                                            v-for="nav in authNavigation"
                                            :to="nav.to"
                                            :key="nav.to"
                                            :class="[
                                                'px-3 py-2 text-white hover:border-b-2 ',
                                                route.path === nav.to ? 'border-b-2' : '',
                                            ]"
                                        >
                                            {{ nav.name }}
                                        </router-link>
                                    </div>
                                </template>

                                <button v-if="authStore.currentUser" type="button" class="relative rounded-full bg-gray-800 p-1 text-gray-400 hover:text-white focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-gray-800 focus:outline-hidden">
                                    <span class="absolute -inset-1.5" />
                                    <span class="sr-only">View notifications</span>
                                    <BellIcon class="size-6" aria-hidden="true" />
                                </button>

                                <Menu as="div" class="relative ml-3" v-if="authStore.currentUser">
                                    <div>
                                        <MenuButton class="relative flex items-center rounded-full bg-gray-800 text-sm focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-gray-800 focus:outline-hidden">
                                            <span class="absolute" />
                                            <span class="sr-only">Open user menu</span>
                                            <UserIcon class="size-8 text-white" aria-hidden="true"/>
                                        </MenuButton>
                                    </div>

                                    <transition enter-active-class="transition ease-out duration-100" enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100" leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 scale-100" leave-to-class="transform opacity-0 scale-95">
                                        <MenuItems class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black/5 focus:outline-hidden">
                                            <MenuItem v-for="item in userNavigation" :key="item.name" v-slot="{ active }" @click="item.onclick">
                                                <a :href="item.href" :class="[active ? 'bg-gray-100 outline-hidden' : '', 'block px-4 py-2 text-sm text-gray-700 hover:cursor-pointer']">{{ item.name }} </a>
                                            </MenuItem>
                                        </MenuItems>
                                    </transition>
                                </Menu>
                                <div v-else-if="isLoading">
                                    <span class="text-gray-400">Loading user...</span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <DisclosurePanel class="md:hidden">
                <template v-if="authStore.currentUser">
                    <div class="border-t border-gray-700 pt-4 pb-3">
                        <div class="flex items-center px-5">
                            <div class="shrink-0">
                                <UserIcon class="size-8 text-white" aria-hidden="true"/>
                            </div>
                            <div class="ml-3">
                                <div class="text-base/5 font-medium text-white">{{ authStore.currentUser.name }}</div>
                                </div>

                            <button type="button" class="relative ml-auto shrink-0 rounded-full bg-gray-800 p-1 text-gray-400 hover:text-white focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-gray-800 focus:outline-hidden">
                                <span class="absolute -inset-1.5" />
                                <h1>sdfsdf</h1>
                                <span class="sr-only">View notifications</span>
                                <BellIcon class="size-6" aria-hidden="true" />
                            </button>
                        </div>

                        <div class="mt-3 space-y-1 px-2">
                            <DisclosureButton v-for="item in userNavigation" @click="item.onclick" :key="item.name" as="a" :href="item.href" class="block rounded-md px-3 py-2 text-base font-medium text-gray-400 hover:bg-gray-700 hover:text-white">{{ item.name }}</DisclosureButton>
                        </div>
                    </div>
                </template>
                <template v-else-if="isLoading">
                    <div class="border-t border-gray-700 pt-4 pb-3 px-2">
                        <span class="text-gray-400">Loading user...</span>
                    </div>
                </template>

                <div class="space-y-1 px-2 pt-2 pb-3">
                    <DisclosureButton
                        v-for="item in navigation"
                        :key="item.name"
                        as="router-link"
                        :to="item.to"
                        :class="[
                            'block rounded-md px-3 py-2 text-base font-medium text-gray-400 hover:bg-gray-700 hover:text-white',
                            route.path === item.to ? 'bg-white text-black' : '',
                        ]"
                        :aria-current="route.path === item.to ? 'page' : undefined"
                    >{{ item.name }}</DisclosureButton>

                    <DisclosureButton
                        v-if="!authStore.currentUser"
                        v-for="nav in authNavigation"
                        :key="nav.to"
                        as="router-link"
                        :to="nav.to"
                        :class="[
                            'block rounded-md px-3 py-2 text-base font-medium text-gray-400 hover:bg-gray-700 hover:cursor-pointer hover:text-white mt-1',
                            route.path === nav.to ? 'bg-white text-black' : '',
                        ]"
                    >{{ nav.name }}</DisclosureButton>

                    <div v-else-if="isLoading" class="px-2 py-2 text-gray-400">
                        Loading authentication links...
                    </div>

                </div>
            </DisclosurePanel>

        </Disclosure>

    </div>

    <div v-if="authStore.LoggingOut">
        <Loader msg="Logging Out..."/>
    </div>
</template>