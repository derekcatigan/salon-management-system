<!-- resources/js/Pages/Manage/ManageServices.vue -->
<script setup>
import { ref, computed, watch } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import Layout from "@/Layouts/Layout.vue";

defineOptions({ layout: Layout });

const props = defineProps({
    services: { type: Object, required: true },
    stats: { type: Object, required: true },
    categories: { type: Array, required: true },
    filters: { type: Object, required: true },
});

/* ------------------------------------------------------------------ */
/* Filters (server-side)                                               */
/* ------------------------------------------------------------------ */
const search = ref(props.filters.q ?? "");
const category = ref(props.filters.category ?? "All");
const status = ref(props.filters.status ?? "all");
const sort = ref(props.filters.sort ?? "name");
const dir = ref(props.filters.dir ?? "asc");

let debounce = null;
function applyFilters() {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
        router.get(
            route("manage.service.index"),
            {
                q: search.value || undefined,
                category: category.value !== "All" ? category.value : undefined,
                status: status.value !== "all" ? status.value : undefined,
                sort: sort.value,
                dir: dir.value,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }, 300);
}

watch([search, category, status], applyFilters);

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
const editingService = ref(null);

const form = useForm({
    name: "",
    category: "",
    price: 0,
    duration_minutes: 30,
    description: "",
    status: "active",
});

const isEditing = computed(() => !!editingService.value);

function openCreateModal() {
    editingService.value = null;
    form.reset();
    form.clearErrors();
    form.status = "active";
    form.duration_minutes = 30;
    showFormModal.value = true;
}

function openEditModal(service) {
    editingService.value = service;
    form.name = service.name;
    form.category = service.category;
    form.price = service.price;
    form.duration_minutes = service.duration_minutes;
    form.description = service.description ?? "";
    form.status = service.status;
    form.clearErrors();
    showFormModal.value = true;
}

function closeFormModal() {
    showFormModal.value = false;
    editingService.value = null;
    form.reset();
    form.clearErrors();
}

