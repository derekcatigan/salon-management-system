<!-- resources\js\Pages\Website\AppointmentHistoryWeb.vue -->
<script setup>
import { ref, computed, watch } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import WebLayout from "@/Layouts/WebLayout.vue";

defineOptions({ layout: WebLayout });

const props = defineProps({
    bookings: { type: Object, required: true },
    stats: { type: Object, required: true },
    filters: { type: Object, required: true },
    statusOptions: { type: Array, default: () => [] },
});

/* ------------------------------------------------------------------ */
/* Local filter state (mirrors props.filters, used for instant UI)    */
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
/* Apply filters via Inertia visit                                    */
/* ------------------------------------------------------------------ */
const applyFilters = (overrides = {}) => {
    const query = {
        status: overrides.status ?? activeStatus.value,
        from: overrides.from ?? (localFrom.value || undefined),
        to: overrides.to ?? (localTo.value || undefined),
        q: overrides.q ?? (localSearch.value || undefined),
    };

    router.get(route("appointment.history.index"), query, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const selectStatus = (key) => {
    applyFilters({ status: key });
};

const applyDateFilters = () => {
    applyFilters();
};

const resetFilters = () => {
    localSearch.value = "";
    localFrom.value = "";
    localTo.value = "";

    router.get(
        route("appointment.history.index"),
        {},
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

/* Debounced search */
let searchTimer = null;
watch(localSearch, (val) => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        applyFilters({ q: val });
    }, 350);
});

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

const badgeClass = (color) => {
    // Map BookingStatusEnum::color() to DaisyUI badge classes
    return (
        {
            warning: "badge-warning",
            info: "badge-info",
            success: "badge-success",
            error: "badge-error",
        }[color] ?? "badge-neutral"
    );
};

const isPast = (iso) => {
    if (!iso) return false;
    const today = new Date().toISOString().slice(0, 10);
    return iso < today;
};

const isEmpty = computed(() => (props.bookings.data ?? []).length === 0);
</script>

<template>
    <Head title="My Appointments" />

    <div class="overflow-x-hidden bg-base-100 text-base-content">
        <!-- ===================== HERO ===================== -->
        <section class="relative pt-16 pb-10 sm:pt-24 sm:pb-14 overflow-hidden">
            <div
                class="absolute -top-32 -left-32 w-96 h-96 rounded-full bg-primary/10 blur-3xl"
            ></div>
            <div
                class="absolute -bottom-32 -right-32 w-md h-112 rounded-full bg-secondary/10 blur-3xl"
            ></div>

            <div class="relative container mx-auto px-4 sm:px-6 lg:px-10">
                <div
                    class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4"
                >
                    <div>
                        <span
                            class="inline-flex items-center gap-2 badge badge-primary badge-outline badge-lg uppercase tracking-widest text-xs mb-4"
                        >
                            <i
                                class="hgi hgi-stroke hgi-rounded hgi-calendar-03"
                            ></i>
                            My Appointments
                        </span>
                        <h1
                            class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight leading-tight"
                        >
                            Your Booking
                            <span
                                class="block bg-linear-to-r from-primary via-secondary to-primary bg-clip-text text-transparent"
                            >
                                History
                            </span>
                        </h1>
                        <p class="mt-3 text-base-content/70 max-w-xl">
                            Track the status of every appointment you've booked
                            with us.
                        </p>
                    </div>

                    <Link
                        :href="route('appointment.web.index')"
                        class="btn btn-primary rounded-full shadow-lg shadow-primary/25 self-start sm:self-auto"
                    >
                        <i
                            class="hgi hgi-stroke hgi-rounded hgi-calendar-add-01"
                        ></i>
                        New Booking
                    </Link>
                </div>

                <!-- Summary tiles -->
                <div class="mt-8 grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div
                        class="rounded-2xl bg-base-100 border border-base-300/60 p-4 shadow-sm"
                    >
                        <p
                            class="text-xs uppercase tracking-widest opacity-60 font-medium"
                        >
                            Total
                        </p>
                        <p class="text-2xl font-extrabold mt-1">
                            {{ stats.total }}
                        </p>
                    </div>
                    <div
                        class="rounded-2xl bg-warning/10 border border-warning/30 p-4"
                    >
                        <p
                            class="text-xs uppercase tracking-widest opacity-70 font-medium"
                        >
                            Pending
                        </p>
                        <p
                            class="text-2xl font-extrabold text-warning-content mt-1"
                        >
                            {{ stats.pending }}
                        </p>
                    </div>
                    <div
                        class="rounded-2xl bg-info/10 border border-info/30 p-4"
                    >
                        <p
                            class="text-xs uppercase tracking-widest opacity-70 font-medium"
                        >
                            Confirmed
                        </p>
                        <p
                            class="text-2xl font-extrabold text-info-content mt-1"
                        >
                            {{ stats.confirmed }}
                        </p>
                    </div>
                    <div
                        class="rounded-2xl bg-success/10 border border-success/30 p-4"
                    >
                        <p
                            class="text-xs uppercase tracking-widest opacity-70 font-medium"
                        >
                            Completed
                        </p>
                        <p
                            class="text-2xl font-extrabold text-success-content mt-1"
                        >
                            {{ stats.completed }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===================== FILTERS ===================== -->
        <section class="pb-6">
            <div class="container mx-auto px-4 sm:px-6 lg:px-10 space-y-4">
                <!-- Status chips -->
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

                <!-- Search + date range -->
                <div
                    class="card bg-base-100 border border-base-300/60 rounded-2xl shadow-sm"
                >
                    <div class="card-body p-4 sm:p-5 gap-3">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                            <!-- Search -->
                            <div class="md:col-span-5">
                                <label
                                    class="text-xs uppercase tracking-widest opacity-60 font-medium"
                                >
                                    Search
                                </label>
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
                                        placeholder="Reference no. or service name"
                                        class="input input-bordered w-full rounded-xl pl-10 focus:input-primary"
                                    />
                                </div>
                            </div>

                            <!-- From -->
                            <div class="md:col-span-3">
                                <label
                                    class="text-xs uppercase tracking-widest opacity-60 font-medium"
                                >
                                    From
                                </label>
                                <input
                                    v-model="localFrom"
                                    type="date"
                                    class="input input-bordered w-full rounded-xl mt-1 focus:input-primary"
                                    @change="applyDateFilters"
                                />
                            </div>

                            <!-- To -->
                            <div class="md:col-span-3">
                                <label
                                    class="text-xs uppercase tracking-widest opacity-60 font-medium"
                                >
                                    To
                                </label>
                                <input
                                    v-model="localTo"
                                    type="date"
                                    class="input input-bordered w-full rounded-xl mt-1 focus:input-primary"
                                    @change="applyDateFilters"
                                />
                            </div>

                            <!-- Reset -->
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
        </section>

        <!-- ===================== BOOKINGS LIST ===================== -->
        <section class="pb-20">
            <div class="container mx-auto px-4 sm:px-6 lg:px-10">
                <!-- Empty state -->
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
                        <h3 class="font-semibold text-lg">
                            No appointments found
                        </h3>
                        <p class="text-sm text-base-content/60 max-w-sm mt-1">
                            Try adjusting the filters, or book your first
                            appointment — it only takes a minute.
                        </p>
                        <Link
                            :href="route('appointment.web.index')"
                            class="btn btn-primary rounded-full mt-4"
                        >
                            <i
                                class="hgi hgi-stroke hgi-rounded hgi-calendar-add-01"
                            ></i>
                            Book Appointment
                        </Link>
                    </div>
                </div>

                <!-- Cards -->
                <div
                    v-else
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4"
                >
                    <div
                        v-for="booking in bookings.data"
                        :key="booking.id"
                        class="card bg-base-100 border border-base-300/60 rounded-2xl shadow-sm hover:shadow-lg transition-all"
                    >
                        <div class="card-body p-5 gap-3">
                            <!-- Header: reference + status -->
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
                                        {{ booking.service_category }}
                                    </p>
                                </div>
                                <span
                                    class="badge rounded-full font-medium shrink-0"
                                    :class="badgeClass(booking.status_color)"
                                >
                                    {{ booking.status_label }}
                                </span>
                            </div>

                            <!-- Date / time / duration -->
                            <div
                                class="grid grid-cols-2 gap-2 pt-2 border-t border-base-200"
                            >
                                <div class="flex items-center gap-2 text-sm">
                                    <div
                                        class="rounded-lg bg-primary/10 p-1.5 shrink-0"
                                    >
                                        <i
                                            class="hgi hgi-stroke hgi-rounded hgi-calendar-03 text-primary"
                                        ></i>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-xs opacity-60">Date</p>
                                        <p class="font-medium truncate">
                                            {{
                                                formatDate(booking.booking_date)
                                            }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 text-sm">
                                    <div
                                        class="rounded-lg bg-primary/10 p-1.5 shrink-0"
                                    >
                                        <i
                                            class="hgi hgi-stroke hgi-rounded hgi-clock-01 text-primary"
                                        ></i>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-xs opacity-60">Time</p>
                                        <p class="font-medium truncate">
                                            {{ booking.time_slot }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Price + duration -->
                            <div
                                class="flex items-center justify-between pt-2 border-t border-base-200"
                            >
                                <span class="font-bold text-primary">
                                    {{ formatPrice(booking.service_price) }}
                                </span>
                                <span
                                    class="inline-flex items-center gap-1 text-xs text-base-content/60"
                                >
                                    <i
                                        class="hgi hgi-stroke hgi-rounded hgi-timer-01"
                                    ></i>
                                    {{ booking.service_duration }} min
                                </span>
                            </div>

                            <!-- Notes (if any) -->
                            <p
                                v-if="booking.notes"
                                class="text-xs opacity-70 italic line-clamp-2 pt-1"
                            >
                                "{{ booking.notes }}"
                            </p>

                            <!-- Past marker -->
                            <div
                                v-if="
                                    isPast(booking.booking_date) &&
                                    ['pending', 'confirmed'].includes(
                                        booking.status,
                                    )
                                "
                                class="alert alert-warning py-2 px-3 rounded-xl text-xs"
                            >
                                <i
                                    class="hgi hgi-stroke hgi-rounded hgi-information-circle"
                                ></i>
                                This appointment date has passed.
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
            </div>
        </section>
    </div>
</template>
