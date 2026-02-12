<script lang="ts" setup>
    import { login } from '@/api/post/login'
    import Loader from '@/components/Loader.vue'
    import { UserRole, type AuthenticatedUserData, type LoginCredentials } from '@/types/user'
    import { useMutation } from '@tanstack/vue-query'
    import { useForm, useField } from 'vee-validate'
    import { ref } from 'vue'
    import * as yup from 'yup'
    import Toast from 'primevue/toast'

    // Heroicons
    import { EyeIcon, EyeSlashIcon } from '@heroicons/vue/24/solid'
    import { useToast } from 'primevue'

    const isLoadingMutation = ref(false)
    const showPassword = ref(false) // 👁️ Password visibility toggle
    const toast = useToast()

    const validationSchema = yup.object({
        username: yup.string().required('Username is required'),
        password: yup.string().required('Password is required'),
    })

    const { handleSubmit, isSubmitting } = useForm({
        validationSchema,
    })

    const { value: username, errorMessage: usernameError } = useField('username')
    const { value: password, errorMessage: passwordError } = useField('password')

    const mutation = useMutation({
        mutationFn: login,
        onSuccess: (response) => {
            isLoadingMutation.value = false
            console.log('response login: ', response)

            const authenticatedUser: AuthenticatedUserData = {
                id: response.id,
                name: response.name,
                email: response.email,
                username: response.username,
                role_id: response.role_id,
                role: response.role,
            }

            if (authenticatedUser.role.name === UserRole.USER) window.location.href = '/'
            if (authenticatedUser.role.name === UserRole.ADMIN)
                window.location.href = '/admin/dashboard'
        },

        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        onError: (error: any) => {
            isLoadingMutation.value = false
            console.error('Error Logging In:', error)

            if (error.statusCode === 401) {
                toast.add({
                    severity: 'error',
                    summary: 'Invalid Username or Password',
                    life: 3000,
                })
            }
        },

        onMutate: () => {
            isLoadingMutation.value = true
        },
    })

    const onSubmit = handleSubmit(async (values) => {
        const userData: LoginCredentials = {
            username: values.username,
            password: values.password,
        }

        mutation.mutate(userData)
    })
</script>

<template>
    <div class="flex flex-col pt-10 px-6 lg:px-8 h-[80vh] bg-white dark:bg-gray-900 transition-colors duration-200">
        <div class="sm:mx-auto sm:w-full sm:max-w-sm">
            <img class="mx-auto w-[20%] rounded-full" src="/jarvis-logo-white.jpeg" alt="Your Company" />
            <h2 class="mt-3 text-center text-2xl/9 font-bold tracking-tight text-gray-900 dark:text-gray-100">Login your account</h2>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-sm">
            <form class="space-y-6" @submit="onSubmit" method="POST">
                <!-- Username -->
                <div>
                    <label for="username" class="block text-sm/6 font-medium text-gray-900 dark:text-gray-100">Username</label>
                    <div class="mt-2">
                        <input
                            type="text"
                            id="username"
                            v-model="username"
                            class="font-medium block w-full rounded-md bg-white dark:bg-gray-800 px-3 py-1.5 text-base text-black dark:text-gray-100 outline-1 -outline-offset-1 outline-gray-300 dark:outline-gray-700 placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:outline-2 focus:-outline-offset-2 focus:outline-none sm:text-sm/6 transition-colors duration-200"
                        />
                    </div>
                    <p v-if="usernameError" class="mt-1 text-red-500 dark:text-red-400 text-sm">
                        {{ usernameError }}
                    </p>
                </div>

                <!-- Password with toggle and forgot -->
                <div>
                    <div class="flex items-center justify-between">
                        <label for="password" class="block text-sm/6 font-medium text-gray-900 dark:text-gray-100">Password</label>
                        <router-link
                            to="/auth/forgot-password"
                            class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300"
                        >
                            Forgot password?
                        </router-link>
                    </div>

                    <div class="mt-2 relative">
                        <input
                            :type="showPassword ? 'text' : 'password'"
                            id="password"
                            v-model="password"
                            autocomplete="current-password"
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

                <!-- Submit -->
                <div>
                    <button
                        type="submit"
                        :disabled="isSubmitting"
                        class="flex w-full justify-center rounded-md bg-black dark:bg-blue-600 px-3 py-1.5 text-sm/6 font-semibold text-white shadow-xs hover:opacity-75 hover:cursor-pointer focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-none transition-colors duration-200"
                    >
                        Login
                    </button>
                </div>
            </form>
        </div>

        <!-- Register Message at the bottom -->
        <div class="mt-6 sm:mx-auto sm:w-full sm:max-w-sm text-center">
            <span class="text-sm text-gray-600 dark:text-gray-300">Don't have an account?</span>
            <router-link
                to="/auth/register"
                class="text-sm text-blue-600 dark:text-blue-400 underline ml-1 hover:text-blue-800 dark:hover:text-blue-300"
            >
                Register here
            </router-link>
        </div>
    </div>

    <!-- Loading Indicator -->
    <div v-if="isLoadingMutation">
        <Loader msg="Logging In..." />
    </div>

    <Toast />
</template>
