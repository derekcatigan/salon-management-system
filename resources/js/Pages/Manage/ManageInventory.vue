<!-- resources/js/Pages/Manage/ManageInventory.vue -->
<script setup>
import { ref, computed } from "vue";
import Layout from "@/Layouts/Layout.vue";

defineOptions({
    layout: Layout,
});

/* ------------------------------------------------------------------ */
/* Dummy data                                                          */
/* ------------------------------------------------------------------ */
const products = ref([
    {
        id: "1",
        sku: "HAIR-001",
        name: "Argan Oil Shampoo",
        category: "Hair Care",
        brand: "LuxeCare",
        price: 349.0,
        cost: 210.0,
        stock: 42,
        reorder: 10,
        unit: "bottle",
        status: "active",
        updated_at: "2026-09-28",
    },
    {
        id: "2",
        sku: "HAIR-002",
        name: "Keratin Repair Conditioner",
        category: "Hair Care",
        brand: "LuxeCare",
        price: 379.0,
        cost: 235.0,
        stock: 8,
        reorder: 10,
        unit: "bottle",
        status: "active",
        updated_at: "2026-09-28",
    },
    {
        id: "3",
        sku: "SKIN-001",
        name: "Vitamin C Facial Serum",
        category: "Skin Care",
        brand: "GlowLab",
        price: 599.0,
        cost: 380.0,
        stock: 25,
        reorder: 8,
        unit: "bottle",
        status: "active",
        updated_at: "2026-09-27",
    },
    {
        id: "4",
        sku: "NAIL-001",
        name: "Gel Polish - Rouge Red",
        category: "Nail Care",
        brand: "ChromaNails",
        price: 129.0,
        cost: 65.0,
        stock: 0,
        reorder: 15,
        unit: "bottle",
        status: "out-of-stock",
        updated_at: "2026-09-26",
    },
    {
        id: "5",
        sku: "NAIL-002",
        name: "Nail Strengthener",
        category: "Nail Care",
        brand: "ChromaNails",
        price: 189.0,
        cost: 95.0,
        stock: 33,
        reorder: 10,
        unit: "bottle",
        status: "active",
        updated_at: "2026-09-25",
    },
    {
        id: "6",
        sku: "TOOL-001",
        name: 'Ceramic Flat Iron 1"',
        category: "Tools",
        brand: "ProStyle",
        price: 1499.0,
        cost: 950.0,
        stock: 5,
        reorder: 5,
        unit: "pc",
        status: "active",
        updated_at: "2026-09-24",
    },
    {
        id: "7",
        sku: "SPA-001",
        name: "Lavender Massage Oil",
        category: "Spa",
        brand: "AromaBliss",
        price: 459.0,
        cost: 270.0,
        stock: 18,
        reorder: 10,
        unit: "bottle",
        status: "active",
        updated_at: "2026-09-23",
    },
    {
        id: "8",
        sku: "SPA-002",
        name: "Eucalyptus Body Scrub",
        category: "Spa",
        brand: "AromaBliss",
        price: 399.0,
        cost: 240.0,
        stock: 6,
        reorder: 10,
        unit: "jar",
        status: "active",
        updated_at: "2026-09-22",
    },
]);

const categories = [
    "All",
    "Hair Care",
    "Skin Care",
    "Nail Care",
    "Spa",
    "Tools",
];

/* ------------------------------------------------------------------ */
/* State                                                               */
/* ------------------------------------------------------------------ */
const search = ref("");
const categoryFilter = ref("All");
const stockFilter = ref("all"); // all | low | out | in
const sortBy = ref("name"); // name | stock | price | updated
const sortDir = ref("asc");
const view = ref("table"); // table | grid

/* ------------------------------------------------------------------ */
/* Derived                                                             */
/* ------------------------------------------------------------------ */
const filteredProducts = computed(() => {
    let list = [...products.value];

    // Search
    const q = search.value.trim().toLowerCase();
    if (q) {
        list = list.filter(
            (p) =>
                p.name.toLowerCase().includes(q) ||
                p.sku.toLowerCase().includes(q) ||
                p.brand.toLowerCase().includes(q) ||
                p.category.toLowerCase().includes(q),
        );
    }

    // Category
    if (categoryFilter.value !== "All") {
        list = list.filter((p) => p.category === categoryFilter.value);
    }

    // Stock
    if (stockFilter.value === "low") {
        list = list.filter((p) => p.stock > 0 && p.stock <= p.reorder);
    } else if (stockFilter.value === "out") {
        list = list.filter((p) => p.stock === 0);
    } else if (stockFilter.value === "in") {
        list = list.filter((p) => p.stock > p.reorder);
    }

    // Sort
    const dir = sortDir.value === "asc" ? 1 : -1;
    list.sort((a, b) => {
        let av, bv;
        switch (sortBy.value) {
            case "stock":
                av = a.stock;
                bv = b.stock;
                break;
            case "price":
                av = a.price;
                bv = b.price;
                break;
            case "updated":
                av = a.updated_at;
                bv = b.updated_at;
                break;
            default:
                av = a.name.toLowerCase();
                bv = b.name.toLowerCase();
        }
        if (av < bv) return -1 * dir;
        if (av > bv) return 1 * dir;
        return 0;
    });

    return list;
});

