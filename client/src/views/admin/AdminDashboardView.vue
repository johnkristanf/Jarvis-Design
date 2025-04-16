<script lang="ts" setup>
import UploadedDesignsTable from '@/components/designs/UploadedDesignsTable.vue';

</script>

<template>
    <!-- THIS WILL DISPLAY GRIDS FOR EACH GRAPHS AND CHARTS FOR DASHBOARD SPECIFIC LAYOUT -->

    <!-- EACH INSIDE COMPONENT HAS EACH GRID -->
    <div class="grid grid-cols-3 gap-4 mb-4">
        <div>
            <UploadedDesignsTable />
        </div>

        <div>
            <UploadedDesignsTable />
        </div>


        <div>
            <UploadedDesignsTable />
        </div>

        <div>
            <UploadedDesignsTable />
        </div>
    </div>
</template>