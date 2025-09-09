<!-- eslint-disable @typescript-eslint/no-explicit-any -->
<script lang="ts" setup>
    import { getAllCustomers, getConversation } from '@/api/get/message'
    import { sendChatMessageApi } from '@/api/post/message'
    import { useFetchAuthenticatedUser } from '@/composables/useFetchAuthenticatedUser'
    import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
    import { computed, ref, watch } from 'vue'
    import {
        ArrowUpOnSquareIcon,
        ChevronDoubleRightIcon,
        XMarkIcon,
        DocumentDuplicateIcon,
        EllipsisVerticalIcon,
        PencilSquareIcon,
        TrashIcon,
    } from '@heroicons/vue/20/solid'

    import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue'

    import Toast from 'primevue/toast'
    import { useToast } from 'primevue'
    import echo from '@/services/echo'
    import type { UpdateChat } from '@/types/message'
    import { apiService } from '@/api/axios'

    const { authStore } = useFetchAuthenticatedUser()
    const queryClient = useQueryClient()

    // ALL CONVERSATION QUERY

    const allCustomersQuery = useQuery({
        queryKey: ['all_customers'],
        queryFn: getAllCustomers,
    })

    // track selected conversation
    const selectedCustomerData = ref({
        id: -1,
        name: '',
        email: '',
    })

    const selectCustomerToChat = (user_id: number, name: string, email: string) => {
        selectedCustomerData.value.id = user_id
        selectedCustomerData.value.name = name
        selectedCustomerData.value.email = email
    }

    // PRE-SELECT 1st CUSTOMER
    watch(
        () => allCustomersQuery.data.value,
        (customer) => {
            if (customer && customer.length > 0) {
                selectedCustomerData.value.id = customer[0].id
                selectedCustomerData.value.name = customer[0].name
                selectedCustomerData.value.email = customer[0].email
            }
        },
    )

    // Local state
    const messageContent = ref('')
    const attachment = ref<File | null>(null)
    const attachmentPreview = ref<string | null>(null)
    const toast = useToast()

    // SEND MESSAGE MUTATION
    const sendMessageMutation = useMutation({
        mutationFn: sendChatMessageApi,
        onSuccess: async () => {
            messageContent.value = ''
            attachment.value = null
        },
        onError: (error) => {
            console.error('Message send failed:', error)
        },
    })

    const messagesQuery = useQuery({
        queryKey: computed(() => ['admin_conversation', selectedCustomerData.value.id]),
        queryFn: async () => {
            if (!selectedCustomerData.value.id) return null
            return await getConversation(selectedCustomerData.value.id)
        },
        enabled: computed(() => !!selectedCustomerData.value.id),
    })

    // Handle file selection
    const handleFileChange = (e: Event) => {
        const target = e.target as HTMLInputElement
        if (target.files && target.files[0]) {
            const file = target.files[0]

            if (!file.type.startsWith('image/')) {
                toast.add({
                    severity: 'warn',
                    summary: 'Invalid File Type',
                    detail: 'Only image is accepted',
                    life: 3000,
                })
                target.value = '' // reset input
                return
            }

            if (file.type.startsWith('image/')) {
                attachment.value = file
                attachmentPreview.value = URL.createObjectURL(file)
            } else {
                attachmentPreview.value = null // optional: preview icon for docs/pdf
            }
        }
    }

    // CLEAR UPLOADED ATTACHMENT
    const clearAttachment = () => {
        attachment.value = null
        attachmentPreview.value = null
    }

    const handleSendMessage = () => {
        if (!messageContent.value && !attachment.value) return // prevent empty submission

        console.log('message: ', messageContent.value)
        console.log('attachment: ', attachment.value)

        const formData = new FormData()
        if (messageContent.value) {
            formData.append('content', messageContent.value)
        }

        if (selectedCustomerData.value.id) {
            formData.append('user_id', selectedCustomerData.value.id.toString())
        }

        if (attachment.value) {
            formData.append('attachment', attachment.value)
        }

        sendMessageMutation.mutate(formData)
    }

    // WATCH FOR NEW CHAT EVENT
    watch(
        () => selectedCustomerData.value.id,
        (newID) => {
            if (newID) {
                const channel = echo.channel(`chat.${newID}`)

                // Debug channel subscription
                channel.subscribed(() => {
                    console.log('✅ Subscribed to channel: chat.' + newID)
                })

                // eslint-disable-next-line @typescript-eslint/no-explicit-any
                channel.listen('.message.sent', (event: any) => {
                    console.log('📨 Event data:', event.message)
                    const eventMessage = event.message

                    // 1. Optimistically update Vue Query cache
                    queryClient.setQueryData(
                        ['admin_conversation', eventMessage.conversation.user_id],
                        (oldData: any) => {
                            if (!oldData) {
                                return {
                                    ...eventMessage.conversation,
                                    messages: [eventMessage.messages],
                                }
                            }

                            return {
                                ...oldData,
                                messages: [...oldData.messages, eventMessage.messages],
                            }
                        },
                    )

                    // 2. Fetch updated data in the background
                    queryClient.invalidateQueries({
                        queryKey: ['admin_conversation', eventMessage.conversation.user_id],
                    })
                })
            }
        },
    )

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
                queryKey: ['admin_conversation', selectedCustomerData.value.id],
            })
        },
        onError: (error) => {
            console.error('Message send failed:', error)
        },
    })

    // UPDATE MESSAGE MUTATION
    const updateMessageMutation = useMutation({
        mutationFn: async ({ message_id, content }: UpdateChat) => {
            const response = await apiService.put(`/api/update/chat/${message_id}`, { content })
            return response
        },
        onSuccess: async () => {
            queryClient.invalidateQueries({
                queryKey: ['admin_conversation', selectedCustomerData.value.id],
            })
        },
        onError: (error) => {
            console.error('Message update failed:', error)
        },
    })

    const editingMessageId = ref<number | null>(null)
    const editedContent = ref<string>('')

    const handleEditMessage = (msg: any) => {
        editingMessageId.value = msg.id
        editedContent.value = msg.content
    }

    const handleUpdateMessage = async (message_id: number) => {
        if (!editedContent.value.trim()) return

        await updateMessageMutation.mutateAsync({
            message_id,
            content: editedContent.value,
        })

        // Reset edit state after successful update
        editingMessageId.value = null
        editedContent.value = ''
    }

    const handleCancelEdit = () => {
        editingMessageId.value = null
        editedContent.value = ''
    }

    const handleCopyMessage = async (content: string) => {
        await navigator.clipboard.writeText(content)
    }

    const handleDeleteMessage = (message_id: number) => {
        deleteMessageMutation.mutate(message_id)
    }
