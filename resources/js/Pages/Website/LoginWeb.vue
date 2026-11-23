<!-- resources\js\Pages\Website\LoginWeb.vue -->
<script setup>
import { ref, computed, watch, onBeforeUnmount } from "vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import WebLayout from "@/Layouts/WebLayout.vue";

defineOptions({ layout: WebLayout });

const showPassword = ref(false);

const form = useForm({
    email: "",
    password: "",
    remember: false,
});

/* ------------------------------------------------------------------ */
/* Rate-limit / countdown state                                       */
/* ------------------------------------------------------------------ */
const countdown = ref(0); // seconds remaining on throttle
const throttled = ref(false); // true while countdown > 0
let timerId = null;

const countdownLabel = computed(() => {
    const m = Math.floor(countdown.value / 60);
    const s = countdown.value % 60;
    return `${String(m).padStart(2, "0")}:${String(s).padStart(2, "0")}`;
});

const startCountdown = (seconds) => {
    clearInterval(timerId);
    countdown.value = Math.max(0, seconds);
    throttled.value = countdown.value > 0;

    if (countdown.value === 0) return;

    timerId = setInterval(() => {
        countdown.value -= 1;
        if (countdown.value <= 0) {
            clearInterval(timerId);
            countdown.value = 0;
            throttled.value = false;
        }
    }, 1000);
};

onBeforeUnmount(() => clearInterval(timerId));

/* ------------------------------------------------------------------ */
/* Parse the server error message to start the countdown              */
/* ------------------------------------------------------------------ */
watch(
    () => form.errors.email,
    (msg) => {
        if (!msg) return;
        const match = msg.match(/(\d+)\s*seconds?/i);
        if (match) startCountdown(parseInt(match[1], 10));
    },
);

/* Reset countdown when user edits the email (fresh attempt) */
watch(
    () => form.email,
    () => {
        if (throttled.value) return; // don't reset while still counting
        clearInterval(timerId);
        countdown.value = 0;
    },
);

/* ------------------------------------------------------------------ */
/* Submit                                                             */
/* ------------------------------------------------------------------ */
const submit = () => {
    if (throttled.value) return;

    form.post(route("auth.web.login"), {
        preserveScroll: true,
        onFinish: () => form.reset("password"),
    });
};
</script>

