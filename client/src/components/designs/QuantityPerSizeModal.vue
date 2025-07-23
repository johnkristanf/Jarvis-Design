<script lang="ts" setup>
    import { Dialog, DialogPanel } from '@headlessui/vue'

    const props = defineProps<{
        selectedOrderSizes: any
    }>()

    const emit = defineEmits(['close'])
    const handleCloseModal = () => emit('close')
</script>

<template>
    <Dialog
        :open="true"
        @close="handleCloseModal"
        class="fixed inset-0 z-[999] flex items-center justify-center bg-black/50"
    >
        <DialogPanel
            class="bg-white h-[400px] overflow-y-auto rounded-2xl shadow-xl w-full max-w-md p-6 space-y-6"
        >
            <h2 class="text-lg font-semibold mb-4">Quantity Per Size</h2>
            <ul class="space-y-2">
                <li v-for="size in props.selectedOrderSizes" :key="size.id" class="flex justify-between bg-gray-500 text-white p-3 rounded-md">
                    <span>{{ size.name }}</span>
                    <span class="font-medium">{{ size.pivot.quantity }}</span>
                </li>
            </ul>
            <div class="text-right mt-4">
                <button
                    @click="handleCloseModal"
                    class="w-full bg-gray-800 text-white px-3 py-1 rounded hover:bg-gray-700 hover:cursor-pointer"
                >
                    Close
                </button>
            </div>
        </DialogPanel>
    </Dialog>
</template>
