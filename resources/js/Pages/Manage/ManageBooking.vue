<!-- resources\js\Pages\Manage\ManageBooking.vue -->
<script setup>
import { ref, computed, watch, nextTick } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import Layout from "@/Layouts/Layout.vue";

defineOptions({ layout: Layout });

const props = defineProps({
    bookings: { type: Object, required: true },
    stats: { type: Object, required: true },
    filters: { type: Object, required: true },
    statusOptions: { type: Array, default: () => [] },
});

const page = usePage();

/* ------------------------------------------------------------------ */
/* Local filter state                                                 */
/* ------------------------------------------------------------------ */
const localSearch = ref(props.filters.q ?? "");
const localFrom = ref(props.filters.from ?? "");
const localTo = ref(props.filters.to ?? "");

const activeStatus = computed(() => props.filters.status ?? "all");

const statusChips = computed(() => [
    { key: "all", label: "All", count: props.stats.total },
    ...props.statusOptions.map((s) => ({
        key: s.value,
        label: s.label,
        count: props.stats[s.value] ?? 0,
    })),
]);

/* ------------------------------------------------------------------ */
/* Filters via Inertia visit                                          */
/* ------------------------------------------------------------------ */
const applyFilters = (overrides = {}) => {
    const query = {
        status: overrides.status ?? activeStatus.value,
        from: overrides.from ?? (localFrom.value || undefined),
        to: overrides.to ?? (localTo.value || undefined),
        q: overrides.q ?? (localSearch.value || undefined),
    };

    router.get(route("manage.booking.index"), query, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const selectStatus = (key) => applyFilters({ status: key });
const applyDateFilters = () => applyFilters();

const resetFilters = () => {
    localSearch.value = "";
    localFrom.value = "";
    localTo.value = "";

    router.get(
        route("manage.booking.index"),
        {},
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

let searchTimer = null;
watch(localSearch, (val) => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => applyFilters({ q: val }), 350);
});

/* ------------------------------------------------------------------ */
/* Status update                                                      */
/* ------------------------------------------------------------------ */
const updatingId = ref(null);

/* Cancel confirmation modal */
const cancelTarget = ref(null);
const cancelModalRef = ref(null);

const openCancelModal = async (booking) => {
    cancelTarget.value = booking;
    await nextTick();
    cancelModalRef.value?.showModal();
};

const closeCancelModal = () => {
    cancelModalRef.value?.close();
    cancelTarget.value = null;
};

/* Common PATCH call */
const sendStatusUpdate = (booking, newStatus) => {
    updatingId.value = booking.id;

    router.patch(
        route("manage.booking.status", booking.id),
        { status: newStatus },
        {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => (updatingId.value = null),
        },
    );
};

/* Called by the <select> on each card */
const updateStatus = (booking, newStatus) => {
    if (newStatus === booking.status) return;

    if (newStatus === "cancelled") {
        // Don't send yet — ask first via modal.
        // Reset the <select> back to the current status so the UI
        // doesn't lie while the modal is open.
        const select = document.querySelector(
            `[data-status-select="${booking.id}"]`,
        );
        if (select) select.value = booking.status;

        openCancelModal(booking);
        return;
    }

    sendStatusUpdate(booking, newStatus);
};

/* Confirm button inside the modal */
const confirmCancel = () => {
    if (!cancelTarget.value) return;
    const target = cancelTarget.value;
    closeCancelModal();
    sendStatusUpdate(target, "cancelled");
};

/* ------------------------------------------------------------------ */
/* Helpers                                                            */
/* ------------------------------------------------------------------ */
const formatPrice = (v) =>
    new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
        minimumFractionDigits: 0,
    }).format(v);

const formatDate = (iso) => {
    if (!iso) return "";
    const d = new Date(iso + "T00:00:00");
    return d.toLocaleDateString(undefined, {
        weekday: "short",
        month: "short",
        day: "numeric",
        year: "numeric",
    });
};

const badgeClass = (color) =>
    ({
        warning: "badge-warning",
        info: "badge-info",
        success: "badge-success",
        error: "badge-error",
    })[color] ?? "badge-neutral";

const isToday = (iso) => iso === new Date().toISOString().slice(0, 10);

const isPast = (iso) => {
    if (!iso) return false;
    return iso < new Date().toISOString().slice(0, 10);
};

const isEmpty = computed(() => (props.bookings.data ?? []).length === 0);
</script>

