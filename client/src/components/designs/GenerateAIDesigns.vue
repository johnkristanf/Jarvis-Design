<script lang="ts" setup>
    import { generateImageDesign } from '@/api/post/generate'
    import { useMutation } from '@tanstack/vue-query'
    import Loader from '../Loader.vue'
    import { ref } from 'vue'
    import type { DesignGenerate } from '@/types/design'
    import { ArrowDownTrayIcon, InformationCircleIcon } from '@heroicons/vue/20/solid'
    import { useToast } from 'primevue/usetoast'
    import { deductPromptLimit } from '@/api/put/user'
    import { useFetchAuthenticatedUser } from '@/composables/useFetchAuthenticatedUser'

    const emit = defineEmits(['close'])
    const handleCloseModal = () => emit('close')

    const aiAPIURL = import.meta.env.VITE_AI_API_URL
    console.log('aiAPIURL: ', aiAPIURL)

    const isLoadingMutation = ref(false)
    const loaderMsg = ref<string>('')
    const imageUrls = ref([])

    const { authStore, refetchUser } = useFetchAuthenticatedUser()
    const toast = useToast()

    const generateImageMutation = useMutation({
        mutationFn: generateImageDesign,
        onSuccess: async (response) => {
            isLoadingMutation.value = false
            console.log('response: ', response)

            if (response && response.data.image_urls) {
                imageUrls.value = response.data.image_urls

                toast.add({
                    severity: 'success',
                    summary: 'AI Design Generated Successfully',
                    detail: 'Scroll down to look up for the designs',
                    life: 3000,
                })

                await deductPromptLimit()
                await refetchUser()
            }
        },

        onError: (error) => {
            isLoadingMutation.value = false
            console.error('Error generating image:', error)

            toast.add({
                severity: 'error',
                summary: 'AI Design Generation Failed',
                detail: 'Please try again',
                life: 3000,
            })
        },

        onMutate: () => {
            loaderMsg.value = 'Generating image take a while to finish, please wait...'
            isLoadingMutation.value = true
        },
    })

    const preferences = ref([
        { name: 'realistic' },
        { name: 'cartoon' },
        { name: 'anime' },
        { name: 'painting' },
        { name: 'sketch' },
    ])

    const prompt = ref(null)
    const style_preference = ref(null)

    const onImageGenerate = () => {
        if (!prompt.value || !style_preference.value) {
            toast.add({
                severity: 'warn',
                summary: 'Missing Fields',
                detail: 'Please enter both a prompt and a style preference.',
                life: 3000,
            })
            return
        }

        const promptLimit = authStore.currentUser?.prompt_limit ?? 0

        if (promptLimit <= 0) {
            toast.add({
                severity: 'warn',
                summary: 'Daily Limit Reached',
                detail: 'You have reached your AI prompt limit for today. Please try again tomorrow generating designs.',
                life: 4000,
            })
            return
        }

        const designGengerateData: DesignGenerate = {
            prompt: prompt.value,
        }

        generateImageMutation.mutate(designGengerateData)
    }

    const downloadImage = async (imageUrl: string, index: number) => {
        try {
            const response = await fetch(`${aiAPIURL}/download/image/${imageUrl}`)

            if (!response.ok) {
                throw new Error('Failed to download image')
            }

            const blob = await response.blob()
            const blobUrl = window.URL.createObjectURL(blob)

            const link = document.createElement('a')
            link.href = blobUrl
            link.download = `generated-design-${index + 1}.png`
            document.body.appendChild(link)
            link.click()

            // Clean up
            document.body.removeChild(link)
            window.URL.revokeObjectURL(blobUrl)

            toast.add({
                severity: 'success',
                summary: 'Download Started',
                detail: 'Image download has started',
                life: 2000,
            })
        } catch (error) {
            console.error('Error downloading image:', error)
            toast.add({
                severity: 'error',
                summary: 'Download Failed',
                detail: 'Failed to download image. Please try again.',
                life: 3000,
            })
        }
    }
</script>

