<script lang="ts" setup>
    import { generateImageDesign } from '@/api/post/generate'
    import { useMutation } from '@tanstack/vue-query'
    import { useField, useForm } from 'vee-validate'
    import Loader from '../Loader.vue'
    import { ref } from 'vue'
    import type { DesignGenerate } from '@/types/design'
    import { ArrowDownTrayIcon, XMarkIcon } from '@heroicons/vue/20/solid'
    import { useToast } from 'primevue/usetoast'
    import ListSelectBox from '../ListSelectBox.vue'
    import { deductPromptLimit } from '@/api/put/user'
    import { useFetchAuthenticatedUser } from '@/composables/useFetchAuthenticatedUser'

    const emit = defineEmits(['close'])
    const handleCloseModal = () => emit('close')

    const aiAPIURL = import.meta.env.VITE_AI_API_URL
    const isLoadingMutation = ref(false)
    const loaderMsg = ref<string>('')
    const imageUrls = ref([])

    const { authStore, refetchUser } = useFetchAuthenticatedUser()
    const { handleSubmit } = useForm()
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

    // const { value: prompt } = useField<string>('prompt')
    // const { value: style_preference } = useField<string>('style_preference')

    const prompt = ref(null)
    const style_preference = ref(null)

    const onImageGenerate = () => {
        console.log('NING SUBMIT MAN')

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
</script>

<template>
    <div class="w-[60%] h-[480px] transform overflow-y-auto bg-white p-8 text-left">
        <div class="flex items-center justify-between my-4">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Prompt your Desired AI Design</h2>
        </div>

        <form class="flex flex-col gap-7 w-full mb-8">
            <div class="flex flex-col gap-2">
                <div class="flex justify-between items-center gap-2">
                    <div class="flex items-center justify-between w-full">
                        <h1>Prompt:</h1>
                        <h1 class="text-gray-500 text-sm">
                            Daily Prompt Limit:
                            {{ authStore.currentUser?.prompt_limit }}
                        </h1>
                    </div>
                </div>

                <textarea
                    type="text"
                    id="prompt"
                    v-model="prompt"
                    placeholder="Mock up: Volleyball Jersey 
Design: Curve Uniques Lines
Color: Black and White"
                    class="font-medium block w-full rounded-md bg-white px-3 text-base text-black placeholder:text-gray-400 focus:outline-none border border-gray-300"
                ></textarea>

                <div class="flex flex-col gap-2">
                    <h1>Style Preference:</h1>
                    <select
                        v-model="style_preference"
                        class="font-medium block w-full rounded-md bg-white px-3 text-base text-black border border-gray-300 focus:outline-none"
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
                    class="px-4 py-2 rounded-md bg-gray-900 text-white hover:cursor-pointer hover:opacity-75"
                >
                    Generate
                </button>

                <button
                    type="button"
                    @click="handleCloseModal"
                    class="px-4 py-2 rounded-md bg-gray-700 text-white hover:cursor-pointer hover:opacity-75"
                >
                    Close
                </button>
            </div>
        </form>

        <!-- LIST OF AI GENERATED DESIGNS -->
        <div v-if="imageUrls && imageUrls.length > 0" class="mt-5">
            <h1 class="mb-3">Generated AI Images:</h1>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="(imageUrl, index) in imageUrls"
                    :key="'generated-' + index"
                    class="group relative overflow-hidden rounded-md"
                >
                    <!-- Download Button -->
                    <a
                        :href="`${aiAPIURL}/download/image/${imageUrl}`"
                        download
                        class="absolute top-2 right-2 z-10 p-1 bg-white/80 hover:bg-white rounded-full shadow-md transition"
                        target="_blank"
                    >
                        <ArrowDownTrayIcon class="w-5 h-5 text-gray-700 hover:text-black" />
                    </a>

                    <!-- Image -->
                    <img
                        :src="`${aiAPIURL}/generated/image/${imageUrl}`"
                        class="aspect-square w-full rounded-md bg-gray-200 object-cover group-hover:opacity-75 transition"
                    />

                    <!-- Caption -->
                    <h3 class="mt-2 text-sm text-center text-gray-700 font-medium">
                        Generated Design {{ index + 1 }}
                    </h3>
                </div>
            </div>
        </div>
    </div>

    <Loader v-if="isLoadingMutation" :msg="loaderMsg" />
</template>