<template>
    <div class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto">
        <!-- Header -->
        <div
            class="mb-6 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4"
        >
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                    Manage Bookings
                </h1>
                <p class="text-sm text-base-content/70 mt-1">
                    Review, confirm, and complete customer appointments.
                </p>
            </div>
            <a
                :href="route('manage.schedule.index')"
                class="btn btn-ghost rounded-full border border-base-300 self-start sm:self-auto"
            >
                <i class="hgi hgi-stroke hgi-rounded hgi-calendar-03"></i>
                Manage Schedule
            </a>
        </div>

        <!-- Summary tiles -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
            <div class="rounded-2xl bg-base-100 border border-base-300/60 p-4">
                <p
                    class="text-xs uppercase tracking-widest opacity-60 font-medium"
                >
                    Total
                </p>
                <p class="text-2xl font-extrabold mt-1">{{ stats.total }}</p>
            </div>
            <div class="rounded-2xl bg-primary/10 border border-primary/30 p-4">
                <p
                    class="text-xs uppercase tracking-widest opacity-70 font-medium"
                >
                    Today
                </p>
                <p class="text-2xl font-extrabold text-primary mt-1">
                    {{ stats.today }}
                </p>
            </div>
            <div class="rounded-2xl bg-warning/10 border border-warning/30 p-4">
                <p
                    class="text-xs uppercase tracking-widest opacity-70 font-medium"
                >
                    Pending
                </p>
                <p class="text-2xl font-extrabold text-warning-content mt-1">
                    {{ stats.pending }}
                </p>
            </div>
            <div class="rounded-2xl bg-info/10 border border-info/30 p-4">
                <p
                    class="text-xs uppercase tracking-widest opacity-70 font-medium"
                >
                    Confirmed
                </p>
                <p class="text-2xl font-extrabold text-info-content mt-1">
                    {{ stats.confirmed }}
                </p>
            </div>
            <div class="rounded-2xl bg-success/10 border border-success/30 p-4">
                <p
                    class="text-xs uppercase tracking-widest opacity-70 font-medium"
                >
                    Completed
                </p>
                <p class="text-2xl font-extrabold text-success-content mt-1">
                    {{ stats.completed }}
                </p>
            </div>
            <div class="rounded-2xl bg-error/10 border border-error/30 p-4">
                <p
                    class="text-xs uppercase tracking-widest opacity-70 font-medium"
                >
                    Cancelled
                </p>
                <p class="text-2xl font-extrabold text-error-content mt-1">
                    {{ stats.cancelled }}
                </p>
            </div>
        </div>

        <!-- Filters -->
        <div class="space-y-4 mb-6">
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="chip in statusChips"
                    :key="chip.key"
                    type="button"
                    class="btn btn-sm rounded-full normal-case"
                    :class="
                        activeStatus === chip.key
                            ? 'btn-primary'
                            : 'btn-ghost border border-base-300'
                    "
                    @click="selectStatus(chip.key)"
                >
                    {{ chip.label }}
                    <span
                        class="badge badge-sm rounded-full ml-1"
                        :class="
                            activeStatus === chip.key
                                ? 'badge-neutral bg-primary-content/20 text-primary-content border-0'
                                : 'badge-ghost'
                        "
                    >
                        {{ chip.count }}
                    </span>
                </button>
            </div>

            <div
                class="card bg-base-100 border border-base-300/60 rounded-2xl shadow-sm"
            >
                <div class="card-body p-4 sm:p-5 gap-3">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                        <div class="md:col-span-5">
                            <label
                                class="text-xs uppercase tracking-widest opacity-60 font-medium"
                                >Search</label
                            >
                            <div class="relative mt-1">
                                <span
                                    class="absolute inset-y-0 left-0 flex items-center pl-3 text-base-content/50 z-1"
                                >
                                    <i
                                        class="hgi hgi-stroke hgi-rounded hgi-search-01 text-lg"
                                    ></i>
                                </span>
                                <input
                                    v-model="localSearch"
                                    type="text"
                                    placeholder="Reference, name, phone, or email"
                                    class="input input-bordered w-full rounded-xl pl-10 focus:input-primary"
                                />
                            </div>
                        </div>
                        <div class="md:col-span-3">
                            <label
                                class="text-xs uppercase tracking-widest opacity-60 font-medium"
                                >From</label
                            >
                            <input
                                v-model="localFrom"
                                type="date"
                                class="input input-bordered w-full rounded-xl mt-1 focus:input-primary"
                                @change="applyDateFilters"
                            />
                        </div>
                        <div class="md:col-span-3">
                            <label
                                class="text-xs uppercase tracking-widest opacity-60 font-medium"
                                >To</label
                            >
                            <input
                                v-model="localTo"
                                type="date"
                                class="input input-bordered w-full rounded-xl mt-1 focus:input-primary"
                                @change="applyDateFilters"
                            />
                        </div>
                        <div class="md:col-span-1 flex items-end">
                            <button
                                type="button"
                                class="btn btn-ghost btn-square rounded-xl w-full md:w-auto"
                                title="Reset filters"
                                @click="resetFilters"
                            >
                                <i
                                    class="hgi hgi-stroke hgi-rounded hgi-filter-reset"
                                ></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- List -->
        <div
            v-if="isEmpty"
            class="card bg-base-100 border border-dashed border-base-300 rounded-3xl"
        >
            <div class="card-body items-center text-center py-16">
                <div class="rounded-full bg-primary/10 p-4 mb-3">
                    <i
                        class="hgi hgi-stroke hgi-rounded hgi-calendar-x text-4xl text-primary"
                    ></i>
                </div>
                <h3 class="font-semibold text-lg">No bookings found</h3>
                <p class="text-sm text-base-content/60 max-w-sm mt-1">
                    Try adjusting the filters, or check back when customers
                    start booking.
                </p>
            </div>
        </div>

        <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div
                v-for="booking in bookings.data"
                :key="booking.id"
                class="card bg-base-100 border border-base-300/60 rounded-2xl shadow-sm hover:shadow-md transition-shadow"
            >
                <div class="card-body p-5 gap-3">
                    <!-- Header -->
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p
                                class="text-xs uppercase tracking-widest opacity-60 font-medium"
                            >
                                {{ booking.reference_no }}
                            </p>
                            <h3
                                class="font-semibold text-base leading-tight mt-1 truncate"
                            >
                                {{ booking.service_name }}
                            </h3>
                            <p class="text-xs opacity-60 mt-0.5">
                                {{ booking.service_category }} ·
                                {{ booking.service_duration }} min
                            </p>
                        </div>
                        <span
                            class="badge rounded-full font-medium shrink-0"
                            :class="badgeClass(booking.status_color)"
                        >
                            {{ booking.status_label }}
                        </span>
                    </div>

                    <!-- Customer info -->
                    <div
                        class="rounded-xl bg-base-200/60 p-3 flex items-start gap-3"
                    >
                        <div
                            class="avatar avatar-placeholder w-9 h-9 rounded-full bg-primary text-primary-content shrink-0"
                        >
                            <div
                                class="flex items-center justify-center w-full h-full text-sm font-bold uppercase"
                            >
                                {{
                                    (
                                        booking.full_name ||
                                        booking.customer_email ||
                                        "?"
                                    ).charAt(0)
                                }}
                            </div>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-medium truncate">
                                {{
                                    booking.full_name ||
                                    booking.customer_name ||
                                    "Guest"
                                }}
                            </p>
                            <p class="text-xs opacity-70 truncate">
                                {{
                                    booking.phone ||
                                    booking.email ||
                                    booking.customer_email
                                }}
                            </p>
                        </div>
                    </div>

                    <!-- Date / time / price -->
                    <div class="grid grid-cols-3 gap-2 pt-1">
                        <div>
                            <p class="text-xs opacity-60">Date</p>
                            <p class="font-medium text-sm">
                                {{ formatDate(booking.booking_date) }}
                            </p>
                            <span
                                v-if="isToday(booking.booking_date)"
                                class="badge badge-primary badge-xs mt-1"
                            >
                                Today
                            </span>
                        </div>
                        <div>
                            <p class="text-xs opacity-60">Time</p>
                            <p class="font-medium text-sm">
                                {{ booking.time_slot }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs opacity-60">Price</p>
                            <p class="font-bold text-primary text-sm">
                                {{ formatPrice(booking.service_price) }}
                            </p>
                        </div>
                    </div>

                    <!-- Notes -->
                    <p
                        v-if="booking.notes"
                        class="text-xs opacity-70 italic line-clamp-2"
                    >
                        "{{ booking.notes }}"
                    </p>

                    <!-- Overdue warning -->
                    <div
                        v-if="
                            isPast(booking.booking_date) &&
                            ['pending', 'confirmed'].includes(booking.status)
                        "
                        class="alert alert-warning py-2 px-3 rounded-xl text-xs"
                    >
                        <i
                            class="hgi hgi-stroke hgi-rounded hgi-information-circle"
                        ></i>
                        Booking date has passed — update to Completed or
                        Cancelled.
                    </div>

                    <!-- Status update -->
                    <div
                        class="flex items-center gap-2 pt-2 border-t border-base-200"
                    >
                        <div class="flex-1">
                            <label class="text-xs opacity-60"
                                >Change status</label
                            >
                            <select
                                :value="booking.status"
                                :data-status-select="booking.id"
                                class="select select-bordered select-sm w-full rounded-xl mt-1"
                                :disabled="updatingId === booking.id"
                                @change="
                                    updateStatus(booking, $event.target.value)
                                "
                            >
                                <option
                                    v-for="opt in statusOptions"
                                    :key="opt.value"
                                    :value="opt.value"
                                >
                                    {{ opt.label }}
                                </option>
                            </select>
                        </div>
                        <div class="pt-5">
                            <span
                                v-if="updatingId === booking.id"
                                class="loading loading-spinner loading-sm text-primary"
                            ></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div
            v-if="bookings.links && bookings.links.length > 3"
            class="mt-8 flex justify-center"
        >
            <div class="join">
                <component
                    :is="link.url ? 'a' : 'span'"
                    v-for="(link, i) in bookings.links"
                    :key="i"
                    :href="link.url || '#'"
                    class="join-item btn btn-sm"
                    :class="{
                        'btn-active btn-primary': link.active,
                        'btn-disabled': !link.url,
                    }"
                    v-html="link.label"
                />
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- CANCEL CONFIRMATION MODAL                                    -->
        <!-- ============================================================ -->
        <dialog ref="cancelModalRef" class="modal modal-bottom sm:modal-middle">
            <div class="modal-box rounded-2xl">
                <div class="flex items-start gap-3">
                    <div class="rounded-full bg-error/10 p-3 shrink-0">
                        <i
                            class="hgi hgi-stroke hgi-rounded hgi-alert-02 text-error text-2xl"
                        ></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="font-bold text-lg leading-tight">
                            Cancel this booking?
                        </h3>
                        <p class="text-sm text-base-content/70 mt-1">
                            This will set the booking to
                            <span class="font-semibold text-error"
                                >Cancelled</span
                            >
                            and re-open the time slot so other customers can
                            book it.
                        </p>
                    </div>
                </div>

                <!-- Booking summary -->
                <div
                    v-if="cancelTarget"
                    class="mt-4 rounded-xl bg-base-200/60 border border-base-300/60 p-4 space-y-2"
                >
                    <div class="flex items-center justify-between gap-2">
                        <span
                            class="text-xs uppercase tracking-widest opacity-60 font-medium"
                        >
                            Reference
                        </span>
                        <span class="font-mono text-sm font-medium">
                            {{ cancelTarget.reference_no }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span
                            class="text-xs uppercase tracking-widest opacity-60 font-medium"
                        >
                            Service
                        </span>
                        <span class="text-sm font-medium truncate ml-2">
                            {{ cancelTarget.service_name }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span
                            class="text-xs uppercase tracking-widest opacity-60 font-medium"
                        >
                            Customer
                        </span>
                        <span class="text-sm font-medium truncate ml-2">
                            {{
                                cancelTarget.full_name ||
                                cancelTarget.customer_name ||
                                "Guest"
                            }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span
                            class="text-xs uppercase tracking-widest opacity-60 font-medium"
                        >
                            Schedule
                        </span>
                        <span class="text-sm font-medium">
                            {{ formatDate(cancelTarget.booking_date) }} ·
                            {{ cancelTarget.time_slot }}
                        </span>
                    </div>
                </div>

                <div class="modal-action mt-5">
                    <button
                        type="button"
                        class="btn btn-ghost rounded-full"
                        @click="closeCancelModal"
                    >
                        Keep booking
                    </button>
                    <button
                        type="button"
                        class="btn btn-error rounded-full text-error-content"
                        @click="confirmCancel"
                    >
                        <i
                            class="hgi hgi-stroke hgi-rounded hgi-cancel-circle"
                        ></i>
                        Yes, cancel booking
                    </button>
                </div>
            </div>

            <!-- Click outside closes -->
            <form method="dialog" class="modal-backdrop">
                <button type="button" @click="closeCancelModal">close</button>
            </form>
        </dialog>
    </div>
</template>
