<script setup lang="ts">
    import { Dialog, DialogPanel, DialogTitle } from '@headlessui/vue'
    import type { Orders } from '@/types/order'
    import { computed } from 'vue'

    const props = defineProps<{
        order: Orders
        isOpen: boolean
    }>()

    const emit = defineEmits<{
        (e: 'close'): void
    }>()

    const formattedOrderDate = computed(() => {
        if (!props.order.created_at) return '—'
        return new Date(props.order.created_at).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
        })
    })

    const formattedDeliveryDate = computed(() => {
        if (!props.order.delivery_date) return '—'
        return new Date(props.order.delivery_date).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: '2-digit',
        })
    })

    const formattedStatus = computed(() =>
        props.order.status.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase()),
    )

    const isDelivery = computed(() => props.order.order_option === 'delivery')

    const formatCurrency = (value: number) =>
        '₱' + value.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })

    function getPrintStyles() {
        return `
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #333; background: #fff; }
        .receipt-wrapper { max-width: 720px; margin: 0 auto; padding: 32px 40px; }
        .receipt-header { text-align: center; padding-bottom: 20px; border-bottom: 2px solid #222; margin-bottom: 24px; }
        .brand-name { font-size: 22px; font-weight: 700; color: #222; margin: 6px 0 2px; }
        .brand-accent { color: #7d6724; }
        .receipt-subtitle { font-size: 13px; color: #666; }
        .section-title { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #999; margin-bottom: 8px; padding-bottom: 4px; border-bottom: 1px solid #eee; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px 32px; margin-bottom: 20px; font-size: 13px; }
        .info-item label { font-size: 10px; text-transform: uppercase; letter-spacing: 0.06em; color: #999; display: block; margin-bottom: 2px; }
        .info-item span { font-weight: 600; color: #222; }
        .info-full { grid-column: 1 / -1; }
        .status-badge { display: inline-block; padding: 2px 8px; border-radius: 20px; font-size: 10px; font-weight: 700; text-transform: uppercase; background: #fff3cd; color: #856404; border: 1px solid #fde68a; }
        table.items-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; font-size: 13px; }
        .items-table thead tr { background: #222; color: #fff; }
        .items-table th { padding: 8px 12px; text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: 0.06em; }
        .items-table th.right { text-align: right; }
        .items-table th.center { text-align: center; }
        .items-table td { padding: 10px 12px; border-bottom: 1px solid #f0f0f0; vertical-align: top; }
        .items-table td.center { text-align: center; }
        .items-table td.right { text-align: right; font-weight: 600; }
        .product-name { font-weight: 600; color: #222; }
        .product-detail { font-size: 11px; color: #777; margin-top: 3px; }
        .totals-block { display: flex; flex-direction: column; align-items: flex-end; gap: 6px; padding-top: 14px; border-top: 2px solid #eee; font-size: 13px; }
        .totals-row { display: flex; gap: 16px; }
        .total-label { color: #666; }
        .total-value { font-weight: 600; min-width: 100px; text-align: right; }
        .grand-total { font-size: 18px; font-weight: 700; color: #7d6724; }
        .sig-section { display: grid; grid-template-columns: 1fr 1fr; gap: 48px; margin-top: 40px; padding-top: 16px; border-top: 1px solid #ddd; }
        .sig-box { text-align: center; font-size: 12px; color: #555; }
        .sig-line { border-top: 1px solid #333; padding-top: 6px; margin-top: 36px; }
        .sig-sub { font-size: 10px; color: #aaa; margin-top: 2px; }
        .receipt-footer { text-align: center; margin-top: 28px; padding-top: 14px; border-top: 1px solid #eee; font-size: 11px; color: #bbb; }
    `
    }

    function handlePrint() {
        const printContent = document.getElementById('delivery-receipt-print-area')
        if (!printContent) return
        const win = window.open('', '_blank', 'width=860,height=960')
        if (!win) return
        win.document.write(`<!DOCTYPE html>
<html><head>
  <meta charset="utf-8">
  <title>Receipt #${props.order.order_number}</title>
  <style>${getPrintStyles()}</style>
</head><body>${printContent.innerHTML}</body></html>`)
        win.document.close()
        win.focus()
        setTimeout(() => {
            win.print()
            win.close()
        }, 300)
    }
</script>

