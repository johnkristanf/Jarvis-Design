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
    const showConfirmPassword = ref(false)

    // VALIDATION SCHEMA
    const validationSchema = yup.object({
        first_name: yup.string().required('First Name is required'),
        last_name: yup.string().required('Last Name is required'),
        username: yup.string().required('Username is required'),
        email: yup.string().email('Invalid Email Address').required('Email is required'),
        address: yup.string().notRequired(),
        phone_number: yup
            .string()
            .matches(/^[0-9]{10}$/, 'Phone number must be 10 digits')
            .notRequired(),
        password: yup
            .string()
            .required('Password is required')
            .min(8, 'Password must be at least 8 characters'),
        confirm_password: yup
            .string()
            .required('Confirm Password is required')
            .oneOf([yup.ref('password')], 'Passwords must match'),
    })

    const { handleSubmit, isSubmitting, handleReset } = useForm({
        validationSchema,
    })

    const { value: firstName, errorMessage: firstNameError } = useField('first_name')
    const { value: lastName, errorMessage: lastNameError } = useField('last_name')
    const { value: username, errorMessage: usernameError } = useField('username')
    const { value: email, errorMessage: emailError } = useField('email')
    const { value: phoneNumber, errorMessage: phoneNumberError } = useField('phone_number')
    const { value: address, errorMessage: addressError } = useField('address')
    const { value: password, errorMessage: passwordError } = useField('password')
    const { value: confirmPassword, errorMessage: confirmPasswordError } =
        useField('confirm_password')

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
            })

            window.location.href = '/email/verification?email=' + encodeURIComponent(response.email)
        },

        onError: (error: any) => {
            isLoadingMutation.value = false
            console.log('ERROR :', error)

            // Try to get error message - look for known keys
            let message = 'An error occurred while registering your account. Please try again.'
            if (error && (error.msg || error.message)) {
                message = error.msg || error.message
            }

            toast.add({
                severity: 'error',
                summary: 'Registration Failed',
                detail: message,
                life: 3000,
            })
        },

        onMutate: () => {
            isLoadingMutation.value = true
        },
    })

    // FORM SUBMISSION HANDLER
    const onSubmit = handleSubmit(async (values) => {
        // Confirm password validation
        if (values.password !== values.confirm_password) {
            toast.add({
                severity: 'error',
                summary: 'Registration Failed',
                detail: 'Passwords do not match. Please confirm your password.',
                life: 3000,
            })
            isLoadingMutation.value = false
            return
        }

        const userData: RegistrationCredentials = {
            name: `${values.first_name} ${values.last_name}`.trim(),
            username: values.username,
            email: values.email,
            address: values.address,
            phone_number: values.phone_number,
            password: values.password,
        }

        mutation.mutate(userData)
    })
</script>

