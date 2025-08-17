<script lang="ts" setup>
    import { getAllConversation } from '@/api/get/message'
    import { sendChatMessageApi } from '@/api/post/message'
    import { useFetchAuthenticatedUser } from '@/composables/useFetchAuthenticatedUser'
    import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
    import { ref, watch } from 'vue'
    import { ArrowUpOnSquareIcon, ChevronDoubleRightIcon, XMarkIcon } from '@heroicons/vue/20/solid'
    import type { Conversation } from '@/types/message'
    import Toast from 'primevue/toast'
    import { useToast } from 'primevue'

    const { authStore } = useFetchAuthenticatedUser()
    const queryClient = useQueryClient()

    // ALL CONVERSATION QUERY
    const allConversationQuery = useQuery({
        queryKey: ['all_conversation'],
        queryFn: getAllConversation,
    })

    // track selected conversation
    const selectedConversation = ref<Conversation>()

    const selectConversation = (data: Conversation) => {
        selectedConversation.value = data
    }

    // PRE-SELECT 1st CONVERSATION
    watch(
        () => allConversationQuery.data.value,
        (conversations) => {
            if (conversations && conversations.length > 0 && !selectedConversation.value) {
                selectedConversation.value = conversations[0]
            }
        },
        { immediate: true },
    )

    // Local state
    const messageContent = ref('')
    const attachment = ref<File | null>(null)
    const attachmentPreview = ref<string | null>(null)
    const toast = useToast()

    // SEND MESSAGE MUTATION
    const sendMessageMutation = useMutation({
        mutationFn: sendChatMessageApi,
        onSuccess: () => {
            console.log('SUCCESSSS')

            // Refetch conversation messages
            queryClient.invalidateQueries({
                queryKey: ['conversation', selectedConversation.value?.id],
            })

            // Also refresh conversations list if needed
            queryClient.invalidateQueries({ queryKey: ['all_conversation'] })
            allConversationQuery.refetch()

            messageContent.value = ''
            attachment.value = null
        },
        onError: (error) => {
            console.error('Message send failed:', error)
        },
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
        console.log('selectedUserId: ', selectedConversation.value?.id)
        console.log('attachment: ', attachment.value)

        const formData = new FormData()
        if (messageContent.value) {
            formData.append('content', messageContent.value)
        }

        if (selectedConversation.value?.user?.id) {
            formData.append('user_id', selectedConversation.value?.user?.id.toString())
        }

        if (attachment.value) {
            formData.append('attachment', attachment.value)
        }

        sendMessageMutation.mutate(formData)
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
                    <li v-for="convo in allConversationQuery.data.value ?? []" :key="convo.id">
                        <button
                            @click="selectConversation(convo)"
                            :class="[
                                'w-full flex items-center p-2 text-left rounded-lg group',
                                selectedConversation?.id === convo.id
                                    ? 'bg-gray-100 dark:bg-gray-700'
                                    : 'text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700',
                            ]"
                        >
                            <img src="/user_icon.jpeg" class="h-7 mr-3 sm:h-8" alt="User avatar" />
                            <div class="flex flex-col text-sm">
                                <h1>{{ convo.user?.name }}</h1>
                                <h1 class="text-gray-400">{{ convo.user?.email }}</h1>
                            </div>
                        </button>
                    </li>
                </ul>
            </div>
        </aside>

        <!-- Chat messages -->
        <div class="relative flex-1">
            <template v-if="selectedConversation">
                <div class="flex flex-col h-full">
                    <!-- Chat Header -->
                    <div class="bg-gray-900 flex items-center font-medium h-18 pl-5">
                        <img src="/user_icon-removebg.png" class="h-12 mr-3" />
                        <div class="flex flex-col text-sm text-white">
                            <h1>{{ selectedConversation.user?.name }}</h1>
                            <h1 class="text-gray-400">{{ selectedConversation.user?.email }}</h1>
                        </div>
                    </div>

                    <!-- Messages -->
                    <div
                        class="flex-1 font-medium h-[70%] pt-5 px-5 pb-8 overflow-y-auto space-y-4"
                    >
                        <div
                            v-for="msg in selectedConversation.messages"
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
                                    :class="[
                                        'px-4 py-2 rounded-lg max-w-sm',
                                        msg.sender_id === authStore.currentUser?.id
                                            ? 'bg-blue-500 text-white'
                                            : 'bg-gray-200 text-gray-800',
                                    ]"
                                >
                                    {{ msg.content }}
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
