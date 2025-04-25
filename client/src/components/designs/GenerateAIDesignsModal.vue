<script lang="ts" setup>
    import { generateImageDesign } from '@/api/post/generate'
    import { useMutation } from '@tanstack/vue-query'
    import { Select, Toast } from 'primevue'
    import { useField, useForm } from 'vee-validate'
    import Loader from '../Loader.vue'
    import { ref } from 'vue'
    import type { DesignGenerate } from '@/types/design'
    import { TransitionRoot, TransitionChild, Dialog, DialogPanel } from '@headlessui/vue'
    import { ArrowDownTrayIcon, XMarkIcon } from '@heroicons/vue/20/solid'
    import { useToast } from 'primevue/usetoast'
    import ListSelectBox from '../ListSelectBox.vue'

    defineProps<{
        isOpen: boolean
        onClose: () => void
    }>()

    const emit = defineEmits(['close'])
    const handleCloseModal = () => emit('close')

    const aiAPIURL = import.meta.env.VITE_AI_API_URL
    const isLoadingMutation = ref(false)
    const loaderMsg = ref<string>('')
    const imageUrls = ref([])

    const handleGenerateAnother = () => (imageUrls.value.length = 0)

    const { handleSubmit, handleReset } = useForm()
    const toast = useToast()

    const generateImageMutation = useMutation({
        mutationFn: generateImageDesign,
        onSuccess: (response) => {
            isLoadingMutation.value = false
            console.log('response: ', response)

            if (response && response.data.image_urls) {
                imageUrls.value = response.data.image_urls
                handleReset()
            }
        },

        onError: (error) => {
            isLoadingMutation.value = false
            console.error('Error generating image:', error)
        },

        onMutate: () => {
            loaderMsg.value = 'Generating Images...'
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

    const { value: prompt } = useField<string>('prompt')
    const { value: style_preference } = useField<string>('style_preference')

    const onImageGenerate = handleSubmit((values) => {
        if (!values.prompt || !values.style_preference) {
            toast.add({
                severity: 'warn',
                summary: 'Missing Fields',
                detail: 'Please enter both a prompt and a style preference.',
                life: 3000,
            })
            return
        }

        const designGengerateData: DesignGenerate = {
            prompt: values.prompt,
            style_preference: values.style_preference.name,
        }

        generateImageMutation.mutate(designGengerateData)
    })
</script>

<template>
    <TransitionRoot as="template" :show="isOpen">
        <Dialog as="div" class="relative z-10" @close="handleCloseModal">
            <TransitionChild
                as="template"
                enter="ease-out duration-300"
                enter-from="opacity-0"
                enter-to="opacity-100"
                leave="ease-in duration-200"
                leave-from="opacity-100"
                leave-to="opacity-0"
            >
                <div class="fixed inset-0 bg-gray-500/75 transition-opacity" />
            </TransitionChild>

            <div class="fixed inset-0 z-10 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4 text-center">
                    <TransitionChild
                        as="template"
                        enter="ease-out duration-300"
                        enter-from="opacity-0 scale-95"
                        enter-to="opacity-100 scale-100"
                        leave="ease-in duration-200"
                        leave-from="opacity-100 scale-100"
                        leave-to="opacity-0 scale-95"
                    >
                        <DialogPanel
                            class="w-full h-[500px] max-w-4xl transform overflow-y-auto bg-white p-8 text-left align-middle shadow-xl transition-all"
                        >
                            <button
                                type="button"
                                class="absolute top-4 right-4 text-gray-400 hover:text-gray-500"
                                @click="handleCloseModal"
                            >
                                <span class="sr-only">Close</span>
                                <XMarkIcon class="size-6" aria-hidden="true" />
                            </button>

                            <div class="flex items-center justify-between my-4">
                                <h2 class="text-2xl font-bold text-gray-900 mb-6">
                                    Prompt your Desired AI Design
                                </h2>
                                <button
                                    v-if="imageUrls && imageUrls.length > 0"
                                    @click="handleGenerateAnother"
                                    class="font-bold bg-gray-900 text-white rounded-md p-2 text-gray-900 mr-3 hover:cursor-pointer hover:opacity-75"
                                >
                                    Generate another
                                </button>
                            </div>

                            <form
                                v-if="imageUrls.length == 0"
                                @submit.prevent="onImageGenerate"
                                class="flex flex-col gap-7 w-full mb-8"
                            >
                                <div class="flex flex-col gap-2">
                                    <h1>Prompt:</h1>

                                    <textarea
                                        type="text"
                                        id="prompt"
                                        v-model="prompt"
                                        placeholder="Sample Prompt: High-end basketball jersey design, front and back view, vibrant team colors, modern athletic cut, dynamic geometric patterns, sleek logo placement, professional sportswear aesthetic"
                                        class="font-medium block w-full rounded-md bg-white px-3 text-base text-black placeholder:text-gray-400 focus:outline-none border border-gray-300"
                                    ></textarea>
                                </div>

                                <div class="flex flex-col gap-2">
                                    <h1>Style Preference:</h1>

                                    <ListSelectBox
                                        v-model="style_preference"
                                        :options="preferences"
                                        displayKey="name"
                                    />
                                </div>

                                <button
                                    type="submit"
                                    class="px-4 py-2 rounded-md bg-black text-white hover:cursor-pointer hover:opacity-75"
                                >
                                    Generate
                                </button>
                            </form>

                            <div
                                v-if="imageUrls && imageUrls.length > 0"
                                class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6"
                            >
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
                                        <ArrowDownTrayIcon
                                            class="w-5 h-5 text-gray-700 hover:text-black"
                                        />
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
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>

    <div v-if="isLoadingMutation">
        <Loader :msg="loaderMsg" />
    </div>

    <Toast />
</template>
