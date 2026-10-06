<!-- resources/js/Pages/Manage/ManageAccount.vue -->
<script setup>
import { ref, computed } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import Layout from "@/Layouts/Layout.vue";

defineOptions({ layout: Layout });

const props = defineProps({
    roles: { type: Array, required: true },
    statuses: { type: Array, required: true },
});

const suffixOptions = [
    { value: "", label: "None" },
    { value: "Jr.", label: "Jr." },
    { value: "Sr.", label: "Sr." },
    { value: "II", label: "II" },
    { value: "III", label: "III" },
    { value: "IV", label: "IV" },
    { value: "V", label: "V" },
];

const search = ref("");
const results = ref([]);
const searching = ref(false);
const showSearchModal = ref(false);
const showCreateModal = ref(false);
const showDeleteModal = ref(false);

const selectedUser = ref(null);
const isEditing = ref(false);
const deleting = ref(false);

const showCreatePassword = ref(false);
const showCreatePasswordConfirm = ref(false);

const form = useForm({
    email: "",
    role: "",
    status: "",
    profile: {
        avatar: null,
        firstname: "",
        middlename: "",
        lastname: "",
        suffix: "",
        contact: "",
        birthdate: "",
        address: "",
    },
});

const createForm = useForm({
    email: "",
    password: "",
    password_confirmation: "",
    role: "",
    status: "active",
    profile: {
        firstname: "",
        middlename: "",
        lastname: "",
        suffix: "",
        contact: "",
        birthdate: "",
        address: "",
    },
});

const hasUser = computed(() => !!selectedUser.value);

const fullName = computed(() => {
    const p = form.profile;
    if (!p.firstname && !p.lastname) return "No user selected";
    return [p.firstname, p.middlename, p.lastname, p.suffix]
        .filter(Boolean)
        .map(titleCase)
        .join(" ");
});

const initials = computed(() => {
    const f = form.profile.firstname?.[0] ?? "";
    const l = form.profile.lastname?.[0] ?? "";
    return (f + l).toUpperCase() || "?";
});

const avatarUrl = computed(() => {
    const path = form.profile.avatar;
    if (!path) return null;
    if (/^https?:\/\//i.test(path)) return path;
    return `/storage/${path}`;
});

function titleCase(str) {
    if (!str) return "";
    return String(str)
        .toLowerCase()
        .replace(/\b\w/g, (c) => c.toUpperCase());
}

function displayName(user) {
    const name = [user.profile?.firstname, user.profile?.lastname]
        .filter(Boolean)
        .join(" ");
    return name ? titleCase(name) : user.email;
}

function userAvatarUrl(user) {
    const path = user.profile?.avatar;
    if (!path) return null;
    if (/^https?:\/\//i.test(path)) return path;
    return `/storage/${path}`;
}

function normalizeDate(value) {
    if (!value) return "";
    const match = String(value).match(/^\d{4}-\d{2}-\d{2}/);
    return match ? match[0] : "";
}

async function runSearch() {
    searching.value = true;
    showSearchModal.value = true;

    try {
        const url = new URL(
            route("manage.account.search"),
            window.location.origin,
        );
        url.searchParams.set("q", search.value.trim());

        const response = await fetch(url, {
            headers: {
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
            },
            credentials: "same-origin",
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        results.value = await response.json();
    } catch (e) {
        console.error("[search] failed:", e.message);
        toast.error("Failed to search users.");
        results.value = [];
    } finally {
        searching.value = false;
    }
}

function closeSearchModal() {
    showSearchModal.value = false;
}

function selectUser(user) {
    selectedUser.value = user;
    isEditing.value = false;
    showSearchModal.value = false;

    form.email = user.email ?? "";
    form.role = user.role ?? "";
    form.status = user.status ?? "";

    form.profile.avatar = user.profile?.avatar ?? null;
    form.profile.firstname = user.profile?.firstname ?? "";
    form.profile.middlename = user.profile?.middlename ?? "";
    form.profile.lastname = user.profile?.lastname ?? "";
    form.profile.suffix = user.profile?.suffix ?? "";
    form.profile.contact = user.profile?.contact ?? "";
    form.profile.birthdate = normalizeDate(user.profile?.birthdate);
    form.profile.address = user.profile?.address ?? "";

    form.clearErrors();
}

function enableEdit() {
    if (!hasUser.value) return;
    isEditing.value = true;
}

function save() {
    if (!hasUser.value) return;
    form.put(route("manage.account.update", selectedUser.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            isEditing.value = false;
        },
        onError: (errors) =>
            Object.values(errors).forEach((e) => toast.error(e)),
    });
}

function openDeleteModal() {
    if (!hasUser.value) return;
    showDeleteModal.value = true;
}

function closeDeleteModal() {
    showDeleteModal.value = false;
}

function confirmDelete() {
    if (!hasUser.value) return;

    deleting.value = true;

    router.delete(route("manage.account.destroy", selectedUser.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            selectedUser.value = null;
            isEditing.value = false;
            form.reset();
        },
        onFinish: () => {
            deleting.value = false;
        },
    });
}

function cancelEdit() {
    if (!selectedUser.value) return;
    selectUser(selectedUser.value);
}

function openCreateModal() {
    createForm.reset();
    createForm.clearErrors();
    showCreatePassword.value = false;
    showCreatePasswordConfirm.value = false;
    showCreateModal.value = true;
}

function closeCreateModal() {
    showCreateModal.value = false;
}

function submitCreate() {
    createForm.profile.birthdate = normalizeDate(createForm.profile.birthdate);

    createForm.post(route("manage.account.store"), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        },
        onError: (errors) =>
            Object.values(errors).forEach((e) => toast.error(e)),
    });
}
</script>

