<script lang="ts" setup>
defineOptions({
    inheritAttrs: false
})

const props = defineProps<{
    modelValue?: string | number | null
    label?: string
    type?: string
    required?: boolean
    error?: string
    placeholder?: string
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: string | number | null): void
}>()

const handleInput = (event: Event) => {
    const target = event.target as HTMLInputElement
    let value: string | number | null = target.value
    
    // Automatically convert to number if type is number and value is not empty
    if (props.type === 'number' && value !== '') {
        value = Number(value)
    }
    
    emit('update:modelValue', value)
}
</script>

<template>
    <div>
        <label v-if="label" class="block text-sm mb-3">
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </label>
        <input
            v-bind="$attrs"
            :value="modelValue"
            @input="handleInput"
            :type="type || 'text'"
            :placeholder="placeholder"
            class="font-medium w-full border border-gray-300 dark:border-gray-600 p-2 rounded bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 transition-colors"
        />
        <p v-if="error" class="text-sm text-red-500 mt-1">{{ error }}</p>
    </div>
</template>
