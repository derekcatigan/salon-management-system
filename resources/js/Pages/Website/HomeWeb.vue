<!-- resources\js\Pages\Website\HomeWeb.vue -->
<script setup>
import { onMounted, ref, watch } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import WebLayout from "@/Layouts/WebLayout.vue";

defineOptions({ layout: WebLayout });

const page = usePage();
const showSuccess = ref(false);
const successMessage = ref("");

// Placeholder service cards — static data only, no server logic yet
const services = [
    {
        title: "Hair Styling",
        description:
            "Precision cuts, vibrant coloring, and elegant styling tailored to your personality.",
        icon: "hgi hgi-stroke hgi-rounded hgi-scissor",
        accent: "from-rose-400/20 to-pink-500/10",
    },
    {
        title: "Makeup Artistry",
        description:
            "Flawless bridal and event makeup using premium, skin-friendly products.",
        icon: "hgi hgi-stroke hgi-rounded hgi-brush",
        accent: "from-amber-300/20 to-orange-400/10",
    },
    {
        title: "Nail Care",
        description:
            "Luxurious manicures, pedicures, and custom nail art in a relaxing setting.",
        icon: "hgi hgi-stroke hgi-rounded hgi-hand",
        accent: "from-violet-300/20 to-purple-400/10",
    },
    {
        title: "Spa & Massage",
        description:
            "Rejuvenating body treatments and massages to restore your inner balance.",
        icon: "hgi hgi-stroke hgi-rounded hgi-leaf-01",
        accent: "from-emerald-300/20 to-teal-400/10",
    },
];

const hours = [
    { day: "Monday – Friday", time: "9:00 AM – 7:00 PM" },
    { day: "Saturday", time: "9:00 AM – 5:00 PM" },
    { day: "Sunday", time: "Closed" },
];

const stats = [
    { value: "10+", label: "Years of Experience" },
    { value: "5k+", label: "Happy Customers" },
    { value: "20+", label: "Expert Stylists" },
    { value: "4.9", label: "Average Rating" },
];

// Quick perks shown on the CTA card
const perks = [
    {
        icon: "hgi hgi-stroke hgi-rounded hgi-clock-01",
        label: "Takes under a minute",
    },
    {
        icon: "hgi hgi-stroke hgi-rounded hgi-calendar-03",
        label: "Pick any date & time",
    },
    {
        icon: "hgi hgi-stroke hgi-rounded hgi-shield-01",
        label: "No account required",
    },
];

// Reveal-on-scroll
onMounted(() => {
    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((e) => {
                if (e.isIntersecting) {
                    e.target.classList.add("is-visible");
                    observer.unobserve(e.target);
                }
            });
        },
        { threshold: 0.12 },
    );
    document.querySelectorAll(".reveal").forEach((el) => observer.observe(el));
});

const flashSuccess = () => {
    return page.props.flash?.success ?? null;
};

onMounted(() => {
    if (flashSuccess()) {
        successMessage.value = flashSuccess();
        showSuccess.value = true;
        setTimeout(() => (showSuccess.value = false), 5000);
    }
});

watch(
    () => page.props.flash?.success,
    (val) => {
        if (val) {
            successMessage.value = val;
            showSuccess.value = true;
            setTimeout(() => (showSuccess.value = false), 5000);
        }
    },
);
</script>

