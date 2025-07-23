<template>
    <div class="min-h-[80vh] px-4 py-8 flex justify-center items-center">
        <!-- Loading State -->
        <div v-if="isLoading" class="text-center text-lg text-gray-700">Loading Profile...</div>

        <!-- Form -->
        <div v-else class="w-full max-w-xl">
            <h2 class="text-2xl font-semibold mb-6">My Profile</h2>

            <form @submit.prevent="handleUpdateProfile" class="space-y-5">
                <!-- Name -->
                <div>
                    <label class="block text-sm mb-1">Full Name</label>
                    <input
                        v-model="name"
                        type="text"
                        class="w-full px-4 py-2 font-medium rounded-md bg-gray-200 border border-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        required
                    />
                    <p v-if="nameError" class="text-sm text-red-500 mt-1">{{ nameError }}</p>
                </div>

                <!-- Username -->
                <div>
                    <label class="block text-sm mb-1">Username</label>
                    <input
                        v-model="username"
                        type="text"
                        class="w-full px-4 py-2 font-medium rounded-md bg-gray-200 border border-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        required
                    />
                    <p v-if="usernameError" class="text-sm text-red-500 mt-1">
                        {{ usernameError }}
                    </p>
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-sm mb-1">Email Address</label>
                    <input
                        v-model="email"
                        type="email"
                        class="w-full px-4 py-2 font-medium rounded-md bg-gray-200 border border-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        required
                    />
                    <p v-if="emailError" class="text-sm text-red-500 mt-1">{{ emailError }}</p>
                </div>

                <!-- Password -->
                <div>
                    <label class="block text-sm mb-1">New Password</label>
                    <input
                        v-model="password"
                        type="password"
                        class="w-full px-4 py-2 font-medium rounded-md bg-gray-200 border border-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        placeholder="Leave blank to keep current password"
                    />
                    <p v-if="passwordError" class="text-sm text-red-500 mt-1">
                        {{ passwordError }}
                    </p>
                </div>

                <!-- Submit -->
                <div>
                    <button
                        type="submit"
                        class="w-full py-2 px-4 bg-gray-900 hover:bg-gray-800 text-white rounded-md font-medium transition"
                    >
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div v-if="isLoadingMutation">
        <Loader msg="Updating Profile..." />
    </div>

    <Toast />
</template>

<script lang="ts" setup>
    import Loader from '@/components/Loader.vue'
    import { updateProfile } from '@/api/post/profile'
    import { useFetchAuthenticatedUser } from '@/composables/useFetchAuthenticatedUser'
    import type { UpdateProfilePayload } from '@/types/user'
    import { useMutation } from '@tanstack/vue-query'
    import { Toast, useToast } from 'primevue'
    import { ref, watch } from 'vue'

    import { useForm, useField } from 'vee-validate'
    import * as yup from 'yup'

    const { authStore, isLoading } = useFetchAuthenticatedUser()
    const toast = useToast()
    const isLoadingMutation = ref(false)

    const profileSchema = yup.object({
        name: yup.string().required('Name is required'),
        username: yup.string().required('Username is required'),
        email: yup.string().email('Invalid email').required('Email is required'),

        // PASSWORD GETS VALIDATED ONLY IF PROVIDED
        password: yup
            .string()
            .transform((value) => (value === '' ? undefined : value)) // handle empty string
            .when([], {
                is: (val: any) => val !== undefined,
                then: (schema) => schema.min(8, 'Password must be at least 8 characters'),
                otherwise: (schema) => schema.notRequired(),
            }),
    })

    const { handleSubmit, setValues } = useForm({
        validationSchema: profileSchema,
    })

    const { value: name, errorMessage: nameError } = useField<string>('name')
    const { value: username, errorMessage: usernameError } = useField<string>('username')
    const { value: email, errorMessage: emailError } = useField<string>('email')
    const { value: password, errorMessage: passwordError } = useField<string>('password')

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
            console.error('Mutation error:', error)
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
</script>
