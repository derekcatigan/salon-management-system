<!-- resources/js/Pages/Dashboard/AdminDashboard.vue -->
<script setup>
import Layout from "@/Layouts/Layout.vue";
import BarChart from "@/Components/Charts/BarChart.vue";
import LineChart from "@/Components/Charts/LineChart.vue";
import DoughnutChart from "@/Components/Charts/DoughnutChart.vue";

defineOptions({ layout: Layout });

const props = defineProps({
    userStats: { type: Object, required: true },
    inventoryStats: { type: Object, required: true },
    serviceStats: { type: Object, required: true },
    salesStats: { type: Object, required: true },
    denominationStats: { type: Object, required: true },
    topProducts: { type: Array, required: true },
    servicesByCategory: { type: Array, required: true },
    salesTrend: { type: Array, required: true },
});

function money(n) {
    return (
        "₱" +
        Number(n || 0).toLocaleString("en-PH", {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        })
    );
}
</script>

<template>
    <div class="mx-auto max-w-7xl space-y-4 p-3 sm:p-5 lg:p-6">
        <!-- Header -->
        <div>
            <h1 class="text-lg font-bold sm:text-xl">Admin Dashboard</h1>
            <p class="text-xs opacity-60">
                Overview of users, inventory, services, and sales.
            </p>
        </div>

        <!-- ==================== Top stat row ==================== -->
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="rounded-xl bg-base-100/60 p-3 sm:p-4">
                <span class="text-[10px] uppercase opacity-60"
                    >Total Users</span
                >
                <p class="mt-1 text-xl font-bold sm:text-2xl">
                    {{ userStats.total }}
                </p>
                <p class="text-[10px] opacity-60">
                    {{ userStats.active }} active ·
                    {{ userStats.inactive }} inactive
                </p>
            </div>

            <div class="rounded-xl bg-base-100/60 p-3 sm:p-4">
                <span class="text-[10px] uppercase opacity-60">Products</span>
                <p class="mt-1 text-xl font-bold sm:text-2xl">
                    {{ inventoryStats.total_products }}
                </p>
                <p class="text-[10px] opacity-60">
                    Value {{ money(inventoryStats.total_value) }}
                </p>
            </div>

            <div class="rounded-xl bg-base-100/60 p-3 sm:p-4">
                <span class="text-[10px] uppercase opacity-60">Services</span>
                <p class="mt-1 text-xl font-bold sm:text-2xl">
                    {{ serviceStats.total }}
                </p>
                <p class="text-[10px] opacity-60">
                    {{ serviceStats.active }} active
                </p>
            </div>

            <div class="rounded-xl bg-base-100/60 p-3 sm:p-4">
                <span class="text-[10px] uppercase opacity-60"
                    >Today's Revenue</span
                >
                <p class="mt-1 text-xl font-bold text-primary sm:text-2xl">
                    {{ money(salesStats.today_revenue) }}
                </p>
                <p class="text-[10px] opacity-60">
                    {{ salesStats.today_count }} sales today
                </p>
            </div>
        </div>

        <!-- ==================== Sales trend ==================== -->
        <div class="rounded-xl bg-base-100/60 p-3 sm:p-4">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-sm font-bold">Revenue · Last 7 Days</h2>
                <span class="text-xs opacity-60">
                    Month: {{ money(salesStats.month_revenue) }}
                </span>
            </div>
            <LineChart
                :labels="salesTrend.map((d) => d.label)"
                :values="salesTrend.map((d) => d.value)"
                :height="240"
            />
        </div>

        <!-- ==================== Two-column charts ==================== -->
        <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
            <!-- Top products by stock value -->
            <div class="rounded-xl bg-base-100/60 p-3 sm:p-4">
                <h2 class="mb-3 text-sm font-bold">
                    Top Products by Stock Value
                </h2>
                <BarChart
                    :labels="topProducts.map((p) => p.name)"
                    :values="topProducts.map((p) => p.value)"
                    color="rgba(139, 92, 246, 0.7)"
                    :height="240"
                />
            </div>

            <!-- Services by category -->
            <div class="rounded-xl bg-base-100/60 p-3 sm:p-4">
                <h2 class="mb-3 text-sm font-bold">Services by Category</h2>
                <DoughnutChart
                    :labels="servicesByCategory.map((s) => s.label)"
                    :values="servicesByCategory.map((s) => s.value)"
                    :height="240"
                />
            </div>
        </div>

        <!-- ==================== Bottom stat grid ==================== -->
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="rounded-xl bg-base-100/60 p-3 sm:p-4">
                <span class="text-[10px] uppercase opacity-60">Low Stock</span>
                <p class="mt-1 text-xl font-bold text-warning sm:text-2xl">
                    {{ inventoryStats.low_stock }}
                </p>
            </div>
            <div class="rounded-xl bg-base-100/60 p-3 sm:p-4">
                <span class="text-[10px] uppercase opacity-60"
                    >Out of Stock</span
                >
                <p class="mt-1 text-xl font-bold text-error sm:text-2xl">
                    {{ inventoryStats.out_of_stock }}
                </p>
            </div>
            <div class="rounded-xl bg-base-100/60 p-3 sm:p-4">
                <span class="text-[10px] uppercase opacity-60"
                    >Cash In Today</span
                >
                <p class="mt-1 text-xl font-bold text-success sm:text-2xl">
                    {{ money(denominationStats.today_cash_in) }}
                </p>
            </div>
            <div class="rounded-xl bg-base-100/60 p-3 sm:p-4">
                <span class="text-[10px] uppercase opacity-60"
                    >Cash Out Today</span
                >
                <p class="mt-1 text-xl font-bold text-error sm:text-2xl">
                    {{ money(denominationStats.today_cash_out) }}
                </p>
            </div>
        </div>
    </div>
</template>