<template>
    <div>
        <div class="mx-auto max-w-7xl space-y-4 p-3 sm:p-5 lg:p-6">
            <!-- Top bar -->
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <!-- Search Input -->
                <div class="flex w-full items-center gap-2 sm:max-w-md">
                    <input
                        v-model="search"
                        type="text"
                        class="input input-sm input-bordered w-full text-xs"
                        placeholder="Search by name, email, or contact…"
                        @keyup.enter="runSearch"
                    />
                    <button
                        type="button"
                        class="btn btn-xs sm:btn-sm btn-neutral"
                        @click="runSearch"
                    >
                        <svg
                            class="h-3.5 w-3.5"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <circle cx="11" cy="11" r="7" />
                            <path d="m20 20-3.5-3.5" />
                        </svg>
                        Search
                    </button>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        class="btn btn-xs sm:btn-sm btn-secondary"
                        @click="openCreateModal"
                    >
                        <svg
                            class="h-3.5 w-3.5"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path d="M12 5v14M5 12h14" />
                        </svg>
                        New
                    </button>

                    <div class="join">
                        <button
                            type="button"
                            class="btn btn-xs sm:btn-sm btn-outline join-item"
                            :disabled="!hasUser || isEditing"
                            @click="enableEdit"
                        >
                            <svg
                                class="h-3.5 w-3.5"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    d="M12 20h9M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"
                                />
                            </svg>
                            Edit
                        </button>

                        <button
                            type="button"
                            class="btn btn-xs sm:btn-sm btn-primary join-item"
                            :disabled="
                                !hasUser || !isEditing || form.processing
                            "
                            @click="save"
                        >
                            <span
                                v-if="form.processing"
                                class="loading loading-spinner loading-xs"
                            ></span>
                            <svg
                                v-else
                                class="h-3.5 w-3.5"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"
                                />
                                <path d="M17 21v-8H7v8M7 3v5h8" />
                            </svg>
                            Save
                        </button>

                        <button
                            type="button"
                            class="btn btn-xs sm:btn-sm btn-error join-item"
                            :disabled="!hasUser"
                            @click="openDeleteModal"
                        >
                            <svg
                                class="h-3.5 w-3.5"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"
                                />
                            </svg>
                            Delete
                        </button>
                    </div>
                </div>
            </div>

            <!-- User form -->
            <div class="rounded-xl bg-base-100/60">
                <div
                    class="flex flex-row items-center gap-3 border-b border-base-200/70 px-4 py-3 sm:px-5"
                >
                    <div class="avatar">
                        <div class="w-10 rounded-full sm:w-12">
                            <img
                                v-if="avatarUrl"
                                :src="avatarUrl"
                                :alt="fullName"
                                class="h-full w-full object-cover"
                            />
                            <div
                                v-else
                                class="flex h-full w-full items-center justify-center rounded-full bg-primary text-primary-content"
                            >
                                <span class="text-sm font-semibold">{{
                                    initials
                                }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex-1 min-w-0">
                        <h2
                            class="flex flex-wrap items-center gap-1.5 text-base font-semibold"
                        >
                            <span class="truncate">{{ fullName }}</span>
                            <span
                                v-if="hasUser && form.role"
                                class="badge badge-sm badge-primary badge-outline capitalize"
                            >
                                {{ form.role }}
                            </span>
                            <span
                                v-if="hasUser && form.status"
                                class="badge badge-sm capitalize"
                                :class="{
                                    'badge-success': form.status === 'active',
                                    'badge-warning': form.status === 'inactive',
                                    'badge-error': form.status === 'suspended',
                                }"
                            >
                                {{ form.status }}
                            </span>
                        </h2>
                        <p class="truncate text-xs opacity-60">
                            {{
                                hasUser
                                    ? form.email
                                    : "Search above to select a user."
                            }}
                        </p>
                    </div>
                </div>

                <div class="p-4 sm:p-5">
                    <div
                        class="grid grid-cols-1 gap-x-4 gap-y-3 sm:grid-cols-2 lg:grid-cols-3"
                    >
                        <div class="sm:col-span-2 lg:col-span-3">
                            <h3
                                class="text-[11px] font-semibold uppercase tracking-wider text-primary"
                            >
                                Account Information
                            </h3>
                            <div class="divider my-1"></div>
                        </div>

                        <div class="form-control">
                            <label class="label py-0.5"
                                ><span class="label-text text-xs"
                                    >Email</span
                                ></label
                            >
                            <input
                                v-model="form.email"
                                type="email"
                                :readonly="!isEditing"
                                class="input input-sm input-bordered w-full text-xs"
                                :class="{ 'input-error': form.errors.email }"
                                placeholder="user@example.com"
                            />
                            <label
                                v-if="form.errors.email"
                                class="label py-0.5"
                            >
                                <span
                                    class="label-text-alt text-[10px] text-error"
                                    >{{ form.errors.email }}</span
                                >
                            </label>
                        </div>

                        <div class="form-control">
                            <label class="label py-0.5"
                                ><span class="label-text text-xs"
                                    >Role</span
                                ></label
                            >
                            <select
                                v-model="form.role"
                                :class="{
                                    'select-error': form.errors.role,
                                    'pointer-events-none': !isEditing,
                                }"
                                class="select select-sm select-bordered w-full text-xs"
                                :tabindex="isEditing ? 0 : -1"
                            >
                                <option value="" disabled>Select role</option>
                                <option
                                    v-for="r in props.roles"
                                    :key="r.value"
                                    :value="r.value"
                                >
                                    {{ r.label }}
                                </option>
                            </select>
                            <label v-if="form.errors.role" class="label py-0.5">
                                <span
                                    class="label-text-alt text-[10px] text-error"
                                    >{{ form.errors.role }}</span
                                >
                            </label>
                        </div>

                        <div class="form-control">
                            <label class="label py-0.5"
                                ><span class="label-text text-xs"
                                    >Status</span
                                ></label
                            >
                            <select
                                v-model="form.status"
                                :class="{
                                    'select-error': form.errors.status,
                                    'pointer-events-none': !isEditing,
                                }"
                                class="select select-sm select-bordered w-full text-xs capitalize"
                                :tabindex="isEditing ? 0 : -1"
                            >
                                <option value="" disabled>Select status</option>
                                <option
                                    v-for="s in props.statuses"
                                    :key="s"
                                    :value="s"
                                    class="capitalize"
                                >
                                    {{ s }}
                                </option>
                            </select>
                            <label
                                v-if="form.errors.status"
                                class="label py-0.5"
                            >
                                <span
                                    class="label-text-alt text-[10px] text-error"
                                    >{{ form.errors.status }}</span
                                >
                            </label>
                        </div>

                        <div class="sm:col-span-2 lg:col-span-3 mt-1">
                            <h3
                                class="text-[11px] font-semibold uppercase tracking-wider text-primary"
                            >
                                Personal Information
                            </h3>
                            <div class="divider my-1"></div>
                        </div>

                        <div class="form-control">
                            <label class="label py-0.5"
                                ><span class="label-text text-xs"
                                    >First name</span
                                ></label
                            >
                            <input
                                v-model="form.profile.firstname"
                                type="text"
                                :readonly="!isEditing"
                                class="input input-sm input-bordered w-full text-xs"
                                :class="{
                                    'input-error':
                                        form.errors['profile.firstname'],
                                }"
                                placeholder="Enter first name"
                            />
                            <label
                                v-if="form.errors['profile.firstname']"
                                class="label py-0.5"
                            >
                                <span
                                    class="label-text-alt text-[10px] text-error"
                                    >{{
                                        form.errors["profile.firstname"]
                                    }}</span
                                >
                            </label>
                        </div>

                        <div class="form-control">
                            <label class="label py-0.5"
                                ><span class="label-text text-xs"
                                    >Middle name</span
                                ></label
                            >
                            <input
                                v-model="form.profile.middlename"
                                type="text"
                                :readonly="!isEditing"
                                class="input input-sm input-bordered w-full text-xs"
                                placeholder="Enter middle name (optional)"
                            />
                        </div>

                        <div class="form-control">
                            <label class="label py-0.5"
                                ><span class="label-text text-xs"
                                    >Last name</span
                                ></label
                            >
                            <input
                                v-model="form.profile.lastname"
                                type="text"
                                :readonly="!isEditing"
                                class="input input-sm input-bordered w-full text-xs"
                                :class="{
                                    'input-error':
                                        form.errors['profile.lastname'],
                                }"
                                placeholder="Enter last name"
                            />
                            <label
                                v-if="form.errors['profile.lastname']"
                                class="label py-0.5"
                            >
                                <span
                                    class="label-text-alt text-[10px] text-error"
                                    >{{ form.errors["profile.lastname"] }}</span
                                >
                            </label>
                        </div>

                        <div class="form-control">
                            <label class="label py-0.5"
                                ><span class="label-text text-xs"
                                    >Suffix</span
                                ></label
                            >
                            <select
                                v-model="form.profile.suffix"
                                :class="{
                                    'select-error':
                                        form.errors['profile.suffix'],
                                    'pointer-events-none': !isEditing,
                                }"
                                class="select select-sm select-bordered w-full text-xs"
                                :tabindex="isEditing ? 0 : -1"
                            >
                                <option
                                    v-for="opt in suffixOptions"
                                    :key="opt.value"
                                    :value="opt.value"
                                >
                                    {{ opt.label }}
                                </option>
                            </select>
                            <label
                                v-if="form.errors['profile.suffix']"
                                class="label py-0.5"
                            >
                                <span
                                    class="label-text-alt text-[10px] text-error"
                                    >{{ form.errors["profile.suffix"] }}</span
                                >
                            </label>
                        </div>

                        <div class="form-control">
                            <label class="label py-0.5"
                                ><span class="label-text text-xs"
                                    >Contact</span
                                ></label
                            >
                            <input
                                v-model="form.profile.contact"
                                type="text"
                                :readonly="!isEditing"
                                class="input input-sm input-bordered w-full text-xs"
                                :class="{
                                    'input-error':
                                        form.errors['profile.contact'],
                                }"
                                placeholder="09XX XXX XXXX"
                            />
                            <label
                                v-if="form.errors['profile.contact']"
                                class="label py-0.5"
                            >
                                <span
                                    class="label-text-alt text-[10px] text-error"
                                    >{{ form.errors["profile.contact"] }}</span
                                >
                            </label>
                        </div>

                        <div class="form-control">
                            <label class="label py-0.5"
                                ><span class="label-text text-xs"
                                    >Birthdate</span
                                ></label
                            >
                            <input
                                v-model="form.profile.birthdate"
                                type="date"
                                :readonly="!isEditing"
                                class="input input-sm input-bordered w-full text-xs"
                            />
                        </div>

                        <div class="form-control sm:col-span-2 lg:col-span-3">
                            <label class="label py-0.5"
                                ><span class="label-text text-xs"
                                    >Address</span
                                ></label
                            >
                            <input
                                v-model="form.profile.address"
                                type="text"
                                :readonly="!isEditing"
                                class="input input-sm input-bordered w-full text-xs"
                                placeholder="Enter complete address"
                            />
                        </div>
                    </div>

                    <div
                        v-if="isEditing"
                        class="mt-4 flex justify-end border-t border-base-200/70 pt-3"
                    >
                        <button
                            type="button"
                            class="btn btn-xs sm:btn-sm btn-ghost"
                            @click="cancelEdit"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search Users Modal -->
        <dialog class="modal" :class="{ 'modal-open': showSearchModal }">
            <div
                class="modal-box w-11/12 max-w-2xl p-4 sm:p-5 max-h-[85vh] overflow-y-auto"
            >
                <div class="flex items-center justify-between mb-1">
                    <h3 class="text-sm font-bold flex items-center gap-2">
                        <span class="badge badge-neutral badge-xs"></span>
                        Select a User
                    </h3>
                    <button
                        type="button"
                        class="btn btn-xs btn-circle btn-ghost"
                        @click="closeSearchModal"
                    >
                        ✕
                    </button>
                </div>
                <div class="divider my-1"></div>

                <div class="flex items-center gap-2 mb-3">
                    <input
                        v-model="search"
                        type="text"
                        class="input input-sm input-bordered w-full text-xs"
                        placeholder="Refine search…"
                        @keyup.enter="runSearch"
                    />
                    <button
                        type="button"
                        class="btn btn-xs btn-neutral"
                        :disabled="searching"
                        @click="runSearch"
                    >
                        <span
                            v-if="searching"
                            class="loading loading-spinner loading-xs"
                        ></span>
                        Search
                    </button>
                </div>

                <div
                    v-if="searching"
                    class="flex items-center gap-2 p-3 text-xs opacity-60"
                >
                    <span class="loading loading-spinner loading-xs"></span>
                    Searching…
                </div>

                <div
                    v-else-if="!results.length"
                    class="p-4 text-center text-xs opacity-60"
                >
                    No users found.
                </div>

                <ul v-else class="menu menu-sm w-full p-0">
                    <li v-for="user in results" :key="user.id">
                        <button
                            type="button"
                            @click="selectUser(user)"
                            class="flex items-center gap-3"
                        >
                            <div class="avatar">
                                <div class="h-8 w-8 rounded-full">
                                    <img
                                        v-if="userAvatarUrl(user)"
                                        :src="userAvatarUrl(user)"
                                        :alt="displayName(user)"
                                        class="h-full w-full object-cover"
                                    />
                                    <div
                                        v-else
                                        class="flex h-full w-full items-center justify-center rounded-full bg-primary/10 text-[11px] font-semibold text-primary"
                                    >
                                        {{
                                            (
                                                user.profile?.firstname?.[0] ??
                                                user.email[0]
                                            ).toUpperCase()
                                        }}
                                    </div>
                                </div>
                            </div>

                            <div class="min-w-0 flex-1 text-left">
                                <p
                                    class="truncate text-xs font-medium text-base-content"
                                >
                                    {{ displayName(user) }}
                                </p>
                                <p class="truncate text-[10px] opacity-60">
                                    {{ user.email }}
                                </p>
                            </div>

                            <span class="badge badge-ghost badge-sm">
                                {{ titleCase(user.role) }}
                            </span>
                            <span
                                class="badge badge-sm"
                                :class="{
                                    'badge-success': user.status === 'active',
                                    'badge-warning': user.status === 'inactive',
                                    'badge-error': user.status === 'suspended',
                                }"
                            >
                                {{ titleCase(user.status) }}
                            </span>
                        </button>
                    </li>
                </ul>
            </div>

            <form method="dialog" class="modal-backdrop">
                <button @click="closeSearchModal">close</button>
            </form>
        </dialog>

        <!-- Delete Confirmation Modal -->
        <dialog class="modal" :class="{ 'modal-open': showDeleteModal }">
            <div class="modal-box w-11/12 max-w-md p-4 sm:p-6">
                <div class="flex items-start gap-3">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-error/10 text-error"
                    >
                        <svg
                            class="h-5 w-5"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                d="M12 9v4M12 17h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"
                            />
                        </svg>
                    </div>

                    <div class="flex-1">
                        <h3 class="text-base font-bold">
                            Delete this account?
                        </h3>
                        <p class="mt-1 text-xs opacity-70">
                            You are about to permanently delete
                            <span class="font-semibold">{{ fullName }}</span>
                            ({{ form.email }}). This action cannot be undone.
                        </p>
                    </div>
                </div>

                <div class="modal-action mt-5">
                    <button
                        type="button"
                        class="btn btn-xs sm:btn-sm btn-ghost"
                        :disabled="deleting"
                        @click="closeDeleteModal"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="btn btn-xs sm:btn-sm btn-error"
                        :disabled="deleting"
                        @click="confirmDelete"
                    >
                        <span
                            v-if="deleting"
                            class="loading loading-spinner loading-xs"
                        ></span>
                        <svg
                            v-else
                            class="h-3.5 w-3.5"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"
                            />
                        </svg>
                        Yes, delete
                    </button>
                </div>
            </div>

            <form method="dialog" class="modal-backdrop">
                <button @click="closeDeleteModal">close</button>
            </form>
        </dialog>

        <!-- Create Modal -->
        <dialog class="modal" :class="{ 'modal-open': showCreateModal }">
            <div
                class="modal-box w-11/12 max-w-3xl p-4 sm:p-6 max-h-[90vh] overflow-y-auto"
            >
                <div class="flex items-center justify-between mb-1">
                    <h3 class="text-sm font-bold flex items-center gap-2">
                        <span class="badge badge-secondary badge-xs"></span>
                        Create New Account
                    </h3>
                    <button
                        type="button"
                        class="btn btn-xs btn-circle btn-ghost"
                        @click="closeCreateModal"
                    >
                        ✕
                    </button>
                </div>
                <div class="divider my-1"></div>

                <!-- Account Information -->
                <h4
                    class="text-[11px] font-semibold uppercase tracking-wider text-secondary mb-2"
                >
                    Account Information
                </h4>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="form-control">
                        <label class="label py-0.5"
                            ><span class="label-text text-xs"
                                >Email</span
                            ></label
                        >
                        <input
                            v-model="createForm.email"
                            type="email"
                            class="input input-sm input-bordered w-full text-xs"
                            :class="{ 'input-error': createForm.errors.email }"
                            placeholder="user@example.com"
                        />
                        <label
                            v-if="createForm.errors.email"
                            class="label py-0.5"
                        >
                            <span
                                class="label-text-alt text-[10px] text-error"
                                >{{ createForm.errors.email }}</span
                            >
                        </label>
                    </div>

                    <div class="form-control">
                        <label class="label py-0.5"
                            ><span class="label-text text-xs">Role</span></label
                        >
                        <select
                            v-model="createForm.role"
                            class="select select-sm select-bordered w-full text-xs"
                            :class="{ 'select-error': createForm.errors.role }"
                        >
                            <option value="" disabled>Select role</option>
                            <option
                                v-for="r in props.roles"
                                :key="r.value"
                                :value="r.value"
                            >
                                {{ r.label }}
                            </option>
                        </select>
                        <label
                            v-if="createForm.errors.role"
                            class="label py-0.5"
                        >
                            <span
                                class="label-text-alt text-[10px] text-error"
                                >{{ createForm.errors.role }}</span
                            >
                        </label>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 mt-3">
                    <div class="form-control">
                        <label class="label py-0.5"
                            ><span class="label-text text-xs"
                                >Status</span
                            ></label
                        >
                        <select
                            v-model="createForm.status"
                            class="select select-sm select-bordered w-full text-xs capitalize"
                        >
                            <option
                                v-for="s in props.statuses"
                                :key="s"
                                :value="s"
                                class="capitalize"
                            >
                                {{ s }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3">
                    <div class="form-control">
                        <label class="label py-0.5"
                            ><span class="label-text text-xs"
                                >Password</span
                            ></label
                        >
                        <input
                            v-model="createForm.password"
                            :type="showCreatePassword ? 'text' : 'password'"
                            class="input input-sm input-bordered w-full text-xs"
                            :class="{
                                'input-error': createForm.errors.password,
                            }"
                            placeholder="Minimum of 8 characters"
                        />
                        <label
                            v-if="createForm.errors.password"
                            class="label py-0.5"
                        >
                            <span
                                class="label-text-alt text-[10px] text-error"
                                >{{ createForm.errors.password }}</span
                            >
                        </label>
                    </div>

                    <div class="form-control">
                        <label class="label py-0.5"
                            ><span class="label-text text-xs"
                                >Confirm Password</span
                            ></label
                        >
                        <input
                            v-model="createForm.password_confirmation"
                            :type="
                                showCreatePasswordConfirm ? 'text' : 'password'
                            "
                            class="input input-sm input-bordered w-full text-xs"
                            :class="{
                                'input-error':
                                    createForm.errors.password_confirmation,
                            }"
                            placeholder="Re-enter password"
                        />
                        <label
                            v-if="createForm.errors.password_confirmation"
                            class="label py-0.5"
                        >
                            <span
                                class="label-text-alt text-[10px] text-error"
                                >{{
                                    createForm.errors.password_confirmation
                                }}</span
                            >
                        </label>
                    </div>
                </div>

                <label class="flex items-center gap-2 cursor-pointer mt-3">
                    <input
                        v-model="showCreatePassword"
                        type="checkbox"
                        class="checkbox checkbox-sm"
                        @change="showCreatePasswordConfirm = showCreatePassword"
                    />
                    <span class="text-xs text-gray-500">Show passwords</span>
                </label>

                <!-- Personal Information -->
                <h4
                    class="text-[11px] font-semibold uppercase tracking-wider text-secondary mb-2 mt-4"
                >
                    Personal Information
                </h4>
                <div
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3"
                >
                    <div class="form-control">
                        <label class="label py-0.5"
                            ><span class="label-text text-xs"
                                >First name</span
                            ></label
                        >
                        <input
                            v-model="createForm.profile.firstname"
                            type="text"
                            class="input input-sm input-bordered w-full text-xs"
                            :class="{
                                'input-error':
                                    createForm.errors['profile.firstname'],
                            }"
                            placeholder="Enter first name"
                        />
                        <label
                            v-if="createForm.errors['profile.firstname']"
                            class="label py-0.5"
                        >
                            <span
                                class="label-text-alt text-[10px] text-error"
                                >{{
                                    createForm.errors["profile.firstname"]
                                }}</span
                            >
                        </label>
                    </div>

                    <div class="form-control">
                        <label class="label py-0.5"
                            ><span class="label-text text-xs"
                                >Middle name</span
                            ></label
                        >
                        <input
                            v-model="createForm.profile.middlename"
                            type="text"
                            class="input input-sm input-bordered w-full text-xs"
                            placeholder="Enter middle name (optional)"
                        />
                    </div>

                    <div class="form-control">
                        <label class="label py-0.5"
                            ><span class="label-text text-xs"
                                >Last name</span
                            ></label
                        >
                        <input
                            v-model="createForm.profile.lastname"
                            type="text"
                            class="input input-sm input-bordered w-full text-xs"
                            :class="{
                                'input-error':
                                    createForm.errors['profile.lastname'],
                            }"
                            placeholder="Enter last name"
                        />
                        <label
                            v-if="createForm.errors['profile.lastname']"
                            class="label py-0.5"
                        >
                            <span
                                class="label-text-alt text-[10px] text-error"
                                >{{
                                    createForm.errors["profile.lastname"]
                                }}</span
                            >
                        </label>
                    </div>

                    <div class="form-control">
                        <label class="label py-0.5"
                            ><span class="label-text text-xs"
                                >Suffix</span
                            ></label
                        >
                        <select
                            v-model="createForm.profile.suffix"
                            class="select select-sm select-bordered w-full text-xs"
                            :class="{
                                'select-error':
                                    createForm.errors['profile.suffix'],
                            }"
                        >
                            <option
                                v-for="opt in suffixOptions"
                                :key="opt.value"
                                :value="opt.value"
                            >
                                {{ opt.label }}
                            </option>
                        </select>
                        <label
                            v-if="createForm.errors['profile.suffix']"
                            class="label py-0.5"
                        >
                            <span
                                class="label-text-alt text-[10px] text-error"
                                >{{ createForm.errors["profile.suffix"] }}</span
                            >
                        </label>
                    </div>

                    <div class="form-control">
                        <label class="label py-0.5"
                            ><span class="label-text text-xs"
                                >Contact</span
                            ></label
                        >
                        <input
                            v-model="createForm.profile.contact"
                            type="text"
                            class="input input-sm input-bordered w-full text-xs"
                            :class="{
                                'input-error':
                                    createForm.errors['profile.contact'],
                            }"
                            placeholder="09XX XXX XXXX"
                        />
                        <label
                            v-if="createForm.errors['profile.contact']"
                            class="label py-0.5"
                        >
                            <span
                                class="label-text-alt text-[10px] text-error"
                                >{{
                                    createForm.errors["profile.contact"]
                                }}</span
                            >
                        </label>
                    </div>

                    <div class="form-control">
                        <label class="label py-0.5"
                            ><span class="label-text text-xs"
                                >Birthdate</span
                            ></label
                        >
                        <input
                            v-model="createForm.profile.birthdate"
                            type="date"
                            class="input input-sm input-bordered w-full text-xs"
                        />
                    </div>

                    <div class="form-control sm:col-span-2 lg:col-span-3">
                        <label class="label py-0.5"
                            ><span class="label-text text-xs"
                                >Address</span
                            ></label
                        >
                        <input
                            v-model="createForm.profile.address"
                            type="text"
                            class="input input-sm input-bordered w-full text-xs"
                            placeholder="Enter complete address"
                        />
                    </div>
                </div>

                <div class="modal-action mt-4">
                    <button
                        type="button"
                        class="btn btn-xs sm:btn-sm btn-ghost"
                        @click="closeCreateModal"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="btn btn-xs sm:btn-sm btn-secondary"
                        :disabled="createForm.processing"
                        @click="submitCreate"
                    >
                        <span
                            v-if="createForm.processing"
                            class="loading loading-spinner loading-xs"
                        ></span>
                        Create
                    </button>
                </div>
            </div>

            <form method="dialog" class="modal-backdrop">
                <button @click="closeCreateModal">close</button>
            </form>
        </dialog>
    </div>
</template>