const stats = computed(() => {
    const all = products.value;
    return {
        total: all.length,
        low: all.filter((p) => p.stock > 0 && p.stock <= p.reorder).length,
        out: all.filter((p) => p.stock === 0).length,
        value: all.reduce((sum, p) => sum + p.cost * p.stock, 0),
    };
});

/* ------------------------------------------------------------------ */
/* Helpers                                                             */
/* ------------------------------------------------------------------ */
function money(n) {
    return (
        "₱" +
        Number(n).toLocaleString("en-PH", {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        })
    );
}

function stockBadge(p) {
    if (p.stock === 0) return { label: "Out of stock", class: "badge-error" };
    if (p.stock <= p.reorder)
        return { label: "Low stock", class: "badge-warning" };
    return { label: "In stock", class: "badge-success" };
}

function toggleSort(field) {
    if (sortBy.value === field) {
        sortDir.value = sortDir.value === "asc" ? "desc" : "asc";
    } else {
        sortBy.value = field;
        sortDir.value = "asc";
    }
}
</script>

<template>
    <div class="mx-auto max-w-7xl space-y-4 p-3 sm:p-5 lg:p-6">
        <!-- ==================== Page header ==================== -->
        <div
            class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1 class="text-lg font-bold sm:text-xl">
                    Inventory Management
                </h1>
                <p class="text-xs opacity-60">
                    Track, add, and manage your salon products.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button type="button" class="btn btn-xs sm:btn-sm btn-outline">
                    <svg
                        class="h-3.5 w-3.5"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"
                        />
                    </svg>
                    Export
                </button>

                <button
                    type="button"
                    class="btn btn-xs sm:btn-sm btn-secondary"
                >
                    <svg
                        class="h-3.5 w-3.5"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path d="M12 5v14M5 12h14" />
                    </svg>
                    Add Product
                </button>
            </div>
        </div>

        <!-- ==================== Stats cards ==================== -->
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="rounded-xl bg-base-100/60 p-3 sm:p-4">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-medium uppercase opacity-60"
                        >Total Products</span
                    >
                    <svg
                        class="h-4 w-4 opacity-40"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            d="m7.5 4.27 9 5.15M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"
                        />
                        <path d="m3.3 7 8.7 5 8.7-5M12 22V12" />
                    </svg>
                </div>
                <p class="mt-1 text-xl font-bold sm:text-2xl">
                    {{ stats.total }}
                </p>
            </div>

            <div class="rounded-xl bg-base-100/60 p-3 sm:p-4">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-medium uppercase opacity-60"
                        >Low Stock</span
                    >
                    <svg
                        class="h-4 w-4 text-warning"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            d="M12 9v4M12 17h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"
                        />
                    </svg>
                </div>
                <p class="mt-1 text-xl font-bold text-warning sm:text-2xl">
                    {{ stats.low }}
                </p>
            </div>

            <div class="rounded-xl bg-base-100/60 p-3 sm:p-4">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-medium uppercase opacity-60"
                        >Out of Stock</span
                    >
                    <svg
                        class="h-4 w-4 text-error"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <circle cx="12" cy="12" r="10" />
                        <path d="m15 9-6 6M9 9l6 6" />
                    </svg>
                </div>
                <p class="mt-1 text-xl font-bold text-error sm:text-2xl">
                    {{ stats.out }}
                </p>
            </div>

            <div class="rounded-xl bg-base-100/60 p-3 sm:p-4">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-medium uppercase opacity-60"
                        >Inventory Value</span
                    >
                    <svg
                        class="h-4 w-4 opacity-40"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"
                        />
                    </svg>
                </div>
                <p class="mt-1 text-xl font-bold sm:text-2xl">
                    {{ money(stats.value) }}
                </p>
            </div>
        </div>

        <!-- ==================== Filters / toolbar ==================== -->
        <div class="rounded-xl bg-base-100/60 p-3 sm:p-4">
            <div
                class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between"
            >
                <!-- Search -->
                <div class="flex w-full items-center gap-2 lg:max-w-md">
                    <input
                        v-model="search"
                        type="text"
                        class="input input-sm input-bordered w-full text-xs"
                        placeholder="Search by name, SKU, brand…"
                    />
                </div>

                <!-- Filter row -->
                <div class="flex flex-wrap items-center gap-2">
                    <!-- Category -->
                    <select
                        v-model="categoryFilter"
                        class="select select-sm select-bordered text-xs"
                    >
                        <option v-for="c in categories" :key="c" :value="c">
                            {{ c }}
                        </option>
                    </select>

                    <!-- Stock -->
                    <select
                        v-model="stockFilter"
                        class="select select-sm select-bordered text-xs"
                    >
                        <option value="all">All Stock</option>
                        <option value="in">In Stock</option>
                        <option value="low">Low Stock</option>
                        <option value="out">Out of Stock</option>
                    </select>

                    <!-- View toggle -->
                    <div class="join">
                        <button
                            type="button"
                            class="btn btn-xs sm:btn-sm join-item"
                            :class="
                                view === 'table' ? 'btn-neutral' : 'btn-ghost'
                            "
                            @click="view = 'table'"
                        >
                            <svg
                                class="h-3.5 w-3.5"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path d="M3 6h18M3 12h18M3 18h18" />
                            </svg>
                        </button>
                        <button
                            type="button"
                            class="btn btn-xs sm:btn-sm join-item"
                            :class="
                                view === 'grid' ? 'btn-neutral' : 'btn-ghost'
                            "
                            @click="view = 'grid'"
                        >
                            <svg
                                class="h-3.5 w-3.5"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <rect x="3" y="3" width="7" height="7" />
                                <rect x="14" y="3" width="7" height="7" />
                                <rect x="3" y="14" width="7" height="7" />
                                <rect x="14" y="14" width="7" height="7" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== Empty state ==================== -->
        <div
            v-if="!filteredProducts.length"
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
                    d="m7.5 4.27 9 5.15M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"
                />
                <path d="m3.3 7 8.7 5 8.7-5M12 22V12" />
            </svg>
            <p class="mt-3 text-sm font-medium">No products found</p>
            <p class="mt-1 text-xs opacity-60">
                Try adjusting your search or filters.
            </p>
        </div>

        <!-- ==================== Table view ==================== -->
        <div
            v-else-if="view === 'table'"
            class="overflow-hidden rounded-xl bg-base-100/60"
        >
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr class="text-[11px] uppercase opacity-70">
                            <th
                                class="cursor-pointer select-none"
                                @click="toggleSort('name')"
                            >
                                <div class="flex items-center gap-1">
                                    Product
                                    <span
                                        v-if="sortBy === 'name'"
                                        class="text-primary"
                                        >{{
                                            sortDir === "asc" ? "▲" : "▼"
                                        }}</span
                                    >
                                </div>
                            </th>
                            <th class="hidden md:table-cell">Category</th>
                            <th class="hidden lg:table-cell">Brand</th>
                            <th
                                class="cursor-pointer select-none text-right"
                                @click="toggleSort('price')"
                            >
                                <div
                                    class="flex items-center justify-end gap-1"
                                >
                                    Price
                                    <span
                                        v-if="sortBy === 'price'"
                                        class="text-primary"
                                        >{{
                                            sortDir === "asc" ? "▲" : "▼"
                                        }}</span
                                    >
                                </div>
                            </th>
                            <th
                                class="cursor-pointer select-none text-center"
                                @click="toggleSort('stock')"
                            >
                                <div
                                    class="flex items-center justify-center gap-1"
                                >
                                    Stock
                                    <span
                                        v-if="sortBy === 'stock'"
                                        class="text-primary"
                                        >{{
                                            sortDir === "asc" ? "▲" : "▼"
                                        }}</span
                                    >
                                </div>
                            </th>
                            <th class="hidden sm:table-cell text-center">
                                Status
                            </th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="p in filteredProducts"
                            :key="p.id"
                            class="hover:bg-base-200/40"
                        >
                            <td>
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-xs font-semibold text-primary"
                                    >
                                        {{ p.name.charAt(0) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate text-xs font-medium">
                                            {{ p.name }}
                                        </p>
                                        <p
                                            class="truncate text-[10px] opacity-60"
                                        >
                                            {{ p.sku }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="hidden md:table-cell">
                                <span class="badge badge-ghost badge-sm">
                                    {{ p.category }}
                                </span>
                            </td>
                            <td class="hidden lg:table-cell text-xs">
                                {{ p.brand }}
                            </td>
                            <td class="text-right text-xs font-medium">
                                {{ money(p.price) }}
                            </td>
                            <td class="text-center">
                                <div
                                    class="inline-flex flex-col items-center gap-1"
                                >
                                    <span class="text-xs font-semibold">
                                        {{ p.stock }} {{ p.unit }}
                                    </span>
                                    <div
                                        class="h-1 w-16 overflow-hidden rounded-full bg-base-300"
                                    >
                                        <div
                                            class="h-full rounded-full transition-all"
                                            :class="
                                                p.stock === 0
                                                    ? 'bg-error'
                                                    : p.stock <= p.reorder
                                                      ? 'bg-warning'
                                                      : 'bg-success'
                                            "
                                            :style="{
                                                width:
                                                    Math.min(
                                                        100,
                                                        (p.stock /
                                                            Math.max(
                                                                p.reorder * 5,
                                                                1,
                                                            )) *
                                                            100,
                                                    ) + '%',
                                            }"
                                        ></div>
                                    </div>
                                </div>
                            </td>
                            <td class="hidden sm:table-cell text-center">
                                <span
                                    class="badge badge-sm"
                                    :class="stockBadge(p).class"
                                >
                                    {{ stockBadge(p).label }}
                                </span>
                            </td>
                            <td>
                                <div class="flex justify-end gap-1">
                                    <button
                                        type="button"
                                        class="btn btn-xs btn-ghost"
                                        title="View"
                                    >
                                        <svg
                                            class="h-3.5 w-3.5"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path
                                                d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"
                                            />
                                            <circle cx="12" cy="12" r="3" />
                                        </svg>
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-xs btn-ghost"
                                        title="Edit"
                                    >
                                        <svg
                                            class="h-3.5 w-3.5"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path
                                                d="M12 20h9M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"
                                            />
                                        </svg>
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-xs btn-ghost text-error"
                                        title="Delete"
                                    >
                                        <svg
                                            class="h-3.5 w-3.5"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path
                                                d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"
                                            />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ==================== Grid view ==================== -->
        <div
            v-else
            class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
        >
            <div
                v-for="p in filteredProducts"
                :key="p.id"
                class="flex flex-col rounded-xl bg-base-100/60 p-3 sm:p-4"
            >
                <div class="flex items-start justify-between gap-2">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-sm font-semibold text-primary"
                    >
                        {{ p.name.charAt(0) }}
                    </div>
                    <span class="badge badge-sm" :class="stockBadge(p).class">
                        {{ stockBadge(p).label }}
                    </span>
                </div>

                <div class="mt-3 min-w-0 flex-1">
                    <h3 class="truncate text-sm font-semibold">
                        {{ p.name }}
                    </h3>
                    <p class="mt-0.5 truncate text-[10px] opacity-60">
                        {{ p.sku }} · {{ p.brand }}
                    </p>
                    <span class="mt-2 inline-block badge badge-ghost badge-sm">
                        {{ p.category }}
                    </span>
                </div>

                <div class="mt-3 flex items-end justify-between gap-2">
                    <div>
                        <p class="text-[10px] uppercase opacity-60">Price</p>
                        <p class="text-sm font-bold">{{ money(p.price) }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] uppercase opacity-60">Stock</p>
                        <p class="text-sm font-semibold">
                            {{ p.stock }} {{ p.unit }}
                        </p>
                    </div>
                </div>

                <div class="mt-3 border-t border-base-200/70 pt-3">
                    <div class="flex justify-end gap-1">
                        <button
                            type="button"
                            class="btn btn-xs btn-ghost"
                            title="View"
                        >
                            <svg
                                class="h-3.5 w-3.5"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"
                                />
                                <circle cx="12" cy="12" r="3" />
                            </svg>
                        </button>
                        <button
                            type="button"
                            class="btn btn-xs btn-ghost"
                            title="Edit"
                        >
                            <svg
                                class="h-3.5 w-3.5"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    d="M12 20h9M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"
                                />
                            </svg>
                        </button>
                        <button
                            type="button"
                            class="btn btn-xs btn-ghost text-error"
                            title="Delete"
                        >
                            <svg
                                class="h-3.5 w-3.5"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"
                                />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== Footer / pagination stub ==================== -->
        <div
            v-if="filteredProducts.length"
            class="flex flex-col items-center justify-between gap-3 rounded-xl bg-base-100/60 p-3 text-xs sm:flex-row sm:p-4"
        >
            <p class="opacity-60">
                Showing
                <span class="font-medium">{{ filteredProducts.length }}</span>
                of
                <span class="font-medium">{{ products.length }}</span>
                products
            </p>

            <div class="join">
                <button class="btn btn-xs sm:btn-sm join-item" disabled>
                    «
                </button>
                <button class="btn btn-xs sm:btn-sm join-item btn-active">
                    1
                </button>
                <button class="btn btn-xs sm:btn-sm join-item">»</button>
            </div>
        </div>
    </div>
</template>
