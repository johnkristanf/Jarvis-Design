<script lang="ts" setup>
import { useFetchAuthenticatedUser } from '@/composables/useFetchAuthenticatedUser';
import { ref } from 'vue';
import { RouterLink } from 'vue-router';


// LOAD THE REAL USER IN THE NAVBAR LATER ON
const { authStore, isLoading } = useFetchAuthenticatedUser();

const sideLinks = ref([
  { name: "Dashboard", to: "/admin/dashboard" },
  { name: "Orders", to: "/admin/orders" },
  { name: "Designs", to: "/admin/designs" },
  { name: "Materials", to: "/admin/materials" },
])

</script>

<template>
    

<nav class="fixed top-0 z-50 w-full bg-gray-900 border-b border-gray-500 dark:bg-gray-800 dark:border-gray-700">
  <div class="px-3 py-3 lg:px-5 lg:pl-3">
    <div class="flex items-center justify-between">
      <div class="flex items-center justify-start rtl:justify-end">
        <button data-drawer-target="logo-sidebar" data-drawer-toggle="logo-sidebar" aria-controls="logo-sidebar" type="button" class="inline-flex items-center p-2 text-sm text-gray-500 rounded-lg sm:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-600">
            <span class="sr-only">Open sidebar</span>
            <svg class="w-6 h-6" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
               <path clip-rule="evenodd" fill-rule="evenodd" d="M2 4.75A.75.75 0 012.75 4h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 4.75zm0 10.5a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5a.75.75 0 01-.75-.75zM2 10a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 10z"></path>
            </svg>
         </button>

        <RouterLink to="/admin/dashboard" class="flex ms-2 md:me-24">
          <img src="/jarvis-logo-circle.png" class="h-8 me-3" />

          <h1 class="text-white text-2xl hover:cursor-pointer hover:opacity-75">
                Jarvis <span class="text-gray-700">Designs</span>
        </h1>
        </RouterLink>
      </div>


        <div class="flex items-center">
          <div class="flex items-center ms-3">
            <div>
              <button type="button" class="flex text-sm bg-gray-800 rounded-full focus:ring-4 focus:ring-gray-300 dark:focus:ring-gray-600" aria-expanded="false" data-dropdown-toggle="dropdown-user">
                <span class="sr-only">Open user menu</span>
                <img class="w-8 h-8 rounded-full" src="https://flowbite.com/docs/images/people/profile-picture-5.jpg" alt="user photo">
              </button>
            </div>
            <div class="z-50 hidden my-4 text-base list-none divide-y divide-gray-100 rounded-sm shadow-sm dark:bg-gray-700 dark:divide-gray-600" id="dropdown-user">
              <div class="px-4 py-3" role="none">
                <p class="text-sm text-gray-900 dark:text-black" role="none">
                  Neil Sims
                </p>
                <p class="text-sm font-medium text-gray-900 truncate dark:text-gray-300" role="none">
                  neil.sims@flowbite.com
                </p>
              </div>
              <ul class="py-1" role="none">
              
                <li>
                    <RouterLink 
                        to="/admin/designs" 
                        class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600 dark:hover:text-black" role="menuitem"
                    >
                     Settings
                    </RouterLink>
                </li>
              </ul>
            </div>
          </div>
        </div>
    </div>
  </div>
</nav>

<aside id="logo-sidebar" class="fixed top-0 left-0 mt-12 z-40 w-64 h-screen transition-transform -translate-x-full border-r border-gray-200 sm:translate-x-0 dark:bg-gray-800 dark:border-gray-700" aria-label="Sidebar">
   <div class="h-full overflow-y-auto bg-gray-900 dark:bg-gray-800 pt-2">
        <ul class="space-y-2 font-medium">
            <li v-for="link in sideLinks">
                <RouterLink :to="link.to" class="flex items-center p-2 text-white mb-3 dark:text-black hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 group">

                  <!-- CHANGE THIS TO SPECIFIC ICON -->
                  <svg class="w-5 h-5 text-gray-500 transition duration-75 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-black" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 22 21">
                      <path d="M16.975 11H10V4.025a1 1 0 0 0-1.066-.998 8.5 8.5 0 1 0 9.039 9.039.999.999 0 0 0-1-1.066h.002Z"/>
                      <path d="M12.5 0c-.157 0-.311.01-.565.027A1 1 0 0 0 11 1.02V10h8.975a1 1 0 0 0 1-.935c.013-.188.028-.374.028-.565A8.51 8.51 0 0 0 12.5 0Z"/>
                  </svg>

                  <span class="ms-3 ">{{ link.name }}</span>

                </RouterLink>
            </li>
        </ul>
   </div>
</aside>


</template>