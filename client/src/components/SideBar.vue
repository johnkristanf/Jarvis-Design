<!-- eslint-disable @typescript-eslint/no-explicit-any -->
<script lang="ts" setup>
    import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'
    import { useFetchAuthenticatedUser } from '@/composables/useFetchAuthenticatedUser'
    import { computed, onMounted, ref } from 'vue'
    import { RouterLink, useRouter } from 'vue-router'
    import { apiService } from '@/api/axios'
    import Loader from './Loader.vue'
    import { BellIcon, CheckIcon } from '@heroicons/vue/20/solid'
    import { Drawer } from 'primevue'
    import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
    import type { AdminNotifications } from '@/types/order'
    import { updateNotificationAsRead, updateNotificationAsReadAll } from '@/api/put/notifications'
    import { initializeEcho } from '@/services/echo'
    import { sideLinks, getSidebarDropdownLinks } from '@/constants/sidebarLinks'

    const showNotificationDrawer = ref<boolean>(false)

    const router = useRouter()
    const queryClient = useQueryClient()

    // LOAD THE REAL USER IN THE NAVBAR LATER ON
    const { authStore } = useFetchAuthenticatedUser()

    const dropdownNavLinks = getSidebarDropdownLinks(router, authStore)

    const { data: notifications } = useQuery({
        queryKey: ['admin_notifications'],
        queryFn: async () => {
            const respData = await apiService.get<AdminNotifications[]>(
                '/api/get/admin/notifications',
            )
            return respData
        },
    })

    // Mark notification as read mutation
    const { isPending: isMarkingRead, mutate: notificationReadMutate } = useMutation({
        mutationFn: updateNotificationAsRead,
        onSuccess: async () => {
            queryClient.invalidateQueries({ queryKey: ['admin_notifications'] })
            router.push('/admin/orders')
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
        const echo = initializeEcho()
        const channel = echo.channel('admin.notification')

        channel.listen('.notify.admin', (event: any) => {
            const eventMessage = event.notification
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
        class="fixed top-0 z-50 w-full bg-gray-900 border-b border-gray-500 class:bg-gray-800 class:border-gray-700"
    >
        <div class="px-3 py-3 lg:px-5 lg:pl-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center justify-start rtl:justify-end">
                    <button
                        data-drawer-target="logo-sidebar"
                        data-drawer-toggle="logo-sidebar"
                        aria-controls="logo-sidebar"
                        type="button"
                        class="inline-flex items-center p-2 text-sm text-gray-500 rounded-lg sm:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 class:text-gray-400 class:hover:bg-gray-700 class:focus:ring-gray-600"
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
                        <img src="/jarvis-logo-white.jpeg" class="h-8 me-2 rounded-full" />

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
                                    (unreadNotificationsCount ?? 0) > 99
                                        ? '99+'
                                        : unreadNotificationsCount
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
                        <Menu as="div" class="relative">
                            <div>
                                <MenuButton
                                    class="flex text-sm bg-gray-800 rounded-full focus:ring-1 focus:ring-gray-300 class:focus:ring-gray-600 hover:cursor-pointer"
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
                                </MenuButton>
                            </div>

                            <transition
                                enter-active-class="transition ease-out duration-100"
                                enter-from-class="transform opacity-0 scale-95"
                                enter-to-class="transform opacity-100 scale-100"
                                leave-active-class="transition ease-in duration-75"
                                leave-from-class="transform opacity-100 scale-100"
                                leave-to-class="transform opacity-0 scale-95"
                            >
                                <MenuItems
                                    class="absolute right-0 z-50 mt-2 w-48 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black/5 focus:outline-hidden class:bg-gray-700"
                                >
                                    <div class="px-4 py-3 border-b border-gray-100 class:border-gray-600">
                                        <p class="text-sm text-gray-900 class:text-black">
                                            {{ authStore.user?.name }}
                                        </p>
                                        <p
                                            class="text-sm font-medium text-gray-900 truncate class:text-gray-300"
                                        >
                                            {{ authStore.user?.username }}
                                        </p>
                                    </div>
                                    <div class="py-1">
                                        <MenuItem
                                            v-for="item in dropdownNavLinks"
                                            :key="item.name"
                                            v-slot="{ active }"
                                        >
                                            <button
                                                @click="
                                                    () => {
                                                        item.onclick?.()
                                                    }
                                                "
                                                :class="[
                                                    active ? 'bg-gray-100 class:bg-gray-600' : '',
                                                    'w-full text-left block px-4 py-2 text-sm text-gray-700 class:text-gray-300'
                                                ]"
                                            >
                                                {{ item.name }}
                                            </button>
                                        </MenuItem>
                                    </div>
                                </MenuItems>
                            </transition>
                        </Menu>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <aside
        id="logo-sidebar"
        class="fixed top-0 left-0 mt-14 z-40 w-64 h-screen transition-transform -translate-x-full sm:translate-x-0 class:bg-gray-800 class:border-gray-700"
        aria-label="Sidebar"
    >
        <div class="h-full overflow-y-auto bg-gray-900 w-[75%] class:bg-gray-800 pt-2">
            <ul class="space-y-2 font-medium">
                <li v-for="link in sideLinks" :key="link.to">
                    <RouterLink :to="link.to" custom v-slot="{ isActive, navigate }">
                        <a
                            @click="navigate"
                            :class="[
                                'flex items-center p-2 text-white hover:cursor-pointer class:text-black hover:text-gray-500 hover:bg-gray-100 class:hover:bg-gray-700 group',
                                isActive ? 'bg-gray-100 class:bg-gray-700 ' : '',
                            ]"
                        >
                            <!-- You can change this icon per route if you want -->
                            <span
                                class="w-5 h-5 text-gray-500 class:text-gray-400 group-hover:text-gray-900 class:group-hover:text-black"
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

    <Loader v-if="authStore.isLoggingOut" msg="Logging Out..." />

    <Loader v-if="isMarkingAllRead" msg="Marking All as Read..." />
</template>
