<script lang="ts" setup>
    import { ref, computed, onMounted } from 'vue'
    import { Dialog, DialogPanel, TransitionChild, TransitionRoot } from '@headlessui/vue'
    import { XMarkIcon } from '@heroicons/vue/20/solid'
    import { type Designs } from '@/types/design'
    import { ProgressSpinner } from 'primevue'

    import FailureMessageDialog from '../FailureMessageDialog.vue'
    import { useProductAttributes } from '@/composables/useProductAttribute'

    import { OrderOptions } from '@/types/order'
    import { FwbButton } from 'flowbite-vue'

    import ListSelectBox from '../ListSelectBox.vue'

    const props = defineProps<{
        design: Designs[]
        category: string | number
        tag: string | number
        isOpen: boolean
        onClose: () => void
    }>()

    const emit = defineEmits(['close'])
    const handleCloseModal = () => {
        openFailureModal.value = false
        emit('close')
    }

    const { sizes, loadingColors, loadingSizes } = useProductAttributes()

    // const paymentData = ref<ProceedPaymentData>({
    //     order_option: '',
    //     price: props.design.price,
    //     name: props.design.name,
    // })

    // const designAttributeData = ref<DesignAttribute>({
    //     design_id: -1,
    //     color: -1,
    //     size: -1,
    //     quantity: 1,
    // })

    const orderOptions = ref([
        { id: 1, name: OrderOptions.DELIVERY, tag: 'DELIVERY' },
        { id: 2, name: OrderOptions.PICK_UP, tag: 'PICK-UP' },
    ])

    // const openPaymentModal = ref<boolean>(false)
    const openFailureModal = ref<boolean>(false)

    const failureModalMessageRef = ref<string>('')
    const failureUnauthenticated = ref<boolean>(false)

    const open = computed(() => props.isOpen)

    // const selectedColor = ref(colors.value && colors.value[0])
    const selectedSize = ref(sizes.value && sizes.value[2])
    const selectedOrderType = ref(orderOptions.value[0])
    const quantity = ref<number>(0)

    // const checkUserIsLoggined = () => {
    //     if (!authStore.currentUser && !authStore.isLogginedIn) {
    //         openFailureModal.value = true
    //         failureModalMessageRef.value = 'Please Login First to Proceed to Order'
    //         failureUnauthenticated.value = true
    //         return
    //     }
    // }

    // const handleProceedPayment = () => {
    //     // HANDLE PAYMENT CANNOT PROCEED IF NOT LOGGED IN
    //     checkUserIsLoggined()

    //     if (selectedColor && selectedColor.value && selectedColor && selectedSize.value) {
    //         openFailureModal.value = false
    //         handleCloseModal()

    //         openPaymentModal.value = true

    //         paymentData.value.order_option = selectedOrderType.value.name
    //         paymentData.value.name = props.design.name
    //         paymentData.value.price = props.design.price

    //         designAttributeData.value.design_id = props.design.id
    //         designAttributeData.value.color = selectedColor.value.id
    //         designAttributeData.value.size = selectedSize.value.id
    //         designAttributeData.value.quantity = quantity.value

    //         console.log(' openPaymentModal: ', openPaymentModal.value)
    //         console.log(' paymentData: ', paymentData.value)
    //         console.log(' designAttributeData: ', designAttributeData.value)
    //     } else {
    //         openFailureModal.value = true
    //         failureUnauthenticated.value = false
    //         failureModalMessageRef.value = 'Please Select a Color and Size to Proceed Order'
    //     }
    // }

    // CONDITION HANDLER FOR FABRIC DROPDOWN
    const fabricRelevantKeywords = ['shirt', 'jersey']

    const hasFabricOptions = computed(() => {
        if (typeof props.category !== 'string') return false
        const categoryLower = props.category.toLowerCase()
        return fabricRelevantKeywords.some((keyword) => categoryLower.includes(keyword))
    })

    onMounted(() => {
        console.log('design: ', props.design)
        console.log('category: ', props.category)
        console.log('tag: ', props.tag)
    })
</script>

