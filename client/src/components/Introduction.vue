<script lang="ts" setup>
    import { InputText } from 'primevue';
</script>

<template>
    <div class="w-full h-full flex flex-col items-center justify-center text-white gap-8">

        <div class="flex flex-col items-center">
            <h1 class="text-7xl">JARVIS DESIGNS</h1>
            <h1 class="text-lg opacity-75">Unleash Your Style with Custom-Crafted Coolness!</h1>
        </div>

        <div class="flex items-center">
            <input 
                type="text" 
                class="placeholder:opacity-75 placeholder:text-sm text-gray-500 border-none p-3" 
                placeholder="Your Work Email"
            />


            <button 
                class="text-sm bg-gray-900 p-[14px] hover:opacity-75 hover:cursor-pointer"
            >
                Start Ordering
            </button>
        </div>


        <!-- <div class="flex flex-col text-sm items-center">
            <h1>• YOU CAN CUSTOMIZED THE DESIGN AND FABRIC</h1>
            <h1>• QUALITY, AFFORDABLE AND FAST PRODUCTION</h1>
            <h1>• CUSTOMER SATISFACTION IS OUR TOP PRIORITY</h1>
        </div>

        <div class="flex flex-col text-sm items-center">
            <h1>• FOR INQUIRIES, YOU CAN REACH US VIA</h1>
            <h1>• EMAIL: santillanjason21@gmail.com</h1>
            <h1>• PHONE: 0998 866 8090</h1>
        </div> -->

    </div>
</template>