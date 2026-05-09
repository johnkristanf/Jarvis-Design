<script setup lang="ts">
    import { computed } from 'vue'
    import { useQuery } from '@tanstack/vue-query'
    import { getAllFeedbacks } from '@/api/get/feedback'

    const { data: feedbacks, isLoading } = useQuery({
        queryKey: ['feedbacks'],
        queryFn: getAllFeedbacks,
    })
</script>

<template>
    <section class="relative isolate overflow-hidden bg-white dark:bg-gray-900 py-24 sm:py-32 transition-colors duration-200">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-4xl">What Our Customers Say</h2>
                <p class="mt-4 text-lg leading-8 text-gray-600 dark:text-gray-400">Trusted by our amazing community.</p>
            </div>
        </div>

        <div v-if="isLoading" class="flex justify-center">
            <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
        </div>

        <div v-else-if="feedbacks && feedbacks.length > 0" class="relative flex overflow-x-hidden">
            <!-- Marquee Track -->
            <div class="flex animate-marquee whitespace-nowrap py-4">
                <div v-for="feedback in [...feedbacks, ...feedbacks]" :key="`${feedback.id}-${Math.random()}`" 
                    class="mx-4 flex w-[350px] flex-col justify-between bg-gray-50 dark:bg-gray-800/50 p-8 rounded-2xl ring-1 ring-gray-200 dark:ring-gray-700 transition-all hover:shadow-lg whitespace-normal">
                    <blockquote class="text-gray-900 dark:text-gray-100">
                        <div class="flex gap-1 mb-4 text-yellow-400">
                            <span v-for="star in 5" :key="star">
                                {{ feedback.rating >= star ? '★' : '☆' }}
                            </span>
                        </div>
                        <p class="text-lg font-semibold leading-7 italic break-words line-clamp-3">“{{ feedback.message }}”</p>
                    </blockquote>
                    <figcaption class="mt-6 flex items-center gap-x-4 border-t border-gray-200 dark:border-gray-700 pt-6">
                        <div class="h-10 w-10 shrink-0 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold">
                            {{ feedback.user.name.charAt(0).toUpperCase() }}
                        </div>
                        <div class="text-sm leading-6 min-w-0">
                            <p class="font-semibold text-gray-900 dark:text-white truncate">{{ feedback.user.name }}</p>
                            <p class="text-gray-500 dark:text-gray-400 truncate">{{ feedback.subject }}</p>
                        </div>
                    </figcaption>
                </div>
            </div>
        </div>

        <div v-else class="text-center text-gray-500 dark:text-gray-400 px-6">
            No high-rated reviews yet. Be the first to share your experience!
        </div>
    </section>
</template>

<style scoped>
    @keyframes marquee {
        0% { transform: translateX(0); }
        100% { transform: translateX(-50%); }
    }

    .animate-marquee {
        display: flex;
        animation: marquee 30s linear infinite;
    }

    .animate-marquee:hover {
        animation-play-state: paused;
    }

    /* Prevent text wrapping issues in marquee */
    .whitespace-normal {
        white-space: normal;
    }
</style>
