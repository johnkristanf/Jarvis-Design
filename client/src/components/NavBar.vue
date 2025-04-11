<script lang="ts" setup>
    import { Disclosure, DisclosureButton, DisclosurePanel, Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'
    import { Bars3Icon, BellIcon, XMarkIcon } from '@heroicons/vue/24/outline'
  
    const user = {
        name: 'Tom Cook',
        email: 'tom@example.com',
        imageUrl:
        'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80',
    }

    const navigation = [
        { name: 'Home', to: '/home', current: true },
        { name: 'Designs', to: '/designs', current: false },
    ]

    const userNavigation = [
        { name: 'Your Profile', href: '#' },
        { name: 'Settings', href: '#' },
        { name: 'Sign out', href: '#' },
    ]

    const authNavigation = [
        { name: 'Login', to: '/auth/login' },
        { name: 'Register', to: '/auth/register' },
    ]

</script>

<template>
    <!--
      This example requires updating your template:
  
      ```
      <html class="h-full bg-gray-100">
      <body class="h-full">
      ```
    -->
    <div class="min-h-full">

        <Disclosure as="nav" class="bg-gray-800 p-4" v-slot="{ open }">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                
            <div class="flex h-16 items-center justify-between">
                <div class="flex items-center">
                    <div class="shrink-0">
                        <img class="size-18" src="/jarvis-logo-circle.png" alt="Jarvis Design Logo" />
                    </div>

                    <div class="hidden md:block">
                        <div class="ml-6 flex items-baseline space-x-4">
                            <router-link 
                                v-for="item in navigation" 
                                :key="item.name" 
                                :to="item.to" 
                                :class="[
                                    item.current 
                                    ? 'bg-gray-900 text-white' 
                                    : 'text-gray-300 hover:bg-gray-700 hover:text-white', 
                                    'rounded-md px-3 py-2 '
                                ]" 
                                
                                :aria-current="item.current ? 'page' : undefined"
                            >
                                {{ item.name }}
                                
                            </router-link>
                        </div>
                    </div>

                </div>


                <div class="hidden md:block">

                    <div class="ml-4 flex items-center md:ml-6">

                        <div class="flex items-center gap-3 mr-3">
                            <router-link 
                                v-for="nav in authNavigation" 
                                :to="nav.to" 
                                :key="nav.to" 
                                class="rounded-md px-3 py-2 hover:bg-gray-700 relative rounded-full bg-gray-800 p-1 text-gray-400 hover:text-white focus:outline-hidden"
                            >
                                {{ nav.name }}
                            </router-link>
                        </div>

                        
                        <!-- ONLY SHOW THIS WHEN AUTENTICATED -->

                        <!-- <button type="button" class="relative rounded-full bg-gray-800 p-1 text-gray-400 hover:text-white focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-gray-800 focus:outline-hidden">
                            <span class="absolute -inset-1.5" />
                            <span class="sr-only">View notifications</span>
                            <BellIcon class="size-6" aria-hidden="true" />
                        </button> -->
        

                        <!-- ONLY SHOW THIS WHEN AUTENTICATED -->

                        <!-- MAIN PROFILE DROPDOWN -->
                        <!-- <Menu as="div" class="relative ml-3">
                            <div>
                                <MenuButton class="relative flex max-w-xs items-center rounded-full bg-gray-800 text-sm focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-gray-800 focus:outline-hidden">
                                    <span class="absolute -inset-1.5" />
                                    <span class="sr-only">Open user menu</span>
                                    <img class="size-8 rounded-full" :src="user.imageUrl" alt="" />
                                </MenuButton>
                            </div>
                            
                            <transition enter-active-class="transition ease-out duration-100" enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100" leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 scale-100" leave-to-class="transform opacity-0 scale-95">
                                <MenuItems class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black/5 focus:outline-hidden">
                                <MenuItem v-for="item in userNavigation" :key="item.name" v-slot="{ active }">
                                    <a :href="item.href" :class="[active ? 'bg-gray-100 outline-hidden' : '', 'block px-4 py-2 text-sm text-gray-700']">{{ item.name }}</a>
                                </MenuItem>
                                </MenuItems>
                            </transition>

                        </Menu> -->
                    </div>
                </div>



                <!-- ONLY SHOW THIS WHEN AUTENTICATED -->

                <!-- THE OPEN AND CLOSING BUTTON FOR THE DROPDOWN IN SMALL SCREEN -->

                <div class="-mr-2 flex md:hidden">
                    <DisclosureButton class="relative inline-flex items-center justify-center rounded-md bg-gray-800 p-2 text-gray-400 hover:bg-gray-700 hover:text-white focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-gray-800 focus:outline-hidden">
                        <span class="absolute -inset-0.5" />
                        <span class="sr-only">Open main menu</span>
                        <Bars3Icon v-if="!open" class="block size-6" aria-hidden="true" />
                        <XMarkIcon v-else class="block size-6" aria-hidden="true" />
                    </DisclosureButton>
                </div>

            </div>

            </div>
    

            <!-- ONLY SHOW THIS WHEN AUTENTICATED -->

            <!-- THE DROPDOWN WHEN THE SCREEN IS SMALLER -->
            <DisclosurePanel class="md:hidden">
                
                <div class="border-t border-gray-700 pt-4 pb-3">
                    <div class="flex items-center px-5">
                        <div class="shrink-0">
                            <img class="size-10 rounded-full" :src="user.imageUrl" alt="" />
                        </div>
                        <div class="ml-3">
                            <div class="text-base/5 font-medium text-white">{{ user.name }}</div>
                            <div class="text-sm font-medium text-gray-400">{{ user.email }}</div>
                        </div>

                        <button type="button" class="relative ml-auto shrink-0 rounded-full bg-gray-800 p-1 text-gray-400 hover:text-white focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-gray-800 focus:outline-hidden">
                            <span class="absolute -inset-1.5" />
                            <span class="sr-only">View notifications</span>
                            <BellIcon class="size-6" aria-hidden="true" />
                        </button>
                    </div>

                    <div class="mt-3 space-y-1 px-2">
                        <DisclosureButton v-for="item in userNavigation" :key="item.name" as="a" :href="item.href" class="block rounded-md px-3 py-2 text-base font-medium text-gray-400 hover:bg-gray-700 hover:text-white">{{ item.name }}</DisclosureButton>
                    </div>
                </div>

            </DisclosurePanel>

        </Disclosure>
  
    </div>
  </template>
  
