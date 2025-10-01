<!-- eslint-disable @typescript-eslint/no-explicit-any -->
<script lang="ts" setup>
    import { useFetchAuthenticatedUser } from '@/composables/useFetchAuthenticatedUser'
    import { computed, onMounted, ref } from 'vue'
    import { RouterLink, useRouter } from 'vue-router'
    import { apiService } from '@/api/axios'
    import Loader from './Loader.vue'
    import { BellIcon, CheckIcon } from '@heroicons/vue/20/solid'
    import { Drawer } from 'primevue'
    import echo from '@/services/echo'
    import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
    import type { AdminNotifications } from '@/types/order'
    import { updateNotificationAsRead, updateNotificationAsReadAll } from '@/api/put/notifications'

    const isLoggingOut = ref<boolean>(false)
    const showNotificationDrawer = ref<boolean>(false)

    const router = useRouter()
    const queryClient = useQueryClient()

    // LOAD THE REAL USER IN THE NAVBAR LATER ON
    const { authStore } = useFetchAuthenticatedUser()

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
            name: 'Fabrics',
            to: '/admin/fabrics',
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

    const handleRedirectProfile = () => {
        router.push('/admin/profile')
    }

    const { data: notifications } = useQuery({
        queryKey: ['admin_notifications'],
        queryFn: async () => {
            const respData = await apiService.get<AdminNotifications[]>(
                '/api/get/admin/notifications',
            )
            console.log('respData: ', respData)

            return respData
        },
    })

    // Mark notification as read mutation
    const { isPending: isMarkingRead, mutate: notificationReadMutate } = useMutation({
        mutationFn: updateNotificationAsRead,
        onSuccess: async () => {
            queryClient.invalidateQueries({ queryKey: ['admin_notifications'] })
        },

        onError: (error) => {
            console.error('Mutation error:', error)
        },
    })

    const handleReadNotification = (notification_id: number) => {
        const notificationData = { notification_id, is_admin: true }
        notificationReadMutate(notificationData)
    }

    // Mark all notification as read mutation
    const { isPending: isMarkingAllRead, mutate: notificationReadAllMutate } = useMutation({
        mutationFn: updateNotificationAsReadAll,
        onSuccess: async () => {
            queryClient.invalidateQueries({ queryKey: ['admin_notifications'] })
        },

        onError: (error) => {
            console.error('Mutation error:', error)
        },
    })

    const handleMarkAllAsRead = () => {
        const isAdmin = true // flag for different notification database model
        notificationReadAllMutate(isAdmin)
    }

    // Unread notification count
    const unreadNotificationsCount = computed(() => {
        if (!notifications) return 0
        return notifications.value?.filter((notification) => !notification.is_read).length
    })

    // WATCH EVERY NEW NOTIFICATION
    onMounted(() => {
        const channel = echo.channel('admin.notification')

        channel.listen('.notify.admin', (event: any) => {
            const eventMessage = event.notification
            console.log('eventMessage:', eventMessage)

            if (eventMessage) {
                queryClient.setQueryData<AdminNotifications[]>(
                    ['admin_notifications'],
                    (oldData) => {
                        if (!oldData) return [eventMessage] // no cache yet, set initial
                        return [eventMessage, ...oldData] // prepend new notification
                    },
                )
            }
        })
    })
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
                        <button
                            v-if="authStore.currentUser"
                            @click="showNotificationDrawer = true"
                            class="relative rounded-full bg-gray-800 p-1 mr-2 text-white hover:text-white focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-gray-800 focus:outline-hidden hover:cursor-pointer"
                        >
                            <span class="absolute -inset-1.5" />
                            <span class="sr-only">View notifications</span>
                            <BellIcon class="size-6" aria-hidden="true" />

                            <!-- Notification badge on bell icon -->
                            <span
                                v-if="(unreadNotificationsCount ?? 0) > 0"
                                class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full min-w-5 h-5 flex items-center justify-center px-1"
                            >
                                {{
                                    (unreadNotificationsCount ?? 0) > 99 ? '99+' : unreadNotificationsCount
                                }}
                            </span>
                        </button>

                        <!-- DRAWER CONTAINER -->
                        <div
                            v-if="showNotificationDrawer"
                            class="absolute card flex justify-center"
                        >
                            <Drawer
                                v-model:visible="showNotificationDrawer"
                                position="right"
                                class="bg-gray-900 !w-full md:!w-80 lg:!w-[30rem] pb-6"
                            >
                                <template #header>
                                    <div
                                        class="w-full flex items-center justify-between pb-4 border-b border-gray-700"
                                    >
                                        <div class="flex items-center gap-2">
                                            <BellIcon class="size-5" />
                                            <span class="font-bold text-lg">Notifications</span>
                                        </div>

                                        <button
                                            v-if="(unreadNotificationsCount ?? 0) > 0"
                                            @click="handleMarkAllAsRead"
                                            class="flex items-center gap-1 text-sm text-gray-400 hover:opacity-75 hover:cursor-pointer transition-colors"
                                        >
                                            <CheckIcon class="size-5" />
                                            Mark All as Read
                                        </button>
                                    </div>
                                </template>

                                <div class="flex flex-col gap-4 h-full">
                                    <!-- Notifications List -->
                                    <div
                                        v-if="notifications && notifications.length > 0"
                                        class="flex flex-col gap-5"
                                    >
                                        <div
                                            v-for="notification in notifications"
                                            :key="notification.id"
                                            @click="
                                                !notification.is_read &&
                                                handleReadNotification(notification.id)
                                            "
                                            :class="[
                                                'flex items-start gap-3 p-4 border-b border-gray-800 transition-colors ',
                                                !notification.is_read
                                                    ? 'bg-gray-800 hover:cursor-pointer hover:opacity-75 '
                                                    : '',
                                            ]"
                                        >
                                            <!-- Notification Icon -->
                                            <div class="flex-shrink-0 mt-1">
                                                <div
                                                    class="w-10 h-10 bg-yellow-600 rounded-full flex items-center justify-center"
                                                >
                                                    <BellIcon class="size-5 text-white" />
                                                </div>
                                            </div>

                                            <!-- Notification Content -->
                                            <div class="flex-1 flex flex-col gap-1">
                                                <div class="flex items-start justify-between gap-2">
                                                    <p
                                                        class="text-sm font-medium break-words whitespace-pre-wrap"
                                                        :class="
                                                            notification.is_read ? '' : 'text-white'
                                                        "
                                                    >
                                                        {{ notification.message }}
                                                    </p>
                                                    <div
                                                        v-if="!notification.is_read"
                                                        class="w-2 h-2 bg-yellow-600 rounded-full flex-shrink-0 mt-1"
                                                    ></div>
                                                </div>
                                                <span class="text-xs text-gray-400">
                                                    {{
                                                        new Date(
                                                            notification.created_at,
                                                        ).toLocaleString()
                                                    }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Empty State -->
                                    <div
                                        v-else
                                        class="flex flex-col items-center justify-center h-full py-12"
                                    >
                                        <BellIcon class="size-16 text-gray-700 mb-4" />
                                        <p class="text-gray-400 text-sm">No notifications yet</p>
                                    </div>
                                </div>
                            </Drawer>
                        </div>

                        <!-- User Profile -->
                        <div>
                            <button
                                type="button"
                                class="flex text-sm bg-gray-800 rounded-full focus:ring-1 focus:ring-gray-300 dark:focus:ring-gray-600 hover:cursor-pointer"
                                aria-expanded="false"
                                data-dropdown-toggle="dropdown-user"
                            >
                                <span class="sr-only">Open user menu</span>
                                <div
                                    v-if="authStore.user?.name"
                                    class="w-8 h-8 bg-gray-700 rounded-full flex items-center justify-center"
                                >
                                    <span class="text-2xl font-bold text-white">
                                        {{ authStore.user?.name?.charAt(0)?.toUpperCase() }}
                                    </span>
                                </div>
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
                                    <button
                                        @click="handleRedirectProfile"
                                        class="w-full block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600 dark:hover:text-black"
                                        role="menuitem"
                                    >
                                        Profile
                                    </button>
                                </li>

                                <li>
                                    <button
                                        @click="handleSignOut"
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

    <Loader v-if="isLoggingOut" msg="Logging Out..." />

    <Loader v-if="isMarkingRead" msg="Marking as Read..." />

    <Loader v-if="isMarkingAllRead" msg="Marking All as Read..." />
</template>
