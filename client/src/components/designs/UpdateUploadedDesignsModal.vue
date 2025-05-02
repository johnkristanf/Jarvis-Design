<script setup lang="ts">
    import { DesignStatus, type UploadedDesign } from '@/types/design'
    import { ref } from 'vue'

    import {
        TransitionRoot,
        TransitionChild,
        Dialog,
        DialogPanel,
        DialogTitle,
    } from '@headlessui/vue'

    import { useMutation, useQueryClient } from '@tanstack/vue-query'
    import { updateUploadedDesign } from '@/api/put/design'
    import Loader from '../Loader.vue'
    import ListSelectBox from '../ListSelectBox.vue'
    import { InformationCircleIcon } from '@heroicons/vue/20/solid'

    const props = defineProps<{
        design: UploadedDesign
    }>()

    const emit = defineEmits(['close'])
    const handleClose = () => emit('close')
    const price = ref(props.design.price)
    const isUpdatingDesigns = ref<boolean>(false)

    const statuses = ref([
        { name: DesignStatus.PENDING },
        { name: DesignStatus.ACKNOWLEDGE },
        { name: DesignStatus.TAGGED },
    ])

    const selectedStatus = ref(
        statuses.value.find((status) => status.name === props.design.status) || statuses.value[0],
    )

    const queryClient = useQueryClient()

    console.log('Selected Design: ', props.design)

    const mutation = useMutation({
        mutationFn: updateUploadedDesign,
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['uploaded-designs'] })
            handleClose()
            isUpdatingDesigns.value = false
        },

        onError: (error) => {
            console.error('Mutation error:', error)
        },
    })

    // Submit handler
    const handleSubmit = () => {
        console.log('status: ', selectedStatus.value.name)
        console.log('price: ', price.value)

        isUpdatingDesigns.value = true

        mutation.mutate({
            status: selectedStatus.value.name,
            price: price.value,
            design_id: props.design.id,
        })
    }

    const priceNote = `
            Note: <br>When setting the original price for your uploaded design, please price it as if it’s for one (1) order only.
            <br>  <br>  Don’t multiply the price based on quantity.
            <br> <br>  When a customer places an order, the system will automatically calculate the total price based on how many units they request.
        `
</script>

<template>
    <TransitionRoot appear :show="true">
        <Dialog as="div" @close="handleClose" class="relative z-[999]">
            <TransitionChild
                enter="duration-300 ease-out"
                enter-from="opacity-0"
                enter-to="opacity-100"
                leave="duration-200 ease-in"
                leave-from="opacity-100"
                leave-to="opacity-0"
            ></TransitionChild>

            <div class="fixed inset-0 overflow-y-auto bg-gray-900/80">
                <div class="flex min-h-full items-center justify-center p-4 text-center">
                    <TransitionChild
                        enter="duration-300 ease-out"
                        enter-from="opacity-0 scale-95"
                        enter-to="opacity-100 scale-100"
                        leave="duration-200 ease-in"
                        leave-from="opacity-100 scale-100"
                        leave-to="opacity-0 scale-95"
                    >
                        <DialogPanel
                            class="w-full h-[20rem] max-w-md transform overflow-hidden bg-white p-6 text-left align-middle shadow-xl transition-all"
                        >
                            <DialogTitle as="h3" class="text-lg leading-6 text-gray-900">
                                Update Customer Uploaded Design
                            </DialogTitle>

                            <form @submit.prevent="handleSubmit" class="max-w-sm mx-auto">
                                <!-- DESIGN STATUS SELECT ELEMENT -->

                                <div class="mt-8 w-full">
                                    <h1 class="font-medium">Pricing Status</h1>

                                    <ListSelectBox
                                        v-model="selectedStatus"
                                        :options="statuses"
                                        displayKey="name"
                                    />
                                </div>

                                <!-- END OF DESIGN STATUS SELECT ELEMENT -->

                                <div class="mt-8">
                                    <div class="flex mb-2 items-center gap-1">
                                        <label
                                            for="price"
                                            class="block text-sm font-medium text-gray-900 dark:text-white"
                                        >
                                            Original Price (₱)
                                        </label>

                                        <!-- PRICE TOOL TIP -->
                                        <span
                                            v-tooltip.right="{
                                                value: priceNote,
                                                escape: false,  // <-- This is the key to render HTML like <br>
                                                pt: {
                                                    root: {},

                                                    text: {
                                                        style: {
                                                            padding: '20px',
                                                            whiteSpace: 'normal',
                                                        },
                                                        class: '!bg-primary !text-white !font-medium text-md w-[500px]',
                                                    },
                                                },
                                            }"
                                            class="cursor-pointer text-gray-500 hover:text-primary transition-colors"
                                        >
                                            <InformationCircleIcon class="w-5 h-5" />
                                        </span>
                                    </div>

                                    <input
                                        type="text"
                                        id="price"
                                        v-model="price"
                                        class="font-medium shadow-xs bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500 dark:shadow-xs-light"
                                        required
                                    />
                                </div>

                                <button
                                    type="submit"
                                    class="mt-4 text-white bg-gray-900 hover:opacity-75 hover:cursor-pointer focus:ring-4 hover:cursor-pointer focus:outline-none focus:ring-blue-300 rounded-md text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800"
                                >
                                    Submit
                                </button>
                            </form>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>

    <!-- LOADER -->
    <div v-if="isUpdatingDesigns">
        <Loader msg="Updating Customer Uploaded Designs..." />
    </div>
</template>
