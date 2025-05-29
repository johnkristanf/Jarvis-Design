<script lang="ts" setup>
    import { useFetchAuthenticatedUser } from '@/composables/useFetchAuthenticatedUser'
    import { ref } from 'vue'
    import { RouterLink } from 'vue-router'
    import { apiService } from '@/api/axios'
    import Loader from './Loader.vue'

    const isLoggingOut = ref<boolean>(false)

    // LOAD THE REAL USER IN THE NAVBAR LATER ON
    const { authStore, isLoading } = useFetchAuthenticatedUser()
    console.log('authStore user: ', authStore.user)

    const sideLinks = ref([
        {
            name: 'Dashboard',
            to: '/admin/dashboard',
            icon: `
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 1 0 7.5 7.5h-7.5V6Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0 0 13.5 3v7.5Z" />
            </svg>

             `,
        },
        {
            name: 'Products',
            to: '/admin/products',
            icon: `
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 0 0-5.78 1.128 2.25 2.25 0 0 1-2.4 2.245 4.5 4.5 0 0 0 8.4-2.245c0-.399-.078-.78-.22-1.128Zm0 0a15.998 15.998 0 0 0 3.388-1.62m-5.043-.025a15.994 15.994 0 0 1 1.622-3.395m3.42 3.42a15.995 15.995 0 0 0 4.764-4.648l3.876-5.814a1.151 1.151 0 0 0-1.597-1.597L14.146 6.32a15.996 15.996 0 0 0-4.649 4.763m3.42 3.42a6.776 6.776 0 0 0-3.42-3.42" />
            </svg>

            `,
        },
        {
            name: 'Materials',
            to: '/admin/materials',
            icon: `
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 0 0 .75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 0 0-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0 1 12 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 0 1-.673-.38m0 0A2.18 2.18 0 0 1 3 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 0 1 3.413-.387m7.5 0V5.25A2.25 2.25 0 0 0 13.5 3h-3a2.25 2.25 0 0 0-2.25 2.25v.894m7.5 0a48.667 48.667 0 0 0-7.5 0M12 12.75h.008v.008H12v-.008Z" />
            </svg>

        `,
        },
        {
            name: 'Orders',
            to: '/admin/orders',
            icon: `
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
            </svg>

        `,
        },

        {
            name: 'Message',
            to: '/admin/message',
            icon: `
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3.75h6M21 12a9 9 0 11-18 0 9 9 0 0118 0zM15.75 15.75l3 3" />
        </svg>
    `,
        },
    ])

    const handleSignOut = async () => {
        isLoggingOut.value = true
        const respData = await apiService.post<{ success: boolean }>('logout', {})

        if (respData.success) {
            isLoggingOut.value = false
            window.location.href = '/'
        }
    }
</script>

<template>
    <nav
        class="fixed top-0 z-50 w-full bg-gray-900 border-b border-gray-500 dark:bg-gray-800 dark:border-gray-700"
    >
        <div class="px-3 py-3 lg:px-5 lg:pl-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center justify-start rtl:justify-end">
                    <button
                        data-drawer-target="logo-sidebar"
                        data-drawer-toggle="logo-sidebar"
                        aria-controls="logo-sidebar"
                        type="button"
                        class="inline-flex items-center p-2 text-sm text-gray-500 rounded-lg sm:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-600"
                    >
                        <span class="sr-only">Open sidebar</span>
                        <svg
                            class="w-6 h-6"
                            aria-hidden="true"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <path
                                clip-rule="evenodd"
                                fill-rule="evenodd"
                                d="M2 4.75A.75.75 0 012.75 4h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 4.75zm0 10.5a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5a.75.75 0 01-.75-.75zM2 10a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 10z"
                            ></path>
                        </svg>
                    </button>

                    <RouterLink to="/admin/dashboard" class="flex ms-2 md:me-24">
                        <img src="/jarvis-logo-circle.png" class="h-8 me-3" />

                        <h1 class="text-white text-2xl hover:cursor-pointer hover:opacity-75">
                            Jarvis
                            <span class="text-yellow-600">Designs</span>
                        </h1>
                    </RouterLink>
                </div>

                <div class="flex items-center">
                    <div class="flex items-center ms-3">
                        <div>
                            <button
                                type="button"
                                class="flex text-sm bg-gray-800 rounded-full focus:ring-1 focus:ring-gray-300 dark:focus:ring-gray-600"
                                aria-expanded="false"
                                data-dropdown-toggle="dropdown-user"
                            >
                                <span class="sr-only">Open user menu</span>
                                <img
                                    class="w-8 h-8 rounded-full"
                                    src="../assets/img/admin-icon.png"
                                    alt="Admin Photo"
                                />
                            </button>
                        </div>

                        <!-- DROPDOWN NAVBAR USER-->
                        <div
                            class="bg-white z-50 hidden my-4 text-base list-none divide-y divide-gray-100 shadow-sm dark:bg-gray-700 dark:divide-gray-600"
                            id="dropdown-user"
                        >
                            <div class="px-4 py-3" role="none">
                                <p class="text-sm text-gray-900 dark:text-black" role="none">
                                    {{ authStore.user?.name }}
                                </p>
                                <p
                                    class="text-sm font-medium text-gray-900 truncate dark:text-gray-300"
                                    role="none"
                                >
                                    {{ authStore.user?.username }}
                                </p>
                            </div>
                            <ul class="py-1" role="none">
                                <li>
                                    <RouterLink
                                        to="/admin/designs"
                                        class="w-full text-center block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600 dark:hover:text-black"
                                        role="menuitem"
                                    >
                                        Settings
                                    </RouterLink>
                                </li>

                                <li>
                                    <button
                                        @click="handleSignOut()"
                                        class="w-full block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600 dark:hover:text-black"
                                        role="menuitem"
                                    >
                                        Sign Out
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <aside
        id="logo-sidebar"
        class="fixed top-0 left-0 mt-14 z-40 w-64 h-screen transition-transform -translate-x-full border-r border-gray-200 sm:translate-x-0 dark:bg-gray-800 dark:border-gray-700"
        aria-label="Sidebar"
    >
        <div class="h-full overflow-y-auto bg-gray-900 dark:bg-gray-800 pt-2">
            <ul class="space-y-2 font-medium">
                <li v-for="link in sideLinks" :key="link.to">
                    <RouterLink :to="link.to" custom v-slot="{ isActive, navigate }">
                        <a
                            @click="navigate"
                            :class="[
                                'flex items-center p-2 text-white hover:cursor-pointer dark:text-black hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 group',
                                isActive ? 'bg-gray-100 dark:bg-gray-700 ' : '',
                            ]"
                        >
                            <!-- You can change this icon per route if you want -->
                            <span
                                class="w-5 h-5 text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-black"
                                v-html="link.icon"
                            />

                            <span :class="['ms-3', isActive ? 'text-gray-500' : '']">
                                {{ link.name }}
                            </span>
                        </a>
                    </RouterLink>
                </li>
            </ul>
        </div>
    </aside>

    <div v-if="isLoggingOut">
        <Loader msg="Logging Out..." />
    </div>
</template>
