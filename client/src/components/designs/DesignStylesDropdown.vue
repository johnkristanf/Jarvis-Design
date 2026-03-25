<script lang="ts" setup>
    import { ref, computed } from 'vue'
    import type { ProductStyle } from '@/types/design'

    const props = defineProps<{
        productStyles: ProductStyle[]
        isDark: boolean
        modelValue: number[]
    }>()

    const emit = defineEmits(['update:modelValue'])

    const showStylesDropdown = ref(false)

    const groupedStyles = computed(() => {
        const groups: Record<string, ProductStyle[]> = {}
        props.productStyles.forEach((st) => {
            const key = st.panel || 'Styles'
            if (!groups[key]) groups[key] = []
            groups[key].push(st)
        })
        return groups
    })

    const selectedStyleIds = computed({
        get: () => props.modelValue,
        set: (value) => emit('update:modelValue', value),
    })
</script>

<template>
    <div v-if="productStyles && productStyles.length > 0">
        <label :class="['block text-sm mb-1', isDark ? 'text-gray-400' : 'text-gray-600']">
            Product Styles:
        </label>
        <div
            class="border rounded-md overflow-hidden relative"
            :class="isDark ? 'border-zinc-700' : 'border-gray-300'"
        >
            <button
                type="button"
                @click="showStylesDropdown = !showStylesDropdown"
                class="w-full flex justify-between items-center text-left px-4 py-2 font-medium text-sm transition-colors"
                :class="
                    isDark
                        ? 'bg-zinc-800 text-gray-300 hover:bg-zinc-700'
                        : 'bg-gray-50 text-gray-700 hover:bg-gray-100'
                "
            >
                <span>Select Styles ({{ selectedStyleIds.length }} selected)</span>
                <svg
                    :class="{ 'rotate-180': showStylesDropdown }"
                    class="w-5 h-5 transition-transform"
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                >
                    <path
                        fill-rule="evenodd"
                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                        clip-rule="evenodd"
                    />
                </svg>
            </button>
            <div
                v-show="showStylesDropdown"
                class="p-3 border-t space-y-4"
                :class="isDark ? 'bg-zinc-900 border-zinc-700' : 'bg-white border-gray-300'"
            >
                <div v-for="(styles, panelName) in groupedStyles" :key="panelName" class="mb-3">
                    <h3
                        class="text-base font-bold mb-2 uppercase tracking-wide"
                        :class="isDark ? 'text-gray-100' : 'text-gray-900'"
                    >
                        {{ panelName }}
                    </h3>
                    <div class="space-y-2">
                        <label
                            v-for="styleItem in styles"
                            :key="styleItem.id"
                            class="flex items-start gap-3 cursor-pointer p-2 rounded-md transition-colors"
                            :class="isDark ? 'hover:bg-zinc-800' : 'hover:bg-gray-50'"
                        >
                            <input
                                type="checkbox"
                                :value="styleItem.id"
                                v-model="selectedStyleIds"
                                class="mt-1 rounded text-blue-600 focus:ring-blue-500"
                                :class="
                                    isDark
                                        ? 'bg-zinc-700 border-zinc-600'
                                        : 'bg-gray-100 border-gray-300'
                                "
                            />
                            <div class="flex flex-col">
                                <span
                                    class="text-sm font-medium"
                                    :class="isDark ? 'text-gray-200' : 'text-gray-800'"
                                >
                                    {{ styleItem.name }}
                                </span>
                                <span
                                    v-if="styleItem.attributes"
                                    class="text-xs italic font-mono mt-0.5"
                                    :class="isDark ? 'text-gray-400' : 'text-gray-500'"
                                >
                                    {{ styleItem.attributes }}
                                </span>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
