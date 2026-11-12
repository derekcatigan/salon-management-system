<!-- resources/js/Components/Charts/LineChart.vue -->
<script setup>
import { Line } from "vue-chartjs";
import {
    Chart as ChartJS,
    LineElement,
    PointElement,
    CategoryScale,
    LinearScale,
    Tooltip,
    Filler,
} from "chart.js";

ChartJS.register(
    LineElement,
    PointElement,
    CategoryScale,
    LinearScale,
    Tooltip,
    Filler,
);

const props = defineProps({
    labels: { type: Array, required: true },
    values: { type: Array, required: true },
    label: { type: String, default: "" },
    color: { type: String, default: "rgba(16, 185, 129, 1)" },
    fillColor: { type: String, default: "rgba(16, 185, 129, 0.15)" },
    height: { type: Number, default: 220 },
});

const data = {
    labels: props.labels,
    datasets: [
        {
            label: props.label,
            data: props.values,
            borderColor: props.color,
            backgroundColor: props.fillColor,
            fill: true,
            tension: 0.3,
            pointRadius: 3,
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
        <Line :data="data" :options="options" />
    </div>
</template>
