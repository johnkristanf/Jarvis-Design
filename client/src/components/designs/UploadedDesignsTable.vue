<script lang="ts" setup>
import { DataTable, Button, Column } from 'primevue';
import { ref } from 'vue';

const products = ref([
    {
        id: '1000',
        image: 'bamboo-watch.jpg',
        price: 65,
        status: 'acknowledge',
    },
    {
        id: '1001',
        image: 'black-watch.jpg',
        price: 72,
        status: 'acknowledge',
    },
    {
        id: '1002',
        name: 'Blue Band',
        description: 'Product Description',
        image: 'blue-band.jpg',
        price: 79,
        status: 'tagged',
    },
    
]);

const formatCurrency = (value) => {
    return value.toLocaleString('en-US', { style: 'currency', currency: 'USD' });
};


const getSeverity = (product) => {
    switch (product.status) {
        case 'tagged':
            return 'success';

        case 'acknowledge':
            return 'warn';

        default:
            return undefined;
    }
};

</script>

<template>
    <div class="card">
        <DataTable :value="products" tableStyle="min-width: 50rem">
           
            <Column header="Image">
                <template #body="slotProps">
                    <img :src="`https://primefaces.org/cdn/primevue/images/product/${slotProps.data.image}`" :alt="slotProps.data.image" class="w-24 rounded" />
                </template>
            </Column>

            <Column field="price" header="Price">
                <template #body="slotProps">
                    {{ formatCurrency(slotProps.data.price) }}
                </template>
            </Column>

            
            <Column header="Status">
                <template #body="slotProps">
                    <Tag :value="slotProps.data.inventoryStatus" :severity="getSeverity(slotProps.data)" />
                </template>
            </Column>

        </DataTable>
    </div>
</template>


