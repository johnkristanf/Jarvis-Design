<script lang="ts" setup>
    import { forgotPassword } from '@/api/post/forgot-password'
    import Loader from '@/components/Loader.vue'
    import { useMutation } from '@tanstack/vue-query'
    import { useForm, useField } from 'vee-validate'
    import { ref } from 'vue'
    import * as yup from 'yup'
    import Toast from 'primevue/toast'
    import { useToast } from 'primevue'

    const isLoadingMutation = ref(false)
    const toast = useToast()

    const validationSchema = yup.object({
        email: yup.string().email('Invalid email address').required('Email is required'),
    })

    const { handleSubmit, isSubmitting } = useForm({
        validationSchema,
    })

    const { value: email, errorMessage: emailError } = useField('email')

    const mutation = useMutation({
        mutationFn: ({ email }: { email: string }) => forgotPassword(email),
        onSuccess: () => {
            isLoadingMutation.value = false
            toast.add({
                severity: 'success',
                summary: 'Check your email',
                detail: 'If an account exists with that email, we have sent a password reset link.',
                life: 5000,
            })
            email.value = ''
        },
        onError: (error: any) => {
            isLoadingMutation.value = false
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: error?.message || 'Something went wrong. Please try again.',
                life: 3000,
            })
        },
        onMutate: () => {
            isLoadingMutation.value = true
        },
    })

    const onSubmit = handleSubmit(async (values) => {
        mutation.mutate({ email: values.email })
    })
</script>

<template>
    <div class="flex flex-col pt-10 px-6 lg:px-8 h-[80vh] bg-white dark:bg-gray-900 transition-colors duration-200">
        <div class="sm:mx-auto sm:w-full sm:max-w-sm">
            <img class="mx-auto w-[20%] rounded-full" src="/jarvis-logo-white.jpeg" alt="Jarvis Designs" />
            <h2 class="mt-3 text-center text-2xl/9 font-bold tracking-tight text-gray-900 dark:text-gray-100">
                Forgot your password?
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
                Enter your email address and we'll send you a link to reset your password.
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-sm">
            <form class="space-y-6" @submit="onSubmit" method="POST">
                <div>
                    <label for="email" class="block text-sm/6 font-medium text-gray-900 dark:text-gray-100">Email address</label>
                    <div class="mt-2">
                        <input
                            type="email"
                            id="email"
                            v-model="email"
                            autocomplete="email"
                            placeholder="you@example.com"
                            class="font-medium block w-full rounded-md bg-white dark:bg-gray-800 px-3 py-1.5 text-base text-black dark:text-gray-100 outline-1 -outline-offset-1 outline-gray-300 dark:outline-gray-700 placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:outline-2 focus:-outline-offset-2 focus:outline-none sm:text-sm/6 transition-colors duration-200"
                        />
                    </div>
                    <p v-if="emailError" class="mt-1 text-red-500 dark:text-red-400 text-sm">
                        {{ emailError }}
                    </p>
                </div>

                <div>
                    <button
                        type="submit"
                        :disabled="isSubmitting"
                        class="flex w-full justify-center rounded-md bg-black dark:bg-blue-600 px-3 py-1.5 text-sm/6 font-semibold text-white shadow-xs hover:opacity-75 hover:cursor-pointer focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-none transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Send reset link
                    </button>
                </div>
            </form>
        </div>

        <div class="mt-6 sm:mx-auto sm:w-full sm:max-w-sm text-center">
            <router-link
                to="/auth/login"
                class="text-sm text-blue-600 dark:text-blue-400 underline hover:text-blue-800 dark:hover:text-blue-300"
            >
                Back to login
            </router-link>
        </div>
    </div>

    <div v-if="isLoadingMutation">
        <Loader msg="Sending reset link..." />
    </div>

    <Toast />
</template>