function submitForm() {
    if (isEditing.value) {
        form.put(route("manage.service.update", editingService.value.id), {
            preserveScroll: true,
            onSuccess: () => closeFormModal(),
            onError: (errors) =>
                Object.values(errors).forEach((e) => toast.error(e)),
        });
    } else {
        form.post(route("manage.service.store"), {
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
const deletingService = ref(null);
const deleting = ref(false);

function openDeleteModal(service) {
    deletingService.value = service;
    showDeleteModal.value = true;
}

function closeDeleteModal() {
    showDeleteModal.value = false;
    deletingService.value = null;
}

function confirmDelete() {
    if (!deletingService.value) return;

    deleting.value = true;

    router.delete(route("manage.service.destroy", deletingService.value.id), {
        preserveScroll: true,
        onSuccess: () => closeDeleteModal(),
        onError: () => toast.error("Failed to delete service."),
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

function formatDuration(minutes) {
    if (minutes < 60) return `${minutes} min`;
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;
    return m === 0 ? `${h}h` : `${h}h ${m}m`;
}
</script>

<template>
    <div class="mx-auto max-w-7xl space-y-4 p-3 sm:p-5 lg:p-6">
        <!-- Header -->
        <div
            class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1 class="text-lg font-bold sm:text-xl">Salon Services</h1>
                <p class="text-xs opacity-60">
                    Manage the services your salon offers and their prices.
                </p>
            </div>

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
                Add Service
            </button>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="rounded-xl bg-base-100/60 p-3 sm:p-4">
                <span class="text-[10px] font-medium uppercase opacity-60"
                    >Total Services</span
                >
                <p class="mt-1 text-xl font-bold sm:text-2xl">
                    {{ stats.total }}
                </p>
            </div>

            <div class="rounded-xl bg-base-100/60 p-3 sm:p-4">
                <span class="text-[10px] font-medium uppercase opacity-60"
                    >Active</span
                >
                <p class="mt-1 text-xl font-bold text-success sm:text-2xl">
                    {{ stats.active }}
                </p>
            </div>

            <div class="rounded-xl bg-base-100/60 p-3 sm:p-4">
                <span class="text-[10px] font-medium uppercase opacity-60"
                    >Inactive</span
                >
                <p class="mt-1 text-xl font-bold text-warning sm:text-2xl">
                    {{ stats.inactive }}
                </p>
            </div>

            <div class="rounded-xl bg-base-100/60 p-3 sm:p-4">
                <span class="text-[10px] font-medium uppercase opacity-60"
                    >Average Price</span
                >
                <p class="mt-1 text-xl font-bold sm:text-2xl">
                    {{ money(stats.avg_price) }}
                </p>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="rounded-xl bg-base-100/60 p-3 sm:p-4">
            <div
                class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between"
            >
                <div class="flex w-full items-center gap-2 lg:max-w-md">
                    <input
                        v-model="search"
                        type="text"
                        class="input input-sm input-bordered w-full text-xs"
                        placeholder="Search services…"
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
                        v-model="status"
                        class="select select-sm select-bordered text-xs"
                    >
                        <option value="all">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>

                    <select
                        v-model="sort"
                        class="select select-sm select-bordered text-xs"
                        @change="applyFilters"
                    >
                        <option value="name">Sort: Name</option>
                        <option value="category">Sort: Category</option>
                        <option value="price">Sort: Price</option>
                        <option value="duration_minutes">Sort: Duration</option>
                        <option value="created_at">Sort: Newest</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Empty state -->
        <div
            v-if="!services.data.length"
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
                    d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"
                />
            </svg>
            <p class="mt-3 text-sm font-medium">No services found</p>
            <p class="mt-1 text-xs opacity-60">
                Try adjusting your search or filters.
            </p>
        </div>

        <!-- Table -->
        <div v-else class="overflow-hidden rounded-xl bg-base-100/60">
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr class="text-[11px] uppercase opacity-70">
                            <th
                                class="cursor-pointer select-none"
                                @click="toggleSort('name')"
                            >
                                <div class="flex items-center gap-1">
                                    Service
                                    <span
                                        v-if="sort === 'name'"
                                        class="text-primary"
                                        >{{ dir === "asc" ? "▲" : "▼" }}</span
                                    >
                                </div>
                            </th>
                            <th
                                class="hidden md:table-cell cursor-pointer select-none"
                                @click="toggleSort('category')"
                            >
                                <div class="flex items-center gap-1">
                                    Category
                                    <span
                                        v-if="sort === 'category'"
                                        class="text-primary"
                                        >{{ dir === "asc" ? "▲" : "▼" }}</span
                                    >
                                </div>
                            </th>
                            <th
                                class="hidden sm:table-cell cursor-pointer select-none text-center"
                                @click="toggleSort('duration_minutes')"
                            >
                                <div
                                    class="flex items-center justify-center gap-1"
                                >
                                    Duration
                                    <span
                                        v-if="sort === 'duration_minutes'"
                                        class="text-primary"
                                        >{{ dir === "asc" ? "▲" : "▼" }}</span
                                    >
                                </div>
                            </th>
                            <th
                                class="cursor-pointer select-none text-right"
                                @click="toggleSort('price')"
                            >
                                <div
                                    class="flex items-center justify-end gap-1"
                                >
                                    Price
                                    <span
                                        v-if="sort === 'price'"
                                        class="text-primary"
                                        >{{ dir === "asc" ? "▲" : "▼" }}</span
                                    >
                                </div>
                            </th>
                            <th class="hidden lg:table-cell text-center">
                                Status
                            </th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="s in services.data"
                            :key="s.id"
                            class="hover:bg-base-200/40"
                        >
                            <td>
                                <div class="min-w-0">
                                    <p class="truncate text-xs font-medium">
                                        {{ s.name }}
                                    </p>
                                    <p class="truncate text-[10px] opacity-60">
                                        {{ s.description || "—" }}
                                    </p>
                                </div>
                            </td>
                            <td class="hidden md:table-cell">
                                <span class="badge badge-ghost badge-sm">
                                    {{ s.category }}
                                </span>
                            </td>
                            <td
                                class="hidden sm:table-cell text-center text-xs"
                            >
                                {{ formatDuration(s.duration_minutes) }}
                            </td>
                            <td class="text-right text-xs font-medium">
                                {{ money(s.price) }}
                            </td>
                            <td class="hidden lg:table-cell text-center">
                                <span
                                    class="badge badge-sm capitalize"
                                    :class="
                                        s.status === 'active'
                                            ? 'badge-success'
                                            : 'badge-warning'
                                    "
                                >
                                    {{ s.status }}
                                </span>
                            </td>
                            <td>
                                <div class="flex justify-end gap-1">
                                    <button
                                        type="button"
                                        class="btn btn-xs btn-ghost"
                                        title="Edit"
                                        @click="openEditModal(s)"
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
                                        @click="openDeleteModal(s)"
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

        <!-- Pagination -->
        <div
            v-if="services.data.length"
            class="flex flex-col items-center justify-between gap-3 rounded-xl bg-base-100/60 p-3 text-xs sm:flex-row sm:p-4"
        >
            <p class="opacity-60">
                Showing
                <span class="font-medium">{{ services.from }}</span>
                to
                <span class="font-medium">{{ services.to }}</span>
                of
                <span class="font-medium">{{ services.total }}</span>
                services
            </p>

            <div class="join">
                <template v-for="(link, i) in services.links" :key="i">
                    <button
                        v-if="link.label === '...'"
                        class="btn btn-xs sm:btn-sm join-item btn-disabled"
                    >
                        …
                    </button>

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
                        v-else-if="i === services.links.length - 1"
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

    <!-- Create / Edit Modal -->
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
                    {{ isEditing ? "Edit Service" : "Add New Service" }}
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
                            >Service Name</span
                        ></label
                    >
                    <input
                        v-model="form.name"
                        type="text"
                        class="input input-sm input-bordered w-full text-xs"
                        :class="{ 'input-error': form.errors.name }"
                        placeholder="e.g. Hair Rebonding"
                    />
                    <label v-if="form.errors.name" class="label py-0.5">
                        <span class="label-text-alt text-[10px] text-error">{{
                            form.errors.name
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
                        ><span class="label-text text-xs">Status</span></label
                    >
                    <select
                        v-model="form.status"
                        class="select select-sm select-bordered w-full text-xs capitalize"
                    >
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
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
                        placeholder="0.00"
                    />
                    <label v-if="form.errors.price" class="label py-0.5">
                        <span class="label-text-alt text-[10px] text-error">{{
                            form.errors.price
                        }}</span>
                    </label>
                </div>

                <div class="form-control">
                    <label class="label py-0.5"
                        ><span class="label-text text-xs"
                            >Duration (minutes)</span
                        ></label
                    >
                    <input
                        v-model.number="form.duration_minutes"
                        type="number"
                        min="5"
                        max="1440"
                        step="5"
                        class="input input-sm input-bordered w-full text-xs"
                        :class="{
                            'input-error': form.errors.duration_minutes,
                        }"
                        placeholder="30"
                    />
                    <label
                        v-if="form.errors.duration_minutes"
                        class="label py-0.5"
                    >
                        <span class="label-text-alt text-[10px] text-error">{{
                            form.errors.duration_minutes
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
                        placeholder="Optional details about this service…"
                    ></textarea>
                    <label v-if="form.errors.description" class="label py-0.5">
                        <span class="label-text-alt text-[10px] text-error">{{
                            form.errors.description
                        }}</span>
                    </label>
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
                    {{ isEditing ? "Save Changes" : "Add Service" }}
                </button>
            </div>
        </div>

        <form method="dialog" class="modal-backdrop">
            <button @click="closeFormModal">close</button>
        </form>
    </dialog>

    <!-- Delete Modal -->
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
                    <h3 class="text-base font-bold">Delete this service?</h3>
                    <p class="mt-1 text-xs opacity-70">
                        You are about to permanently delete
                        <span class="font-semibold">{{
                            deletingService?.name
                        }}</span>
                        ({{ deletingService?.category }}). This action cannot be
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
