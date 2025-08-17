<script lang="ts" setup>
    import { getConversation } from '@/api/get/message'
    import { sendChatMessageApi } from '@/api/post/message'
    import { useFetchAuthenticatedUser } from '@/composables/useFetchAuthenticatedUser'
    import { TransitionRoot, TransitionChild, Dialog, DialogPanel } from '@headlessui/vue'
    import { ArrowUpOnSquareIcon, ChevronDoubleRightIcon, XMarkIcon } from '@heroicons/vue/20/solid'
    import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query'
    import { ref } from 'vue'
    import Toast from 'primevue/toast'
    import { useToast } from 'primevue'

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
        queryKey: ['conversation', authStore.currentUser?.id],
        queryFn: async () => {
            if (!authStore.currentUser?.id) return null
            return await getConversation(authStore.currentUser.id)
        },
        enabled: !!authStore.currentUser?.id,
    })

    // SEND MESSAGE MUTATION
    const sendMessageMutation = useMutation({
        mutationFn: sendChatMessageApi,
        onSuccess: () => {
            // ✅ Refresh messages query (if you have a messages query keyed by conversation)
            queryClient.invalidateQueries({ queryKey: ['conversation', 'all_conversation'] })

            // reset form
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
                <DialogPanel
                    class="w-1/2 h-[85vh] mb-12 bg-gray-100 border border-gray-400 rounded-md flex"
                >
                    <!-- Chat messages -->
                    <div class="relative flex-1">
                        <!-- Chat Header -->
                        <div
                            class="bg-gray-900 flex items-center justify-between font-medium h-18 pl-5"
                        >
                            <div class="flex items-center">
                                <img
                                    src="/user_icon-removebg.png"
                                    class="h-12 mr-3"
                                    alt="Flowbite Logo"
                                />

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

                        <div
                            class="flex-1 font-medium h-[75%] pt-5 px-5 overflow-y-auto space-y-4 pb-8"
                        >
                            <!-- Loop messages -->
                            <template v-if="conversationQuery.data.value?.messages?.length">
                                <div
                                    v-for="msg in conversationQuery.data.value.messages"
                                    :key="msg.id"
                                    :class="{
                                        'flex justify-end':
                                            msg.sender_id === authStore.currentUser?.id,
                                        'flex justify-start':
                                            msg.sender_id !== authStore.currentUser?.id,
                                    }"
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
                </DialogPanel>
            </div>
        </Dialog>
    </TransitionRoot>

    <Toast />
</template>