<template>
    <div class="transform bg-white dark:bg-gray-900 p-8 text-left transition-colors duration-200 min-h-screen">
        <div class="flex items-center justify-between my-4">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6 transition-colors duration-200">
                Prompt your Desired AI Design
            </h2>
        </div>

        <form class="flex flex-col gap-7 w-full mb-8">
            <div class="flex flex-col gap-2 w-full">
                <div class="flex justify-between items-center gap-2">
                    <div class="flex items-end justify-between w-full">
                        <h1 class="text-gray-900 dark:text-gray-100 transition-colors duration-200">Enter your Prompt:</h1>
                        <div class="flex flex-col items-center gap-2 w-[20%]">
                            <h1 class="text-gray-500 dark:text-gray-400 text-sm transition-colors duration-200">
                                Daily Prompt Limit:
                                {{ authStore.currentUser?.prompt_limit }}
                            </h1>
                            <div class="flex items-center gap-1">
                                <div class="relative group">
                                    <InformationCircleIcon
                                        class="size-5 text-gray-900 dark:text-gray-100 cursor-pointer transition-colors duration-200"
                                    />
                                    <div
                                        class="absolute right-6 top-1/2 z-20 w-60 -translate-y-1/2 rounded bg-gray-900 dark:bg-gray-700 px-3 py-2 text-xs text-white opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none"
                                        style="white-space: normal;"
                                    >
                                        For every generation, it's equivalent to 5 credits, which will add up to your overall payment on your next order.
                                    </div>
                                </div>
                                <h1 class="text-gray-500 dark:text-gray-400 text-sm mr-13 transition-colors duration-200">
                                    Prompt credit: ₱ {{ authStore.currentUser?.prompt_credit }}
                                </h1>
                            </div>
                        </div>
                    </div>
                </div>

                <textarea
                    type="text"
                    id="prompt"
                    v-model="prompt"
                    placeholder="Mock up: Volleyball Jersey
Design: Curve Uniques Lines
Color: Black and White"
                    class="font-medium block w-full rounded-md bg-white dark:bg-gray-800 px-3 text-base text-black dark:text-gray-100 placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:outline-none border border-gray-300 dark:border-gray-700 transition-colors duration-200"
                ></textarea>

                <div class="flex flex-col gap-2">
                    <h1 class="text-gray-900 dark:text-gray-100 transition-colors duration-200">Style Preference:</h1>
                    <select
                        v-model="style_preference"
                        class="font-medium block w-full rounded-md bg-white dark:bg-gray-800 px-3 text-base text-black dark:text-gray-100 border border-gray-300 dark:border-gray-700 focus:outline-none transition-colors duration-200"
                    >
                        <option :value="null" disabled selected>Select a style preference</option>
                        <option v-for="option in preferences" :key="option.name" :value="option">
                            {{ option.name }}
                        </option>
                    </select>
                </div>
            </div>

            <div class="flex flex-col gap-3">
                <button
                    type="button"
                    @click="onImageGenerate"
                    class="px-4 py-2 rounded-md bg-gray-900 dark:bg-blue-600 text-white hover:cursor-pointer hover:opacity-75 transition-colors duration-200"
                >
                    Generate
                </button>

                <button
                    type="button"
                    @click="handleCloseModal"
                    class="px-4 py-2 rounded-md bg-gray-700 dark:bg-gray-800 text-white hover:cursor-pointer hover:opacity-75 transition-colors duration-200"
                >
                    Close
                </button>
            </div>
        </form>

        <!-- LIST OF AI GENERATED DESIGNS -->
        <div class="mt-5">
            <div v-if="imageUrls && imageUrls.length > 0">
                <h1 class="mb-3 text-gray-900 dark:text-gray-100 transition-colors duration-200">Generated AI Images:</h1>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div
                        v-for="(imageUrl, index) in imageUrls"
                        :key="'generated-' + index"
                        class="group relative overflow-hidden rounded-md"
                    >
                        <!-- Download Button -->
                        <button
                            @click="downloadImage(imageUrl, index)"
                            class="absolute top-2 right-2 z-10 p-1 bg-white/80 dark:bg-gray-800/80 hover:bg-white dark:hover:bg-gray-700 rounded-full shadow-md transition cursor-pointer"
                            type="button"
                        >
                            <ArrowDownTrayIcon class="w-5 h-5 text-gray-700 dark:text-gray-100 hover:text-black dark:hover:text-gray-200 transition-colors duration-200" />
                        </button>

                        <!-- Image -->
                        <img
                            :src="`${aiAPIURL}/generated/image/${imageUrl}`"
                            class="aspect-square w-full rounded-md bg-gray-200 dark:bg-gray-700 object-cover group-hover:opacity-75 transition"
                        />

                        <!-- Caption -->
                        <h3 class="mt-2 text-sm text-center text-gray-700 dark:text-gray-100 font-medium transition-colors duration-200">
                            Generated Design {{ index + 1 }}
                        </h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <Loader v-if="isLoadingMutation" :msg="loaderMsg" />
</template>