<template>
    <Dialog
        :open="isOpen"
        @close="emit('close')"
        class="fixed inset-0 z-[999999] flex items-center justify-center bg-gray-900/70"
    >
        <DialogPanel class="w-full max-w-4xl mx-4 flex flex-col max-h-[90vh]">
            <div class="bg-white overflow-hidden rounded-2xl shadow-2xl flex flex-col max-h-[90vh]">
                <!-- ── HEADER ── -->
                <div
                    class="bg-gray-900 text-white px-6 py-4 flex items-center justify-between flex-shrink-0"
                >
                    <div>
                        <DialogTitle as="h1" class="text-xl font-bold">
                            {{ isDelivery ? 'Delivery Receipt' : 'Pick-up Receipt' }}
                        </DialogTitle>
                        <p class="text-gray-300 text-sm">Order # {{ order.order_number }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            @click="handlePrint"
                            class="text-sm bg-white text-gray-900 px-3 py-2 rounded-lg font-medium hover:bg-gray-100 transition-colors cursor-pointer inline-flex items-center gap-2"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="w-4 h-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2m-10 0v4h8v-4m-8 0h8"
                                />
                            </svg>
                            Print / Download
                        </button>
                        <button
                            @click="emit('close')"
                            class="text-gray-300 hover:text-white p-2 rounded-lg hover:bg-gray-800 transition-colors cursor-pointer"
                        >
                            <svg
                                class="w-6 h-6"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- ── SCROLLABLE RECEIPT BODY ── -->
                <div class="flex-1 overflow-y-auto p-6 sm:p-8 bg-white">
                    <div id="delivery-receipt-print-area" class="receipt-wrapper max-w-none">
                        <!-- RECEIPT HEADER -->
                        <div
                            class="receipt-header text-center pb-5 border-b-2 border-gray-800 mb-6"
                        >
                            <h2 class="brand-name text-2xl font-bold text-gray-800 mt-1">
                                Jarvis
                                <span class="text-[#7d6724]">Designs</span>
                            </h2>
                            <p class="receipt-subtitle text-sm text-gray-500 mt-1">
                                {{ isDelivery ? 'Delivery Receipt' : 'Pick-up Receipt' }}
                            </p>
                        </div>

                        <!-- ORDER INFORMATION -->
                        <p
                            class="section-title text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-3 pb-1 border-b border-gray-100"
                        >
                            Order Information
                        </p>
                        <div
                            class="info-grid grid grid-cols-2 sm:grid-cols-3 gap-x-8 gap-y-4 mb-6 text-sm"
                        >
                            <div class="info-item">
                                <label
                                    class="text-[10px] text-gray-400 uppercase tracking-wide block mb-0.5"
                                >
                                    Order Number
                                </label>
                                <span class="font-semibold text-gray-800 break-all">
                                    #{{ order.order_number }}
                                </span>
                            </div>
                            <div class="info-item">
                                <label
                                    class="text-[10px] text-gray-400 uppercase tracking-wide block mb-0.5"
                                >
                                    Order Date
                                </label>
                                <span class="font-semibold text-gray-800">
                                    {{ formattedOrderDate }}
                                </span>
                            </div>
                            <div class="info-item">
                                <label
                                    class="text-[10px] text-gray-400 uppercase tracking-wide block mb-0.5"
                                >
                                    {{ isDelivery ? 'Delivery Date' : 'Pick-up Date' }}
                                </label>
                                <span class="font-semibold text-gray-800">
                                    {{ formattedDeliveryDate }}
                                </span>
                            </div>
                            <div class="info-item">
                                <label
                                    class="text-[10px] text-gray-400 uppercase tracking-wide block mb-0.5"
                                >
                                    Option
                                </label>
                                <span class="font-semibold text-gray-800 uppercase">
                                    {{ order.order_option }}
                                </span>
                            </div>
                            <div class="info-item">
                                <label
                                    class="text-[10px] text-gray-400 uppercase tracking-wide block mb-0.5"
                                >
                                    Status
                                </label>
                                <span
                                    class="status-badge inline-block px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-amber-50 text-amber-700 border border-amber-200"
                                >
                                    {{ formattedStatus }}
                                </span>
                            </div>
                        </div>

                        <!-- CUSTOMER INFORMATION -->
                        <p
                            class="section-title text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-3 pb-1 border-b border-gray-100"
                        >
                            Customer Information
                        </p>
                        <div
                            class="info-grid grid grid-cols-2 sm:grid-cols-3 gap-x-8 gap-y-4 mb-6 text-sm"
                        >
                            <div class="info-item">
                                <label
                                    class="text-[10px] text-gray-400 uppercase tracking-wide block mb-0.5"
                                >
                                    Name
                                </label>
                                <span class="font-semibold text-gray-800">
                                    {{ order.user?.name ?? '—' }}
                                </span>
                            </div>
                            <div class="info-item">
                                <label
                                    class="text-[10px] text-gray-400 uppercase tracking-wide block mb-0.5"
                                >
                                    Email
                                </label>
                                <span class="font-semibold text-gray-800 break-all">
                                    {{ order.user?.email ?? '—' }}
                                </span>
                            </div>
                            <div class="info-item">
                                <label
                                    class="text-[10px] text-gray-400 uppercase tracking-wide block mb-0.5"
                                >
                                    Phone Number
                                </label>
                                <span class="font-semibold text-gray-800">
                                    {{ order.phone_number ?? '—' }}
                                </span>
                            </div>
                            <div class="info-full col-span-2 sm:col-span-3">
                                <label
                                    class="text-[10px] text-gray-400 uppercase tracking-wide block mb-0.5"
                                >
                                    Address
                                </label>
                                <span class="font-semibold text-gray-800">
                                    {{ order.address ?? '—' }}
                                </span>
                            </div>
                        </div>

                        <!-- ORDER ITEMS -->
                        <p
                            class="section-title text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-3 pb-1 border-b border-gray-100"
                        >
                            Order Items
                        </p>
                        <table class="items-table w-full border-collapse mb-5 text-sm">
                            <thead>
                                <tr class="bg-gray-800 text-white">
                                    <th
                                        class="px-3 py-2.5 text-left text-[10px] uppercase tracking-wider w-[55%]"
                                    >
                                        Product
                                    </th>
                                    <th
                                        class="px-3 py-2.5 text-center text-[10px] uppercase tracking-wider"
                                    >
                                        Qty
                                    </th>
                                    <th
                                        class="px-3 py-2.5 text-right text-[10px] uppercase tracking-wider"
                                    >
                                        Subtotal
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="item in order.items"
                                    :key="item.id"
                                    class="border-b border-gray-100"
                                >
                                    <td class="px-3 py-3 align-top">
                                        <div class="product-name font-semibold text-gray-800">
                                            {{ item.product?.name ?? 'Custom Item' }}
                                        </div>
                                        <div class="product-detail text-xs text-gray-500 mt-1">
                                            Color:
                                            {{
                                                item.color
                                                    ? item.color.charAt(0).toUpperCase() +
                                                      item.color.slice(1)
                                                    : '—'
                                            }}
                                        </div>
                                        <div
                                            v-if="item.sizes && item.sizes.length > 0"
                                            class="product-detail text-xs text-gray-500 mt-0.5"
                                        >
                                            Sizes:
                                            <span v-for="(size, idx) in item.sizes" :key="size.id">
                                                {{ size.name }} ({{ size.pivot.quantity }})
                                                <span v-if="idx < item.sizes.length - 1">,</span>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-3 text-center text-gray-700 font-medium">
                                        {{ item.total_quantity }}
                                    </td>
                                    <td class="px-3 py-3 text-right text-gray-700 font-semibold">
                                        {{ formatCurrency(item.total_price) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- TOTALS -->
                        <div
                            class="totals-block flex flex-col items-end gap-1.5 pt-4 border-t-2 border-gray-100"
                        >
                            <div class="totals-row flex gap-4 text-sm">
                                <span class="total-label text-gray-500">Total Paid:</span>
                                <span
                                    class="total-value font-semibold text-gray-800 min-w-[100px] text-right"
                                >
                                    {{ formatCurrency(order.total_paid) }}
                                </span>
                            </div>
                            <div class="totals-row flex gap-4 text-sm">
                                <span class="total-label text-gray-500">Balance:</span>
                                <span
                                    class="total-value font-semibold min-w-[100px] text-right"
                                    :class="order.balance > 0 ? 'text-red-600' : 'text-green-600'"
                                >
                                    {{ formatCurrency(order.balance) }}
                                </span>
                            </div>
                            <div class="totals-row flex gap-4 mt-1">
                                <span class="font-semibold text-gray-700">Grand Total:</span>
                                <span
                                    class="grand-total text-xl font-bold text-[#7d6724] min-w-[100px] text-right"
                                >
                                    {{ formatCurrency(order.total_price) }}
                                </span>
                            </div>
                        </div>

                        <!-- SIGNATURE -->
                        <div
                            class="sig-section grid grid-cols-2 gap-12 mt-10 pt-4 border-t border-gray-200"
                        >
                            <div class="sig-box text-center text-xs text-gray-500">
                                <div
                                    class="sig-line border-t border-gray-700 pt-1.5 mt-9 text-gray-600 font-medium"
                                >
                                    Prepared by
                                </div>
                                <p class="sig-sub text-[10px] text-gray-400 mt-0.5">
                                    Authorized Signature
                                </p>
                            </div>
                            <div class="sig-box text-center text-xs text-gray-500">
                                <div
                                    class="sig-line border-t border-gray-700 pt-1.5 mt-9 text-gray-600 font-medium"
                                >
                                    Received by
                                </div>
                                <p class="sig-sub text-[10px] text-gray-400 mt-0.5">
                                    Customer Signature &amp; Date
                                </p>
                            </div>
                        </div>

                        <!-- FOOTER -->
                        <div
                            class="receipt-footer text-center mt-7 pt-4 border-t border-gray-100 text-[11px] text-gray-400"
                        >
                            <p>Thank you for choosing Jarvis Designs!</p>
                            <p class="mt-0.5">
                                &copy; {{ new Date().getFullYear() }} Jarvis Designs. All rights
                                reserved.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- ── FOOTER ACTIONS ── -->
                <div
                    class="border-t border-gray-200 bg-gray-50 px-6 py-4 flex-shrink-0 flex justify-end"
                >
                    <button
                        type="button"
                        @click="emit('close')"
                        class="inline-flex justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-500 cursor-pointer"
                    >
                        Close
                    </button>
                </div>
            </div>
        </DialogPanel>
    </Dialog>
</template>
