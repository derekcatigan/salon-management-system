<!-- resources\js\Layouts\WebLayout.vue -->
<script setup>
import { ref, computed } from "vue";
import { Link, router, usePage } from "@inertiajs/vue3";
import { NavbarLinks } from "@/Data/NavbarLinks";

const page = usePage();

const user = computed(() => page.props.auth?.user ?? null);
const isCustomer = computed(
    () => page.props.auth?.role?.toLowerCase() === "customer",
);

const initials = computed(() => {
    const email = user.value?.email ?? "";
    return email.charAt(0).toUpperCase() || "?";
});

const isScrolled = ref(false);
const handleScroll = () => {
    isScrolled.value = window.scrollY > 10;
};

if (typeof window !== "undefined") {
    window.addEventListener("scroll", handleScroll);
}

const logout = () => {
    router.post(route("auth.web.logout"));
};
</script>

<template>
    <div class="drawer">
        <input
            id="my-drawer-2"
            type="checkbox"
            class="drawer-toggle lg:hidden"
        />
        <div class="drawer-content flex flex-col min-h-screen">
            <!-- Navbar -->
            <div
                class="navbar bg-base-100/90 backdrop-blur-md w-full fixed top-0 z-50 transition-shadow duration-300"
                :class="{ 'shadow-md': isScrolled }"
            >
                <div class="flex-none lg:hidden">
                    <label
                        for="my-drawer-2"
                        aria-label="open sidebar"
                        class="btn btn-square btn-ghost drawer-button"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            class="inline-block h-6 w-6 stroke-current"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16"
                            ></path>
                        </svg>
                    </label>
                </div>

                <!-- Brand -->
                <div class="mx-2 flex-1 px-2">
                    <Link href="/" class="text-xl font-bold tracking-tight">
                        MI Bella Vida
                    </Link>
                </div>

                <!-- Desktop Nav Links -->
                <div class="hidden flex-none lg:block">
                    <ul class="menu menu-horizontal gap-1 items-center">
                        <li v-for="link in NavbarLinks.links" :key="link.label">
                            <Link
                                :href="link.link"
                                class="flex items-center gap-2 font-medium"
                            >
                                <i :class="link.icon" class="text-lg"></i>
                                {{ link.label }}
                            </Link>
                        </li>

                        <!-- Logged-in customer -->
                        <li v-if="isCustomer" class="ml-2">
                            <div class="dropdown dropdown-end">
                                <div
                                    tabindex="0"
                                    role="button"
                                    class="flex items-center gap-2 p-1 pr-3 rounded-full hover:bg-base-200 transition-colors cursor-pointer"
                                >
                                    <div
                                        class="avatar avatar-placeholder w-9 h-9 rounded-full bg-primary text-primary-content ring-2 ring-primary/20"
                                    >
                                        <div
                                            class="flex items-center justify-center w-full h-full text-sm font-bold uppercase"
                                        >
                                            {{ initials }}
                                        </div>
                                    </div>
                                    <span
                                        class="hidden xl:inline text-sm font-medium max-w-40 truncate"
                                    >
                                        {{ user?.email }}
                                    </span>
                                    <i
                                        class="hgi hgi-stroke hgi-rounded hgi-arrow-down-01 text-sm opacity-60"
                                    ></i>
                                </div>

                                <ul
                                    tabindex="0"
                                    class="menu menu-sm dropdown-content bg-base-100 rounded-2xl shadow-xl border border-base-300 z-60 w-60 mt-3 p-2"
                                >
                                    <li
                                        class="menu-title px-3 py-2 border-b border-base-200 mb-1"
                                    >
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="avatar avatar-placeholder w-8 h-8 rounded-full bg-primary text-primary-content"
                                            >
                                                <div
                                                    class="flex items-center justify-center w-full h-full text-xs font-bold uppercase"
                                                >
                                                    {{ initials }}
                                                </div>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-xs opacity-60">
                                                    Signed in as
                                                </p>
                                                <p
                                                    class="text-sm font-medium truncate"
                                                >
                                                    {{ user?.email }}
                                                </p>
                                            </div>
                                        </div>
                                    </li>
                                    <li>
                                        <Link
                                            href="/mi-bella-la-vida/view-appointment-history"
                                        >
                                            <i
                                                class="hgi hgi-stroke hgi-rounded hgi-document-attachment"
                                            ></i>
                                            Appointments History
                                        </Link>
                                    </li>
                                    <li>
                                        <button
                                            type="button"
                                            class="text-error"
                                            @click="logout"
                                        >
                                            <i
                                                class="hgi hgi-stroke hgi-rounded hgi-logout-01"
                                            ></i>
                                            Sign out
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </li>

                        <!-- Guest -->
                        <li v-else>
                            <Link
                                :href="route('auth.web.index')"
                                class="btn btn-primary btn-sm text-primary-content ml-2"
                            >
                                Login
                            </Link>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Spacer for fixed navbar -->
            <div class="h-16"></div>

            <!-- Page content -->
            <main class="flex-1">
                <slot></slot>
            </main>

            <!-- Footer -->
            <footer
                class="footer footer-center bg-base-300 text-base-content p-10"
            >
                <aside>
                    <p class="text-lg font-semibold">MI Bella Vida</p>
                    <p>Your beauty, our passion.</p>
                    <p class="text-sm opacity-60">
                        &copy; {{ new Date().getFullYear() }} MI Bella Vida. All
                        rights reserved.
                    </p>
                </aside>
            </footer>
        </div>

        <!-- Mobile Drawer -->
        <div class="drawer-side z-50">
            <label
                for="my-drawer-2"
                aria-label="close sidebar"
                class="drawer-overlay"
            ></label>
            <ul class="menu bg-base-200 min-h-full w-80 p-4 gap-1">
                <li class="menu-title text-lg font-bold">MI Bella Vida</li>
                <li v-for="link in NavbarLinks.links" :key="link.label">
                    <Link
                        :href="link.link"
                        class="flex items-center gap-3 text-base"
                    >
                        <i :class="link.icon" class="text-xl"></i>
                        {{ link.label }}
                    </Link>
                </li>

                <!-- Mobile logged-in -->
                <li v-if="isCustomer" class="mt-4">
                    <div
                        class="flex items-center gap-3 px-3 py-2 rounded-xl bg-base-300"
                    >
                        <div
                            class="avatar avatar-placeholder w-10 h-10 rounded-full bg-primary text-primary-content"
                        >
                            <div
                                class="flex items-center justify-center w-full h-full text-sm font-bold uppercase"
                            >
                                {{ initials }}
                            </div>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs opacity-60">Signed in as</p>
                            <p class="text-sm font-medium truncate">
                                {{ user?.email }}
                            </p>
                        </div>
                    </div>
                </li>
                <li v-if="isCustomer">
                    <Link href="/mi-bella-la-vida/view-appointment-history">
                        <i
                            class="hgi hgi-stroke hgi-rounded hgi-document-attachment"
                        ></i>
                        Appointments History
                    </Link>
                </li>
                <li v-if="isCustomer">
                    <button
                        type="button"
                        class="text-error flex items-center gap-3"
                        @click="logout"
                    >
                        <i
                            class="hgi hgi-stroke hgi-rounded hgi-logout-01 text-xl"
                        ></i>
                        Sign out
                    </button>
                </li>

                <!-- Mobile guest -->
                <li v-else class="mt-4">
                    <Link
                        :href="route('auth.web.index')"
                        class="btn btn-primary btn-block text-primary-content"
                    >
                        Login
                    </Link>
                </li>
            </ul>
        </div>
    </div>
</template>
