<script lang="ts" setup>
  import { register } from '@/api/post/register';
import Loader from '@/components/Loader.vue';
import type { UserData } from '@/types/user';
import { useMutation } from '@tanstack/vue-query';
import { useToast } from 'primevue';
import { useForm, useField } from 'vee-validate';
import { ref } from 'vue';
  import * as yup from 'yup';

  const validationSchema = yup.object({
    name: yup.string().required('Name is required'),
    username: yup.string().required('Username is required'),
    password: yup.string().required('Password is required').min(8, 'Password must be at least 8 characters'),
  });

  const isLoadingMutation = ref(false);

  const { handleSubmit, isSubmitting, handleReset } = useForm({
    validationSchema,
  });

  const { value: name, errorMessage: nameError } = useField('name');
  const { value: username, errorMessage: usernameError } = useField('username');
  const { value: password, errorMessage: passwordError } = useField('password');


  const mutation = useMutation({
      mutationFn: register,
      onSuccess: (response) => {
        isLoadingMutation.value = false; 
        console.log("register response: ", response);
        handleReset()
      },

      onError: (error) => {
        isLoadingMutation.value = false; 
        console.error("Error generating image:", error);
      },

      onMutate: () => {
        isLoadingMutation.value = true; 
      },
  });


  const onSubmit = handleSubmit(async (values) => {
    console.log('Form submitted with:', values);

    const userData: UserData = {
      name: values.name,
      username: values.username,
      password: values.password,
    }

    mutation.mutate(userData);

  });


</script>

<template>
  <div class="flex flex-col pt-10 px-6 lg:px-8 h-[100vh] bg-white">
    <div class="sm:mx-auto sm:w-full sm:max-w-sm">
      <img class="mx-auto w-[20%]" src="/jarvis-logo-circle.png" alt="Your Company" />
      <h2 class="mt-3 text-center text-2xl/9 font-bold tracking-tight ">Register to get started</h2>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-sm">
      <form class="space-y-6" @submit="onSubmit" method="POST">
        <div>
          <label for="name" class="block text-sm/6 font-medium ">Name</label>
          <div class="mt-2">
            <input
              type="text"
              id="name"
              v-model="name"
              class="font-medium block w-full rounded-md bg-white px-3 py-1.5 text-base text-black outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-none sm:text-sm/6"
            />
          </div>
          <p v-if="nameError" class="mt-1 text-red-500 text-sm">{{ nameError }}</p>
        </div>

        <div>
          <label for="username" class="block text-sm/6 font-medium ">Username</label>
          <div class="mt-2">
            <input
              type="text"
              id="username"
              v-model="username"
              class="font-medium block w-full rounded-md bg-white px-3 py-1.5 text-base text-black outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-none sm:text-sm/6"
            />
          </div>
          <p v-if="usernameError" class="mt-1 text-red-500 text-sm">{{ usernameError }}</p>
        </div>

        <div>
          <div class="flex items-center justify-between">
            <label for="password" class="block text-sm/6 font-medium ">Password</label>
          </div>
          <div class="mt-2">
            <input
              type="password"
              id="password"
              v-model="password"
              autocomplete="current-password"
              class="font-medium block w-full rounded-md bg-white px-3 py-1.5 text-base text-black outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-none sm:text-sm/6"
            />
          </div>
          <p v-if="passwordError" class="mt-1 text-red-500 text-sm">{{ passwordError }}</p>
        </div>

        <div>
          <button
            type="submit"
            :disabled="isSubmitting"
            class="flex w-full text-white justify-center rounded-md bg-black px-3 py-1.5 text-sm/6 font-semibold  shadow-xs hover:opacity-75 hover:cursor-pointer focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-none"
          >
            Register
          </button>
        </div>
      </form>
    </div>
  </div>

  <div v-if="isLoadingMutation">
    <Loader msg="Registering Account..."/>
  </div>
</template>