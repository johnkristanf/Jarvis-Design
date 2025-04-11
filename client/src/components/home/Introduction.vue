<!-- <script lang="ts" setup></script>

<template>
    <div class="w-full h-full flex flex-col items-center justify-center text-white gap-8">

        <div class="flex flex-col items-center">
            <h1 class="text-7xl">JARVIS DESIGNS</h1>
            <h2 class="text-xl opacity-75">Unleash Your Style with Custom-Crafted Coolness!</h2>

            <p class="text-center text-sm opacity-75">
                Transform your ideas into vibrant realities with our premium printing and sublimation services.
            </p>

            <p class="text-center text-sm opacity-75">
                Experience the power of personalized products – start designing your masterpiece today!
            </p>
        </div>

        <div class="w-full flex justify-center items-center ">

            <input 
                type="text" 
                class="placeholder:opacity-75 placeholder:text-sm text-gray-500 border-none p-[11.3px] w-[35%]" 
                placeholder="Your Work Email"
            />


            <button 
                class="text-sm bg-gray-900 p-[14px] hover:opacity-75 hover:cursor-pointer"
            >
                Start Ordering
            </button>
        </div>

    </div>
</template> -->




<template>
    <div class="relative overflow-hidden bg-white">
      <div class="sm:pt-24 sm:pb-40 lg:pt-48 lg:pb-48">
        <div class="relative mx-auto max-w-7xl px-4 sm:static sm:px-6 lg:px-8">
          <div class="sm:max-w-lg">
            <h1 class="text-6xl font-bold tracking-tight text-gray-900 sm:text-4xl">JARVIS DESIGNS</h1>
            <h2 class="text-xl opacity-75">Unleash Your Style with Custom-Crafted Coolness!</h2>

            <p class="text-md text-gray-600 opacity-75 mt-2">
                Transform your ideas into vibrant realities with our premium printing and sublimation services.
            </p>


          </div>

          <div>
            <div class="mt-5">
              <!-- Decorative image grid -->
              <div aria-hidden="true" class="pointer-events-none lg:absolute lg:inset-y-0 lg:mx-auto lg:w-full lg:max-w-7xl">
                <div class="absolute transform sm:top-0 sm:left-1/2 sm:translate-x-8 lg:top-1/2 lg:left-1/2 lg:translate-x-8 lg:-translate-y-1/2">
                    <div class="flex items-center space-x-6 lg:space-x-8">
                        <div class="grid shrink-0 grid-cols-1 gap-y-6 lg:gap-y-8">
                            <div class="h-64 w-44 overflow-hidden rounded-lg sm:opacity-0 lg:opacity-100">
                                <img src="/durant-jersey.jpg" alt="" class="size-full object-cover" />
                            </div>
                            <div class="h-64 w-44 overflow-hidden rounded-lg">
                                <img src="/random-jersey.jpg" alt="" class="size-full object-cover" />
                            </div>
                        </div>
                    
                        <div class="grid shrink-0 grid-cols-1 gap-y-6 lg:gap-y-8">
                            <div class="h-64 w-44 overflow-hidden rounded-lg">
                                <img src="/oneal-jersey.jpg" alt="" class="size-full object-cover" />
                            </div>
                            <div class="h-64 w-44 overflow-hidden rounded-lg">
                                <img src="/steph-jersey.jpg" alt="" class="size-full object-cover" />
                            </div>
                        </div>
                    </div>
                </div>
              </div>
  
              <router-link to="/designs" class="inline-block rounded-md border border-transparent bg-gray-900 px-8 py-3 text-center font-medium text-white hover:opacity-75">Shop Designs</router-link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </template>
  