<!-- resources/js/Layouts/Layout.vue -->
<script setup>
import { router, usePage } from "@inertiajs/vue3";
import { computed, watch } from "vue";
import { toast } from "vue-sonner";
import { SidebarLinks } from "@/Data/SidebarLinks";

const page = usePage();
const user = computed(() => page.props.auth.user?.email ?? "-");
const role = computed(() => page.props.auth.user?.role ?? "-");
const roleLabel = computed(() => page.props.auth.role ?? "-");
const navLinks = computed(() => SidebarLinks[role.value] ?? []);

const initials = computed(() => {
    if (!user) return "-";
    return user.value[0].toUpperCase();
});

watch(
    () => page.props.flash?.success,
    (msg) => msg && toast.success(msg),
);
watch(
    () => page.props.flash?.error,
    (msg) => msg && toast.error(msg),
);

function logout() {
    router.post("/logout");
}
</script>
<template>
    <div class="drawer lg:drawer-open">
        <input
            id="my-drawer-2"
            type="checkbox"
            class="drawer-toggle lg:hidden"
        />
        <div class="drawer-content flex flex-col">
            <!-- Navbar -->
            <div
                class="navbar bg-white w-full border-b border-b-gray-300 justify-between"
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
                <div class="mx-2 flex-1 px-2 hidden lg:block">
                    SALON MANAGEMENT SYSTEM
                </div>
                <div
                    class="flex-none block dropdown dropdown-end cursor-pointer"
                >
                    <!-- Avatar + Name -->
                    <div
                        tabindex="0"
                        role="button"
                        class="p-1 border border-gray-300 rounded-sm shadow-sm flex items-center gap-1"
                    >
                        <div class="avatar">
                            <div
                                class="w-10 rounded flex justify-center items-center bg-gray-200 border border-gray-300"
                            >
                                {{ initials }}
                            </div>
                        </div>
                        <div class="px-1">
                            <p class="text-xs font-medium line-clamp-1">
                                {{ user }}
                            </p>
                            <p class="text-xs font-medium text-gray-500">
                                {{ roleLabel }}
                            </p>
                        </div>
                    </div>

                    <ul
                        tabindex="-1"
                        class="dropdown-content menu bg-base-100 rounded-box z-1 w-52 p-2 shadow-sm"
                    >
                        <li>
                            <Link class="btn btn-xs btn-ghost">Profile</Link>
                        </li>
                        <li class="mt-1 pt-1 border-t border-gray-300">
                            <Link
                                @click="logout"
                                class="btn btn-soft btn-block btn-xs btn-error border border-red-200"
                            >
                                Logout
                            </Link>
                        </li>
                    </ul>
                </div>
            </div>
            <!-- Page content here -->
            <main class="min-h-screen bg-slate-100 p-3">
                <slot></slot>
                <Toaster />
            </main>
        </div>
        <div class="drawer-side">
            <label
                for="my-drawer-2"
                aria-label="close sidebar"
                class="drawer-overlay"
            ></label>
            <ul class="menu bg-white min-h-full w-64 p-4">
                <div
                    class="p-3 w-full border border-gray-300 rounded-sm shadow-sm bg-linear-to-tl to-pink-400 from-pink-500 mb-3"
                >
                    <h1 class="text-center font-black text-lg text-yellow-400">
                        MI BELLA - VIDA
                    </h1>
                    <h3 class="text-center font-bold text-yellow-400">
                        BEAUTY SALON AND SPA
                    </h3>
                </div>

                <!-- Sidebar Links -->
                <template v-for="group in navLinks" :key="group.category">
                    <li
                        class="menu-title text-xs text-gray-400 uppercase tracking-wider px-3 mt-3"
                    >
                        {{ group.category }}
                    </li>

                    <li
                        v-for="navLink in group.links"
                        :key="navLink.label"
                        class="mb-1"
                    >
                        <template v-if="navLink.children">
                            <details
                                :open="
                                    navLink.children.some((child) =>
                                        $page.url.startsWith(child.link),
                                    )
                                "
                            >
                                <summary
                                    class="mb-1 flex items-center gap-2"
                                    :class="{
                                        'bg-secondary text-white mb-1':
                                            $page.url.startsWith(navLink.link),
                                    }"
                                >
                                    <i :class="navLink.icon"></i>
                                    <span>{{ navLink.label }}</span>
                                </summary>
                                <ul>
                                    <li
                                        v-for="child in navLink.children"
                                        :key="child.label"
                                        class="mb-1"
                                    >
                                        <Link
                                            :href="child.link"
                                            class="flex items-center gap-2"
                                            :class="{
                                                'bg-secondary text-white':
                                                    $page.url === child.link,
                                            }"
                                        >
                                            <i :class="child.icon"></i>
                                            <span>{{ child.label }}</span>
                                        </Link>
                                    </li>
                                </ul>
                            </details>
                        </template>

                        <template v-else>
                            <Link
                                :href="navLink.link"
                                class="flex items-center gap-2"
                                :class="{
                                    'bg-secondary text-white':
                                        $page.url === navLink.link,
                                }"
                            >
                                <i :class="navLink.icon"></i>
                                <span>{{ navLink.label }}</span>
                            </Link>
                        </template>
                    </li>
                </template>
            </ul>
        </div>
    </div>
</template>
