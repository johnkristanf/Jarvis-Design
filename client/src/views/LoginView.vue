<script lang="ts" setup></script>

<template>
    <!--
      This example requires updating your template:
  
      ```
      <html class="h-full bg-white">
      <body class="h-full">
      ```
    -->
    <div class="flex flex-col mt-10 px-6 lg:px-8 h-[90vh] ">
      <div class="sm:mx-auto sm:w-full sm:max-w-sm">
        <img class="mx-auto w-[20%]" src="/jarvis-logo-circle.png" alt="Your Company" />
        <h2 class="mt-3 text-center text-2xl/9 font-bold tracking-tight text-white">Login your account</h2>
      </div>
  
      <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-sm">
        <form class="space-y-6" action="#" method="POST">

            <div>
                <label for="email" class="block text-sm/6 font-medium text-white">Username</label>
                <div class="mt-2">
                <input type="email" name="email" id="email"required class="font-medium block w-full rounded-md bg-white px-3 py-1.5 text-base text-black outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-none sm:text-sm/6" />
                </div>
            </div>
  
            <div>
                <div class="flex items-center justify-between">
                    <label for="password" class="block text-sm/6 font-medium text-white">Password</label>
                </div>
                    <div class="mt-2">
                    <input type="password" name="password" id="password" autocomplete="current-password" required class="font-medium block w-full rounded-md bg-white px-3 py-1.5 text-base text-black outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-none sm:text-sm/6" />
                </div>
            </div>
  
          <div>
            <button type="submit" class="flex w-full justify-center rounded-md bg-black px-3 py-1.5 text-sm/6 font-semibold text-white shadow-xs hover:opacity-75 hover:cursor-pointer focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-none">Sign in</button>
          </div>
        </form>
  
      </div>
    </div>
  </template>
  