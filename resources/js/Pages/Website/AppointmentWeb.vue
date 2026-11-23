<!-- resources\js\Pages\Website\AppointmentWeb.vue -->
<script setup>
import { ref, computed, watch } from "vue";
import { Head, router, useForm, usePage } from "@inertiajs/vue3";
import WebLayout from "@/Layouts/WebLayout.vue";

defineOptions({ layout: WebLayout });

const props = defineProps({
    services: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    timeSlots: { type: Array, default: () => [] },
    availability: { type: Object, default: () => ({}) },
    isAuthenticated: { type: Boolean, default: false },
    defaults: {
        type: Object,
        default: () => ({ full_name: "", phone: "", email: "" }),
    },
});

const page = usePage();

/* ------------------------------------------------------------------ */
/* Form — prefilled from the customer's account                       */
/* ------------------------------------------------------------------ */
const form = useForm({
    service_id: null,
    full_name: props.defaults.full_name,
    phone: props.defaults.phone,
    email: props.defaults.email,
    booking_date: "",
    time_slot: "",
    notes: "",
});

/* Whether the customer has a prefilled profile that we can collapse */
const hasPrefilledProfile = computed(
    () =>
        props.isAuthenticated &&
        (props.defaults.full_name || props.defaults.phone),
);

/* Toggle to "edit my details" */
const editingDetails = ref(!hasPrefilledProfile.value);

const activeCategory = ref("all");
const searchQuery = ref("");
const selectedService = ref(null);

/* ------------------------------------------------------------------ */
/* Success alert                                                      */
/* ------------------------------------------------------------------ */
const showSuccess = ref(false);
const successMessage = ref("");

watch(
    () => page.props.flash?.success,
    (val) => {
        if (val) {
            successMessage.value = val;
            showSuccess.value = true;
            setTimeout(() => (showSuccess.value = false), 6000);
        }
    },
    { immediate: true },
);

/* ------------------------------------------------------------------ */
/* Filtering                                                          */
/* ------------------------------------------------------------------ */
const filteredServices = computed(() =>
    props.services.filter((s) => {
        const mc =
            activeCategory.value === "all" ||
            s.category === activeCategory.value;
        const ms =
            !searchQuery.value ||
            s.name.toLowerCase().includes(searchQuery.value.toLowerCase());
        return mc && ms;
    }),
);

const formatPrice = (v) =>
    new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
        minimumFractionDigits: 0,
    }).format(v);

/* ------------------------------------------------------------------ */
/* Availability                                                       */
/* ------------------------------------------------------------------ */
const todayIso = new Date().toISOString().slice(0, 10);

const isSlotAvailable = (date, slot) => {
    if (!date) return false;
    const day = props.availability?.[date];
    if (!day) return true;
    return day[slot] !== false;
};

const isDateFullyBlocked = (date) => {
    const day = props.availability?.[date];
    if (!day) return false;
    return Object.values(day).every((v) => v === false);
};

const dateIsValid = computed(
    () => !!form.booking_date && !isDateFullyBlocked(form.booking_date),
);

watch(
    () => form.booking_date,
    (d) => {
        if (d && form.time_slot && !isSlotAvailable(d, form.time_slot)) {
            form.time_slot = "";
        }
    },
);

const canPickSlot = (slot) =>
    dateIsValid.value && isSlotAvailable(form.booking_date, slot);

/* ------------------------------------------------------------------ */
/* Selection                                                          */
/* ------------------------------------------------------------------ */
const selectService = (service) => {
    selectedService.value = service;
    form.service_id = service.id;
    if (window.innerWidth < 1024) {
        setTimeout(() => {
            document
                .getElementById("booking-form")
                ?.scrollIntoView({ behavior: "smooth", block: "start" });
        }, 100);
    }
};

const clearSelection = () => {
    selectedService.value = null;
    form.service_id = null;
};

/* ------------------------------------------------------------------ */
/* Submit                                                             */
/* ------------------------------------------------------------------ */
const confirmBooking = () => {
    if (!props.isAuthenticated) {
        router.visit(route("auth.web.index"));
        return;
    }

    form.post(route("appointment.web.store"), {
        preserveScroll: true,
        onSuccess: () => {
            // Keep the prefilled customer details, only reset booking fields
            form.reset("service_id", "booking_date", "time_slot", "notes");
            selectedService.value = null;
        },
    });
};
</script>