</script>

<template>
    <div class="w-full h-[75vh] bg-gray-100 border border-gray-400 rounded-md flex">
        <!-- Sidebar with conversations -->
        <aside id="logo-sidebar" class="w-[25%] h-full" aria-label="Sidebar">
            <div class="h-full px-3 py-4 overflow-y-auto bg-white dark:bg-gray-800">
                <a href="#" class="flex items-center ps-2.5 mb-5">
                    <img src="/jarvis-logo-circle.png" class="h-6 me-3 sm:h-7" />
                    <div class="flex flex-col">
                        <span class="text-xl font-semibold whitespace-nowrap dark:text-white">
                            Message
                        </span>
                        <p class="text-xs text-gray-500">Talk with the customer.</p>
                    </div>
                </a>

                <!-- loop conversations -->
                <ul class="space-y-2 font-medium">
                    <li v-for="customer in allCustomersQuery.data.value ?? []" :key="customer.id">
                        <button
                            @click="
                                selectCustomerToChat(customer.id, customer.name, customer.email)
                            "
                            :class="[
                                'w-full flex items-center p-2 text-left rounded-lg group',
                                selectedCustomerData.id === customer.id
                                    ? 'bg-gray-100 dark:bg-gray-700'
                                    : 'text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700',
                            ]"
                        >
                            <img src="/user_icon.jpeg" class="h-7 mr-3 sm:h-8" alt="User avatar" />
                            <div class="flex flex-col text-sm">
                                <h1>{{ customer.name }}</h1>
                                <h1 class="text-gray-400">{{ customer.email }}</h1>
                            </div>
                        </button>
                    </li>
                </ul>
            </div>
        </aside>

        <!-- Chat messages -->
        <div class="relative flex-1">
            <template v-if="messagesQuery">
                <div class="flex flex-col h-full">
                    <!-- Chat Header -->
                    <div class="bg-gray-900 flex items-center font-medium h-18 pl-5">
                        <img src="/user_icon-removebg.png" class="h-12 mr-3" />
                        <div class="flex flex-col text-sm text-white">
                            <h1>{{ selectedCustomerData.name }}</h1>
                            <h1 class="text-gray-400">
                                {{ selectedCustomerData.email }}
                            </h1>
                        </div>
                    </div>

                    <!-- Messages -->
                    <div
                        class="flex-1 font-medium h-[70%] pt-5 px-5 pb-8 overflow-y-auto space-y-4"
                    >
                        <div
                            v-for="msg in messagesQuery.data.value?.messages"
                            :key="msg.id"
                            :class="[
                                'flex',
                                msg.sender_id === authStore.currentUser?.id
                                    ? 'justify-end'
                                    : 'justify-start',
                            ]"
                        >
                            <div class="flex flex-col">
                                <!-- If message has an attachment (image/file) -->
                                <div v-if="msg.attachment_temp_url" class="mb-2">
                                    <img
                                        :src="msg.attachment_temp_url"
                                        alt="Attachment"
                                        class="w-full max-w-[200px] rounded-md object-cover"
                                    />
                                </div>

                                <div
                                    class="flex items-center gap-2"
                                    :class="{
                                        'flex-row-reverse':
                                            msg.sender_id !== authStore.currentUser?.id,
                                    }"
                                >
                                    <!-- Ellipsis Icon -->

                                    <Menu as="div" class="relative inline-block text-left">
                                        <div>
                                            <MenuButton
                                                class="inline-flex w-full justify-center rounded-md text-sm font-medium focus:outline-none focus-visible:ring-2 focus-visible:ring-white/75"
                                            >
                                                <EllipsisVerticalIcon
                                                    class="size-5 hover:opacity-75 hover:cursor-pointer"
                                                />
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
                                                    msg.sender_id === authStore.currentUser?.id
                                                        ? 'right-0 origin-top-right' // Your messages → menu on LEFT side of bubble
                                                        : 'left-0 origin-top-left', // Other user's messages → menu on RIGHT side of bubble
                                                ]"
                                            >
                                                <div class="px-1 py-1">
                                                    <MenuItem v-slot="{ active }">
                                                        <button
                                                            @click="handleCopyMessage(msg.content)"
                                                            :class="[
                                                                active
                                                                    ? 'bg-gray-800 text-white cursor-pointer'
                                                                    : 'text-gray-900',
                                                                'group flex w-full items-center rounded-md px-2 py-2 text-sm',
                                                            ]"
                                                        >
                                                            <DocumentDuplicateIcon
                                                                :active="active"
                                                                :class="[
                                                                    'mr-2 h-5 w-5',
                                                                    active
                                                                        ? 'text-white'
                                                                        : 'text-gray-900',
                                                                ]"
                                                                aria-hidden="true"
                                                            />
                                                            Copy
                                                        </button>
                                                    </MenuItem>
                                                </div>

                                                <div
                                                    v-if="
                                                        msg.sender_id === authStore.currentUser?.id
                                                    "
                                                    class="px-1 py-1"
                                                >
                                                    <MenuItem v-slot="{ active }">
                                                        <button
                                                            @click="handleEditMessage(msg)"
                                                            :class="[
                                                                active
                                                                    ? 'bg-blue-600 text-white cursor-pointer'
                                                                    : 'text-gray-900',
                                                                'group flex w-full items-center rounded-md px-2 py-2 text-sm',
                                                            ]"
                                                        >
                                                            <PencilSquareIcon
                                                                :class="[
                                                                    'mr-2 h-5 w-5',
                                                                    active
                                                                        ? 'text-white'
                                                                        : 'text-blue-700',
                                                                ]"
                                                                aria-hidden="true"
                                                            />
                                                            Edit
                                                        </button>
                                                    </MenuItem>
                                                </div>

                                                <div
                                                    v-if="
                                                        msg.sender_id === authStore.currentUser?.id
                                                    "
                                                    class="px-1 py-1"
                                                >
                                                    <MenuItem v-slot="{ active }">
                                                        <button
                                                            @click="handleDeleteMessage(msg.id)"
                                                            :class="[
                                                                active
                                                                    ? 'bg-red-600 text-white cursor-pointer'
                                                                    : 'text-gray-900',
                                                                'group flex w-full items-center rounded-md px-2 py-2 text-sm',
                                                            ]"
                                                        >
                                                            <TrashIcon
                                                                :class="[
                                                                    'mr-2 h-5 w-5',
                                                                    active
                                                                        ? 'text-white'
                                                                        : 'text-red-700',
                                                                ]"
                                                                aria-hidden="true"
                                                            />
                                                            Delete
                                                        </button>
                                                    </MenuItem>
                                                </div>
                                            </MenuItems>
                                        </transition>
                                    </Menu>

                                    <!-- Message Bubble -->
                                    <div
                                        :class="[
                                            'relative px-4 py-2 rounded-lg max-w-sm',
                                            msg.sender_id === authStore.currentUser?.id
                                                ? 'bg-blue-500 text-white'
                                                : 'bg-gray-200 text-gray-800',
                                        ]"
                                    >
                                        <!-- If message is being edited -->
                                        <template v-if="editingMessageId === msg.id">
                                            <!-- Cancel Button at Top Right -->
                                            <button
                                                @click="handleCancelEdit"
                                                class="absolute top-2 right-2 px-1 py-1 text-white bg-red-800 hover:cursor-pointer hover:opacity-75 rounded-md"
                                                title="Cancel Edit"
                                            >
                                                <XMarkIcon class="w-4 h-4" />
                                            </button>

                                            <!-- Editable Input -->
                                            <input
                                                v-model="editedContent"
                                                class="px-2 py-1 rounded-md w-full text-white focus:outline-none"
                                                @keyup.enter="handleUpdateMessage(msg.id)"
                                            />

                                            <!-- Save Button Below Input -->
                                            <!-- <div class="mt-2">
                                                        <button
                                                            @click="handleUpdateMessage(msg.id)"
                                                            class="bg-gray-900 hover:bg-gray-700 text-white px-1 py-1 rounded flex items-center gap-1"
                                                        >
                                                            <CheckIcon class="w-5 h-5" />
                                                        </button>
                                                    </div> -->
                                        </template>

                                        <!-- Otherwise, just show the message -->
                                        <template v-else>
                                            {{ msg.content }}
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Message input -->
                    <div
                        class="w-full flex items-center gap-2 p-4 bg-gray-50 border-t border-gray-300"
                    >
                        <!-- File preview before sending -->
                        <div v-if="attachment" class="flex items-center gap-2">
                            <div v-if="attachmentPreview" class="relative">
                                <!-- Image preview -->
                                <img
                                    :src="attachmentPreview"
                                    class="h-16 w-16 object-cover rounded-md border"
                                />

                                <!-- X button inside image -->
                                <button
                                    @click="clearAttachment"
                                    class="absolute top-1 right-1 bg-black/40 backdrop-blur-sm rounded-full p-0.5 text-white hover:bg-black/60"
                                >
                                    <XMarkIcon class="w-4 h-4" />
                                </button>
                            </div>

                            <!-- Fallback if not image (file only) -->
                            <p v-else class="text-sm text-gray-600">{{ attachment.name }}</p>
                        </div>

                        <label
                            v-if="!attachment && !attachmentPreview"
                            for="file-upload"
                            class="cursor-pointer hover:opacity-75 bg-blue-500 rounded-md p-2"
                        >
                            <ArrowUpOnSquareIcon class="size-5 text-white" />
                            <input
                                id="file-upload"
                                type="file"
                                class="hidden"
                                @change="handleFileChange"
                            />
                        </label>

                        <input
                            type="text"
                            v-model="messageContent"
                            placeholder="Type your message..."
                            class="flex-1 font-medium px-4 py-2 border border-gray-300 rounded-full focus:outline-none focus:ring-1 focus:ring-blue-500"
                        />

                        <button
                            class="p-2 rounded-full hover:cursor-pointer bg-blue-500 hover:bg-blue-600 text-white"
                            @click="handleSendMessage"
                            :disabled="sendMessageMutation.isPending.value"
                            :class="{
                                'opacity-50 cursor-not-allowed':
                                    sendMessageMutation.isPending.value,
                            }"
                        >
                            <ChevronDoubleRightIcon class="size-5" />
                        </button>
                    </div>
                </div>
            </template>

            <!-- No conversation selected -->
            <div v-else class="flex items-center justify-center h-full text-gray-500">
                Select a conversation to start chatting
            </div>
        </div>

        <Toast />
    </div>
</template>
