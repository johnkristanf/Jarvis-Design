<script lang="ts" setup>
    import Loader from '@/components/Loader.vue'
    import { updateProfile } from '@/api/post/profile'
    import { useFetchAuthenticatedUser } from '@/composables/useFetchAuthenticatedUser'
    import type { UpdateProfilePayload } from '@/types/user'
    import { useMutation } from '@tanstack/vue-query'
    import { Toast, useToast } from 'primevue'
    import { ref, watch, computed } from 'vue'

    import { useForm, useField } from 'vee-validate'
    import * as yup from 'yup'

    const { authStore, isLoading } = useFetchAuthenticatedUser()
    const toast = useToast()
    const isLoadingMutation = ref(false)
    const showPassword = ref(false)

    const profileSchema = yup.object({
        name: yup.string().required('Name is required').min(2, 'Name must be at least 2 characters'),
        username: yup.string().required('Username is required').min(3, 'Username must be at least 3 characters'),
        email: yup.string().email('Invalid email format').required('Email is required'),

        // PASSWORD GETS VALIDATED ONLY IF PROVIDED
        password: yup
            .string()
            .transform((value) => (value === '' ? undefined : value)) // handle empty string
            .when([], {
                is: (val: any) => val !== undefined,
                then: (schema) =>
                    schema
                        .min(8, 'Password must be at least 8 characters')
                        .matches(/[A-Z]/, 'Password must contain at least one uppercase letter')
                        .matches(/[a-z]/, 'Password must contain at least one lowercase letter')
                        .matches(/[0-9]/, 'Password must contain at least one number'),
                otherwise: (schema) => schema.notRequired(),
            }),
    })

    const { handleSubmit, setValues, meta } = useForm({
        validationSchema: profileSchema,
    })

    const { value: name, errorMessage: nameError } = useField<string>('name')
    const { value: username, errorMessage: usernameError } = useField<string>('username')
    const { value: email, errorMessage: emailError } = useField<string>('email')
    const { value: password, errorMessage: passwordError } = useField<string>('password')

    const hasChanges = computed(() => {
        if (!authStore.user) return false
        return (
            name.value !== authStore.user.name ||
            username.value !== authStore.user.username ||
            email.value !== authStore.user.email ||
            (password.value && password.value.length > 0)
        )
    })

    const isFormValid = computed(() => meta.value.valid && hasChanges.value)

    watch(
        isLoading,
        (loading) => {
            if (!loading && authStore.user) {
                setValues({
                    name: authStore.user.name,
                    username: authStore.user.username,
                    email: authStore.user.email,
                    password: '',
                })
            }
        },
        { immediate: true },
    )

    const mutation = useMutation({
        mutationFn: updateProfile,
        onSuccess: () => {
            isLoadingMutation.value = false
            toast.add({
                severity: 'success',
                summary: 'Profile Updated',
                detail: 'Your profile has been updated successfully.',
                life: 3000,
            })
            password.value = '' // clear password after update
        },
        onError: (error) => {
            isLoadingMutation.value = false
            console.error('Mutation error:', error)
            toast.add({
                severity: 'error',
                summary: 'Update Failed',
                detail: 'Failed to update profile. Please try again.',
                life: 5000,
            })
        },
        onMutate: () => {
            isLoadingMutation.value = true
        },
    })

    const handleUpdateProfile = handleSubmit((values) => {
        const data: UpdateProfilePayload = {
            name: values.name,
            username: values.username,
            email: values.email,
            password: values.password || undefined,
        }
        mutation.mutate(data)
    })

    const handleReset = () => {
        if (authStore.user) {
            setValues({
                name: authStore.user.name,
                username: authStore.user.username,
                email: authStore.user.email,
                password: '',
            })
        }
    }

    const togglePasswordVisibility = () => {
        showPassword.value = !showPassword.value
    }
</script>