<template>
    <Head title="Book an Appointment" />

    <div class="overflow-x-hidden bg-base-100 text-base-content">
        <!-- SUCCESS ALERT -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="showSuccess"
                class="fixed top-20 left-1/2 -translate-x-1/2 z-60 w-[calc(100%-2rem)] max-w-md"
            >
                <div
                    class="alert alert-success rounded-2xl shadow-xl border border-success/30 flex items-start gap-3"
                    role="alert"
                >
                    <i
                        class="hgi hgi-stroke hgi-rounded hgi-checkmark-circle-02 text-2xl shrink-0"
                    ></i>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold leading-tight">
                            Booking confirmed
                        </p>
                        <p class="text-xs mt-0.5 opacity-80 wrap-break-word">
                            {{ successMessage }}
                        </p>
                    </div>
                    <button
                        type="button"
                        class="btn btn-ghost btn-xs btn-circle"
                        aria-label="Close"
                        @click="showSuccess = false"
                    >
                        <i class="hgi hgi-stroke hgi-rounded hgi-cancel-01"></i>
                    </button>
                </div>
            </div>
        </Transition>

        <!-- HERO (unchanged) -->
        <section class="relative pt-20 pb-16 sm:pt-28 sm:pb-20 overflow-hidden">
            <div
                class="absolute -top-32 -left-32 w-96 h-96 rounded-full bg-primary/10 blur-3xl"
            ></div>
            <div
                class="absolute -bottom-32 -right-32 w-md h-112 rounded-full bg-secondary/10 blur-3xl"
            ></div>

            <div class="relative container mx-auto px-6 lg:px-10 text-center">
                <span
                    class="inline-flex items-center gap-2 badge badge-primary badge-outline badge-lg uppercase tracking-widest text-xs mb-5"
                >
                    <i class="hgi hgi-stroke hgi-rounded hgi-calendar-03"></i>
                    Reservations
                </span>
                <h1
                    class="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight leading-tight"
                >
                    Book Your
                    <span
                        class="block bg-linear-to-r from-primary via-secondary to-primary bg-clip-text text-transparent"
                    >
                        Beauty Appointment
                    </span>
                </h1>
                <p
                    class="mt-4 text-base sm:text-lg text-base-content/70 max-w-2xl mx-auto"
                >
                    Pick a service, choose a date and time, and we'll handle the
                    rest. It only takes a minute.
                </p>

                <div
                    v-if="!props.isAuthenticated"
                    class="mt-6 inline-flex items-center gap-2 rounded-full bg-warning/10 text-warning-content border border-warning/30 px-4 py-2 text-sm"
                >
                    <i
                        class="hgi hgi-stroke hgi-rounded hgi-information-circle"
                    ></i>
                    <span>
                        Please
                        <a
                            href="/mi-bella-la-vida/login"
                            class="font-semibold underline"
                            >sign in</a
                        >
                        to complete your booking.
                    </span>
                </div>
            </div>
        </section>

        <!-- MAIN GRID (unchanged layout) -->
        <section class="pb-20">
            <div
                class="container mx-auto px-4 sm:px-6 lg:px-10 grid grid-cols-1 lg:grid-cols-12 gap-8"
            >
                <!-- LEFT: services -->
                <div class="lg:col-span-7 xl:col-span-8 space-y-6">
                    <div
                        class="card bg-base-100 border border-base-300/60 rounded-3xl shadow-sm"
                    >
                        <div class="card-body p-5 sm:p-6 gap-4">
                            <div class="relative">
                                <span
                                    class="absolute inset-y-0 left-0 flex items-center pl-3 text-base-content/50 z-1"
                                >
                                    <i
                                        class="hgi hgi-stroke hgi-rounded hgi-search-01 text-lg"
                                    ></i>
                                </span>
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Search services (e.g. haircut, rebond, manicure)"
                                    class="input input-bordered w-full rounded-xl pl-10 focus:input-primary"
                                />
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="cat in props.categories"
                                    :key="cat.key"
                                    type="button"
                                    class="btn btn-sm rounded-full"
                                    :class="
                                        activeCategory === cat.key
                                            ? 'btn-primary'
                                            : 'btn-ghost border border-base-300'
                                    "
                                    @click="activeCategory = cat.key"
                                >
                                    {{ cat.label }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="filteredServices.length"
                        class="grid grid-cols-1 sm:grid-cols-2 gap-4"
                    >
                        <button
                            v-for="service in filteredServices"
                            :key="service.id"
                            type="button"
                            class="group text-left card bg-base-100 border rounded-2xl shadow-sm hover:shadow-xl hover:-translate-y-0.5 transition-all duration-200 overflow-hidden"
                            :class="
                                selectedService?.id === service.id
                                    ? 'border-primary ring-2 ring-primary/30'
                                    : 'border-base-300/60'
                            "
                            @click="selectService(service)"
                        >
                            <div class="card-body p-4 sm:p-5">
                                <div
                                    class="flex items-start justify-between gap-3"
                                >
                                    <div class="min-w-0">
                                        <p
                                            class="text-xs uppercase tracking-widest opacity-60 font-medium"
                                        >
                                            {{ service.category }}
                                        </p>
                                        <h3
                                            class="font-semibold text-base sm:text-lg leading-tight mt-1 truncate"
                                        >
                                            {{ service.name }}
                                        </h3>
                                    </div>
                                    <div
                                        class="shrink-0 w-8 h-8 rounded-full flex items-center justify-center transition-colors"
                                        :class="
                                            selectedService?.id === service.id
                                                ? 'bg-primary text-primary-content'
                                                : 'bg-base-200 text-base-content/70 group-hover:bg-primary/10'
                                        "
                                    >
                                        <i
                                            :class="
                                                selectedService?.id ===
                                                service.id
                                                    ? 'hgi hgi-stroke hgi-rounded hgi-checkmark-01'
                                                    : 'hgi hgi-stroke hgi-rounded hgi-add-01'
                                            "
                                        ></i>
                                    </div>
                                </div>

                                <div
                                    class="mt-3 flex items-center justify-between text-sm"
                                >
                                    <span class="font-bold text-primary">
                                        {{ formatPrice(service.price) }}
                                    </span>
                                    <span
                                        class="inline-flex items-center gap-1 text-base-content/60"
                                    >
                                        <i
                                            class="hgi hgi-stroke hgi-rounded hgi-clock-01"
                                        ></i>
                                        {{ service.duration }} min
                                    </span>
                                </div>
                            </div>
                        </button>
                    </div>

                    <div
                        v-else
                        class="card bg-base-100 border border-dashed border-base-300 rounded-2xl"
                    >
                        <div class="card-body items-center text-center py-12">
                            <i
                                class="hgi hgi-stroke hgi-rounded hgi-search-x text-4xl opacity-40"
                            ></i>
                            <p class="font-medium mt-2">No services found</p>
                            <p class="text-sm opacity-60">
                                Try a different keyword or category.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- RIGHT: booking form -->
                <div class="lg:col-span-5 xl:col-span-4">
                    <div class="lg:sticky lg:top-24">
                        <div
                            id="booking-form"
                            class="card bg-base-100 border border-base-300/60 rounded-3xl shadow-xl overflow-hidden scroll-mt-24"
                        >
                            <div
                                class="p-5 sm:p-6 border-b border-base-200"
                                :class="
                                    selectedService
                                        ? 'bg-linear-to-br from-primary/10 via-secondary/5 to-transparent'
                                        : 'bg-base-200/60'
                                "
                            >
                                <div
                                    class="flex items-start justify-between gap-3"
                                >
                                    <div class="min-w-0">
                                        <p
                                            class="text-xs uppercase tracking-widest opacity-60 font-medium"
                                        >
                                            {{
                                                selectedService
                                                    ? "Selected"
                                                    : "No service yet"
                                            }}
                                        </p>
                                        <h2
                                            class="font-bold text-lg leading-tight mt-1 truncate"
                                        >
                                            {{
                                                selectedService
                                                    ? selectedService.name
                                                    : "Choose a service"
                                            }}
                                        </h2>
                                        <p
                                            v-if="selectedService"
                                            class="text-sm opacity-70 mt-1"
                                        >
                                            {{
                                                formatPrice(
                                                    selectedService.price,
                                                )
                                            }}
                                            · {{ selectedService.duration }} min
                                        </p>
                                        <p
                                            v-else
                                            class="text-sm opacity-60 mt-1"
                                        >
                                            Pick one on the left to continue.
                                        </p>
                                    </div>
                                    <button
                                        v-if="selectedService"
                                        type="button"
                                        class="btn btn-ghost btn-xs btn-circle"
                                        title="Remove"
                                        @click="clearSelection"
                                    >
                                        <i
                                            class="hgi hgi-stroke hgi-rounded hgi-cancel-01"
                                        ></i>
                                    </button>
                                </div>
                            </div>

                            <form
                                class="card-body p-5 sm:p-6 space-y-5"
                                @submit.prevent="confirmBooking"
                            >
                                <div
                                    v-if="
                                        hasPrefilledProfile && !editingDetails
                                    "
                                    class="rounded-2xl bg-base-200/60 border border-base-300/60 p-4 space-y-2"
                                >
                                    <div
                                        class="flex items-start justify-between gap-3"
                                    >
                                        <div class="min-w-0">
                                            <p
                                                class="text-xs uppercase tracking-widest opacity-60 font-medium"
                                            >
                                                Booking as
                                            </p>
                                            <p
                                                class="font-semibold truncate mt-1"
                                            >
                                                {{ form.full_name || "—" }}
                                            </p>
                                            <p
                                                class="text-sm opacity-70 truncate"
                                            >
                                                {{ form.phone || form.email }}
                                            </p>
                                        </div>
                                        <button
                                            type="button"
                                            class="btn btn-ghost btn-xs rounded-full shrink-0"
                                            @click="editingDetails = true"
                                        >
                                            <i
                                                class="hgi hgi-stroke hgi-rounded hgi-edit-02"
                                            ></i>
                                            Edit
                                        </button>
                                    </div>
                                    <p class="text-xs opacity-60">
                                        We'll use your account details for this
                                        booking.
                                    </p>
                                </div>

                                <div v-else class="space-y-5">
                                    <!-- Full Name -->
                                    <div class="flex flex-col gap-1.5">
                                        <label
                                            for="full_name"
                                            class="text-sm font-medium text-base-content/80"
                                        >
                                            Full Name
                                        </label>
                                        <input
                                            id="full_name"
                                            v-model="form.full_name"
                                            type="text"
                                            placeholder="Jane Doe"
                                            class="input input-bordered w-full rounded-xl focus:input-primary"
                                            :class="{
                                                'input-error':
                                                    form.errors.full_name,
                                            }"
                                        />
                                        <p
                                            v-if="form.errors.full_name"
                                            class="text-error text-xs"
                                        >
                                            {{ form.errors.full_name }}
                                        </p>
                                    </div>

                                    <!-- Phone -->
                                    <div class="flex flex-col gap-1.5">
                                        <label
                                            for="phone"
                                            class="text-sm font-medium text-base-content/80"
                                        >
                                            Phone Number
                                        </label>
                                        <input
                                            id="phone"
                                            v-model="form.phone"
                                            type="tel"
                                            placeholder="+63 912 345 6789"
                                            class="input input-bordered w-full rounded-xl focus:input-primary"
                                            :class="{
                                                'input-error':
                                                    form.errors.phone,
                                            }"
                                        />
                                        <p
                                            v-if="form.errors.phone"
                                            class="text-error text-xs"
                                        >
                                            {{ form.errors.phone }}
                                        </p>
                                    </div>

                                    <!-- Email -->
                                    <div class="flex flex-col gap-1.5">
                                        <label
                                            for="email"
                                            class="text-sm font-medium text-base-content/80"
                                        >
                                            Email (optional)
                                        </label>
                                        <input
                                            id="email"
                                            v-model="form.email"
                                            type="email"
                                            placeholder="you@example.com"
                                            class="input input-bordered w-full rounded-xl focus:input-primary"
                                            :class="{
                                                'input-error':
                                                    form.errors.email,
                                            }"
                                        />
                                        <p
                                            v-if="form.errors.email"
                                            class="text-error text-xs"
                                        >
                                            {{ form.errors.email }}
                                        </p>
                                    </div>

                                    <!-- Back to prefilled mode (only for authenticated customers) -->
                                    <button
                                        v-if="hasPrefilledProfile"
                                        type="button"
                                        class="btn btn-ghost btn-xs self-start"
                                        @click="editingDetails = false"
                                    >
                                        <i
                                            class="hgi hgi-stroke hgi-rounded hgi-arrow-left-01"
                                        ></i>
                                        Use my account details
                                    </button>
                                </div>

                                <!-- Preferred Date -->
                                <div class="flex flex-col gap-1.5">
                                    <label
                                        for="booking_date"
                                        class="text-sm font-medium text-base-content/80"
                                    >
                                        Preferred Date
                                    </label>
                                    <input
                                        id="booking_date"
                                        v-model="form.booking_date"
                                        type="date"
                                        :min="todayIso"
                                        class="input input-bordered w-full rounded-xl focus:input-primary"
                                        :class="{
                                            'input-error':
                                                (form.booking_date &&
                                                    isDateFullyBlocked(
                                                        form.booking_date,
                                                    )) ||
                                                form.errors.booking_date,
                                        }"
                                    />
                                    <p
                                        v-if="
                                            form.booking_date &&
                                            isDateFullyBlocked(
                                                form.booking_date,
                                            )
                                        "
                                        class="text-error text-xs"
                                    >
                                        This date is fully booked. Please choose
                                        another day.
                                    </p>
                                    <p
                                        v-if="form.errors.booking_date"
                                        class="text-error text-xs"
                                    >
                                        {{ form.errors.booking_date }}
                                    </p>
                                </div>

                                <!-- Preferred Time -->
                                <div class="flex flex-col gap-1.5">
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <span
                                            class="text-sm font-medium text-base-content/80"
                                        >
                                            Preferred Time
                                        </span>
                                        <span
                                            v-if="!form.booking_date"
                                            class="text-xs opacity-60"
                                        >
                                            Pick a date first
                                        </span>
                                    </div>
                                    <div
                                        class="grid grid-cols-3 sm:grid-cols-4 gap-2"
                                    >
                                        <button
                                            v-for="slot in props.timeSlots"
                                            :key="slot"
                                            type="button"
                                            class="btn btn-sm rounded-lg justify-center font-medium"
                                            :class="[
                                                form.time_slot === slot &&
                                                isSlotAvailable(
                                                    form.booking_date,
                                                    slot,
                                                )
                                                    ? 'btn-primary'
                                                    : 'btn-ghost border border-base-300',
                                                !canPickSlot(slot)
                                                    ? 'btn-disabled opacity-50 line-through'
                                                    : '',
                                            ]"
                                            :disabled="!canPickSlot(slot)"
                                            @click="form.time_slot = slot"
                                        >
                                            {{ slot }}
                                        </button>
                                    </div>
                                    <p
                                        v-if="form.errors.time_slot"
                                        class="text-error text-xs"
                                    >
                                        {{ form.errors.time_slot }}
                                    </p>
                                </div>

                                <!-- Notes -->
                                <div class="flex flex-col gap-1.5">
                                    <label
                                        for="notes"
                                        class="text-sm font-medium text-base-content/80"
                                    >
                                        Notes (optional)
                                    </label>
                                    <textarea
                                        id="notes"
                                        v-model="form.notes"
                                        rows="3"
                                        placeholder="Any preferences or special requests?"
                                        class="textarea textarea-bordered w-full rounded-xl focus:textarea-primary"
                                    ></textarea>
                                    <p
                                        v-if="form.errors.notes"
                                        class="text-error text-xs"
                                    >
                                        {{ form.errors.notes }}
                                    </p>
                                </div>

                                <!-- Submit -->
                                <div class="pt-1">
                                    <button
                                        type="submit"
                                        class="btn btn-primary btn-lg btn-block rounded-full shadow-lg shadow-primary/25"
                                        :disabled="
                                            form.processing ||
                                            !selectedService ||
                                            !form.booking_date ||
                                            !form.time_slot
                                        "
                                    >
                                        <span
                                            v-if="form.processing"
                                            class="loading loading-spinner loading-sm"
                                        ></span>
                                        <template v-else>
                                            <i
                                                class="hgi hgi-stroke hgi-rounded hgi-calendar-check-01"
                                            ></i>
                                            Confirm Booking
                                        </template>
                                    </button>
                                    <p
                                        class="text-xs text-center opacity-60 mt-2"
                                    >
                                        You'll receive a confirmation shortly
                                        after submitting.
                                    </p>
                                </div>
                            </form>
                        </div>

                        <div
                            class="mt-4 card bg-base-200/60 border border-base-300/60 rounded-2xl"
                        >
                            <div class="card-body p-4 sm:p-5">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="rounded-full bg-primary/10 p-2 shrink-0"
                                    >
                                        <i
                                            class="hgi hgi-stroke hgi-rounded hgi-customer-support text-primary"
                                        ></i>
                                    </div>
                                    <div class="text-sm">
                                        <p class="font-semibold">
                                            Need help booking?
                                        </p>
                                        <p class="opacity-70 mt-0.5">
                                            Call us at
                                            <span class="font-medium"
                                                >+63 912 345 6789</span
                                            >
                                            or visit us during opening hours.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>
