<script setup lang="ts">
  import Designs from '@/components/home/Designs.vue';
  import Introduction from '@/components/home/Introduction.vue';
</script>

<template>
  <main class="pb-[8rem]">

    <!-- BUSINESS INTRODUCTION -->
    <div class="h-[90vh] ">
      <Introduction />
    </div>

    
    <!-- BUSINESS ANNOUNCEMENTS -->
    <div class="h-[80vh] ">
      <Designs />
    </div>


    <!-- BUSINESS CUSTOMER REVIEWS -->

    <!-- <div class="h-[80vh] p-3 mt-[12rem] ">

      <div class="flex flex-col text-white gap-1 ">
        <h1 class="text-5xl">
          Customer Reviews
        </h1>

        <p class="opacity-75 text-xl">Genuine feedback from individuals who have experienced the quality and customization offered by Jarvis Designs.</p>
      </div>

      <CustomerReview />

    </div> -->

  </main>
</template>
