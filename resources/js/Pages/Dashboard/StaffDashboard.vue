<!-- resources/js/Pages/Dashboard/StaffDashboard.vue -->
<script setup>
import Layout from "@/Layouts/Layout.vue";
import BarChart from "@/Components/Charts/BarChart.vue";
import DoughnutChart from "@/Components/Charts/DoughnutChart.vue";

defineOptions({ layout: Layout });

const props = defineProps({
    inventoryStats: { type: Object, required: true },
    serviceStats: { type: Object, required: true },
    salesStats: { type: Object, required: true },
    productsByCategory: { type: Array, required: true },
    servicesByCategory: { type: Array, required: true },
    lowStockProducts: { type: Array, required: true },
    topServices: { type: Array, required: true },
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
            <h1 class="text-lg font-bold sm:text-xl">Staff Dashboard</h1>
            <p class="text-xs opacity-60">Inventory and service overview.</p>
        </div>

        <!-- ==================== Top stat row ==================== -->
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
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
                    >Sales Today</span
                >
                <p class="mt-1 text-xl font-bold sm:text-2xl">
                    {{ salesStats.today_count }}
                </p>
                <p class="text-[10px] opacity-60">
                    {{ money(salesStats.today_revenue) }}
                </p>
            </div>

            <div class="rounded-xl bg-base-100/60 p-3 sm:p-4">
                <span class="text-[10px] uppercase opacity-60">Low Stock</span>
                <p class="mt-1 text-xl font-bold text-warning sm:text-2xl">
                    {{ inventoryStats.low_stock }}
                </p>
                <p class="text-[10px] opacity-60">
                    {{ inventoryStats.out_of_stock }} out of stock
                </p>
            </div>
        </div>

        <!-- ==================== Two-column charts ==================== -->
        <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
            <div class="rounded-xl bg-base-100/60 p-3 sm:p-4">
                <h2 class="mb-3 text-sm font-bold">Products by Category</h2>
                <DoughnutChart
                    :labels="productsByCategory.map((p) => p.label)"
                    :values="productsByCategory.map((p) => p.value)"
                    :height="240"
                />
            </div>

            <div class="rounded-xl bg-base-100/60 p-3 sm:p-4">
                <h2 class="mb-3 text-sm font-bold">Services by Category</h2>
                <DoughnutChart
                    :labels="servicesByCategory.map((s) => s.label)"
                    :values="servicesByCategory.map((s) => s.value)"
                    :height="240"
                />
            </div>
        </div>

        <!-- ==================== Top 5 Services ==================== -->
        <div class="rounded-xl bg-base-100/60 p-3 sm:p-4">
            <h2 class="mb-3 text-sm font-bold">
                Top 5 Most Expensive Services
            </h2>
            <BarChart
                :labels="topServices.map((s) => s.label)"
                :values="topServices.map((s) => s.value)"
                color="rgba(16, 185, 129, 0.7)"
                :height="240"
            />
        </div>

        <!-- ==================== Low stock alert list ==================== -->
        <div class="rounded-xl bg-base-100/60 p-3 sm:p-4">
            <h2 class="mb-3 text-sm font-bold">Low Stock Alerts</h2>

            <div
                v-if="!lowStockProducts.length"
                class="py-6 text-center text-xs opacity-60"
            >
                No low-stock products. 👍
            </div>

            <div v-else class="space-y-2">
                <div
                    v-for="p in lowStockProducts"
                    :key="p.sku"
                    class="flex items-center justify-between gap-3 rounded-lg bg-base-200/50 px-3 py-2"
                >
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-xs font-medium">{{ p.name }}</p>
                        <p class="truncate text-[10px] opacity-60">
                            {{ p.sku }}
                        </p>
                    </div>
                    <div class="text-right text-xs">
                        <p class="font-semibold text-warning">
                            {{ p.stock }} {{ p.unit }} left
                        </p>
                        <p class="text-[10px] opacity-60">
                            reorder at {{ p.reorder_level }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
