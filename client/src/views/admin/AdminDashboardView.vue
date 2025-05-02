<script lang="ts" setup>
    import {
        Chart as ChartJS,
        CategoryScale,
        BarElement,
        LinearScale,
        PointElement,
        LineElement,
        Title,
        Tooltip,
        Legend,
    } from 'chart.js'
    import { Bar } from 'vue-chartjs'
    import { Line } from 'vue-chartjs'

    ChartJS.register(
        CategoryScale,
        LinearScale,
        PointElement,
        LineElement,
        Title,
        Tooltip,
        Legend,
        BarElement,
    )

    const chartData = {
        labels: ['January', 'February', 'March'],
        datasets: [
            {
                label: 'Sales',
                data: [40, 20, 12],
                backgroundColor: ['#3B82F6', '#10B981', '#F59E0B'], // For Bar chart
                borderColor: '#3B82F6', // For Line chart
                borderWidth: 2,
                fill: false, // Optional for line chart
                tension: 0.4, // Smooth the line
            },
        ],
    }

    const chartOptions = {
        responsive: true,
        plugins: {
            legend: {
                display: true,
                position: 'top',
            },
            title: {
                display: true,
                text: 'Monthly Sales Data',
            },
        },
        scales: {
            y: {
                beginAtZero: true,
            },
        },
    }
</script>

<template>
    <!-- THIS WILL DISPLAY GRIDS FOR EACH GRAPHS AND CHARTS FOR DASHBOARD SPECIFIC LAYOUT -->

    <div class="w-full p-4 rounded-md bg-gray-100 border-1 border-gray-400">
        <h1 class="text-2xl">Dashboard</h1>
        <p class="text-sm text-gray-400 mt-1 mb-7">
            Gives an overview of key metrics, recent activities, and system summaries at a glance.
        </p>

        <div class="grid grid-cols-1 sm:grid-cols-1 md:grid-cols-1 lg:grid-cols-2 gap-4 mb-4 ">
            <div class="w-[500px] h-[300px] rounded-md p-3">
                <Bar id="my-chart-id" :options="chartOptions" :data="chartData" />
            </div>

            <div class="w-[500px] h-[300px] rounded-md p-3">
                <Line :data="chartData" :options="chartOptions" />
            </div>
        </div>
    </div>
</template>