<template>
    <div class="min-h-screen bg-gray-50 ">
        <!-- Header Section -->
        <div class="bg-white border-b border-gray-200">
            <div class="max-w-4xl mx-auto px-4 py-6">
                <div class="flex items-center space-x-4">
                    <div class="w-16 h-16 bg-gray-900 rounded-full flex items-center justify-center">
                        <span class="text-2xl font-bold text-white">
                            {{ authStore.user?.name?.charAt(0)?.toUpperCase() || 'U' }}
                        </span>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Profile</h1>
                        <p class="text-gray-600 mt-1">Manage your account information</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="max-w-4xl mx-auto px-4 py-5">
            <!-- Loading State -->
            <div v-if="isLoading" class="flex justify-center items-center py-20">
                <div class="text-center">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-gray-900 mx-auto mb-4"></div>
                    <p class="text-lg text-gray-600">Loading your profile...</p>
                </div>
            </div>

            <!-- Form -->
            <div v-else class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden ">
                <!-- Form Header -->
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h2 class="text-xl font-semibold text-gray-900">Account Information</h2>
                    <p class="text-sm text-gray-600 mt-1">Update your personal details below</p>
                </div>

                <!-- Form Body -->
                <form @submit.prevent="handleUpdateProfile" class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Name -->
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-900">
                                Full Name
                                <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="name"
                                type="text"
                                class="w-full px-4 py-3 rounded-lg border transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent"
                                :class="{
                                    'border-gray-300 bg-white': !nameError,
                                    'border-red-300 bg-red-50': nameError,
                                }"
                                placeholder="Enter your full name"
                                required
                            />
                            <p v-if="nameError" class="text-sm text-red-600 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                {{ nameError }}
                            </p>
                        </div>

                        <!-- Username -->
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-900">
                                Username
                                <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="username"
                                type="text"
                                class="w-full px-4 py-3 rounded-lg border transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent"
                                :class="{
                                    'border-gray-300 bg-white': !usernameError,
                                    'border-red-300 bg-red-50': usernameError,
                                }"
                                placeholder="Choose a unique username"
                                required
                            />
                            <p v-if="usernameError" class="text-sm text-red-600 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                {{ usernameError }}
                            </p>
                        </div>

                        <!-- Email -->
                        <div class="space-y-2 md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-900">
                                Email Address
                                <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="email"
                                type="email"
                                class="w-full px-4 py-3 rounded-lg border transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent"
                                :class="{
                                    'border-gray-300 bg-white': !emailError,
                                    'border-red-300 bg-red-50': emailError,
                                }"
                                placeholder="Enter your email address"
                                required
                            />
                            <p v-if="emailError" class="text-sm text-red-600 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                {{ emailError }}
                            </p>
                        </div>

                        <!-- Password -->
                        <div class="space-y-2 md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-900">New Password</label>
                            <div class="relative">
                                <input
                                    v-model="password"
                                    :type="showPassword ? 'text' : 'password'"
                                    class="w-full px-4 py-3 pr-12 rounded-lg border transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent"
                                    :class="{
                                        'border-gray-300 bg-white': !passwordError,
                                        'border-red-300 bg-red-50': passwordError,
                                    }"
                                    placeholder="Leave blank to keep current password"
                                />
                                <button
                                    type="button"
                                    @click="togglePasswordVisibility"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-gray-700"
                                >
                                    <svg
                                        v-if="!showPassword"
                                        class="w-5 h-5"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                        />
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                        />
                                    </svg>
                                    <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"
                                        />
                                    </svg>
                                </button>
                            </div>
                            <p v-if="passwordError" class="text-sm text-red-600 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                {{ passwordError }}
                            </p>
                            <p v-else-if="!password" class="text-sm text-gray-500 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                Leave empty to keep your current password
                            </p>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div
                        class="flex flex-col sm:flex-row justify-between items-center pt-6 mt-6 border-t border-gray-200 space-y-3 sm:space-y-0"
                    >
                        <div class="flex items-center space-x-2">
                            <div v-if="hasChanges" class="flex items-center text-sm text-amber-600">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                You have unsaved changes
                            </div>
                        </div>

                        <div class="flex space-x-3">
                            <button
                                type="button"
                                @click="handleReset"
                                :disabled="!hasChanges || isLoadingMutation"
                                class="px-6 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200"
                            >
                                Reset Changes
                            </button>

                            <button
                                type="submit"
                                :disabled="!isFormValid || isLoadingMutation"
                                class="px-6 py-2.5 text-sm font-medium text-white bg-gray-900 rounded-lg hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200 flex items-center"
                            >
                                <svg
                                    v-if="isLoadingMutation"
                                    class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle
                                        class="opacity-25"
                                        cx="12"
                                        cy="12"
                                        r="10"
                                        stroke="currentColor"
                                        stroke-width="4"
                                    ></circle>
                                    <path
                                        class="opacity-75"
                                        fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                    ></path>
                                </svg>
                                {{ isLoadingMutation ? 'Saving...' : 'Save Changes' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Loading Overlay -->
        <div
            v-if="isLoadingMutation"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
        >
            <div class="bg-white rounded-lg p-6 flex items-center space-x-4">
                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-gray-900"></div>
                <span class="text-gray-900 font-medium">Updating your profile...</span>
            </div>
        </div>

        <Toast />
    </div>
</template>
