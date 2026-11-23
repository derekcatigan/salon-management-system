<!-- resources\js\Pages\Website\RegisterWeb.vue -->
<script setup>
import { ref } from "vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import WebLayout from "@/Layouts/WebLayout.vue";

defineOptions({ layout: WebLayout });

const showPassword = ref(false);
const showConfirmPassword = ref(false);

const form = useForm({
    email: "",
    password: "",
    password_confirmation: "",
    terms: false,
});

const submit = () => {
    form.post(route("register.web.store"), {
        preserveScroll: true,
        onFinish: () => form.reset("password", "password_confirmation"),
    });
};
</script>

<template>
    <Head title="Create Account" />

    <div
        class="relative min-h-screen flex items-center justify-center overflow-hidden bg-base-100 text-base-content py-12 px-4 sm:px-6 lg:px-8"
    >
        <div
            class="absolute -top-32 -right-32 w-96 h-96 rounded-full bg-primary/10 blur-3xl"
        ></div>
        <div
            class="absolute -bottom-40 -left-32 w-md h-112 rounded-full bg-secondary/10 blur-3xl"
        ></div>

        <div
            class="relative w-full max-w-5xl bg-base-100 rounded-3xl shadow-2xl shadow-base-300/50 border border-base-300/60 overflow-hidden grid grid-cols-1 lg:grid-cols-2"
        >
            <!-- left panel -->
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
                        Begin your
                        <span
                            class="block bg-linear-to-r from-primary via-secondary to-primary bg-clip-text text-transparent"
                        >
                            Beauty Journey
                        </span>
                    </h2>
                    <p class="mt-4 text-base-content/70 max-w-sm">
                        Create an account to book appointments, track your
                        visits, and enjoy exclusive salon perks.
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
                            Register Image Placeholder
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
                        Create an account
                    </h1>
                    <p class="mt-2 text-sm text-base-content/70">
                        Join us and start booking your beauty appointments.
                    </p>

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
                        Sign up with Google
                    </button>

                    <div class="divider text-xs text-base-content/50 my-6">
                        OR
                    </div>

                    <form class="space-y-4" @submit.prevent="submit">
                        <!-- Email -->
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
                                />
                            </div>
                            <p
                                v-if="form.errors.email"
                                class="text-error text-xs mt-1"
                            >
                                {{ form.errors.email }}
                            </p>
                        </div>

                        <!-- Password -->
                        <div class="form-control">
                            <label class="label pb-1">
                                <span class="label-text font-medium"
                                    >Password</span
                                >
                            </label>
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
                                    placeholder="Create a password"
                                    class="input input-bordered w-full rounded-xl pl-10 pr-10 focus:input-primary"
                                    :class="{
                                        'input-error': form.errors.password,
                                    }"
                                    autocomplete="new-password"
                                />
                                <button
                                    type="button"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-base-content/50 hover:text-base-content"
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
                            <p
                                v-if="form.errors.password"
                                class="text-error text-xs mt-1"
                            >
                                {{ form.errors.password }}
                            </p>
                        </div>

                        <!-- Confirm Password -->
                        <div class="form-control">
                            <label class="label pb-1">
                                <span class="label-text font-medium"
                                    >Confirm Password</span
                                >
                            </label>
                            <div class="relative">
                                <span
                                    class="absolute inset-y-0 left-0 flex items-center pl-3 text-base-content/50 z-1"
                                >
                                    <i
                                        class="hgi hgi-stroke hgi-rounded hgi-lock-password text-lg"
                                    ></i>
                                </span>
                                <input
                                    v-model="form.password_confirmation"
                                    :type="
                                        showConfirmPassword
                                            ? 'text'
                                            : 'password'
                                    "
                                    placeholder="Re-enter your password"
                                    class="input input-bordered w-full rounded-xl pl-10 pr-10 focus:input-primary"
                                    :class="{
                                        'input-error':
                                            form.errors.password_confirmation,
                                    }"
                                    autocomplete="new-password"
                                />
                                <button
                                    type="button"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-base-content/50 hover:text-base-content"
                                    @click="
                                        showConfirmPassword =
                                            !showConfirmPassword
                                    "
                                >
                                    <i
                                        :class="
                                            showConfirmPassword
                                                ? 'hgi hgi-stroke hgi-rounded hgi-view'
                                                : 'hgi hgi-stroke hgi-rounded hgi-view-off'
                                        "
                                        class="text-lg"
                                    ></i>
                                </button>
                            </div>
                            <p
                                v-if="form.errors.password_confirmation"
                                class="text-error text-xs mt-1"
                            >
                                {{ form.errors.password_confirmation }}
                            </p>
                        </div>

                        <!-- Terms -->
                        <div class="form-control">
                            <label
                                class="flex items-start gap-2 cursor-pointer"
                            >
                                <input
                                    v-model="form.terms"
                                    type="checkbox"
                                    class="checkbox checkbox-primary checkbox-sm rounded mt-0.5"
                                />
                                <span class="text-sm text-base-content/70">
                                    I agree to the
                                    <a
                                        href="#"
                                        class="text-primary font-medium hover:underline"
                                        >Terms of Service</a
                                    >
                                    and
                                    <a
                                        href="#"
                                        class="text-primary font-medium hover:underline"
                                        >Privacy Policy</a
                                    >
                                </span>
                            </label>
                            <p
                                v-if="form.errors.terms"
                                class="text-error text-xs mt-1"
                            >
                                {{ form.errors.terms }}
                            </p>
                        </div>

                        <button
                            type="submit"
                            class="btn btn-primary btn-lg btn-block rounded-full shadow-lg shadow-primary/25 mt-2"
                            :disabled="form.processing"
                        >
                            <span
                                v-if="form.processing"
                                class="loading loading-spinner loading-sm"
                            ></span>
                            <span v-else>Create Account</span>
                        </button>
                    </form>

                    <p class="mt-6 text-center text-sm text-base-content/70">
                        Already have an account?
                        <Link
                            :href="route('auth.web.index')"
                            class="text-primary font-semibold hover:underline"
                        >
                            Sign in
                        </Link>
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
