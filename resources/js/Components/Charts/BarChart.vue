<!-- resources/js/Components/Charts/BarChart.vue -->
<script setup>
import { Bar } from "vue-chartjs";
import {
    Chart as ChartJS,
    BarElement,
    CategoryScale,
    LinearScale,
    Tooltip,
    Legend,
} from "chart.js";

ChartJS.register(BarElement, CategoryScale, LinearScale, Tooltip, Legend);

const props = defineProps({
    labels: { type: Array, required: true },
    values: { type: Array, required: true },
    label: { type: String, default: "" },
    color: { type: String, default: "rgba(59, 130, 246, 0.7)" },
    height: { type: Number, default: 220 },
});

const data = {
    labels: props.labels,
    datasets: [
        {
            label: props.label,
            data: props.values,
            backgroundColor: props.color,
            borderRadius: 4,
        },
    ],
};

const options = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
        y: { beginAtZero: true, ticks: { font: { size: 10 } } },
        x: { ticks: { font: { size: 10 } } },
    },
};
</script>

<template>
    <div :style="{ height: height + 'px' }">
        <Bar :data="data" :options="options" />
    </div>
</template>