<template>
    <TransitionRoot as="template" :show="open">
        <Dialog class="relative z-10" @close="handleCloseModal">
            <TransitionChild
                as="template"
                enter="ease-out duration-300"
                enter-from="opacity-0"
                enter-to="opacity-100"
                leave="ease-in duration-200"
                leave-from="opacity-100"
                leave-to="opacity-0"
            >
                <div class="fixed inset-0 hidden bg-gray-500/75 transition-opacity md:block" />
            </TransitionChild>

            <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                <div class="flex min-h-full items-stretch justify-center text-center md:items-center md:px-2 lg:px-4">
                    <TransitionChild
                        as="template"
                        enter="ease-out duration-300"
                        enter-from="opacity-0 translate-y-4 md:translate-y-0 md:scale-95"
                        enter-to="opacity-100 translate-y-0 md:scale-100"
                        leave="ease-in duration-200"
                        leave-from="opacity-100 translate-y-0 md:scale-100"
                        leave-to="opacity-0 translate-y-4 md:translate-y-0 md:scale-95"
                    >
                        <DialogPanel class="flex w-full transform text-left text-base transition md:my-8 md:max-w-2xl md:px-4 lg:max-w-4xl">
                            <div
                                class="relative flex w-full items-center overflow-hidden mb-16 bg-white px-4 pt-14 pb-8 shadow-2xl sm:px-6 sm:pt-8 md:p-6 lg:p-8"
                            >
                                <button
                                    type="button"
                                    class="absolute top-4 right-4 text-gray-400 hover:text-gray-500 sm:top-8 sm:right-6 md:top-6 md:right-6 lg:top-8 lg:right-8"
                                    @click="handleCloseModal"
                                >
                                    <span class="sr-only">Close</span>
                                    <XMarkIcon class="size-6" aria-hidden="true" />
                                </button>

                                <div class="flex flex-col justify-between w-full">
                                    <!-- <img
                                        :src="props.design.image_path"
                                        class="w-full h-[350px] rounded-lg bg-gray-100 object-cover object-center sm:col-span-4 lg:col-span-5"
                                    /> -->

                                    <div class="sm:col-span-8 lg:col-span-7 mt-5">
                                        <!-- <h2 class="text-2xl font-bold text-gray-900 sm:pr-12">
                                            {{ props.design.name }}
                                        </h2> -->

                                        <section aria-labelledby="information-heading" class="mt-2">
                                            <h3 id="information-heading" class="sr-only">Design Information</h3>
                                            <!-- <p class="text-2xl text-gray-900">
                                                ₱ {{ props.design.price }}
                                            </p> -->
                                        </section>

                                        <section aria-labelledby="options-heading" class="mt-10">
                                            <h3 id="options-heading" class="sr-only">design options</h3>

                                            <form>
                                                <div class="flex flex-col items-center justify-center">
                                                    <h1 class="text-2xl">{{ props.category }}</h1>
                                                </div>

                                                <!-- FABRIC TYPE FIELD -->
                                                <fieldset v-if="hasFabricOptions" class="mt-10 w-full">
                                                    <label for="fabric" class="block text-md mb-2">Fabric Type</label>
                                                    <select
                                                        id="fabric"
                                                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    >
                                                        <option value="cotton">Cotton</option>
                                                        <option value="polyester">Polyester</option>
                                                        <option value="dry_fit">Dry Fit</option>
                                                    </select>
                                                </fieldset>

                                                <!-- COLOR FIELD -->
                                                <fieldset class="mt-10 w-full" aria-label="Select quantity">
                                                    <div class="flex items-center justify-between">
                                                        <div class="text-md">Color</div>
                                                    </div>

                                                    <div class="mt-4 w-full">
                                                        <input
                                                            type="text"
                                                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                        />
                                                    </div>
                                                </fieldset>

                                                <div class="flex gap-3">
                                                    <!-- SIZES FIELD -->
                                                    <fieldset v-if="sizes && !loadingSizes" class="mt-10" aria-label="Choose a color">
                                                        <div class="flex items-center justify-between">
                                                            <div class="text-md">Sizes</div>
                                                        </div>

                                                        <div class="mt-4 w-full">
                                                            <ListSelectBox :options="sizes" :modelValue="selectedSize" displayKey="name" />
                                                        </div>
                                                    </fieldset>

                                                    <!-- QUANTITY FIELD -->

                                                    <fieldset class="mt-10 w-1/2" aria-label="Select quantity">
                                                        <div class="flex items-center justify-between">
                                                            <div class="text-md">Quantity</div>
                                                        </div>

                                                        <div class="mt-4 w-full">
                                                            <input
                                                                type="number"
                                                                min="1"
                                                                v-model="quantity"
                                                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                            />
                                                        </div>
                                                    </fieldset>

                                                    <!-- ADD BOTH QUANTITY AND SIZE -->

                                                    <div class="w-1/4 flex items-end">
                                                        <fwb-button class="w-full !text-gray-900 hover:cursor-pointer" color="light">Add</fwb-button>
                                                    </div>
                                                </div>

                                                <!-- ORDER TYPES -->

                                                <fieldset v-if="orderOptions" class="mt-10" aria-label="Choose a color">
                                                    <div class="flex items-center justify-between">
                                                        <div class="text-md">Order Options</div>
                                                    </div>

                                                    <!-- DESIGN STATUS SELECT ELEMENT -->

                                                    <div class="mt-4 w-full">
                                                        <ListSelectBox v-model="selectedOrderType" :options="orderOptions" displayKey="tag" />
                                                    </div>

                                                    <!-- END OF DESIGN STATUS SELECT ELEMENT -->
                                                </fieldset>

                                                <!-- COLORS -->

                                                <!-- <fieldset
                                                    v-if="colors && !loadingColors"
                                                    class="mt-10"
                                                    aria-label="Choose a color"
                                                >
                                                    <div class="flex items-center justify-between">
                                                        <div class="text-md">Color</div>
                                                    </div> -->

                                                <!-- DESIGN STATUS SELECT ELEMENT -->

                                                <!-- <div class="mt-4 w-full">
                                                        <ListSelectBox
                                                            :options="colors"
                                                            displayKey="name"
                                                        />
                                                    </div>
                                                </fieldset> -->

                                                <!-- END OF DESIGN STATUS SELECT ELEMENT -->

                                                <div class="w-full flex items-center" v-if="loadingColors">
                                                    <h1>Loading Colors...</h1>
                                                    <ProgressSpinner :pt="{ root: { style: { width: '40px' } } }" />
                                                </div>

                                                <!-- SIZES -->

                                                <!-- <fieldset
                                                    v-if="sizes && !loadingSizes"
                                                    class="mt-10"
                                                    aria-label="Choose a size"
                                                >
                                                    <div class="flex items-center justify-between">
                                                        <div class="text-md">Size</div>
                                                    </div>

                                                    <RadioGroup
                                                        v-model="selectedSize"
                                                        class="mt-4 grid grid-cols-4 gap-4"
                                                    >
                                                        <RadioGroupOption
                                                            as="template"
                                                            v-for="size in sizes"
                                                            :key="size.name"
                                                            :value="size"
                                                            v-slot="{ active, checked }"
                                                            class="hover:cursor-pointer"
                                                        >
                                                            <div
                                                                :class="[
                                                                    active || checked
                                                                        ? 'ring-2 ring-indigo-500'
                                                                        : '',
                                                                    'group relative flex items-center justify-center rounded-md border px-4 py-3 text-sm font-medium uppercase hover:bg-gray-50 focus:outline-hidden sm:flex-1',
                                                                ]"
                                                            >
                                                                <span>{{ size.name }}</span>
                                                            </div>
                                                        </RadioGroupOption>
                                                    </RadioGroup>
                                                </fieldset> -->

                                                <!-- <div
                                                    class="w-full flex items-center"
                                                    v-if="loadingSizes"
                                                >
                                                    <h1>Loading Sizes...</h1>
                                                    <ProgressSpinner
                                                        :pt="{ root: { style: { width: '40px' } } }"
                                                    />
                                                </div> -->

                                                <button
                                                    type="submit"
                                                    class="mt-6 flex w-full items-center justify-center rounded-md border border-transparent bg-gray-900 px-8 py-3 text-base font-medium text-white hover:opacity-75 hover:cursor-pointer"
                                                >
                                                    Place Order
                                                </button>
                                            </form>
                                        </section>
                                    </div>
                                </div>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>

    <!-- <PaymentModal
        v-if="designAttributeData && paymentData && openPaymentModal"
        :orderType="OrderTypes.PRE_MADE"
        :paymentData="paymentData"
        :attributeData="designAttributeData"
        :isOpen="openPaymentModal"
        @close="openPaymentModal = false"
    /> -->

    <FailureMessageDialog
        v-if="openFailureModal"
        title="Place Order Failure"
        :message="failureModalMessageRef"
        :isUnauthenticated="failureUnauthenticated"
        :isOpen="openFailureModal"
        @close="openFailureModal = false"
    />
</template>
