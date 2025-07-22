<script lang="ts" setup>
    import { ref, onMounted } from 'vue'
    import { useRoute } from 'vue-router'

    const route = useRoute()
    const email = ref('')

    onMounted(() => {
        const emailParam = route.query.email
        // Ensure it's a string
        if (typeof emailParam === 'string') {
            email.value = emailParam
        } else if (Array.isArray(emailParam)) {
            email.value = emailParam[0] || 'your email'
        } else {
            email.value = 'your email'
        }
    })
</script>

<template>
    <div class="min-h-[80vh] flex items-center justify-center bg-gray-50 px-4">
        <div class="bg-white p-6 rounded-xl shadow-md text-center max-w-md w-full">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">📧 Check Your Email</h2>
            <p class="text-gray-600 mb-2">
                Hello
                <span class="font-medium text-indigo-600">{{ email }}</span>
                ,
            </p>
            <p class="text-gray-600">
                We've sent a verification link to your email. Please check your inbox and click the
                link to verify your account.
            </p>
        </div>
    </div>
</template>
