<!-- resources/js/Components/Charts/DoughnutChart.vue -->
<script setup>
import { Doughnut } from "vue-chartjs";
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from "chart.js";

ChartJS.register(ArcElement, Tooltip, Legend);

const props = defineProps({
    labels: { type: Array, required: true },
    values: { type: Array, required: true },
    colors: {
        type: Array,
        default: () => [
            "#3b82f6",
            "#10b981",
            "#f59e0b",
            "#ef4444",
            "#8b5cf6",
            "#ec4899",
            "#06b6d4",
            "#84cc16",
        ],
    },
    height: { type: Number, default: 220 },
});

const data = {
    labels: props.labels,
    datasets: [
        {
            data: props.values,
            backgroundColor: props.colors.slice(0, props.values.length),
            borderWidth: 0,
        },
    ],
};

const options = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: "bottom",
            labels: { font: { size: 10 }, boxWidth: 10 },
        },
    },
    cutout: "60%",
};
</script>

<template>
    <div :style="{ height: height + 'px' }">
        <Doughnut :data="data" :options="options" />
    </div>
</template>
