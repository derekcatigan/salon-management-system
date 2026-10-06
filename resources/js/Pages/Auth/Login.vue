<!-- resources/js/Pages/Auth/Login.vue -->
<script setup>
import { ref, computed, watch, onBeforeUnmount } from "vue";
import { useForm, usePage, router } from "@inertiajs/vue3";
import { toast } from "vue-sonner";

defineProps({
    logo: String,
});

const page = usePage();

const togglePassword = ref(false);

const form = useForm({
    email: "",
    password: "",
    remember: false,
});

/* ------------------------------------------------------------------ */
/* Throttle state                                                      */
/* ------------------------------------------------------------------ */
const lockSeconds = ref(0);
const attemptsLeft = ref(null);
const maxAttempts = ref(5);

let timer = null;

function startCountdown(seconds) {
    stopCountdown();
    lockSeconds.value = Math.max(0, Math.ceil(seconds));
    if (lockSeconds.value <= 0) return;

    timer = setInterval(() => {
        lockSeconds.value = Math.max(0, lockSeconds.value - 1);

        if (lockSeconds.value === 0) {
            stopCountdown();
            router.reload({ preserveScroll: true });
        }
    }, 1000);
}

function stopCountdown() {
    if (timer) {
        clearInterval(timer);
        timer = null;
    }
}

const isLocked = computed(() => lockSeconds.value > 0);

/* ------------------------------------------------------------------ */
/* Sync throttle info from Inertia props                               */
/* ------------------------------------------------------------------ */
watch(
    () => page.props.throttle,
    (t) => {
        if (!t) return;

        if (t.seconds && t.seconds > 0) {
            startCountdown(t.seconds);
        }

        if (typeof t.attempts === "number" && typeof t.max === "number") {
            attemptsLeft.value = Math.max(0, t.max - t.attempts);
            maxAttempts.value = t.max;
        }
    },
    { immediate: true, deep: true },
);

onBeforeUnmount(stopCountdown);

/* ------------------------------------------------------------------ */
/* Submit                                                              */
/* ------------------------------------------------------------------ */
function submit() {
    if (isLocked.value) {
        toast.error(`Too many attempts. Try again in ${lockSeconds.value}s.`);
        return;
    }

    form.post("/login", {
        preserveScroll: true,
        onError: (errors) => {
            Object.values(errors).forEach((error) => toast.error(error));
            form.password = "";
        },
        onSuccess: () => {
            form.reset();
            stopCountdown();
        },
    });
}
</script>

<template>
    <main class="min-h-screen flex justify-center items-center p-3">
        <div
            class="w-full max-w-lg bg-white border border-gray-300 p-3 shadow rounded-sm"
        >
            <!-- Logo -->
            <div class="w-full flex items-center gap-3 mb-5">
                <img
                    :src="logo"
                    alt="Salon Logo"
                    class="w-auto h-16 object-cover rounded-sm shadow"
                />
                <div>
                    <h1 class="text-xl font-bold">MI BELLA - VIDA</h1>
                    <h3 class="text-xs font-medium text-gray-500">
                        BEAUTY SALON AND SPA
                    </h3>
                </div>
            </div>

            <div
                class="mb-5 bg-gray-100 border border-gray-300 rounded-sm shadow p-3"
            >
                <h1 class="text-xl font-bold text-center">Welcome, back!</h1>
                <p class="text-xs font-medium text-gray-500 text-center">
                    Log in credentials to access your account.
                </p>
            </div>

            <!-- Throttle banner -->
            <div
                v-if="isLocked"
                class="mb-4 flex items-center gap-2 bg-error/10 border border-error/30 text-error rounded-sm px-3 py-2 text-xs"
            >
                <svg
                    class="h-4 w-4 shrink-0"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <circle cx="12" cy="12" r="10" />
                    <path d="M12 6v6l4 2" />
                </svg>
                <span>
                    Too many attempts. Please wait
                    <strong>{{ lockSeconds }}s</strong> before trying again.
                </span>
            </div>

            <!-- Attempts remaining hint (optional) -->
            <div
                v-else-if="attemptsLeft !== null && attemptsLeft < maxAttempts"
                class="mb-4 text-xs text-warning"
            >
                {{ attemptsLeft }} of {{ maxAttempts }} attempts remaining.
            </div>

            <form @submit.prevent="submit()">
                <!-- Email -->
                <fieldset class="fieldset">
                    <legend class="fieldset-legend">Email:</legend>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        autocomplete="username"
                        :disabled="form.processing || isLocked"
                        class="input input-sm w-full"
                        :class="{ 'input-error': form.errors.email }"
                        placeholder="Enter email"
                    />
                    <p v-if="form.errors.email" class="mt-1 text-xs text-error">
                        {{ form.errors.email }}
                    </p>
                </fieldset>

                <!-- Password -->
                <fieldset class="fieldset">
                    <legend class="fieldset-legend">Password:</legend>
                    <input
                        id="password"
                        v-model="form.password"
                        :type="togglePassword ? 'text' : 'password'"
                        autocomplete="current-password"
                        :disabled="form.processing || isLocked"
                        class="input input-sm w-full"
                        :class="{ 'input-error': form.errors.password }"
                        placeholder="Enter password"
                    />
                    <p
                        v-if="form.errors.password"
                        class="mt-1 text-xs text-error"
                    >
                        {{ form.errors.password }}
                    </p>
                </fieldset>

                <div class="flex justify-between items-center gap-2 mt-1">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input
                            v-model="form.remember"
                            type="checkbox"
                            class="checkbox checkbox-sm"
                            :disabled="isLocked"
                        />
                        <span class="text-xs text-gray-500">Remember me</span>
                    </label>

                    <label class="flex items-center gap-2 cursor-pointer">
                        <input
                            v-model="togglePassword"
                            type="checkbox"
                            class="checkbox checkbox-sm"
                        />
                        <span class="text-xs text-gray-500">Show password</span>
                    </label>
                </div>

                <div class="mt-5">
                    <button
                        type="submit"
                        class="btn btn-sm btn-secondary btn-block"
                        :disabled="form.processing || isLocked"
                    >
                        <span
                            v-if="form.processing"
                            class="loading loading-spinner loading-xs"
                        ></span>
                        {{
                            isLocked
                                ? `Locked (${lockSeconds}s)`
                                : form.processing
                                  ? "Signing in..."
                                  : "Sign in"
                        }}
                    </button>
                </div>
            </form>
        </div>
    </main>
</template>
