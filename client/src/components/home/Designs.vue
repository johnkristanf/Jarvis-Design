<script lang="ts" setup>
    const callouts = [
        {
            name: 'Task Masters',
            price: 150,
            imageSrc: '/jersey-1.jpg',
            imageAlt:
                'Desk with leather desk pad, walnut desk organizer, wireless keyboard and mouse, and porcelain mug.',
        },

        {
            name: 'Division',
            price: 200,
            imageSrc: '/jersey-2.jpg',
            imageAlt:
                'Wood table with porcelain mug, leather journal, brass pen, leather key ring, and a houseplant.',
        },

        {
            name: 'Asukat Brothers',
            price: 500,
            imageSrc: '/jersey-3.jpg',
            imageAlt: 'Collection of four insulated travel bottles on wooden shelf.',
        },
    ]
</script>

<template>
    <div class="bg-white">
        <div class="mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mx-auto py-16 sm:py-24">
                <div class="flex items-center justify-between">
                    <div class="flex flex-col">
                        <h2 class="text-5xl font-bold text-gray-900">Designs</h2>
                        <h2 class="text-md text-gray-500 mb-6">
                            PM us now for orders. We have big discounts and freebies!
                        </h2>
                    </div>

                    <router-link to="/designs" class="flex hover:cursor-pointer opacity-75 text-lg">
                        <h1 class="text-gray-900 hover:opacity-40">View All</h1>
                    </router-link>
                </div>

                <div class="space-y-12 lg:grid lg:grid-cols-3 lg:space-y-0 lg:gap-x-6">
                    <div v-for="callout in callouts" :key="callout.name" class="group relative">
                        <img
                            :src="callout.imageSrc"
                            :alt="callout.imageAlt"
                            class="w-full rounded-lg bg-white object-cover group-hover:opacity-75 max-sm:h-80 sm:aspect-2/1 lg:aspect-square"
                        />
                        <h3 class="mt-6 text-sm text-gray-500">
                            {{ callout.name }}
                        </h3>
                        <p class="text-base font-semibold text-gray-900">₱ {{ callout.price }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
