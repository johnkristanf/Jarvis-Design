<script lang="ts" setup>
    import { register } from '@/api/post/register'
    import Loader from '@/components/Loader.vue'
    import type { RegistrationCredentials } from '@/types/user'
    import { useMutation } from '@tanstack/vue-query'
    import { Toast, useToast } from 'primevue'
    import { useForm, useField } from 'vee-validate'
    import { ref } from 'vue'
    import * as yup from 'yup'

    // 👁️ Eye toggle icons
    import { EyeIcon, EyeSlashIcon } from '@heroicons/vue/24/solid'

    const isLoadingMutation = ref(false)
    const toast = useToast()
    const showPassword = ref(false)

    // VALIDATION SCHEMA
    const validationSchema = yup.object({
        first_name: yup.string().required('First Name is required'),
        last_name: yup.string().required('Last Name is required'),
        username: yup.string().required('Username is required'),
        email: yup.string().email('Invalid Email Address').required('Email is required'),
        password: yup
            .string()
            .required('Password is required')
            .min(8, 'Password must be at least 8 characters'),
    })

    const { handleSubmit, isSubmitting, handleReset } = useForm({
        validationSchema,
    })

    const { value: firstName, errorMessage: firstNameError } = useField('first_name')
    const { value: lastName, errorMessage: lastNameError } = useField('last_name')
    const { value: username, errorMessage: usernameError } = useField('username')
    const { value: email, errorMessage: emailError } = useField('email')
    const { value: password, errorMessage: passwordError } = useField('password')

    // REGISTER MUTATION
    const mutation = useMutation({
        mutationFn: register,
        onSuccess: (response: any) => {
            isLoadingMutation.value = false
            handleReset()

            toast.add({
                severity: 'success',
                summary: 'Registration Success!',
                detail: 'Account Registered',
                life: 3000,
            });

            window.location.href = '/email/verification?email=' + encodeURIComponent(response.email);

        },

        onError: () => {
            isLoadingMutation.value = false
            toast.add({
                severity: 'error',
                summary: 'Registration Failed',
                detail: 'An error occurred while registering your account. Please try again.',
                life: 3000,
            })
        },

        onMutate: () => {
            isLoadingMutation.value = true
        },
    })


    // FORM SUBMISSION HANDLER
    const onSubmit = handleSubmit(async (values) => {
        const userData: RegistrationCredentials = {
            name: `${values.first_name} ${values.last_name}`.trim(),
            username: values.username,
            email: values.email,
            password: values.password,
        }

        mutation.mutate(userData)
    })
</script>

<template>
    <div class="flex flex-col pt-4 px-6 lg:px-8 h-screen bg-white">
        <div class="sm:mx-auto sm:w-full sm:max-w-sm">
            <img class="mx-auto w-[20%]" src="/jarvis-logo-circle.png" alt="Your Company" />
            <h2 class="mt-3 text-center text-2xl/9 font-bold tracking-tight">
                Register to get started
            </h2>
        </div>

        <div class="mt-3 sm:mx-auto sm:w-full sm:max-w-sm">
            <form class="space-y-4 grid grid-cols-2 gap-3" @submit="onSubmit" method="POST">
                <!-- First Name -->
                <div>
                    <label for="first_name" class="block text-sm/6 font-medium">First Name</label>
                    <div class="mt-2">
                        <input
                            type="text"
                            id="first_name"
                            v-model="firstName"
                            class="font-medium block w-full rounded-md bg-white px-3 py-1.5 text-base text-black outline-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:outline-none sm:text-sm/6"
                        />
                    </div>
                    <p v-if="firstNameError" class="mt-1 text-red-500 text-sm">
                        {{ firstNameError }}
                    </p>
                </div>

                <!-- Last Name -->
                <div>
                    <label for="last_name" class="block text-sm/6 font-medium">Last Name</label>
                    <div class="mt-2">
                        <input
                            type="text"
                            id="last_name"
                            v-model="lastName"
                            class="font-medium block w-full rounded-md bg-white px-3 py-1.5 text-base text-black outline-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:outline-none sm:text-sm/6"
                        />
                    </div>
                    <p v-if="lastNameError" class="mt-1 text-red-500 text-sm">
                        {{ lastNameError }}
                    </p>
                </div>

                <!-- Username -->
                <div class="col-span-2">
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

                <!-- Email -->
                <div class="col-span-2">
                    <label for="email" class="block text-sm/6 font-medium">Email</label>
                    <div class="mt-2">
                        <input
                            type="email"
                            id="email"
                            v-model="email"
                            class="font-medium block w-full rounded-md bg-white px-3 py-1.5 text-base text-black outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-none sm:text-sm/6"
                        />
                    </div>
                    <p v-if="emailError" class="mt-1 text-red-500 text-sm">{{ emailError }}</p>
                </div>

                <!-- Password with Eye Toggle -->
                <div class="col-span-2">
                    <label for="password" class="block text-sm/6 font-medium">Password</label>
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
                <div class="col-span-2">
                    <button
                        type="submit"
                        :disabled="isSubmitting"
                        class="flex w-full text-white justify-center rounded-md bg-black px-3 py-1.5 text-sm/6 font-semibold shadow-xs hover:opacity-75 hover:cursor-pointer focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-none"
                    >
                        Register
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div v-if="isLoadingMutation">
        <Loader msg="Registering Account..." />
    </div>

    <Toast />
</template>
