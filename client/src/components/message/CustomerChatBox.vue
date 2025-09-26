<!-- eslint-disable @typescript-eslint/no-explicit-any -->
<script lang="ts" setup>
    import { getConversation } from '@/api/get/message'
    import { sendChatMessageApi } from '@/api/post/message'
    import { useFetchAuthenticatedUser } from '@/composables/useFetchAuthenticatedUser'
    import { TransitionRoot, TransitionChild, Dialog, DialogPanel } from '@headlessui/vue'

    import { ArrowUpOnSquareIcon, ChevronDoubleRightIcon, XMarkIcon } from '@heroicons/vue/20/solid'
    import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
    import { ref, watch } from 'vue'
    import Toast from 'primevue/toast'
    import { useToast } from 'primevue'
    import echo from '@/services/echo'
    import ChatBubble from './ChatBubble.vue'

    defineProps<{
        isOpen: boolean
    }>()

    // USER DATA
    const { authStore } = useFetchAuthenticatedUser()

    // MODAL EMITS
    const emit = defineEmits(['close'])
    const handleCloseModal = () => emit('close')

    const queryClient = useQueryClient()

    // Local state
    const messageContent = ref('')
    const attachment = ref<File | null>(null)
    const attachmentPreview = ref<string | null>(null)
    const toast = useToast()

    // USE QUERY COVERSATION
    const conversationQuery = useQuery({
        queryKey: ['user_conversation', authStore.currentUser?.id],
        queryFn: async () => {
            if (!authStore.currentUser?.id) return null
            return await getConversation(authStore.currentUser.id)
        },
        enabled: !!authStore.currentUser?.id,
    })

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

        const formData = new FormData()
        formData.append('content', messageContent.value)

        if (authStore.currentUser?.id) {
            formData.append('user_id', authStore.currentUser?.id.toString())
        }

        if (attachment.value) {
            formData.append('attachment', attachment.value)
        }

        sendMessageMutation.mutate(formData)
    }

    // WATCH FOR NEW CHAT EVENT
    watch(
        () => authStore.currentUser?.id,
        (newUserId) => {
            if (newUserId) {
                const channel = echo.channel(`chat.${newUserId}`)

                // Debug channel subscription
                channel.subscribed(() => {
                    console.log('✅ Subscribed to channel: chat.' + newUserId)
                })

                // eslint-disable-next-line @typescript-eslint/no-explicit-any
                channel.listen('.message.sent', (event: any) => {
                    console.log('📨 Event data:', event.message)
                    const eventMessage = event.message

                    // 1. Optimistically update Vue Query cache
                    queryClient.setQueryData(
                        ['user_conversation', eventMessage.conversation.user_id],
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
                        queryKey: ['user_conversation', eventMessage.conversation.user_id],
                    })
                })
            }
        },
        { immediate: true },
    )
</script>

<template>
    <TransitionRoot appear :show="isOpen" as="template">
        <Dialog as="div" @close="handleCloseModal" class="relative z-[999]">
            <TransitionChild
                as="template"
                enter="duration-300 ease-out"
                enter-from="opacity-0"
                enter-to="opacity-100"
                leave="duration-200 ease-in"
                leave-from="opacity-100"
                leave-to="opacity-0"
            >
                <div class="fixed inset-0 bg-black/25" />
            </TransitionChild>
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <DialogPanel class="w-1/2 h-[85vh] mb-12 bg-gray-100 border border-gray-400 rounded-md flex">
                    <!-- Chat messages -->
                    <div class="relative flex-1">
                        <!-- Chat Header -->
                        <div class="bg-gray-900 flex items-center justify-between font-medium h-18 pl-5">
                            <div class="flex items-center">
                                <img src="/user_icon-removebg.png" class="h-12 mr-3" alt="Flowbite Logo" />

                                <div class="flex flex-col text-sm text-white">
                                    <h1>Jarvis Designs</h1>
                                    <h1 class="text-gray-400">jarvisdesigns@gmail.com</h1>
                                </div>
                            </div>
                            <XMarkIcon
                                class="text-white size-8 mr-3 hover:cursor-pointer hover:opacity-75"
                                @click="handleCloseModal"
                            />
                        </div>

                        <div class="flex-1 font-medium h-[75%] pt-5 px-5 overflow-y-auto space-y-4 pb-8">
                            <!-- Loop messages -->
                            <template v-if="conversationQuery.data.value?.messages?.length">
                                <ChatBubble
                                    :messages="conversationQuery.data.value?.messages"
                                    :conversationUserID="authStore.user?.id"
                                    queryKey="user_conversation"
                                />
                            </template>

                            <!-- Empty state -->
                            <div v-else class="text-center text-gray-400">No messages yet.</div>
                        </div>

                        <!-- Message input -->
                        <div
                            class="absolute bottom-0 left-0 w-full flex items-center gap-2 p-4 bg-gray-50 border-t border-gray-300"
                        >
                            <!-- File preview before sending -->
                            <div v-if="attachment" class="flex items-center gap-2">
                                <div v-if="attachmentPreview" class="relative">
                                    <!-- Image preview -->
                                    <img :src="attachmentPreview" class="h-16 w-16 object-cover rounded-md border" />

                                    <!-- X button inside image -->
                                    <button
                                        @click="clearAttachment"
                                        class="absolute top-1 right-1 bg-black/40 backdrop-blur-sm rounded-full p-0.5 text-white hover:bg-black/60"
                                    >
                                        <XMarkIcon class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>

                            <label
                                v-if="!attachment && !attachmentPreview"
                                for="file-upload"
                                class="cursor-pointer hover:opacity-75 bg-blue-500 rounded-md p-2"
                            >
                                <ArrowUpOnSquareIcon class="size-5 text-white" />
                                <input id="file-upload" type="file" class="hidden" @change="handleFileChange" />
                            </label>

                            <input
                                type="text"
                                v-model="messageContent"
                                @keyup.enter="handleSendMessage"
                                placeholder="Type your message..."
                                class="flex-1 font-medium px-4 py-2 border border-gray-300 rounded-full focus:outline-none focus:ring-1 focus:ring-blue-500"
                            />

                            <button
                                class="p-2 rounded-full hover:cursor-pointer bg-blue-500 hover:bg-blue-600 text-white"
                                @click="handleSendMessage"
                                :disabled="sendMessageMutation.isPending.value"
                                :class="{
                                    'opacity-50 cursor-not-allowed': sendMessageMutation.isPending.value,
                                }"
                            >
                                <ChevronDoubleRightIcon class="size-5" />
                            </button>
                        </div>
                    </div>
                </DialogPanel>
            </div>
        </Dialog>
    </TransitionRoot>

    <Toast />
</template>