<template>
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
                            Account created successfully
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

        <!-- ===================== HERO SECTION ===================== -->
        <section
            class="relative min-h-[92vh] flex items-center overflow-hidden scroll-mt-20"
        >
            <!-- Decorative background blobs -->
            <div
                class="absolute -top-24 -left-24 w-96 h-96 rounded-full bg-primary/10 blur-3xl"
            ></div>
            <div
                class="absolute -bottom-32 -right-24 w-md h-112 rounded-full bg-secondary/10 blur-3xl"
            ></div>

            <div
                class="relative container mx-auto px-6 lg:px-10 py-24 grid grid-cols-1 lg:grid-cols-2 gap-14 lg:gap-20 items-center"
            >
                <!-- Copy -->
                <div class="space-y-7 reveal">
                    <span
                        class="inline-flex items-center gap-2 badge badge-primary badge-lg badge-outline font-medium tracking-wide uppercase text-xs"
                    >
                        <i class="hgi hgi-stroke hgi-rounded hgi-sparkles"></i>
                        Welcome to MI Bella Vida
                    </span>

                    <h1
                        class="text-4xl sm:text-5xl lg:text-6xl xl:text-7xl font-extrabold leading-[1.05] tracking-tight"
                    >
                        Where Beauty
                        <span
                            class="block bg-linear-to-r from-primary via-secondary to-primary bg-clip-text text-transparent"
                        >
                            Meets Elegance
                        </span>
                    </h1>

                    <p
                        class="text-base sm:text-lg text-base-content/70 max-w-xl leading-relaxed"
                    >
                        Experience premium salon services tailored to you. From
                        hair and makeup to nails and spa, we bring out your
                        natural glow in a calm, luxurious space.
                    </p>

                    <!-- Responsive CTAs -->
                    <div
                        class="flex flex-col sm:flex-row sm:flex-wrap gap-3 sm:gap-4"
                    >
                        <Link
                            :href="route('appointment.web.index')"
                            class="btn btn-primary btn-lg rounded-full shadow-lg shadow-primary/25 hover:shadow-primary/40 transition-all w-full sm:w-auto"
                        >
                            <i
                                class="hgi hgi-stroke hgi-rounded hgi-calendar-03"
                            ></i>
                            Book Appointment
                        </Link>
                        <a
                            href="#services"
                            class="btn btn-outline btn-lg rounded-full w-full sm:w-auto"
                        >
                            View Services
                        </a>
                    </div>

                    <!-- Trust indicators -->
                    <div
                        class="flex flex-wrap items-center gap-x-6 gap-y-3 pt-4 text-sm text-base-content/60"
                    >
                        <div class="flex items-center gap-2">
                            <i
                                class="hgi hgi-stroke hgi-rounded hgi-checkmark-circle-02 text-primary"
                            ></i>
                            Certified Experts
                        </div>
                        <div class="flex items-center gap-2">
                            <i
                                class="hgi hgi-stroke hgi-rounded hgi-checkmark-circle-02 text-primary"
                            ></i>
                            Premium Products
                        </div>
                        <div class="flex items-center gap-2">
                            <i
                                class="hgi hgi-stroke hgi-rounded hgi-checkmark-circle-02 text-primary"
                            ></i>
                            Relaxing Ambience
                        </div>
                    </div>
                </div>

                <!-- Hero Image Placeholder -->
                <div class="relative reveal">
                    <div
                        class="absolute -inset-4 rounded-[2.5rem] bg-linear-to-br from-primary/20 via-secondary/10 to-transparent blur-2xl"
                    ></div>
                    <div
                        class="relative aspect-4/5 w-full max-w-md mx-auto rounded-[2.5rem] bg-base-200 border-2 border-dashed border-primary/30 flex items-center justify-center shadow-2xl"
                    >
                        <div class="text-center opacity-50">
                            <i
                                class="hgi hgi-stroke hgi-rounded hgi-image-01 text-6xl"
                            ></i>
                            <p class="mt-2 text-sm font-medium">
                                Hero Image Placeholder
                            </p>
                        </div>
                    </div>

                    <!-- Floating badge -->
                    <div
                        class="absolute -bottom-4 left-1/2 -translate-x-1/2 sm:left-auto sm:translate-x-0 bg-base-100 shadow-xl rounded-2xl px-4 py-3 flex items-center gap-3 border border-base-300"
                    >
                        <div class="rounded-full bg-primary/10 p-2">
                            <i
                                class="hgi hgi-stroke hgi-rounded hgi-star text-primary text-xl"
                            ></i>
                        </div>
                        <div>
                            <p class="font-bold leading-none">4.9 / 5</p>
                            <p class="text-xs text-base-content/60 mt-1">
                                Client Rating
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===================== BOOK APPOINTMENT CTA ===================== -->
        <section
            id="book"
            class="relative py-20 sm:py-24 bg-base-200/60 scroll-mt-24"
        >
            <div class="container mx-auto px-6 lg:px-10">
                <div
                    class="text-center max-w-2xl mx-auto mb-12 sm:mb-14 reveal"
                >
                    <span
                        class="badge badge-primary badge-outline badge-sm uppercase tracking-widest mb-4"
                        >Reservations</span
                    >
                    <h2
                        class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight"
                    >
                        Book an Appointment
                    </h2>
                    <p class="mt-4 text-base-content/70">
                        Reserve your spot and let our experts take care of the
                        rest. We'll confirm within a few hours.
                    </p>
                </div>

                <!-- CTA Card -->
                <div class="relative max-w-5xl mx-auto reveal">
                    <!-- Glow -->
                    <div
                        class="absolute -inset-4 rounded-[2.5rem] bg-linear-to-br from-primary/20 via-secondary/10 to-transparent blur-2xl"
                    ></div>

                    <div
                        class="relative card bg-base-100 rounded-4xl border border-base-300/60 shadow-2xl shadow-base-300/40 overflow-hidden"
                    >
                        <div
                            class="grid grid-cols-1 lg:grid-cols-2 items-center"
                        >
                            <!-- Left: Copy + CTAs -->
                            <div class="p-6 sm:p-10 lg:p-12 space-y-6">
                                <span
                                    class="inline-flex items-center gap-2 badge badge-primary badge-lg badge-outline font-medium tracking-wide uppercase text-xs"
                                >
                                    <i
                                        class="hgi hgi-stroke hgi-rounded hgi-calendar-03"
                                    ></i>
                                    Let's get you booked
                                </span>

                                <h3
                                    class="text-2xl sm:text-3xl lg:text-4xl font-extrabold tracking-tight leading-tight"
                                >
                                    Your glow-up
                                    <span
                                        class="block bg-linear-to-r from-primary via-secondary to-primary bg-clip-text text-transparent"
                                    >
                                        starts with one click
                                    </span>
                                </h3>

                                <p class="text-base-content/70 leading-relaxed">
                                    Browse our full menu of hair, nail, spa,
                                    facial, and waxing services. Pick a service,
                                    choose a date and time, and we'll take care
                                    of the rest.
                                </p>

                                <!-- Perks -->
                                <ul
                                    class="grid grid-cols-1 sm:grid-cols-3 gap-3"
                                >
                                    <li
                                        v-for="perk in perks"
                                        :key="perk.label"
                                        class="flex items-center gap-2 rounded-xl bg-base-200/70 border border-base-300/60 px-3 py-2 text-sm"
                                    >
                                        <i
                                            :class="perk.icon"
                                            class="text-primary text-lg shrink-0"
                                        ></i>
                                        <span
                                            class="font-medium leading-tight"
                                            >{{ perk.label }}</span
                                        >
                                    </li>
                                </ul>

                                <!-- CTA buttons -->
                                <div
                                    class="flex flex-col sm:flex-row gap-3 pt-2"
                                >
                                    <Link
                                        :href="route('appointment.web.index')"
                                        class="btn btn-primary btn-lg rounded-full shadow-lg shadow-primary/25 hover:shadow-primary/40 transition-all w-full sm:w-auto"
                                    >
                                        <i
                                            class="hgi hgi-stroke hgi-rounded hgi-calendar-check-01"
                                        ></i>
                                        Start Booking
                                    </Link>
                                    <a
                                        href="#services"
                                        class="btn btn-ghost btn-lg rounded-full border border-base-300 w-full sm:w-auto"
                                    >
                                        Browse Services
                                    </a>
                                </div>
                            </div>

                            <!-- Right: Image placeholder -->
                            <div
                                class="relative order-first lg:order-last p-6 sm:p-10 lg:p-12 lg:pl-0"
                            >
                                <div
                                    class="relative aspect-square w-full max-w-sm mx-auto rounded-4xl bg-base-200 border-2 border-dashed border-primary/30 flex items-center justify-center"
                                >
                                    <div class="text-center opacity-50">
                                        <i
                                            class="hgi hgi-stroke hgi-rounded hgi-image-01 text-6xl"
                                        ></i>
                                        <p class="mt-2 text-sm font-medium">
                                            Appointment Image Placeholder
                                        </p>
                                    </div>
                                </div>

                                <!-- Floating time badge -->
                                <div
                                    class="absolute top-6 right-6 lg:right-10 bg-base-100 shadow-xl rounded-2xl px-4 py-3 flex items-center gap-3 border border-base-300"
                                >
                                    <div class="rounded-full bg-primary/10 p-2">
                                        <i
                                            class="hgi hgi-stroke hgi-rounded hgi-clock-01 text-primary text-xl"
                                        ></i>
                                    </div>
                                    <div>
                                        <p class="font-bold leading-none">
                                            Open today
                                        </p>
                                        <p
                                            class="text-xs text-base-content/60 mt-1"
                                        >
                                            Until 7:00 PM
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===================== SERVICES SECTION ===================== -->
        <section id="services" class="py-20 sm:py-24 bg-base-100 scroll-mt-24">
            <div class="container mx-auto px-6 lg:px-10">
                <div
                    class="text-center max-w-2xl mx-auto mb-12 sm:mb-14 reveal"
                >
                    <span
                        class="badge badge-primary badge-outline badge-sm uppercase tracking-widest mb-4"
                        >What We Offer</span
                    >
                    <h2
                        class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight"
                    >
                        Our Signature Services
                    </h2>
                    <p class="mt-4 text-base-content/70">
                        A full range of beauty and wellness services under one
                        roof, crafted to help you feel your best.
                    </p>
                </div>

                <div
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6"
                >
                    <div
                        v-for="service in services"
                        :key="service.title"
                        class="group relative card bg-base-100 border border-base-300/70 rounded-3xl shadow-sm hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 overflow-hidden reveal"
                    >
                        <div
                            class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 bg-linear-to-br"
                            :class="service.accent"
                        ></div>

                        <div
                            class="relative card-body items-center text-center p-6 sm:p-8"
                        >
                            <div
                                class="rounded-2xl bg-base-200 p-4 mb-3 group-hover:bg-primary group-hover:text-primary-content transition-colors duration-300"
                            >
                                <i :class="service.icon" class="text-3xl"></i>
                            </div>
                            <h3 class="card-title text-lg">
                                {{ service.title }}
                            </h3>
                            <p
                                class="text-sm text-base-content/60 leading-relaxed"
                            >
                                {{ service.description }}
                            </p>
                            <Link
                                :href="route('appointment.web.index')"
                                class="mt-2 text-primary font-medium text-sm inline-flex items-center gap-1 sm:opacity-0 group-hover:opacity-100 transition-opacity"
                            >
                                Book now
                                <i
                                    class="hgi hgi-stroke hgi-rounded hgi-arrow-right-01"
                                ></i>
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Services Image Placeholder -->
                <div
                    class="mt-12 sm:mt-16 aspect-16/6 w-full rounded-3xl bg-base-200 border-2 border-dashed border-primary/30 flex items-center justify-center reveal"
                >
                    <div class="text-center opacity-50">
                        <i
                            class="hgi hgi-stroke hgi-rounded hgi-image-01 text-5xl sm:text-6xl"
                        ></i>
                        <p class="mt-2 text-sm font-medium">
                            Services Banner Image Placeholder
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===================== ABOUT SECTION ===================== -->
        <section
            id="about"
            class="py-20 sm:py-24 bg-base-200/60 scroll-mt-24 relative overflow-hidden"
        >
            <!-- Decorative blob -->
            <div
                class="absolute top-1/2 -left-40 w-96 h-96 rounded-full bg-primary/10 blur-3xl -translate-y-1/2"
            ></div>

            <div
                class="relative container mx-auto px-6 lg:px-10 grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-20 items-center"
            >
                <!-- About Image Placeholder -->
                <div class="relative order-2 lg:order-1 reveal">
                    <div
                        class="absolute -inset-4 rounded-[2.5rem] bg-linear-to-tr from-secondary/20 via-primary/10 to-transparent blur-2xl"
                    ></div>
                    <div
                        class="relative aspect-square w-full max-w-md mx-auto rounded-[2.5rem] bg-base-100 border-2 border-dashed border-primary/30 flex items-center justify-center shadow-xl"
                    >
                        <div class="text-center opacity-50">
                            <i
                                class="hgi hgi-stroke hgi-rounded hgi-image-01 text-6xl"
                            ></i>
                            <p class="mt-2 text-sm font-medium">
                                About Image Placeholder
                            </p>
                        </div>
                    </div>
                </div>

                <div class="space-y-6 order-1 lg:order-2 reveal">
                    <span
                        class="badge badge-primary badge-outline badge-sm uppercase tracking-widest"
                        >About Us</span
                    >
                    <h2
                        class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight"
                    >
                        More Than Just a Salon
                    </h2>
                    <p class="text-base-content/70 leading-relaxed">
                        At MI Bella Vida, we believe beauty is a form of
                        self-care. Our team of skilled professionals is
                        dedicated to helping you look and feel your best in a
                        warm, welcoming environment.
                    </p>
                    <p class="text-base-content/70 leading-relaxed">
                        With years of experience and a passion for excellence,
                        we use only premium products and the latest techniques
                        to deliver results you'll love — every single visit.
                    </p>

                    <!-- Stats -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-2">
                        <div
                            v-for="stat in stats"
                            :key="stat.label"
                            class="rounded-2xl bg-base-100 border border-base-300/70 p-4 text-center shadow-sm"
                        >
                            <p
                                class="text-2xl font-extrabold text-primary leading-none"
                            >
                                {{ stat.value }}
                            </p>
                            <p class="text-xs text-base-content/60 mt-2">
                                {{ stat.label }}
                            </p>
                        </div>
                    </div>

                    <!-- Opening Hours -->
                    <div
                        class="rounded-2xl bg-base-100 border border-base-300/70 p-5 sm:p-6 space-y-3 shadow-sm"
                    >
                        <h3
                            class="font-semibold text-lg flex items-center gap-2"
                        >
                            <i
                                class="hgi hgi-stroke hgi-rounded hgi-clock-01 text-primary"
                            ></i>
                            Opening Hours
                        </h3>
                        <div
                            v-for="h in hours"
                            :key="h.day"
                            class="flex justify-between text-sm border-b border-base-200 last:border-0 pb-2 last:pb-0"
                        >
                            <span class="text-base-content/70">{{
                                h.day
                            }}</span>
                            <span class="font-medium">{{ h.time }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>

<style scoped>
/* Reveal on scroll */
.reveal {
    opacity: 0;
    transform: translateY(24px);
    transition:
        opacity 0.7s ease,
        transform 0.7s ease;
}
.reveal.is-visible {
    opacity: 1;
    transform: translateY(0);
}

/* Smooth scrolling + offset for fixed navbar */
html {
    scroll-behavior: smooth;
    scroll-padding-top: 5rem;
}

/* Respect reduced motion */
@media (prefers-reduced-motion: reduce) {
    .reveal {
        opacity: 1;
        transform: none;
        transition: none;
    }
    html {
        scroll-behavior: auto;
    }
}
</style>
