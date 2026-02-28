<script lang="ts" setup>
    import { apiService } from '@/api/axios'
    import { useFetchAuthenticatedUser } from '@/composables/useFetchAuthenticatedUser'
    import type { Message, UpdateChat } from '@/types/message'
    import { useMutation, useQueryClient } from '@tanstack/vue-query'
    import { ref, computed } from 'vue'
    import ToolTipMenu from './ToolTipMenu.vue'
    import { XMarkIcon } from '@heroicons/vue/20/solid'
    import { marked } from 'marked'
    import DOMPurify from 'dompurify'
    import { useAuthorization } from '@/composables/useAuthorization'

    const props = defineProps<{
        messages: Message[] | undefined
        conversationUserID: number
        queryKey: string
    }>()

    const { authStore } = useFetchAuthenticatedUser()
    const queryClient = useQueryClient()
    const { isAdmin } = useAuthorization()

    // UPDATE MESSAGE MUTATION
    const updateMessageMutation = useMutation({
        mutationFn: async ({ message_id, content }: UpdateChat) => {
            const response = await apiService.put(`/api/update/chat/${message_id}`, { content })
            return response
        },
        onSuccess: async () => {
            queryClient.invalidateQueries({
                queryKey: [props.queryKey, props.conversationUserID],
            })
        },
        onError: (error) => {
            console.error('Message update failed:', error)
        },
    })

    const editingMessageId = ref<number | null>(null)
    const editedContent = ref<string>('')

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

    const handleStartEdit = (msg: Message) => {
        editingMessageId.value = msg.id
        editedContent.value = msg.content
    }

    const handleCancelEdit = () => {
        editingMessageId.value = null
        editedContent.value = ''
    }

    // Parse and sanitize markdown content
    const formatMessageContent = (content: string) => {
        if (!content) return ''

        // Remove the admin link token so it doesn't render in the markdown body
        let processedContent = content
        processedContent = processedContent.replace(/\[ADMIN_ORDER_LINK:.*?\]/g, '')

        const rawHtml = marked.parse(processedContent, { gfm: true, breaks: true }) as string
        return DOMPurify.sanitize(rawHtml)
    }

    const getAdminOrderLink = (content: string) => {
        if (!content) return null
        const match = content.match(/\[ADMIN_ORDER_LINK:(.*?)\]/)
        return match ? match[1] : null
    }
</script>

<style scoped>
    /* Scoped styles to ensure markdown renders nicely inside the bubble */
    :deep(.markdown-body p) {
        margin-bottom: 0.5rem;
    }
    :deep(.markdown-body p:last-child) {
        margin-bottom: 0;
    }
    :deep(.markdown-body strong) {
        font-weight: 700;
    }
    :deep(.markdown-body ul) {
        list-style-type: disc;
        padding-left: 1.5rem;
        margin-bottom: 0.5rem;
    }
    :deep(.markdown-body ol) {
        list-style-type: decimal;
        padding-left: 1.5rem;
        margin-bottom: 0.5rem;
    }
    :deep(.markdown-body a) {
        text-decoration: underline;
    }
</style>

<template>
    <div
        v-for="msg in messages"
        :key="msg.id"
        :class="[
            'flex',
            msg.sender_id === authStore.currentUser?.id ? 'justify-end' : 'justify-start',
        ]"
    >
        <div
            :class="[
                'flex flex-col gap-2',
                msg.sender_id === authStore.currentUser?.id ? 'items-end' : 'items-start',
            ]"
        >
            <div
                v-if="msg.content"
                class="flex items-center gap-2"
                :class="{
                    'flex-row-reverse': msg.sender_id !== authStore.currentUser?.id,
                }"
            >
                <!-- Ellipsis Icon -->
                <ToolTipMenu
                    :message="msg"
                    :conversationUserID="conversationUserID"
                    queryKey="admin_conversation"
                    @start-edit="handleStartEdit"
                />

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
                    </template>

                    <template v-else>
                        <div
                            class="markdown-body text-sm break-words"
                            v-html="formatMessageContent(msg.content)"
                        ></div>
                        <div v-if="isAdmin && getAdminOrderLink(msg.content)" class="mt-3">
                            <router-link
                                :to="`/admin/orders?highlight=${getAdminOrderLink(msg.content)}`"
                                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors duration-200"
                            >
                                View Order #{{ getAdminOrderLink(msg.content) }}
                            </router-link>
                        </div>
                    </template>
                </div>
            </div>

            <!-- If message has an attachment (image/file) -->
            <div v-if="msg.message_attachments && msg.message_attachments.length > 0" class="mb-2">
                <img
                    v-for="attachment in msg.message_attachments"
                    :key="attachment.id"
                    :src="attachment.attachment_temp_url"
                    alt="Attachment"
                    class="w-full max-w-[200px] rounded-md object-cover mb-2"
                />
            </div>
        </div>
    </div>
</template>
