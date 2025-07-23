<template>
    <div class="min-h-[80vh] flex items-center justify-center bg-gray-50 px-4">
        <div class="bg-white p-6 rounded-xl shadow-md text-center max-w-md w-full">
            <h2 class="text-2xl font-semibold text-green-600 mb-4">✅ Email Verified</h2>
            <p class="text-gray-700 mb-6">
                Your email has been successfully verified. You can now log in to your account.
            </p>
            <button
                @click="goToLogin"
                class="px-6 py-2 bg-gray-900 text-white rounded-md font-medium hover:bg-gray-800 transition"
            >
                Proceed to Login
            </button>
        </div>
    </div>
</template>

<script lang="ts" setup>
    function goToLogin() {
        window.location.href = '/auth/login';
    }
</script>
