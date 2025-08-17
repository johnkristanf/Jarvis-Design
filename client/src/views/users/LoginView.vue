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
                username: response.username,
                role_id: response.role_id,
                role: response.role,
            }

            if (authenticatedUser.role.name === UserRole.USER) window.location.href = '/'
            if (authenticatedUser.role.name === UserRole.ADMIN)
                window.location.href = '/admin/dashboard'
        },

        onError: (error) => {
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
    <div class="flex flex-col pt-10 px-6 lg:px-8 h-[80vh] bg-white">
        <div class="sm:mx-auto sm:w-full sm:max-w-sm">
            <img class="mx-auto w-[20%]" src="/jarvis-logo-circle.png" alt="Your Company" />
            <h2 class="mt-3 text-center text-2xl/9 font-bold tracking-tight">Login your account</h2>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-sm">
            <form class="space-y-6" @submit="onSubmit" method="POST">
                <!-- Username -->
                <div>
                    <label for="username" class="block text-sm/6 font-medium">Username</label>
                    <div class="mt-2">
                        <input
                            type="text"
                            id="username"
                            v-model="username"
                            class="font-medium block w-full rounded-md bg-white px-3 py-1.5 text-base text-black outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-none sm:text-sm/6"
                        />
                    </div>
                    <p v-if="usernameError" class="mt-1 text-red-500 text-sm">
                        {{ usernameError }}
                    </p>
                </div>

                <!-- Password with toggle and forgot -->
                <div>
                    <div class="flex items-center justify-between">
                        <label for="password" class="block text-sm/6 font-medium">Password</label>
                        <div class="text-sm">
                            <a
                                href="/forgot-password"
                                class="font-medium text-gray-600 hover:underline"
                            >
                                Forgot password?
                            </a>
                        </div>
                    </div>

                    <div class="mt-2 relative">
                        <input
                            :type="showPassword ? 'text' : 'password'"
                            id="password"
                            v-model="password"
                            autocomplete="current-password"
                            class="font-medium block w-full rounded-md bg-white px-3 py-1.5 pr-10 text-base text-black outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-none sm:text-sm/6"
                        />
                        <button
                            type="button"
                            @click="showPassword = !showPassword"
                            class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500 hover:text-gray-700"
                            tabindex="-1"
                        >
                            <component
                                :is="showPassword ? EyeSlashIcon : EyeIcon"
                                class="h-5 w-5"
                            />
                        </button>
                    </div>

                    <p v-if="passwordError" class="mt-1 text-red-500 text-sm">
                        {{ passwordError }}
                    </p>
                </div>

                <!-- Submit -->
                <div>
                    <button
                        type="submit"
                        :disabled="isSubmitting"
                        class="flex w-full justify-center rounded-md bg-black px-3 py-1.5 text-sm/6 font-semibold text-white shadow-xs hover:opacity-75 hover:cursor-pointer focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-none"
                    >
                        Login
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Loading Indicator -->
    <div v-if="isLoadingMutation">
        <Loader msg="Logging In..." />
    </div>

    <Toast />
</template>
