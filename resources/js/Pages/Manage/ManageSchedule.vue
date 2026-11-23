<!-- resources\js\Pages\Manage\ManageSchedule.vue -->
<script setup>
import { ref, computed, watch } from "vue";
import { router, useForm, usePage } from "@inertiajs/vue3";
import Layout from "@/Layouts/Layout.vue";

defineOptions({ layout: Layout });

const props = defineProps({
    month: String,
    selectedDate: String,
    timeSlots: Array,
    dayMap: Object,
    takenPerDay: Object,
});

const page = usePage();

/* Month navigation */
const goToMonth = (offset) => {
    const [y, m] = props.month.split("-").map(Number);
    const d = new Date(y, m - 1 + offset, 1);
    const next = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}`;
    router.get(route("manage.schedule.index"), {
        month: next,
        date: props.selectedDate,
    });
};

const selectDate = (date) => {
    router.get(
        route("manage.schedule.index"),
        { month: props.month, date },
        { preserveState: true, preserveScroll: true },
    );
};

/* Build a grid of days for the month */
const calendarDays = computed(() => {
    const [y, m] = props.month.split("-").map(Number);
    const first = new Date(y, m - 1, 1);
    const last = new Date(y, m, 0);
    const startWeekday = first.getDay(); // 0=Sun
    const days = [];

    // Leading blanks
    for (let i = 0; i < startWeekday; i++) days.push(null);

    for (let d = 1; d <= last.getDate(); d++) {
        const iso = `${y}-${String(m).padStart(2, "0")}-${String(d).padStart(2, "0")}`;
        days.push({
            day: d,
            iso,
            taken: props.takenPerDay[iso] ?? 0,
        });
    }
    return days;
});

const monthLabel = computed(() => {
    const [y, m] = props.month.split("-").map(Number);
    return new Date(y, m - 1, 1).toLocaleDateString(undefined, {
        month: "long",
        year: "numeric",
    });
});

/* Slot state for the selected date */
const slotState = computed(() => {
    const map = props.dayMap[props.selectedDate] ?? {};
    return props.timeSlots.map((slot) => ({
        slot,
        is_available: map[slot]?.is_available ?? true,
        note: map[slot]?.note ?? "",
    }));
});

/* Toggle one slot */
const toggleForm = useForm({
    date: "",
    time_slot: "",
    is_available: true,
    note: null,
});

const toggleSlot = (slot, currentAvailable) => {
    toggleForm.date = props.selectedDate;
    toggleForm.time_slot = slot;
    toggleForm.is_available = !currentAvailable;
    toggleForm.post(route("manage.schedule.toggle"), {
        preserveScroll: true,
        onSuccess: () => {
            // no extra work; server re-renders the props
        },
    });
};

/* Open/close all slots for the day */
const bulkForm = useForm({ date: "", is_available: true });

const bulkToggle = (isAvailable) => {
    bulkForm.date = props.selectedDate;
    bulkForm.is_available = isAvailable;
    bulkForm.post(route("manage.schedule.bulk"), { preserveScroll: true });
};

const selectedDateLabel = computed(() => {
    const d = new Date(props.selectedDate + "T00:00:00");
    return d.toLocaleDateString(undefined, {
        weekday: "long",
        month: "long",
        day: "numeric",
        year: "numeric",
    });
});
</script>

<template>
    <div class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                Booking Schedule
            </h1>
            <p class="text-sm text-base-content/70 mt-1">
                Set which dates and time slots are open for customer bookings.
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
            <!-- ============ LEFT: calendar ============ -->
            <div class="lg:col-span-2">
                <div
                    class="card bg-base-100 border border-base-300/60 rounded-3xl shadow-sm"
                >
                    <div class="card-body p-5 sm:p-6 gap-4">
                        <!-- Month nav -->
                        <div class="flex items-center justify-between">
                            <button
                                type="button"
                                class="btn btn-ghost btn-sm btn-circle"
                                aria-label="Previous month"
                                @click="goToMonth(-1)"
                            >
                                <i
                                    class="hgi hgi-stroke hgi-rounded hgi-arrow-left-01"
                                ></i>
                            </button>
                            <h2 class="font-bold text-lg">{{ monthLabel }}</h2>
                            <button
                                type="button"
                                class="btn btn-ghost btn-sm btn-circle"
                                aria-label="Next month"
                                @click="goToMonth(1)"
                            >
                                <i
                                    class="hgi hgi-stroke hgi-rounded hgi-arrow-right-01"
                                ></i>
                            </button>
                        </div>

                        <!-- Weekday labels -->
                        <div
                            class="grid grid-cols-7 gap-1 text-center text-xs font-semibold uppercase tracking-wide opacity-60"
                        >
                            <span>Sun</span><span>Mon</span><span>Tue</span>
                            <span>Wed</span><span>Thu</span><span>Fri</span>
                            <span>Sat</span>
                        </div>

                        <!-- Days -->
                        <div class="grid grid-cols-7 gap-1">
                            <template v-for="(d, i) in calendarDays" :key="i">
                                <div v-if="!d"></div>
                                <button
                                    v-else
                                    type="button"
                                    class="relative aspect-square rounded-xl flex flex-col items-center justify-center text-sm font-medium transition-colors"
                                    :class="
                                        d.iso === selectedDate
                                            ? 'bg-primary text-primary-content'
                                            : 'hover:bg-base-200'
                                    "
                                    @click="selectDate(d.iso)"
                                >
                                    <span>{{ d.day }}</span>
                                    <span
                                        v-if="d.taken > 0"
                                        class="absolute bottom-1 w-1.5 h-1.5 rounded-full"
                                        :class="
                                            d.iso === selectedDate
                                                ? 'bg-primary-content/70'
                                                : 'bg-error'
                                        "
                                    ></span>
                                </button>
                            </template>
                        </div>

                        <div class="flex items-center gap-3 text-xs opacity-70">
                            <span class="inline-flex items-center gap-1">
                                <span
                                    class="w-2 h-2 rounded-full bg-error"
                                ></span>
                                Some slots blocked
                            </span>
                            <span class="inline-flex items-center gap-1">
                                <span
                                    class="w-2 h-2 rounded-full bg-base-300"
                                ></span>
                                No blocked slots
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============ RIGHT: slot manager ============ -->
            <div class="lg:col-span-3 space-y-4">
                <div
                    class="card bg-base-100 border border-base-300/60 rounded-3xl shadow-sm"
                >
                    <div class="card-body p-5 sm:p-6 gap-4">
                        <div
                            class="flex flex-wrap items-center justify-between gap-3"
                        >
                            <div>
                                <p
                                    class="text-xs uppercase tracking-widest opacity-60 font-medium"
                                >
                                    Selected date
                                </p>
                                <h2 class="font-bold text-lg mt-1">
                                    {{ selectedDateLabel }}
                                </h2>
                            </div>
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    class="btn btn-sm rounded-full btn-success"
                                    :disabled="bulkForm.processing"
                                    @click="bulkToggle(true)"
                                >
                                    <i
                                        class="hgi hgi-stroke hgi-rounded hgi-checkmark-circle-02"
                                    ></i>
                                    Open all
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-sm rounded-full btn-error btn-outline"
                                    :disabled="bulkForm.processing"
                                    @click="bulkToggle(false)"
                                >
                                    <i
                                        class="hgi hgi-stroke hgi-rounded hgi-cancel-circle"
                                    ></i>
                                    Block all
                                </button>
                            </div>
                        </div>

                        <!-- Slot grid -->
                        <div
                            class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2"
                        >
                            <button
                                v-for="s in slotState"
                                :key="s.slot"
                                type="button"
                                class="group relative rounded-xl border px-3 py-3 text-left transition-all"
                                :class="
                                    s.is_available
                                        ? 'bg-success/10 border-success/30 hover:bg-success/20'
                                        : 'bg-error/10 border-error/30 hover:bg-error/20'
                                "
                                :disabled="toggleForm.processing"
                                @click="toggleSlot(s.slot, s.is_available)"
                            >
                                <div
                                    class="flex items-center justify-between gap-2"
                                >
                                    <span class="font-semibold text-sm">
                                        {{ s.slot }}
                                    </span>
                                    <i
                                        :class="
                                            s.is_available
                                                ? 'hgi hgi-stroke hgi-rounded hgi-checkmark-circle-02 text-success'
                                                : 'hgi hgi-stroke hgi-rounded hgi-cancel-circle text-error'
                                        "
                                    ></i>
                                </div>
                                <p
                                    class="text-xs mt-1 font-medium"
                                    :class="
                                        s.is_available
                                            ? 'text-success'
                                            : 'text-error'
                                    "
                                >
                                    {{
                                        s.is_available ? "Available" : "Blocked"
                                    }}
                                </p>
                            </button>
                        </div>

                        <p class="text-xs opacity-60 mt-1">
                            Click a slot to toggle between available and
                            blocked. Customers only see and pick available
                            slots.
                        </p>
                    </div>
                </div>

                <!-- Legend / help -->
                <div
                    class="card bg-base-200/60 border border-base-300/60 rounded-2xl"
                >
                    <div class="card-body p-4 sm:p-5">
                        <div class="flex items-start gap-3">
                            <div
                                class="rounded-full bg-primary/10 p-2 shrink-0"
                            >
                                <i
                                    class="hgi hgi-stroke hgi-rounded hgi-information-circle text-primary"
                                ></i>
                            </div>
                            <div class="text-sm">
                                <p class="font-semibold">Tip</p>
                                <p class="opacity-70 mt-0.5">
                                    Block a slot when it's already taken
                                    offline, reserved for a walk-in, or
                                    unavailable for maintenance. Blocked slots
                                    are removed from the customer booking page
                                    immediately.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
