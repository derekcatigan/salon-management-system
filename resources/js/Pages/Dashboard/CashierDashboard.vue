<!-- resources/js/Pages/Dashboard/CashierDashboard.vue -->
<script setup>
import Layout from "@/Layouts/Layout.vue";
import LineChart from "@/Components/Charts/LineChart.vue";
import DoughnutChart from "@/Components/Charts/DoughnutChart.vue";
import { Link } from "@inertiajs/vue3";

defineOptions({ layout: Layout });

const props = defineProps({
    inventoryStats: { type: Object, required: true },
    serviceStats: { type: Object, required: true },
    tellerStats: { type: Object, required: true },
    salesTrend: { type: Array, required: true },
    topServicesToday: { type: Array, required: true },
    paymentSplit: { type: Array, required: true },
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
        <div
            class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1 class="text-lg font-bold sm:text-xl">Cashier Dashboard</h1>
                <p class="text-xs opacity-60">
                    Your teller and store overview.
                </p>
            </div>

            <Link
                :href="route('teller.index')"
                class="btn btn-xs sm:btn-sm btn-primary"
            >
                Open POS
            </Link>
        </div>

        <!-- ==================== Top stat row ==================== -->
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="rounded-xl bg-base-100/60 p-3 sm:p-4">
                <span class="text-[10px] uppercase opacity-60"
                    >My Sales Today</span
                >
                <p class="mt-1 text-xl font-bold sm:text-2xl">
                    {{ tellerStats.today_sales_count }}
                </p>
                <p class="text-[10px] opacity-60">
                    {{ money(tellerStats.today_revenue) }} revenue
                </p>
            </div>

            <div class="rounded-xl bg-base-100/60 p-3 sm:p-4">
                <span class="text-[10px] uppercase opacity-60">Cash In</span>
                <p class="mt-1 text-xl font-bold text-success sm:text-2xl">
                    {{ money(tellerStats.cash_in) }}
                </p>
            </div>

            <div class="rounded-xl bg-base-100/60 p-3 sm:p-4">
                <span class="text-[10px] uppercase opacity-60">Cash Out</span>
                <p class="mt-1 text-xl font-bold text-error sm:text-2xl">
                    {{ money(tellerStats.cash_out) }}
                </p>
            </div>

            <div class="rounded-xl bg-base-100/60 p-3 sm:p-4">
                <span class="text-[10px] uppercase opacity-60">Drawer</span>
                <p class="mt-1 text-xl font-bold text-primary sm:text-2xl">
                    {{ money(tellerStats.drawer) }}
                </p>
            </div>
        </div>

        <!-- ==================== Sales trend ==================== -->
        <div class="rounded-xl bg-base-100/60 p-3 sm:p-4">
            <h2 class="mb-3 text-sm font-bold">My Revenue · Last 7 Days</h2>
            <LineChart
                :labels="salesTrend.map((d) => d.label)"
                :values="salesTrend.map((d) => d.value)"
                :height="240"
            />
        </div>

        <!-- ==================== Two-column charts ==================== -->
        <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
            <div class="rounded-xl bg-base-100/60 p-3 sm:p-4">
                <h2 class="mb-3 text-sm font-bold">Top Services Sold Today</h2>

                <div
                    v-if="!topServicesToday.length"
                    class="flex h-55 items-center justify-center text-xs opacity-60"
                >
                    No service sales yet today.
                </div>

                <DoughnutChart
                    v-else
                    :labels="topServicesToday.map((s) => s.label)"
                    :values="topServicesToday.map((s) => s.value)"
                    :height="240"
                />
            </div>

            <div class="rounded-xl bg-base-100/60 p-3 sm:p-4">
                <h2 class="mb-3 text-sm font-bold">
                    Payment Method Split Today
                </h2>

                <div
                    v-if="!paymentSplit.length"
                    class="flex h-55 items-center justify-center text-xs opacity-60"
                >
                    No sales recorded yet today.
                </div>

                <DoughnutChart
                    v-else
                    :labels="paymentSplit.map((s) => s.label)"
                    :values="paymentSplit.map((s) => s.value)"
                    :height="240"
                />
            </div>
        </div>

        <!-- ==================== Inventory + Services summary ==================== -->
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="rounded-xl bg-base-100/60 p-3 sm:p-4">
                <span class="text-[10px] uppercase opacity-60">Products</span>
                <p class="mt-1 text-xl font-bold sm:text-2xl">
                    {{ inventoryStats.total_products }}
                </p>
                <p class="text-[10px] opacity-60">
                    {{ inventoryStats.in_stock }} in stock
                </p>
            </div>

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
                <span class="text-[10px] uppercase opacity-60">Services</span>
                <p class="mt-1 text-xl font-bold sm:text-2xl">
                    {{ serviceStats.total }}
                </p>
                <p class="text-[10px] opacity-60">
                    Avg {{ money(serviceStats.avg_price) }}
                </p>
            </div>
        </div>
    </div>
</template>
