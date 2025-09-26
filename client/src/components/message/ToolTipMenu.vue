<!-- eslint-disable @typescript-eslint/no-explicit-any -->
<script lang="ts" setup>
    import { apiService } from '@/api/axios'
    import { useFetchAuthenticatedUser } from '@/composables/useFetchAuthenticatedUser'
    import type { Message } from '@/types/message'
    import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue'
    import { DocumentDuplicateIcon, EllipsisVerticalIcon, PencilSquareIcon, TrashIcon } from '@heroicons/vue/20/solid'
    import { useMutation, useQueryClient } from '@tanstack/vue-query'

    const props = defineProps<{
        message: Message
        conversationUserID: number
        queryKey: string
    }>()

    const emit = defineEmits<{
        (e: 'start-edit', msg: Message): void
    }>()

    const handleEditMessage = (msg: Message) => {
        emit('start-edit', msg)
    }

    const queryClient = useQueryClient()
    const { authStore } = useFetchAuthenticatedUser()

    const handleCopyMessage = async (content: string) => {
        await navigator.clipboard.writeText(content)
    }

    // DELETE MESSAGE MUTATION
    const deleteMessageMutation = useMutation({
        mutationFn: async (message_id: number) => {
            const response = await apiService.delete<{ success: boolean; message: string }>(
                `/api/delete/chat/${message_id}`,
            )
            console.log('response delete: ', response)
            return response
        },
        onSuccess: async () => {
            queryClient.invalidateQueries({
                queryKey: [props.queryKey, props.conversationUserID],
            })
        },
        onError: (error) => {
            console.error('Message send failed:', error)
        },
    })

    const handleDeleteMessage = (message_id: number) => {
        deleteMessageMutation.mutate(message_id)
    }
</script>
<template>
    <Menu as="div" class="relative inline-block text-left z-[9999]">
        <div>
            <MenuButton
                class="inline-flex w-full justify-center rounded-md text-sm font-medium focus:outline-none focus-visible:ring-2 focus-visible:ring-white/75"
            >
                <EllipsisVerticalIcon class="size-5 hover:opacity-75 hover:cursor-pointer" />
            </MenuButton>
        </div>

        <transition
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="transform scale-95 opacity-0"
            enter-to-class="transform scale-100 opacity-100"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="transform scale-100 opacity-100"
            leave-to-class="transform scale-95 opacity-0"
        >
            <MenuItems
                :class="[
                    'absolute mt-2 w-56 divide-y divide-gray-100 rounded-md bg-white shadow-lg ring-1 ring-black/5 focus:outline-none transition',
                    message.sender_id === authStore.currentUser?.id
                        ? 'right-0 origin-top-right' // Your messages → menu on LEFT side of bubble
                        : 'left-0 origin-top-left', // Other user's messages → menu on RIGHT side of bubble
                ]"
            >
                <div class="px-1 py-1">
                    <MenuItem v-slot="{ active }">
                        <button
                            @click="handleCopyMessage(message.content)"
                            :class="[
                                active ? 'bg-gray-800 text-white cursor-pointer' : 'text-gray-900',
                                'group flex w-full items-center rounded-md px-2 py-2 text-sm',
                            ]"
                        >
                            <DocumentDuplicateIcon
                                :active="active"
                                :class="['mr-2 h-5 w-5', active ? 'text-white' : 'text-gray-900']"
                                aria-hidden="true"
                            />
                            Copy
                        </button>
                    </MenuItem>
                </div>

                <div v-if="message.sender_id === authStore.currentUser?.id" class="px-1 py-1">
                    <MenuItem v-slot="{ active }">
                        <button
                            @click="handleEditMessage(message)"
                            :class="[
                                active ? 'bg-blue-600 text-white cursor-pointer' : 'text-gray-900',
                                'group flex w-full items-center rounded-md px-2 py-2 text-sm',
                            ]"
                        >
                            <PencilSquareIcon
                                :class="['mr-2 h-5 w-5', active ? 'text-white' : 'text-blue-700']"
                                aria-hidden="true"
                            />
                            Edit
                        </button>
                    </MenuItem>
                </div>

                <div v-if="message.sender_id === authStore.currentUser?.id" class="px-1 py-1">
                    <MenuItem v-slot="{ active }">
                        <button
                            @click="handleDeleteMessage(message.id)"
                            :class="[
                                active ? 'bg-red-600 text-white cursor-pointer' : 'text-gray-900',
                                'group flex w-full items-center rounded-md px-2 py-2 text-sm',
                            ]"
                        >
                            <TrashIcon
                                :class="['mr-2 h-5 w-5', active ? 'text-white' : 'text-red-700']"
                                aria-hidden="true"
                            />
                            Delete
                        </button>
                    </MenuItem>
                </div>
            </MenuItems>
        </transition>
    </Menu>
</template>
