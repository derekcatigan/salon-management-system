<!-- resources/js/Pages/Manage/ManageInventory.vue -->
<script setup>
import { ref, computed, watch } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import Layout from "@/Layouts/Layout.vue";

defineOptions({ layout: Layout });

const props = defineProps({
    products: { type: Object, required: true }, // paginator
    stats: { type: Object, required: true },
    categories: { type: Array, required: true },
    filters: { type: Object, required: true },
});

/* ------------------------------------------------------------------ */
/* Filters (server-side)                                               */
/* ------------------------------------------------------------------ */
const search = ref(props.filters.q ?? "");
const category = ref(props.filters.category ?? "All");
const stockFilter = ref(props.filters.stock ?? "all");
const sort = ref(props.filters.sort ?? "name");
const dir = ref(props.filters.dir ?? "asc");

let debounce = null;
function applyFilters() {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
        router.get(
            route("manage.inventory"),
            {
                q: search.value || undefined,
                category: category.value !== "All" ? category.value : undefined,
                stock:
                    stockFilter.value !== "all" ? stockFilter.value : undefined,
                sort: sort.value,
                dir: dir.value,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }, 300);
}

watch([search, category, stockFilter], applyFilters);

function toggleSort(field) {
    if (sort.value === field) {
        dir.value = dir.value === "asc" ? "desc" : "asc";
    } else {
        sort.value = field;
        dir.value = "asc";
    }
    applyFilters();
}

/* ------------------------------------------------------------------ */
/* Create / Edit modal                                                 */
/* ------------------------------------------------------------------ */
const showFormModal = ref(false);
const editingProduct = ref(null);
const processing = ref(false);

const form = useForm({
    sku: "",
    name: "",
    category: "",
    brand: "",
    price: 0,
    cost: 0,
    stock: 0,
    reorder_level: 10,
    unit: "pc",
    description: "",
});

const isEditing = computed(() => !!editingProduct.value);

function openCreateModal() {
    editingProduct.value = null;
    form.reset();
    form.clearErrors();
    showFormModal.value = true;
}

function openEditModal(product) {
    editingProduct.value = product;
    form.sku = product.sku;
    form.name = product.name;
    form.category = product.category;
    form.brand = product.brand ?? "";
    form.price = product.price;
    form.cost = product.cost;
    form.stock = product.stock;
    form.reorder_level = product.reorder_level;
    form.unit = product.unit;
    form.description = product.description ?? "";
    form.clearErrors();
    showFormModal.value = true;
}

function closeFormModal() {
    showFormModal.value = false;
    editingProduct.value = null;
    form.reset();
    form.clearErrors();
}

function submitForm() {
    if (isEditing.value) {
        form.put(route("manage.inventory.update", editingProduct.value.id), {
            preserveScroll: true,
            onSuccess: () => closeFormModal(),
            onError: (errors) =>
                Object.values(errors).forEach((e) => toast.error(e)),
        });
    } else {
        form.post(route("manage.inventory.store"), {
            preserveScroll: true,
            onSuccess: () => closeFormModal(),
            onError: (errors) =>
                Object.values(errors).forEach((e) => toast.error(e)),
        });
    }
}

/* ------------------------------------------------------------------ */
/* Delete modal                                                        */
/* ------------------------------------------------------------------ */
const showDeleteModal = ref(false);
const deletingProduct = ref(null);
const deleting = ref(false);

function openDeleteModal(product) {
    deletingProduct.value = product;
    showDeleteModal.value = true;
}

function closeDeleteModal() {
    showDeleteModal.value = false;
    deletingProduct.value = null;
}

function confirmDelete() {
    if (!deletingProduct.value) return;

    deleting.value = true;

    router.delete(route("manage.inventory.destroy", deletingProduct.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeDeleteModal();
            toast.success("Product deleted.");
        },
        onError: () => toast.error("Failed to delete product."),
        onFinish: () => (deleting.value = false),
    });
}

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
    if (p.stock === 0) return { label: "Out of Stock", class: "badge-error" };
    if (p.stock <= p.reorder_level)
        return { label: "Low Stock", class: "badge-warning" };
    return { label: "In Stock", class: "badge-success" };
}

function stockBarColor(p) {
    if (p.stock === 0) return "bg-error";
    if (p.stock <= p.reorder_level) return "bg-warning";
    return "bg-success";
}

function stockBarWidth(p) {
    const max = Math.max(p.reorder_level * 5, 1);
    return Math.min(100, (p.stock / max) * 100) + "%";
}

