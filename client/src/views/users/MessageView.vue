<script lang="ts" setup></script>

<template>
    <div class="w-[40%] h-[100vh] mb-16 bg-gray-100 border border-gray-400 rounded-md flex">
        <!-- Chat messages -->
        <div class="relative flex-1">
            <!-- Chat Header -->
            <div class="bg-gray-900 flex items-center font-medium h-18 pl-5">
                <img src="/user_icon-removebg.png" class="h-12 mr-3" alt="Flowbite Logo" />

                <div class="flex flex-col text-sm text-white">
                    <h1>John Doe G. Seguerra</h1>
                    <h1 class="text-gray-400">johndoe@gmail.com</h1>
                </div>
            </div>

            <div class="flex-1 font-medium h-[75%] pt-5 px-5 overflow-y-auto space-y-4">
                <!-- Dummy chat messages -->
                <div class="flex justify-start">
                    <div class="bg-gray-200 px-4 py-2 rounded-lg max-w-sm">
                        Hello, I have a question about the latest design.
                    </div>
                </div>
                <div class="flex justify-end">
                    <div class="bg-blue-500 text-white px-4 py-2 rounded-lg max-w-sm">
                        Sure, let me know what you're thinking!
                    </div>
                </div>
                <div class="flex justify-start">
                    <div class="bg-gray-200 px-4 py-2 rounded-lg max-w-sm">
                        Can we make the background white and the logo smaller?
                    </div>
                </div>
                <div class="flex justify-end">
                    <div class="bg-blue-500 text-white px-4 py-2 rounded-lg max-w-sm">
                        Noted! I’ll update it.
                    </div>
                </div>

                <!-- RECEIVED MESSAGE (left) -->
                <div class="flex justify-start">
                    <div class="rounded-lg max-w-xs overflow-hidden bg-gray-200 p-2 space-y-2">
                        <img
                            src="/JD-JERSEY-1.jpg"
                            alt="Received"
                            class="w-full max-w-[200px] rounded-md object-cover"
                        />
                        <p class="text-sm text-gray-800">
                            Can we make the background white and the logo smaller?
                        </p>
                    </div>
                </div>

                <!-- SENT MESSAGE (right) -->
                <div class="flex justify-end">
                    <div class="rounded-lg max-w-xs overflow-hidden bg-blue-500 p-2 space-y-2">
                        <img
                            src="/JD-JERSEY-2.jpg"
                            alt="Sent"
                            class="w-full max-w-[200px] rounded-md object-cover"
                        />
                        <p class="text-sm text-white">Noted! I’ll update it.</p>
                    </div>
                </div>
            </div>

            <!-- Message input -->
            <div
                class="absolute bottom-0 left-0 w-full flex items-center gap-2 p-4 bg-gray-50 border-t border-gray-300"
            >
                <label for="file-upload" class="cursor-pointer">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-6 w-6 text-gray-600 hover:text-black"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828L18 9.828m-3-2.828L18 9.828M18 9.828V16a2 2 0 002 2h2"
                        />
                    </svg>
                    <input id="file-upload" type="file" class="hidden" />
                </label>

                <input
                    type="text"
                    placeholder="Type your message..."
                    class="flex-1 font-medium px-4 py-2 border border-gray-300 rounded-full focus:outline-none focus:ring-1 focus:ring-blue-500"
                />

                <button class="p-2 rounded-full bg-blue-500 hover:bg-blue-600 text-white">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                    >
                        <path
                            d="M2.94 2.94a.75.75 0 011.06 0l12 12a.75.75 0 01-1.06 1.06L13 14.06V16a.75.75 0 01-1.28.53L9 13H7.5A1.5 1.5 0 016 11.5V10h-.5A1.5 1.5 0 014 8.5V7l-1.06-1.06a.75.75 0 010-1.06z"
                        />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</template>