<template>
    <div class="flex flex-col pt-4 px-6 lg:px-8 h-[150vh] bg-white dark:bg-gray-900 transition-colors duration-300">
        <div class="sm:mx-auto sm:w-full sm:max-w-sm">
            <img class="mx-auto w-[20%] rounded-full" src="/jarvis-logo-white.jpeg" alt="Your Company" />
            <h2 class="mt-3 text-center text-2xl/9 font-bold tracking-tight text-gray-900 dark:text-white">
                Register to get started
            </h2>
        </div>

        <div class="mt-3 sm:mx-auto sm:w-full sm:max-w-sm">
            <form class="space-y-4 grid grid-cols-2 gap-3" @submit="onSubmit" method="POST">
                <!-- First Name -->
                <div>
                    <label for="first_name" class="block text-sm/6 font-medium text-gray-900 dark:text-gray-200">
                        First Name
                        <span class="text-red-500">*</span>
                    </label>
                    <div class="mt-2">
                        <input
                            type="text"
                            id="first_name"
                            v-model="firstName"
                            class="font-medium block w-full rounded-md bg-white dark:bg-gray-900 px-3 py-1.5 text-base text-black dark:text-white outline-1 outline-gray-300 dark:outline-gray-700 placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:outline-2 focus:outline-none sm:text-sm/6"
                        />
                    </div>
                    <p v-if="firstNameError" class="mt-1 text-red-500 dark:text-red-400 text-sm">
                        {{ firstNameError }}
                    </p>
                </div>

                <!-- Last Name -->
                <div>
                    <label for="last_name" class="block text-sm/6 font-medium text-gray-900 dark:text-gray-200">
                        Last Name
                        <span class="text-red-500">*</span>
                    </label>
                    <div class="mt-2">
                        <input
                            type="text"
                            id="last_name"
                            v-model="lastName"
                            class="font-medium block w-full rounded-md bg-white dark:bg-gray-900 px-3 py-1.5 text-base text-black dark:text-white outline-1 outline-gray-300 dark:outline-gray-700 placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:outline-2 focus:outline-none sm:text-sm/6"
                        />
                    </div>
                    <p v-if="lastNameError" class="mt-1 text-red-500 dark:text-red-400 text-sm">
                        {{ lastNameError }}
                    </p>
                </div>

                <!-- Username -->
                <div>
                    <label for="username" class="block text-sm/6 font-medium text-gray-900 dark:text-gray-200">
                        Username
                        <span class="text-red-500">*</span>
                    </label>
                    <div class="mt-2">
                        <input
                            type="text"
                            id="username"
                            v-model="username"
                            class="font-medium block w-full rounded-md bg-white dark:bg-gray-900 px-3 py-1.5 text-base text-black dark:text-white outline-1 -outline-offset-1 outline-gray-300 dark:outline-gray-700 placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:outline-2 focus:-outline-offset-2 focus:outline-none sm:text-sm/6"
                        />
                    </div>
                    <p v-if="usernameError" class="mt-1 text-red-500 dark:text-red-400 text-sm">
                        {{ usernameError }}
                    </p>
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm/6 font-medium text-gray-900 dark:text-gray-200">
                        Email
                        <span class="text-red-500">*</span>
                    </label>
                    <div class="mt-2">
                        <input
                            type="email"
                            id="email"
                            v-model="email"
                            class="font-medium block w-full rounded-md bg-white dark:bg-gray-900 px-3 py-1.5 text-base text-black dark:text-white outline-1 -outline-offset-1 outline-gray-300 dark:outline-gray-700 placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:outline-2 focus:-outline-offset-2 focus:outline-none sm:text-sm/6"
                        />
                    </div>
                    <p v-if="emailError" class="mt-1 text-red-500 dark:text-red-400 text-sm">{{ emailError }}</p>
                </div>
                <!-- Phone Number -->
                <div class="col-span-2">
                    <label for="phone_number" class="block text-sm/6 font-medium text-gray-900 dark:text-gray-200">
                        Phone Number
                    </label>
                    <div class="mt-2 flex rounded-md shadow-sm">
                        <span
                            class="inline-flex items-center rounded-l-md border border-r-0 border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3 text-gray-500 dark:text-gray-400 text-sm/6"
                        >
                            +63
                        </span>
                        <input
                            type="number"
                            id="phone_number"
                            v-model="phoneNumber"
                            class="font-medium block w-full min-w-0 flex-1 rounded-none rounded-r-md bg-white dark:bg-gray-900 px-3 py-1.5 text-base text-black dark:text-white outline-1 outline-gray-300 dark:outline-gray-700 placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:outline-2 focus:outline-none sm:text-sm/6"
                            placeholder="9123456789"
                            min="0"
                        />
                    </div>
                    <p v-if="phoneNumberError" class="mt-1 text-red-500 dark:text-red-400 text-sm">
                        {{ phoneNumberError }}
                    </p>
                </div>

                <!-- Address -->
                <div class="col-span-2">
                    <label for="address" class="block text-sm/6 font-medium text-gray-900 dark:text-gray-200">Address</label>
                    <div class="mt-2">
                        <input
                            type="text"
                            id="address"
                            v-model="address"
                            class="font-medium block w-full rounded-md bg-white dark:bg-gray-900 px-3 py-1.5 text-base text-black dark:text-white outline-1 outline-gray-300 dark:outline-gray-700 placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:outline-2 focus:outline-none sm:text-sm/6"
                        />
                    </div>
                    <p v-if="addressError" class="mt-1 text-red-500 dark:text-red-400 text-sm">
                        {{ addressError }}
                    </p>
                </div>

                <!-- Password with Eye Toggle -->
                <div class="col-span-2">
                    <label for="password" class="block text-sm/6 font-medium text-gray-900 dark:text-gray-200">
                        Password
                        <span class="text-red-500">*</span>
                    </label>
                    <div class="mt-2 relative">
                        <input
                            :type="showPassword ? 'text' : 'password'"
                            id="password"
                            v-model="password"
                            autocomplete="current-password"
                            class="font-medium block w-full rounded-md bg-white dark:bg-gray-900 px-3 py-1.5 pr-10 text-base text-black dark:text-white outline-1 -outline-offset-1 outline-gray-300 dark:outline-gray-700 placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:outline-2 focus:-outline-offset-2 focus:outline-none sm:text-sm/6"
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

                <!-- Confirm Password -->
                <div class="col-span-2">
                    <label for="confirmPassword" class="block text-sm/6 font-medium text-gray-900 dark:text-gray-200">
                        Confirm Password
                        <span class="text-red-500">*</span>
                    </label>
                    <div class="mt-2 relative">
                        <input
                            :type="showConfirmPassword ? 'text' : 'password'"
                            id="confirmPassword"
                            v-model="confirmPassword"
                            autocomplete="new-password"
                            class="font-medium block w-full rounded-md bg-white dark:bg-gray-900 px-3 py-1.5 pr-10 text-base text-black dark:text-white outline-1 -outline-offset-1 outline-gray-300 dark:outline-gray-700 placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:outline-2 focus:-outline-offset-2 focus:outline-none sm:text-sm/6"
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
                    <p v-if="confirmPasswordError" class="mt-1 text-red-500 dark:text-red-400 text-sm">
                        {{ confirmPasswordError }}
                    </p>
                </div>

                <!-- Submit -->
                <div class="col-span-2">
                    <button
                        type="submit"
                        :disabled="isSubmitting"
                        class="flex w-full text-white justify-center rounded-md bg-black dark:bg-blue-600 px-3 py-1.5 text-sm/6 font-semibold shadow-xs hover:opacity-75 hover:cursor-pointer focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-none transition-colors duration-200"
                    >
                        Register
                    </button>
                </div>
            </form>
        </div>

        <!-- Login Message at the bottom -->
        <div class="mt-6 sm:mx-auto sm:w-full sm:max-w-sm text-center">
            <span class="text-sm text-gray-600 dark:text-gray-300">Already have an account?</span>
            <router-link
                to="/auth/login"
                class="text-sm text-blue-600 dark:text-blue-400 underline ml-1 hover:text-blue-800 dark:hover:text-blue-300"
            >
                Login here
            </router-link>
        </div>
    </div>

    <div v-if="isLoadingMutation">
        <Loader msg="Registering Account..." />
    </div>

    <Toast />
</template>
