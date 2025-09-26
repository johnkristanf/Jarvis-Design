<script lang="ts" setup>
    import { apiService } from '@/api/axios'
    import { useFetchAuthenticatedUser } from '@/composables/useFetchAuthenticatedUser'
    import type { Message, UpdateChat } from '@/types/message'
    import { useMutation, useQueryClient } from '@tanstack/vue-query'
    import { ref } from 'vue'
    import ToolTipMenu from './ToolTipMenu.vue'
    import { XMarkIcon } from '@heroicons/vue/20/solid'

    const props = defineProps<{
        messages: Message[]
        conversationUserID: number
        queryKey: string
    }>()

    const { authStore } = useFetchAuthenticatedUser()
    const queryClient = useQueryClient()

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
</script>

<template>
    <div
        v-for="msg in messages"
        :key="msg.id"
        :class="['flex', msg.sender_id === authStore.currentUser?.id ? 'justify-end' : 'justify-start']"
    >
        <div
            :class="['flex flex-col gap-2', msg.sender_id === authStore.currentUser?.id ? 'items-end' : 'items-start']"
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
                        {{ msg.content }}
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
