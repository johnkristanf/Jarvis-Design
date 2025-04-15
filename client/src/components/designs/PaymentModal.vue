<script lang="ts" setup>

import { generateQrCode } from '@/api/post/payment';
import { type ProceedPaymentResponseData, type PreferredDesignAttribute, type ProceedPaymentData } from '@/types/payment';
import {
  TransitionRoot,
  TransitionChild,
  Dialog,
  DialogPanel,
  DialogTitle,
} from '@headlessui/vue'
import { ProgressSpinner } from 'primevue';
import { onMounted, ref } from 'vue';

const props = defineProps<{
    paymentData: ProceedPaymentData;
    attributeData: PreferredDesignAttribute;
    isOpen: boolean;
    onClose: () => void;
}>();


const paymentResponseRef = ref<ProceedPaymentResponseData>({
    code_id: '',
    amount: -1,
    business_name: '',
    qrcode_img_src: ''
});


const emit = defineEmits(['close']);
const handleClose = () => emit('close');


const handleGeneratePaymentQrCode = async () => {
    const response = await generateQrCode(props.paymentData.amount);
    console.log("response qrcode: ", response);

    if(response && paymentResponseRef.value){
        paymentResponseRef.value = response
    }
}

onMounted(() => {
    handleGeneratePaymentQrCode();
})

</script>


<template>
    <TransitionRoot appear :show="isOpen" as="template">
    <Dialog as="div" @close="handleClose" class="relative z-10 ">
      <TransitionChild
        as="template"
        enter="duration-300 ease-out"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="duration-200 ease-in"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-black/25" />
      </TransitionChild>

      <div class="fixed inset-0 overflow-y-auto">
        <div
          class="flex min-h-full items-center justify-center p-4 text-center"
        >
          <TransitionChild
            as="template"
            enter="duration-300 ease-out"
            enter-from="opacity-0 scale-95"
            enter-to="opacity-100 scale-100"
            leave="duration-200 ease-in"
            leave-from="opacity-100 scale-100"
            leave-to="opacity-0 scale-95"
          >
            <DialogPanel
              class="w-full max-w-md transform overflow-hidden rounded-2xl bg-white p-6 mb-8 text-left align-middle shadow-xl transition-all"
            >
                <DialogTitle
                    as="h3"
                    class="text-lg font-medium leading-6 text-gray-900"
                >
                    Scan Gcash QR Code
                </DialogTitle>

                <div class="mt-2">
                    <p class="text-sm text-gray-500">Item Name: {{ paymentData.name }}</p>
                    <p class="text-sm text-gray-500">Price: {{ paymentData.amount }}</p>
                </div>


                <div class="mt-2" v-if="paymentResponseRef.qrcode_img_src != ''">
                    <img :src="paymentResponseRef.qrcode_img_src" alt="Generated QR CODE">
                </div>


                <div class="w-full flex flex-col justify-center items-center" v-else>
                    <h1>Generating QR Code...</h1>
                    <ProgressSpinner :pt="{root: {style: {width: '40px'}}}" />
                </div>

              <!-- <div class="mt-4">
                <button
                  type="button"
                  class="inline-flex justify-center rounded-md border border-transparent bg-blue-100 px-4 py-2 text-sm font-medium text-blue-900 hover:bg-blue-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2"
                  @click="closeModal"
                >
                  Got it, thanks!
                </button>
              </div> -->
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>
