<!-- resources/js/Pages/Teller/TellerReport.vue -->
<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import Layout from "@/Layouts/Layout.vue";

defineOptions({ layout: Layout });

const props = defineProps({
    dates: { type: Array, required: true },
    filters: { type: Object, required: true },
});

/* ------------------------------------------------------------------ */
/* Filters                                                             */
/* ------------------------------------------------------------------ */
const from = ref(props.filters.from ?? "");
const to = ref(props.filters.to ?? "");

function applyFilters() {
    router.get(
        route("teller.report.index"),
        {
            from: from.value || undefined,
            to: to.value || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function clearFilters() {
    from.value = "";
    to.value = "";
    applyFilters();
}

/* ------------------------------------------------------------------ */
/* Summary modal                                                       */
/* ------------------------------------------------------------------ */
const showSummary = ref(false);
const loadingSummary = ref(false);
const summaryData = ref(null);
const summaryDate = ref(null);

async function openSummary(businessDate) {
    const dateOnly = String(businessDate).split(" ")[0].split("T")[0];

    summaryDate.value = dateOnly;
    showSummary.value = true;
    loadingSummary.value = true;
    summaryData.value = null;

    try {
        const res = await fetch(route("teller.report.show", dateOnly), {
            headers: { Accept: "application/json" },
            credentials: "same-origin",
        });

        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        summaryData.value = await res.json();
    } catch (e) {
        toast.error("Failed to load summary.");
        showSummary.value = false;
    } finally {
        loadingSummary.value = false;
    }
}

function closeSummary() {
    showSummary.value = false;
    summaryData.value = null;
    summaryDate.value = null;
}

function printSummary() {
    if (!summaryDate.value) return;

    const dateOnly = String(summaryDate.value).split(" ")[0].split("T")[0];

    window.open(
        route("teller.report.print", dateOnly),
        "_blank",
        "width=800,height=900,noopener,noreferrer",
    );
}

/* ------------------------------------------------------------------ */
/* Helpers                                                             */
/* ------------------------------------------------------------------ */
function money(n) {
    return (
        "₱" +
        Number(n || 0).toLocaleString("en-PH", {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        })
    );
}

function formatDate(dateStr) {
    if (!dateStr) return "—";
    const dateOnly = String(dateStr).split(" ")[0].split("T")[0];

    const d = new Date(dateOnly + "T00:00:00");
    if (isNaN(d.getTime())) return "—";

    return d.toLocaleDateString("en-PH", {
        weekday: "short",
        year: "numeric",
        month: "short",
        day: "numeric",
    });
}
</script>

<template>
    <div class="mx-auto max-w-6xl space-y-4 p-3 sm:p-5 lg:p-6">
        <!-- Header -->
        <div>
            <h1 class="text-lg font-bold sm:text-xl">Teller Report</h1>
            <p class="text-xs opacity-60">
                View your shift summaries and cash reports.
            </p>
        </div>

        <!-- Filters -->
        <div class="rounded-xl bg-base-100/60 p-3 sm:p-4">
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"
            >
                <div class="flex flex-1 flex-col gap-3 sm:flex-row">
                    <div class="form-control flex-1">
                        <label class="label py-0.5"
                            ><span class="label-text text-xs">From</span></label
                        >
                        <input
                            v-model="from"
                            type="date"
                            class="input input-sm input-bordered w-full text-xs"
                        />
                    </div>

                    <div class="form-control flex-1">
                        <label class="label py-0.5"
                            ><span class="label-text text-xs">To</span></label
                        >
                        <input
                            v-model="to"
                            type="date"
                            class="input input-sm input-bordered w-full text-xs"
                        />
                    </div>
                </div>

                <div class="flex gap-2">
                    <button
                        type="button"
                        class="btn btn-xs sm:btn-sm btn-ghost"
                        @click="clearFilters"
                    >
                        Clear
                    </button>
                    <button
                        type="button"
                        class="btn btn-xs sm:btn-sm btn-primary"
                        @click="applyFilters"
                    >
                        Filter
                    </button>
                </div>
            </div>
        </div>

        <!-- Empty state -->
        <div
            v-if="!dates.length"
            class="rounded-xl bg-base-100/60 p-10 text-center"
        >
            <svg
                class="mx-auto h-10 w-10 opacity-30"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.5"
            >
                <path
                    d="M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"
                />
            </svg>
            <p class="mt-3 text-sm font-medium">No shifts found</p>
            <p class="mt-1 text-xs opacity-60">
                Try adjusting your date range.
            </p>
        </div>

        <!-- Dates table -->
        <div v-else class="overflow-hidden rounded-xl bg-base-100/60">
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr class="text-[11px] uppercase opacity-70">
                            <th>Business Date</th>
                            <th class="text-center">Transactions</th>
                            <th class="text-right">Revenue</th>
                            <th class="text-right hidden md:table-cell">
                                Beginning
                            </th>
                            <th class="text-right hidden md:table-cell">
                                Cash In
                            </th>
                            <th class="text-right hidden md:table-cell">
                                Cash Out
                            </th>
                            <th class="text-right">Expected Drawer</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="d in dates"
                            :key="d.business_date"
                            class="hover:bg-base-200/40"
                        >
                            <td class="text-xs font-medium">
                                {{ formatDate(d.business_date) }}
                            </td>
                            <td class="text-center text-xs">
                                {{ d.sale_count }}
                            </td>
                            <td class="text-right text-xs font-semibold">
                                {{ money(d.total_revenue) }}
                            </td>
                            <td
                                class="text-right text-xs hidden md:table-cell text-info"
                            >
                                {{ money(d.cash_beginning) }}
                            </td>
                            <td
                                class="text-right text-xs hidden md:table-cell text-success"
                            >
                                {{ money(d.cash_in) }}
                            </td>
                            <td
                                class="text-right text-xs hidden md:table-cell text-error"
                            >
                                {{ money(d.cash_out) }}
                            </td>
                            <td
                                class="text-right text-xs font-semibold text-primary"
                            >
                                {{ money(d.expected_drawer) }}
                            </td>
                            <td>
                                <div class="flex justify-end">
                                    <button
                                        type="button"
                                        class="btn btn-xs btn-outline"
                                        @click="openSummary(d.business_date)"
                                    >
                                        View
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- =============================== Summary Modal =============================== -->
    <dialog class="modal" :class="{ 'modal-open': showSummary }">
        <div
            class="modal-box w-11/12 max-w-2xl p-4 sm:p-6 max-h-[90vh] overflow-y-auto"
        >
            <div class="flex items-center justify-between mb-1">
                <h3 class="text-sm font-bold">
                    Shift Summary
                    <span class="text-xs opacity-60 font-normal">
                        · {{ formatDate(summaryDate) }}
                    </span>
                </h3>
                <button
                    type="button"
                    class="btn btn-xs btn-circle btn-ghost"
                    @click="closeSummary"
                >
                    ✕
                </button>
            </div>
            <div class="divider my-1"></div>

            <div v-if="loadingSummary" class="flex justify-center py-12">
                <span class="loading loading-spinner loading-md"></span>
            </div>

            <div v-else-if="summaryData" class="space-y-4 text-xs">
                <!-- Sales summary -->
                <div>
                    <h4
                        class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-primary"
                    >
                        Sales
                    </h4>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                        <div class="rounded-lg bg-base-200/40 p-2">
                            <div class="text-[10px] opacity-60">
                                Transactions
                            </div>
                            <div class="text-sm font-bold">
                                {{ summaryData.sales.count }}
                            </div>
                        </div>
                        <div class="rounded-lg bg-base-200/40 p-2">
                            <div class="text-[10px] opacity-60">Subtotal</div>
                            <div class="text-sm font-bold">
                                {{ money(summaryData.sales.subtotal) }}
                            </div>
                        </div>
                        <div class="rounded-lg bg-base-200/40 p-2">
                            <div class="text-[10px] opacity-60">Discounts</div>
                            <div class="text-sm font-bold">
                                {{ money(summaryData.sales.discount_total) }}
                            </div>
                        </div>
                        <div class="rounded-lg bg-base-200/40 p-2">
                            <div class="text-[10px] opacity-60">
                                Gross Total
                            </div>
                            <div class="text-sm font-bold text-primary">
                                {{ money(summaryData.sales.gross_total) }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cash summary -->
                <div>
                    <h4
                        class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-primary"
                    >
                        Cash
                    </h4>
                    <div class="space-y-1 rounded-lg bg-base-200/40 p-2">
                        <div class="flex justify-between">
                            <span class="opacity-70">Cash Beginning</span>
                            <span class="font-semibold text-info">
                                {{ money(summaryData.cash.beginning) }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="opacity-70">Cash In</span>
                            <span class="font-semibold text-success">
                                {{ money(summaryData.cash.cash_in) }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="opacity-70">Cash Out</span>
                            <span class="font-semibold text-error">
                                {{ money(summaryData.cash.cash_out) }}
                            </span>
                        </div>
                        <div
                            class="flex justify-between border-t border-base-300 pt-1 text-sm"
                        >
                            <span class="font-semibold">Expected Drawer</span>
                            <span class="font-bold text-primary">
                                {{ money(summaryData.cash.expected_drawer) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Payment method breakdown -->
                <div v-if="summaryData.payment_breakdown.length">
                    <h4
                        class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-primary"
                    >
                        Payment Methods
                    </h4>
                    <div class="space-y-1 rounded-lg bg-base-200/40 p-2">
                        <div
                            v-for="p in summaryData.payment_breakdown"
                            :key="p.payment_method"
                            class="flex justify-between"
                        >
                            <span class="capitalize opacity-70">
                                {{ p.payment_method }} ({{ p.count }}x)
                            </span>
                            <span class="font-semibold">{{
                                money(p.total)
                            }}</span>
                        </div>
                    </div>
                </div>

                <!-- Item breakdown -->
                <div v-if="summaryData.item_breakdown.length">
                    <h4
                        class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-primary"
                    >
                        Items Sold
                    </h4>
                    <div
                        class="max-h-56 overflow-y-auto rounded-lg bg-base-200/40 p-2"
                    >
                        <div
                            v-for="(item, i) in summaryData.item_breakdown"
                            :key="i"
                            class="flex justify-between border-b border-base-300/50 py-1 last:border-0"
                        >
                            <div class="min-w-0 flex-1 pr-2">
                                <div class="truncate font-medium">
                                    {{ item.item_name }}
                                </div>
                                <div class="text-[10px] opacity-60">
                                    {{
                                        item.item_type === "service"
                                            ? "Service"
                                            : "Product"
                                    }}
                                    <span v-if="item.item_sku">
                                        · {{ item.item_sku }}</span
                                    >
                                    · {{ item.quantity_sold }} pcs
                                </div>
                            </div>
                            <div class="font-semibold">
                                {{ money(item.total_sales) }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Transactions list -->
                <div v-if="summaryData.sales.items.length">
                    <h4
                        class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-primary"
                    >
                        Transactions
                    </h4>
                    <div
                        class="max-h-56 overflow-y-auto rounded-lg bg-base-200/40 p-2"
                    >
                        <div
                            v-for="t in summaryData.sales.items"
                            :key="t.reference_no"
                            class="flex items-center justify-between border-b border-base-300/50 py-1 last:border-0"
                        >
                            <div class="min-w-0 flex-1 pr-2">
                                <div class="truncate font-medium">
                                    {{ t.reference_no }}
                                </div>
                                <div class="text-[10px] opacity-60">
                                    {{ t.created_at }} ·
                                    {{ t.payment_method }} ·
                                    {{ t.items_count }} item(s)
                                </div>
                            </div>
                            <div class="font-semibold">
                                {{ money(t.total) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-action mt-4">
                <button
                    type="button"
                    class="btn btn-xs sm:btn-sm btn-ghost"
                    @click="closeSummary"
                >
                    Close
                </button>
                <button
                    type="button"
                    class="btn btn-xs sm:btn-sm btn-primary"
                    :disabled="loadingSummary || !summaryData"
                    @click="printSummary"
                >
                    Print Report
                </button>
            </div>
        </div>

        <form method="dialog" class="modal-backdrop">
            <button @click="closeSummary">close</button>
        </form>
    </dialog>
</template>
