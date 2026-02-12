<script lang="ts" setup>
    import { resetPassword, type ResetPasswordCredentials } from '@/api/post/reset-password'
    import Loader from '@/components/Loader.vue'
    import { useMutation } from '@tanstack/vue-query'
    import { useForm, useField } from 'vee-validate'
    import { ref, onMounted } from 'vue'
    import { useRoute, useRouter } from 'vue-router'
    import * as yup from 'yup'
    import Toast from 'primevue/toast'
    import { useToast } from 'primevue'
    import { EyeIcon, EyeSlashIcon } from '@heroicons/vue/24/solid'

    const isLoadingMutation = ref(false)
    const showPassword = ref(false)
    const showConfirmPassword = ref(false)
    const toast = useToast()
    const route = useRoute()
    const router = useRouter()

    const token = ref('')
    const email = ref('')

    onMounted(() => {
        token.value = (route.query.token as string) || ''
        email.value = (route.query.email as string) || ''
        if (!token.value || !email.value) {
            toast.add({
                severity: 'warn',
                summary: 'Invalid link',
                detail: 'This password reset link is invalid or has expired.',
                life: 5000,
            })
        }
    })

    const validationSchema = yup.object({
        password: yup
            .string()
            .required('Password is required')
            .min(8, 'Password must be at least 8 characters'),
        password_confirmation: yup
            .string()
            .required('Confirm password is required')
            .oneOf([yup.ref('password')], 'Passwords must match'),
    })

    const { handleSubmit, isSubmitting } = useForm({
        validationSchema,
    })

    const { value: password, errorMessage: passwordError } = useField('password')
    const { value: passwordConfirmation, errorMessage: passwordConfirmationError } =
        useField('password_confirmation')

    const mutation = useMutation({
        mutationFn: (data: ResetPasswordCredentials) => resetPassword(data),
        onSuccess: () => {
            isLoadingMutation.value = false
            toast.add({
                severity: 'success',
                summary: 'Password reset',
                detail: 'Your password has been reset successfully. You can now log in.',
                life: 5000,
            })
            router.push('/auth/login')
        },
        onError: (error: any) => {
            isLoadingMutation.value = false
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: error?.message || 'Failed to reset password. The link may have expired.',
                life: 5000,
            })
        },
        onMutate: () => {
            isLoadingMutation.value = true
        },
    })

    const onSubmit = handleSubmit(async (values) => {
        if (!token.value || !email.value) {
            toast.add({
                severity: 'error',
                summary: 'Invalid link',
                detail: 'Please use the link from your email.',
                life: 3000,
            })
            return
        }

        mutation.mutate({
            email: email.value,
            token: token.value,
            password: values.password,
            password_confirmation: values.password_confirmation,
        })
    })
</script>

<template>
    <div class="flex flex-col pt-10 px-6 lg:px-8 h-[80vh] bg-white dark:bg-gray-900 transition-colors duration-200">
        <div class="sm:mx-auto sm:w-full sm:max-w-sm">
            <img class="mx-auto w-[20%] rounded-full" src="/jarvis-logo-white.jpeg" alt="Jarvis Designs" />
            <h2 class="mt-3 text-center text-2xl/9 font-bold tracking-tight text-gray-900 dark:text-gray-100">
                Set new password
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
                Enter your new password below.
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-sm">
            <form class="space-y-6" @submit="onSubmit" method="POST">
                <input type="hidden" :value="email" />

                <div>
                    <label for="password" class="block text-sm/6 font-medium text-gray-900 dark:text-gray-100">
                        New password
                    </label>
                    <div class="mt-2 relative">
                        <input
                            :type="showPassword ? 'text' : 'password'"
                            id="password"
                            v-model="password"
                            autocomplete="new-password"
                            placeholder="Enter new password"
                            class="font-medium block w-full rounded-md bg-white dark:bg-gray-800 px-3 py-1.5 pr-10 text-base text-black dark:text-gray-100 outline-1 -outline-offset-1 outline-gray-300 dark:outline-gray-700 placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:outline-2 focus:-outline-offset-2 focus:outline-none sm:text-sm/6 transition-colors duration-200"
                        />
                        <button
                            type="button"
                            @click="showPassword = !showPassword"
                            class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200"
                            tabindex="-1"
                        >
                            <component
                                :is="showPassword ? EyeSlashIcon : EyeIcon"
                                class="h-5 w-5"
                            />
                        </button>
                    </div>
                    <p v-if="passwordError" class="mt-1 text-red-500 dark:text-red-400 text-sm">
                        {{ passwordError }}
                    </p>
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm/6 font-medium text-gray-900 dark:text-gray-100">
                        Confirm password
                    </label>
                    <div class="mt-2 relative">
                        <input
                            :type="showConfirmPassword ? 'text' : 'password'"
                            id="password_confirmation"
                            v-model="passwordConfirmation"
                            autocomplete="new-password"
                            placeholder="Confirm new password"
                            class="font-medium block w-full rounded-md bg-white dark:bg-gray-800 px-3 py-1.5 pr-10 text-base text-black dark:text-gray-100 outline-1 -outline-offset-1 outline-gray-300 dark:outline-gray-700 placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:outline-2 focus:-outline-offset-2 focus:outline-none sm:text-sm/6 transition-colors duration-200"
                        />
                        <button
                            type="button"
                            @click="showConfirmPassword = !showConfirmPassword"
                            class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200"
                            tabindex="-1"
                        >
                            <component
                                :is="showConfirmPassword ? EyeSlashIcon : EyeIcon"
                                class="h-5 w-5"
                            />
                        </button>
                    </div>
                    <p v-if="passwordConfirmationError" class="mt-1 text-red-500 dark:text-red-400 text-sm">
                        {{ passwordConfirmationError }}
                    </p>
                </div>

                <div>
                    <button
                        type="submit"
                        :disabled="isSubmitting || !token || !email"
                        class="flex w-full justify-center rounded-md bg-black dark:bg-blue-600 px-3 py-1.5 text-sm/6 font-semibold text-white shadow-xs hover:opacity-75 hover:cursor-pointer focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-none transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Reset password
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
        <Loader msg="Resetting password..." />
    </div>

    <Toast />
</template>
