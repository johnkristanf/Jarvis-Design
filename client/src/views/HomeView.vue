<script setup lang="ts">
  import CustomerReview from '@/components/home/CustomerReview.vue';
  import Designs from '@/components/home/Designs.vue';
  import Introduction from '@/components/home/Introduction.vue';
</script>

<template>
  <main>

    <!-- BUSINESS INTRODUCTION -->
    <div class="h-full">
      <Introduction />
    </div>

    
    <!-- BUSINESS ANNOUNCEMENTS -->
    <div class="h-full border-t-1 border-gray-300">
      <Designs />
    </div>


    <!-- BUSINESS CUSTOMER REVIEWS -->

    <div class="h-full border-t-1 border-gray-300">
      <CustomerReview />
    </div>

  </main>
</template>