/* ------------------------------------------------------------------ */
/* Pagination links with ellipsis                                      */
/* ------------------------------------------------------------------ */
const paginationLinks = computed(() => {
    const links = props.products.links ?? [];
    // Laravel gives us: [Previous, 1, 2, ..., 8, Next]
    // Its default rendering collapses with "...". We just style it.
    return links;
});
</script>

<template>
    <div class="mx-auto max-w-7xl space-y-4 p-3 sm:p-5 lg:p-6">
        <!-- ============================ Header ============================ -->
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
                    @click="openCreateModal"
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

        <!-- ============================ Stats ============================ -->
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
                        >Total Value</span
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

        <!-- ============================ Toolbar ============================ -->
        <div class="rounded-xl bg-base-100/60 p-3 sm:p-4">
            <div
                class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between"
            >
                <div class="flex w-full items-center gap-2 lg:max-w-md">
                    <input
                        v-model="search"
                        type="text"
                        class="input input-sm input-bordered w-full text-xs"
                        placeholder="Search by name, SKU, brand…"
                    />
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <select
                        v-model="category"
                        class="select select-sm select-bordered text-xs"
                    >
                        <option v-for="c in categories" :key="c" :value="c">
                            {{ c }}
                        </option>
                    </select>

                    <select
                        v-model="stockFilter"
                        class="select select-sm select-bordered text-xs"
                    >
                        <option value="all">All Stock</option>
                        <option value="in">In Stock</option>
                        <option value="low">Low Stock</option>
                        <option value="out">Out of Stock</option>
                    </select>

                    <select
                        v-model="sort"
                        class="select select-sm select-bordered text-xs"
                        @change="applyFilters"
                    >
                        <option value="name">Sort: Name</option>
                        <option value="sku">Sort: SKU</option>
                        <option value="price">Sort: Price</option>
                        <option value="stock">Sort: Stock</option>
                        <option value="created_at">Sort: Newest</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- ============================ Empty state ============================ -->
        <div
            v-if="!products.data.length"
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

        <!-- ============================ Grid ============================ -->
        <div
            v-else
            class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
        >
            <div
                v-for="p in products.data"
                :key="p.id"
                class="flex flex-col rounded-xl bg-base-100/60 p-3 sm:p-4 transition hover:shadow-md"
            >
                <div class="flex items-start justify-between gap-2">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-sm font-semibold text-primary"
                    >
                        {{ p.name.charAt(0).toUpperCase() }}
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
                        {{ p.sku }}
                        <span v-if="p.brand"> · {{ p.brand }}</span>
                    </p>
                    <span class="mt-2 inline-block badge badge-ghost badge-sm">
                        {{ p.category }}
                    </span>
                </div>

                <div class="mt-3">
                    <div
                        class="flex items-center justify-between text-[10px] opacity-60"
                    >
                        <span>Stock</span>
                        <span
                            >{{ p.stock }} {{ p.unit }} · reorder at
                            {{ p.reorder_level }}</span
                        >
                    </div>
                    <div
                        class="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-base-300"
                    >
                        <div
                            class="h-full rounded-full transition-all"
                            :class="stockBarColor(p)"
                            :style="{ width: stockBarWidth(p) }"
                        ></div>
                    </div>
                </div>

                <div class="mt-3 flex items-end justify-between gap-2">
                    <div>
                        <p class="text-[10px] uppercase opacity-60">Price</p>
                        <p class="text-sm font-bold">{{ money(p.price) }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] uppercase opacity-60">Cost</p>
                        <p class="text-xs">{{ money(p.cost) }}</p>
                    </div>
                </div>

                <div class="mt-3 border-t border-base-200/70 pt-3">
                    <div class="flex justify-end gap-1">
                        <button
                            type="button"
                            class="btn btn-xs btn-ghost"
                            title="Edit"
                            @click="openEditModal(p)"
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
                            Edit
                        </button>
                        <button
                            type="button"
                            class="btn btn-xs btn-ghost text-error"
                            title="Delete"
                            @click="openDeleteModal(p)"
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
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================ Pagination ============================ -->
        <div
            v-if="products.data.length"
            class="flex flex-col items-center justify-between gap-3 rounded-xl bg-base-100/60 p-3 text-xs sm:flex-row sm:p-4"
        >
            <p class="opacity-60">
                Showing
                <span class="font-medium">{{ products.from }}</span>
                to
                <span class="font-medium">{{ products.to }}</span>
                of
                <span class="font-medium">{{ products.total }}</span>
                products
            </p>

            <div class="join">
                <template v-for="(link, i) in products.links" :key="i">
                    <!-- Ellipsis -->
                    <button
                        v-if="link.label === '...'"
                        class="btn btn-xs sm:btn-sm join-item btn-disabled"
                    >
                        …
                    </button>

                    <!-- Previous / Next -->
                    <button
                        v-else-if="i === 0"
                        class="btn btn-xs sm:btn-sm join-item"
                        :class="{ 'btn-disabled': !link.url }"
                        :disabled="!link.url"
                        @click="
                            link.url &&
                            router.visit(link.url, { preserveScroll: true })
                        "
                    >
                        «
                    </button>
                    <button
                        v-else-if="i === products.links.length - 1"
                        class="btn btn-xs sm:btn-sm join-item"
                        :class="{ 'btn-disabled': !link.url }"
                        :disabled="!link.url"
                        @click="
                            link.url &&
                            router.visit(link.url, { preserveScroll: true })
                        "
                    >
                        »
                    </button>

                    <!-- Numbered pages -->
                    <button
                        v-else
                        class="btn btn-xs sm:btn-sm join-item"
                        :class="{ 'btn-active': link.active }"
                        :disabled="!link.url"
                        @click="
                            link.url &&
                            router.visit(link.url, { preserveScroll: true })
                        "
                    >
                        {{ link.label }}
                    </button>
                </template>
            </div>
        </div>
    </div>

    <!-- ============================ Create / Edit Modal ============================ -->
    <dialog class="modal" :class="{ 'modal-open': showFormModal }">
        <div
            class="modal-box w-11/12 max-w-2xl p-4 sm:p-6 max-h-[90vh] overflow-y-auto"
        >
            <div class="flex items-center justify-between mb-1">
                <h3 class="text-sm font-bold flex items-center gap-2">
                    <span
                        class="badge badge-xs"
                        :class="isEditing ? 'badge-primary' : 'badge-secondary'"
                    ></span>
                    {{ isEditing ? "Edit Product" : "Add New Product" }}
                </h3>
                <button
                    type="button"
                    class="btn btn-xs btn-circle btn-ghost"
                    @click="closeFormModal"
                >
                    ✕
                </button>
            </div>
            <div class="divider my-1"></div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="form-control sm:col-span-2">
                    <label class="label py-0.5"
                        ><span class="label-text text-xs"
                            >Product Name</span
                        ></label
                    >
                    <input
                        v-model="form.name"
                        type="text"
                        class="input input-sm input-bordered w-full text-xs"
                        :class="{ 'input-error': form.errors.name }"
                        placeholder="e.g. Argan Oil Shampoo 250ml"
                    />
                    <label v-if="form.errors.name" class="label py-0.5">
                        <span class="label-text-alt text-[10px] text-error">{{
                            form.errors.name
                        }}</span>
                    </label>
                </div>

                <div class="form-control">
                    <label class="label py-0.5"
                        ><span class="label-text text-xs">SKU</span></label
                    >
                    <input
                        v-model="form.sku"
                        type="text"
                        class="input input-sm input-bordered w-full text-xs"
                        :class="{ 'input-error': form.errors.sku }"
                        placeholder="HAIR-001"
                    />
                    <label v-if="form.errors.sku" class="label py-0.5">
                        <span class="label-text-alt text-[10px] text-error">{{
                            form.errors.sku
                        }}</span>
                    </label>
                </div>

                <div class="form-control">
                    <label class="label py-0.5"
                        ><span class="label-text text-xs">Category</span></label
                    >
                    <select
                        v-model="form.category"
                        class="select select-sm select-bordered w-full text-xs"
                        :class="{ 'select-error': form.errors.category }"
                    >
                        <option value="" disabled>Select category</option>
                        <option
                            v-for="c in categories.filter((c) => c !== 'All')"
                            :key="c"
                            :value="c"
                        >
                            {{ c }}
                        </option>
                    </select>
                    <label v-if="form.errors.category" class="label py-0.5">
                        <span class="label-text-alt text-[10px] text-error">{{
                            form.errors.category
                        }}</span>
                    </label>
                </div>

                <div class="form-control">
                    <label class="label py-0.5"
                        ><span class="label-text text-xs">Brand</span></label
                    >
                    <input
                        v-model="form.brand"
                        type="text"
                        class="input input-sm input-bordered w-full text-xs"
                        :class="{ 'input-error': form.errors.brand }"
                        placeholder="Optional"
                    />
                </div>

                <div class="form-control">
                    <label class="label py-0.5"
                        ><span class="label-text text-xs">Unit</span></label
                    >
                    <input
                        v-model="form.unit"
                        type="text"
                        class="input input-sm input-bordered w-full text-xs"
                        :class="{ 'input-error': form.errors.unit }"
                        placeholder="pc, bottle, jar…"
                    />
                </div>

                <div class="form-control">
                    <label class="label py-0.5"
                        ><span class="label-text text-xs"
                            >Price (₱)</span
                        ></label
                    >
                    <input
                        v-model.number="form.price"
                        type="number"
                        step="0.01"
                        min="0"
                        class="input input-sm input-bordered w-full text-xs"
                        :class="{ 'input-error': form.errors.price }"
                    />
                    <label v-if="form.errors.price" class="label py-0.5">
                        <span class="label-text-alt text-[10px] text-error">{{
                            form.errors.price
                        }}</span>
                    </label>
                </div>

                <div class="form-control">
                    <label class="label py-0.5"
                        ><span class="label-text text-xs">Cost (₱)</span></label
                    >
                    <input
                        v-model.number="form.cost"
                        type="number"
                        step="0.01"
                        min="0"
                        class="input input-sm input-bordered w-full text-xs"
                        :class="{ 'input-error': form.errors.cost }"
                    />
                    <label v-if="form.errors.cost" class="label py-0.5">
                        <span class="label-text-alt text-[10px] text-error">{{
                            form.errors.cost
                        }}</span>
                    </label>
                </div>

                <div class="form-control">
                    <label class="label py-0.5"
                        ><span class="label-text text-xs">Stock</span></label
                    >
                    <input
                        v-model.number="form.stock"
                        type="number"
                        min="0"
                        class="input input-sm input-bordered w-full text-xs"
                        :class="{ 'input-error': form.errors.stock }"
                    />
                    <label v-if="form.errors.stock" class="label py-0.5">
                        <span class="label-text-alt text-[10px] text-error">{{
                            form.errors.stock
                        }}</span>
                    </label>
                </div>

                <div class="form-control">
                    <label class="label py-0.5"
                        ><span class="label-text text-xs"
                            >Reorder Level</span
                        ></label
                    >
                    <input
                        v-model.number="form.reorder_level"
                        type="number"
                        min="0"
                        class="input input-sm input-bordered w-full text-xs"
                        :class="{ 'input-error': form.errors.reorder_level }"
                    />
                    <label
                        v-if="form.errors.reorder_level"
                        class="label py-0.5"
                    >
                        <span class="label-text-alt text-[10px] text-error">{{
                            form.errors.reorder_level
                        }}</span>
                    </label>
                </div>

                <div class="form-control sm:col-span-2">
                    <label class="label py-0.5"
                        ><span class="label-text text-xs"
                            >Description</span
                        ></label
                    >
                    <textarea
                        v-model="form.description"
                        rows="2"
                        class="textarea textarea-sm textarea-bordered w-full text-xs"
                        placeholder="Optional notes about this product…"
                    ></textarea>
                </div>
            </div>

            <div class="modal-action mt-4">
                <button
                    type="button"
                    class="btn btn-xs sm:btn-sm btn-ghost"
                    @click="closeFormModal"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    class="btn btn-xs sm:btn-sm"
                    :class="isEditing ? 'btn-primary' : 'btn-secondary'"
                    :disabled="form.processing"
                    @click="submitForm"
                >
                    <span
                        v-if="form.processing"
                        class="loading loading-spinner loading-xs"
                    ></span>
                    {{ isEditing ? "Save Changes" : "Add Product" }}
                </button>
            </div>
        </div>

        <form method="dialog" class="modal-backdrop">
            <button @click="closeFormModal">close</button>
        </form>
    </dialog>

    <!-- ============================ Delete Modal ============================ -->
    <dialog class="modal" :class="{ 'modal-open': showDeleteModal }">
        <div class="modal-box w-11/12 max-w-md p-4 sm:p-6">
            <div class="flex items-start gap-3">
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-error/10 text-error"
                >
                    <svg
                        class="h-5 w-5"
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

                <div class="flex-1">
                    <h3 class="text-base font-bold">Delete this product?</h3>
                    <p class="mt-1 text-xs opacity-70">
                        You are about to permanently delete
                        <span class="font-semibold">{{
                            deletingProduct?.name
                        }}</span>
                        ({{ deletingProduct?.sku }}). This action cannot be
                        undone.
                    </p>
                </div>
            </div>

            <div class="modal-action mt-5">
                <button
                    type="button"
                    class="btn btn-xs sm:btn-sm btn-ghost"
                    :disabled="deleting"
                    @click="closeDeleteModal"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    class="btn btn-xs sm:btn-sm btn-error"
                    :disabled="deleting"
                    @click="confirmDelete"
                >
                    <span
                        v-if="deleting"
                        class="loading loading-spinner loading-xs"
                    ></span>
                    Yes, delete
                </button>
            </div>
        </div>

        <form method="dialog" class="modal-backdrop">
            <button @click="closeDeleteModal">close</button>
        </form>
    </dialog>
</template>