<template>
    <Head title="Sign In" />

    <div
        class="relative min-h-screen flex items-center justify-center overflow-hidden bg-base-100 text-base-content py-12 px-4 sm:px-6 lg:px-8"
    >
        <!-- blobs -->
        <div
            class="absolute -top-32 -left-32 w-96 h-96 rounded-full bg-primary/10 blur-3xl"
        ></div>
        <div
            class="absolute -bottom-40 -right-32 w-md h-112 rounded-full bg-secondary/10 blur-3xl"
        ></div>

        <div
            class="relative w-full max-w-5xl bg-base-100 rounded-3xl shadow-2xl shadow-base-300/50 border border-base-300/60 overflow-hidden grid grid-cols-1 lg:grid-cols-2"
        >
            <!-- left panel (unchanged) -->
            <div
                class="relative hidden lg:flex flex-col justify-between bg-base-200 p-10 overflow-hidden"
            >
                <div
                    class="absolute inset-0 bg-linear-to-br from-primary/20 via-secondary/10 to-transparent"
                ></div>

                <div class="relative z-10">
                    <span
                        class="inline-flex items-center gap-2 badge badge-primary badge-lg badge-outline font-medium tracking-wide uppercase text-xs"
                    >
                        <i class="hgi hgi-stroke hgi-rounded hgi-sparkles"></i>
                        MI Bella Vida
                    </span>
                    <h2
                        class="mt-6 text-3xl xl:text-4xl font-extrabold leading-tight tracking-tight"
                    >
                        Welcome back to
                        <span
                            class="block bg-linear-to-r from-primary via-secondary to-primary bg-clip-text text-transparent"
                        >
                            Beauty & Elegance
                        </span>
                    </h2>
                    <p class="mt-4 text-base-content/70 max-w-sm">
                        Sign in to manage your appointments and discover our
                        latest salon services.
                    </p>
                </div>

                <div
                    class="relative z-10 mt-10 aspect-square w-full max-w-xs mx-auto rounded-4xl bg-base-100 border-2 border-dashed border-primary/30 flex items-center justify-center shadow-lg"
                >
                    <div class="text-center opacity-50">
                        <i
                            class="hgi hgi-stroke hgi-rounded hgi-image-01 text-5xl"
                        ></i>
                        <p class="mt-2 text-sm font-medium">
                            Login Image Placeholder
                        </p>
                    </div>
                </div>

                <p class="relative z-10 mt-8 text-xs text-base-content/50">
                    &copy; {{ new Date().getFullYear() }} MI Bella Vida. All
                    rights reserved.
                </p>
            </div>

            <!-- right form -->
            <div class="p-6 sm:p-10 lg:p-12">
                <div class="lg:hidden text-center mb-8">
                    <span
                        class="inline-flex items-center gap-2 badge badge-primary badge-outline badge-sm uppercase tracking-widest"
                    >
                        <i class="hgi hgi-stroke hgi-rounded hgi-sparkles"></i>
                        MI Bella Vida
                    </span>
                </div>

                <div class="max-w-sm mx-auto">
                    <h1
                        class="text-2xl sm:text-3xl font-extrabold tracking-tight"
                    >
                        Sign in
                    </h1>
                    <p class="mt-2 text-sm text-base-content/70">
                        Enter your credentials to access your account.
                    </p>

                    <!-- ============ RATE-LIMIT BANNER ============ -->
                    <div
                        v-if="throttled || form.errors.email"
                        class="mt-6 alert rounded-2xl border flex items-start gap-3 py-3 px-4 text-sm"
                        :class="
                            throttled
                                ? 'bg-error/10 border-error/30 text-error'
                                : 'bg-warning/10 border-warning/30 text-warning-content'
                        "
                        role="alert"
                    >
                        <i
                            :class="
                                throttled
                                    ? 'hgi hgi-stroke hgi-rounded hgi-alert-02'
                                    : 'hgi hgi-stroke hgi-rounded hgi-information-circle'
                            "
                            class="text-xl shrink-0 mt-0.5"
                        ></i>

                        <div class="flex-1 min-w-0">
                            <p
                                v-if="throttled"
                                class="font-semibold leading-tight"
                            >
                                Too many login attempts
                            </p>
                            <p v-else class="font-semibold leading-tight">
                                Sign-in failed
                            </p>

                            <p class="mt-1 opacity-80 wrap-break-word">
                                {{ form.errors.email }}
                            </p>

                            <div
                                v-if="throttled"
                                class="mt-3 flex items-center gap-2 flex-wrap"
                            >
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full bg-error/20 px-3 py-1 text-xs font-bold tabular-nums"
                                >
                                    <i
                                        class="hgi hgi-stroke hgi-rounded hgi-clock-01"
                                    ></i>
                                    Retry in {{ countdownLabel }}
                                </span>
                                <span class="text-xs opacity-70">
                                    Please wait before trying again.
                                </span>
                            </div>
                        </div>
                    </div>
                    <!-- ========== /RATE-LIMIT BANNER ========== -->

                    <!-- Google: NON-FUNCTIONAL for now -->
                    <button
                        type="button"
                        class="btn btn-outline btn-block rounded-full mt-6 gap-3 normal-case font-medium"
                        disabled
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 48 48"
                            class="w-5 h-5"
                        >
                            <path
                                fill="#FFC107"
                                d="M43.6 20.5H42V20H24v8h11.3C33.7 32.9 29.3 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.9 1.2 8 3.1l5.7-5.7C34.3 6.1 29.4 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.1-2.4-.4-3.5z"
                            />
                            <path
                                fill="#FF3D00"
                                d="M6.3 14.7l6.6 4.8C14.7 15.1 19 12 24 12c3.1 0 5.9 1.2 8 3.1l5.7-5.7C34.3 6.1 29.4 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"
                            />
                            <path
                                fill="#4CAF50"
                                d="M24 44c5.3 0 10.1-2 13.7-5.3l-6.3-5.3C29.4 35.1 26.8 36 24 36c-5.2 0-9.6-3.1-11.3-7.6l-6.5 5C9.5 39.6 16.2 44 24 44z"
                            />
                            <path
                                fill="#1976D2"
                                d="M43.6 20.5H42V20H24v8h11.3c-.8 2.3-2.2 4.2-4.1 5.6l6.3 5.3C41.4 35.5 44 30.2 44 24c0-1.3-.1-2.4-.4-3.5z"
                            />
                        </svg>
                        Continue with Google
                    </button>

                    <div class="divider text-xs text-base-content/50 my-6">
                        OR
                    </div>

                    <form class="space-y-4" @submit.prevent="submit">
                        <div class="form-control">
                            <label class="label pb-1">
                                <span class="label-text font-medium"
                                    >Email Address</span
                                >
                            </label>
                            <div class="relative">
                                <span
                                    class="absolute inset-y-0 left-0 flex items-center pl-3 text-base-content/50 z-1"
                                >
                                    <i
                                        class="hgi hgi-stroke hgi-rounded hgi-mail-01 text-lg"
                                    ></i>
                                </span>
                                <input
                                    v-model="form.email"
                                    type="email"
                                    placeholder="you@example.com"
                                    class="input input-bordered w-full rounded-xl pl-10 focus:input-primary"
                                    :class="{
                                        'input-error': form.errors.email,
                                    }"
                                    autocomplete="email"
                                    :disabled="throttled"
                                />
                            </div>
                        </div>

                        <div class="form-control">
                            <div class="flex items-center justify-between pb-1">
                                <label class="label p-0">
                                    <span class="label-text font-medium"
                                        >Password</span
                                    >
                                </label>
                                <a
                                    href="#"
                                    class="text-xs text-primary font-medium hover:underline"
                                >
                                    Forgot password?
                                </a>
                            </div>
                            <div class="relative">
                                <span
                                    class="absolute inset-y-0 left-0 flex items-center pl-3 text-base-content/50 z-1"
                                >
                                    <i
                                        class="hgi hgi-stroke hgi-rounded hgi-lock-password text-lg"
                                    ></i>
                                </span>
                                <input
                                    v-model="form.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    placeholder="Enter your password"
                                    class="input input-bordered w-full rounded-xl pl-10 pr-10 focus:input-primary"
                                    :class="{
                                        'input-error': form.errors.password,
                                    }"
                                    autocomplete="current-password"
                                    :disabled="throttled"
                                />
                                <button
                                    type="button"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-base-content/50 hover:text-base-content disabled:opacity-40"
                                    :disabled="throttled"
                                    @click="showPassword = !showPassword"
                                >
                                    <i
                                        :class="
                                            showPassword
                                                ? 'hgi hgi-stroke hgi-rounded hgi-view'
                                                : 'hgi hgi-stroke hgi-rounded hgi-view-off'
                                        "
                                        class="text-lg"
                                    ></i>
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 pt-1">
                            <input
                                v-model="form.remember"
                                type="checkbox"
                                class="checkbox checkbox-primary checkbox-sm rounded"
                                :disabled="throttled"
                            />
                            <span class="text-sm text-base-content/70"
                                >Remember me</span
                            >
                        </div>

                        <button
                            type="submit"
                            class="btn btn-primary btn-lg btn-block rounded-full shadow-lg shadow-primary/25 mt-2"
                            :disabled="form.processing || throttled"
                        >
                            <span
                                v-if="form.processing"
                                class="loading loading-spinner loading-sm"
                            ></span>
                            <span v-else-if="throttled">
                                <i
                                    class="hgi hgi-stroke hgi-rounded hgi-clock-01 mr-1"
                                ></i>
                                Try again in {{ countdownLabel }}
                            </span>
                            <span v-else>Sign In</span>
                        </button>
                    </form>

                    <p class="mt-6 text-center text-sm text-base-content/70">
                        Don't have an account?
                        <Link
                            :href="route('register.web.index')"
                            class="text-primary font-semibold hover:underline"
                        >
                            Create one
                        </Link>
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
