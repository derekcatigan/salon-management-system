<!-- resources/js/Pages/Teller/Teller.vue -->
<script setup>
import { ref, computed } from "vue";
import { router, useForm, usePage } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import Layout from "@/Layouts/Layout.vue";

defineOptions({ layout: Layout });

const props = defineProps({
    services: { type: Array, required: true },
    products: { type: Array, required: true },
    recentSales: { type: Array, required: true },
    stats: { type: Object, required: true },
});

const page = usePage();

/* ------------------------------------------------------------------ */
/* Short / Over indicator                                              */
/* ------------------------------------------------------------------ */
const hasDrawerCount = computed(() => props.stats.has_drawer_count);
const expectedDrawer = computed(() => props.stats.expected_drawer ?? 0);
const countedAmount = computed(() => props.stats.counted_amount ?? 0);
const variance = computed(() => props.stats.variance ?? 0);

const varianceState = computed(() => {
    if (!hasDrawerCount.value) return "uncounted";
    if (variance.value === 0) return "balanced";
    return variance.value > 0 ? "over" : "short";
});

const varianceLabel = computed(() => {
    if (!hasDrawerCount.value) return "Not yet counted";
    if (variance.value === 0) return "Balanced";
    return variance.value > 0 ? "Over" : "Short";
});

const varianceClass = computed(() => {
    switch (varianceState.value) {
        case "balanced":
            return "text-success";
        case "over":
            return "text-info";
        case "short":
            return "text-error";
        default:
            return "text-warning";
    }
});

const varianceBadgeClass = computed(() => {
    switch (varianceState.value) {
        case "balanced":
            return "badge-success";
        case "over":
            return "badge-info";
        case "short":
            return "badge-error";
        default:
            return "badge-warning";
    }
});

/* ------------------------------------------------------------------ */
/* Catalog tabs + search                                               */
/* ------------------------------------------------------------------ */
const tab = ref("services");
const search = ref("");

const filteredServices = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return props.services;
    return props.services.filter(
        (s) =>
            s.name.toLowerCase().includes(q) ||
            s.category.toLowerCase().includes(q),
    );
});

const filteredProducts = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return props.products;
    return props.products.filter(
        (p) =>
            p.name.toLowerCase().includes(q) ||
            p.sku.toLowerCase().includes(q) ||
            (p.brand ?? "").toLowerCase().includes(q),
    );
});

/* ------------------------------------------------------------------ */
/* Cart                                                                */
/* ------------------------------------------------------------------ */
const cart = ref([]);

function addToCart(item, type) {
    const key = `${type}:${item.id}`;
    const existing = cart.value.find((c) => c.key === key);

    if (existing) {
        if (type === "product" && existing.quantity >= item.stock) {
            toast.error(`Only ${item.stock} ${item.unit} in stock.`);
            return;
        }
        existing.quantity++;
    } else {
        cart.value.push({
            key,
            item_type: type,
            item_id: item.id,
            name: item.name,
            sku: item.sku ?? null,
            price: item.price,
            quantity: 1,
            max: type === "product" ? item.stock : null,
            unit: item.unit ?? null,
        });
    }
}

function increment(item) {
    if (item.max && item.quantity >= item.max) {
        toast.error(`Only ${item.max} left.`);
        return;
    }
    item.quantity++;
}

function decrement(item) {
    if (item.quantity > 1) item.quantity--;
    else removeItem(item);
}

function removeItem(item) {
    cart.value = cart.value.filter((c) => c.key !== item.key);
}

const subtotal = computed(() =>
    cart.value.reduce((sum, c) => sum + c.price * c.quantity, 0),
);

/* ------------------------------------------------------------------ */
/* Clear cart confirmation                                             */
/* ------------------------------------------------------------------ */
const showClearCartModal = ref(false);

function openClearCartModal() {
    if (!cart.value.length) return;
    showClearCartModal.value = true;
}

function closeClearCartModal() {
    showClearCartModal.value = false;
}

function confirmClearCart() {
    cart.value = [];
    showClearCartModal.value = false;
}

/* ------------------------------------------------------------------ */
/* Checkout modal                                                      */
/* ------------------------------------------------------------------ */
const showCheckout = ref(false);
const discount = ref(0);
const amountPaid = ref(0);
const paymentMethod = ref("cash");
const notes = ref("");
const showDenominationStep = ref(false);

const denominations = [1000, 500, 200, 100, 50, 20, 10, 5, 1];

const denominationInputs = ref(
    Object.fromEntries(denominations.map((d) => [d, 0])),
);

const total = computed(() =>
    Math.max(0, subtotal.value - Number(discount.value || 0)),
);

const change = computed(() =>
    Math.max(0, Number(amountPaid.value || 0) - total.value),
);

const denominationTotal = computed(() =>
    denominations.reduce(
        (sum, d) => sum + d * Number(denominationInputs.value[d] || 0),
        0,
    ),
);

function openCheckout() {
    if (!cart.value.length) {
        toast.error("Cart is empty.");
        return;
    }
    discount.value = 0;
    amountPaid.value = 0;
    paymentMethod.value = "cash";
    notes.value = "";
    showDenominationStep.value = false;
    denominationInputs.value = Object.fromEntries(
        denominations.map((d) => [d, 0]),
    );
    showCheckout.value = true;
}

function closeCheckout() {
    showCheckout.value = false;
}

function submitCheckout() {
    if (
        paymentMethod.value === "cash" &&
        Number(amountPaid.value) < total.value
    ) {
        toast.error("Amount paid is less than the total.");
        return;
    }

    const payload = {
        items: cart.value.map((c) => ({
            item_type: c.item_type,
            item_id: c.item_id,
            quantity: c.quantity,
        })),
        discount: Number(discount.value || 0),
        amount_paid:
            paymentMethod.value === "cash"
                ? Number(amountPaid.value || 0)
                : total.value,
        payment_method: paymentMethod.value,
        notes: notes.value || null,
        denomination:
            paymentMethod.value === "cash" && showDenominationStep.value
                ? denominationInputs.value
                : null,
    };

    router.post(route("teller.checkout"), payload, {
        preserveScroll: true,
        onSuccess: () => {
            cart.value = [];
            showCheckout.value = false;
            const saleId = page.props.flash?.receipt_sale_id;
            if (saleId) openReceipt(saleId);
        },
        onError: (errors) => {
            Object.values(errors).forEach((e) => toast.error(e));
        },
    });
}

/* ------------------------------------------------------------------ */
/* Receipt modal                                                       */
/* ------------------------------------------------------------------ */
const showReceipt = ref(false);
const receiptData = ref(null);
const loadingReceipt = ref(false);
const receiptSaleId = ref(null);

async function openReceipt(saleId) {
    receiptSaleId.value = saleId;
    showReceipt.value = true;
    loadingReceipt.value = true;

    try {
        const res = await fetch(route("teller.receipt", saleId), {
            headers: { Accept: "application/json" },
            credentials: "same-origin",
        });
        if (!res.ok) throw new Error("Failed to load receipt");
        receiptData.value = await res.json();
    } catch (e) {
        toast.error("Failed to load receipt.");
        showReceipt.value = false;
    } finally {
        loadingReceipt.value = false;
    }
}

function closeReceipt() {
    showReceipt.value = false;
    receiptData.value = null;
    receiptSaleId.value = null;
    router.reload({ preserveScroll: true, only: ["recentSales", "stats"] });
}

function printReceipt() {
    if (!receiptData.value?.reference_no) return;

    const saleId = receiptSaleId.value;
    if (!saleId) return;

    const url = route("teller.receipt.print", saleId);
    window.open(url, "_blank", "width=400,height=700,noopener,noreferrer");
}

/* ------------------------------------------------------------------ */
/* Denomination modal (manual)                                         */
/* ------------------------------------------------------------------ */
const showDenominationModal = ref(false);
const denominationType = ref("cash_in");
const manualDenomination = ref(
    Object.fromEntries(denominations.map((d) => [d, 0])),
);

function openDenomination(type) {
    denominationType.value = type;
    manualDenomination.value = Object.fromEntries(
        denominations.map((d) => [d, 0]),
    );
    showDenominationModal.value = true;
}

const manualDenominationTotal = computed(() =>
    denominations.reduce(
        (sum, d) => sum + d * Number(manualDenomination.value[d] || 0),
        0,
    ),
);

function submitDenomination() {
    router.post(
        route("teller.denomination"),
        {
            type: denominationType.value,
            breakdown: manualDenomination.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                showDenominationModal.value = false;
                router.reload({ preserveScroll: true, only: ["stats"] });
            },
            onError: (errors) =>
                Object.values(errors).forEach((e) => toast.error(e)),
        },
    );
}

/* ------------------------------------------------------------------ */
/* Helpers                                                             */
/* ------------------------------------------------------------------ */
function money(n) {
    return (
        "₱" +
        Number(n || 0).toLocaleString("en-PH", {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        })
    );
}

function quickCash(amount) {
    amountPaid.value = amount;
}

function formatDate(dateStr) {
    if (!dateStr) return "—";
    const d = new Date(dateStr + "T00:00:00");
    return d.toLocaleDateString("en-PH", {
        weekday: "short",
        year: "numeric",
        month: "short",
        day: "numeric",
    });
}
</script>

<template>
    <div class="mx-auto max-w-[1600px] p-3 sm:p-4 lg:p-5">
        <!-- Top bar -->
        <div
            class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1 class="text-lg font-bold sm:text-xl">Point of Sale</h1>
                <p class="text-xs opacity-60">
                    {{ formatDate(stats.business_date) }}
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <button
                    v-if="!stats.has_beginning"
                    type="button"
                    class="btn btn-xs sm:btn-sm btn-success"
                    @click="openDenomination('cash_beginning')"
                >
                    Open Shift (Cash Beginning)
                </button>

                <button
                    type="button"
                    class="btn btn-xs sm:btn-sm btn-outline"
                    @click="openDenomination('cash_in')"
                >
                    Cash In
                </button>
                <button
                    type="button"
                    class="btn btn-xs sm:btn-sm btn-outline"
                    @click="openDenomination('cash_out')"
                >
                    Cash Out
                </button>

                <Link
                    :href="route('teller.report.index')"
                    class="btn btn-xs sm:btn-sm btn-outline"
                >
                    Reports
                </Link>

                <button
                    v-if="stats.has_beginning && !stats.has_drawer_count"
                    type="button"
                    class="btn btn-xs sm:btn-sm btn-primary"
                    @click="openDenomination('drawer_count')"
                >
                    Count Drawer
                </button>
            </div>
        </div>

        <!-- Stats strip -->
        <div class="mb-3 grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-7">
            <div class="rounded-xl bg-base-100/60 p-3">
                <span class="text-[10px] uppercase opacity-60"
                    >Today's Sales</span
                >
                <p class="text-lg font-bold">{{ stats.today_count }}</p>
            </div>
            <div class="rounded-xl bg-base-100/60 p-3">
                <span class="text-[10px] uppercase opacity-60">Revenue</span>
                <p class="text-lg font-bold">
                    {{ money(stats.today_revenue) }}
                </p>
            </div>
            <div class="rounded-xl bg-base-100/60 p-3">
                <span class="text-[10px] uppercase opacity-60">Beginning</span>
                <p class="text-lg font-bold text-info">
                    {{ money(stats.cash_beginning) }}
                </p>
            </div>
            <div class="rounded-xl bg-base-100/60 p-3">
                <span class="text-[10px] uppercase opacity-60">Cash In</span>
                <p class="text-lg font-bold text-success">
                    {{ money(stats.cash_in) }}
                </p>
            </div>
            <div class="rounded-xl bg-base-100/60 p-3">
                <span class="text-[10px] uppercase opacity-60">Cash Out</span>
                <p class="text-lg font-bold text-error">
                    {{ money(stats.cash_out) }}
                </p>
            </div>

            <div class="rounded-xl bg-base-100/60 p-3">
                <span class="text-[10px] uppercase opacity-60">Drawer</span>
                <p class="text-lg font-bold text-primary">
                    {{ money(stats.cash_drawer) }}
                </p>
            </div>

            <div class="rounded-xl bg-base-100/60 p-3">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] uppercase opacity-60"
                        >Variance</span
                    >
                    <span
                        v-if="hasDrawerCount"
                        class="badge badge-xs"
                        :class="varianceBadgeClass"
                    >
                        {{ varianceLabel }}
                    </span>
                </div>
                <p class="text-lg font-bold" :class="varianceClass">
                    <span v-if="hasDrawerCount">
                        {{ variance > 0 ? "+" : "" }}{{ money(variance) }}
                    </span>
                    <span v-else class="text-xs font-normal opacity-60">
                        Not counted
                    </span>
                </p>
                <p v-if="hasDrawerCount" class="text-[10px] opacity-60">
                    Counted: {{ money(countedAmount) }}
                </p>
            </div>
        </div>

        <!-- Main split -->
        <div class="grid grid-cols-1 gap-3 lg:grid-cols-5">
            <!-- LEFT: catalog -->
            <div class="rounded-xl bg-base-100/60 p-3 lg:col-span-3">
                <!-- Tabs -->
                <div role="tablist" class="tabs tabs-boxed mb-2 w-fit">
                    <a
                        role="tab"
                        class="tab tab-sm"
                        :class="{ 'tab-active': tab === 'services' }"
                        @click="tab = 'services'"
                    >
                        Services ({{ services.length }})
                    </a>
                    <a
                        role="tab"
                        class="tab tab-sm"
                        :class="{ 'tab-active': tab === 'products' }"
                        @click="tab = 'products'"
                    >
                        Products ({{ products.length }})
                    </a>
                </div>

                <!-- Search -->
                <input
                    v-model="search"
                    type="text"
                    class="input input-sm input-bordered w-full text-xs mb-3"
                    :placeholder="
                        tab === 'services'
                            ? 'Search services…'
                            : 'Search products by name or SKU…'
                    "
                />

                <!-- Services grid -->
                <div
                    v-if="tab === 'services'"
                    class="grid max-h-125 grid-cols-2 gap-2 overflow-y-auto pr-1 sm:grid-cols-3 xl:grid-cols-4"
                >
                    <button
                        v-for="s in filteredServices"
                        :key="s.id"
                        type="button"
                        class="flex flex-col items-start rounded-lg bg-base-200/50 p-2 text-left hover:bg-base-300/60"
                        @click="addToCart(s, 'service')"
                    >
                        <span class="text-[10px] opacity-60">{{
                            s.category
                        }}</span>
                        <span class="mt-0.5 line-clamp-2 text-xs font-medium">{{
                            s.name
                        }}</span>
                        <span
                            class="mt-auto pt-2 text-xs font-bold text-primary"
                        >
                            {{ money(s.price) }}
                        </span>
                    </button>
                </div>

                <!-- Products grid -->
                <div
                    v-else
                    class="grid max-h-125 grid-cols-2 gap-2 overflow-y-auto pr-1 sm:grid-cols-3 xl:grid-cols-4"
                >
                    <button
                        v-for="p in filteredProducts"
                        :key="p.id"
                        type="button"
                        class="flex flex-col items-start rounded-lg bg-base-200/50 p-2 text-left hover:bg-base-300/60"
                        @click="addToCart(p, 'product')"
                    >
                        <span class="text-[10px] opacity-60">{{
                            p.category
                        }}</span>
                        <span class="mt-0.5 line-clamp-2 text-xs font-medium">{{
                            p.name
                        }}</span>
                        <span class="mt-0.5 text-[10px] opacity-60">
                            {{ p.sku }} · stock: {{ p.stock }} {{ p.unit }}
                        </span>
                        <span
                            class="mt-auto pt-2 text-xs font-bold text-primary"
                        >
                            {{ money(p.price) }}
                        </span>
                    </button>
                </div>
            </div>

            <!-- RIGHT: cart -->
            <div class="rounded-xl bg-base-100/60 p-3 lg:col-span-2">
                <div class="mb-2 flex items-center justify-between">
                    <h2 class="text-sm font-bold">Cart</h2>
                    <button
                        v-if="cart.length"
                        type="button"
                        class="btn btn-xs btn-ghost text-error"
                        @click="openClearCartModal"
                    >
                        Clear
                    </button>
                </div>

                <div
                    v-if="!cart.length"
                    class="flex h-40 items-center justify-center text-xs opacity-60"
                >
                    Cart is empty. Click a service or product.
                </div>

                <div v-else class="max-h-100 space-y-2 overflow-y-auto pr-1">
                    <div
                        v-for="c in cart"
                        :key="c.key"
                        class="flex items-start gap-2 rounded-lg bg-base-200/50 p-2"
                    >
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs font-medium">
                                {{ c.name }}
                            </p>
                            <p class="text-[10px] opacity-60">
                                {{
                                    c.item_type === "service"
                                        ? "Service"
                                        : "Product"
                                }}
                                · {{ money(c.price) }} each
                            </p>
                        </div>
                        <div class="flex items-center gap-1">
                            <button
                                class="btn btn-xs btn-ghost"
                                @click="decrement(c)"
                            >
                                −
                            </button>
                            <span class="w-6 text-center text-xs font-semibold">
                                {{ c.quantity }}
                            </span>
                            <button
                                class="btn btn-xs btn-ghost"
                                @click="increment(c)"
                            >
                                +
                            </button>
                        </div>
                        <div class="w-16 text-right text-xs font-semibold">
                            {{ money(c.price * c.quantity) }}
                        </div>
                        <button
                            type="button"
                            class="btn btn-xs btn-ghost text-error"
                            @click="removeItem(c)"
                        >
                            ✕
                        </button>
                    </div>
                </div>

                <div
                    class="mt-3 space-y-1 border-t border-base-200 pt-3 text-xs"
                >
                    <div class="flex justify-between">
                        <span class="opacity-60">Subtotal</span>
                        <span class="font-semibold">{{ money(subtotal) }}</span>
                    </div>
                </div>

                <button
                    type="button"
                    class="btn btn-primary btn-block mt-3 btn-sm sm:btn-md"
                    :disabled="!cart.length"
                    @click="openCheckout"
                >
                    Checkout ({{ money(subtotal) }})
                </button>

                <!-- Recent sales -->
                <div v-if="recentSales.length" class="mt-4">
                    <h3
                        class="mb-2 text-[11px] font-semibold uppercase opacity-60"
                    >
                        Recent Sales
                    </h3>
                    <div class="space-y-1">
                        <button
                            v-for="s in recentSales"
                            :key="s.id"
                            type="button"
                            class="flex w-full items-center justify-between rounded bg-base-200/40 px-2 py-1 text-left text-[11px] hover:bg-base-300/60"
                            @click="openReceipt(s.id)"
                        >
                            <span class="truncate">{{ s.reference_no }}</span>
                            <span class="ml-2 font-semibold">{{
                                money(s.total)
                            }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- =============================== Checkout Modal =============================== -->
    <dialog class="modal" :class="{ 'modal-open': showCheckout }">
        <div
            class="modal-box w-11/12 max-w-lg p-4 sm:p-6 max-h-[90vh] overflow-y-auto"
        >
            <div class="flex items-center justify-between mb-1">
                <h3 class="text-sm font-bold">Checkout</h3>
                <button
                    type="button"
                    class="btn btn-xs btn-circle btn-ghost"
                    @click="closeCheckout"
                >
                    ✕
                </button>
            </div>
            <div class="divider my-1"></div>

            <div class="space-y-3">
                <!-- Items summary -->
                <div class="rounded-lg bg-base-200/40 p-2">
                    <div class="max-h-32 space-y-1 overflow-y-auto">
                        <div
                            v-for="c in cart"
                            :key="c.key"
                            class="flex justify-between text-[11px]"
                        >
                            <span class="truncate">
                                {{ c.quantity }}× {{ c.name }}
                            </span>
                            <span class="ml-2 font-semibold">
                                {{ money(c.price * c.quantity) }}
                            </span>
                        </div>
                    </div>
                    <div
                        class="mt-2 flex justify-between border-t border-base-300 pt-2 text-xs"
                    >
                        <span>Subtotal</span>
                        <span class="font-semibold">{{ money(subtotal) }}</span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="form-control">
                        <label class="label py-0.5"
                            ><span class="label-text text-xs"
                                >Discount (₱)</span
                            ></label
                        >
                        <input
                            v-model.number="discount"
                            type="number"
                            min="0"
                            step="0.01"
                            class="input input-sm input-bordered w-full text-xs"
                        />
                    </div>

                    <div class="form-control">
                        <label class="label py-0.5"
                            ><span class="label-text text-xs"
                                >Payment Method</span
                            ></label
                        >
                        <select
                            v-model="paymentMethod"
                            class="select select-sm select-bordered w-full text-xs"
                        >
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="gcash">GCash</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>

                <div
                    class="flex justify-between rounded-lg bg-base-200/40 px-3 py-2 text-sm"
                >
                    <span class="font-semibold">Total</span>
                    <span class="font-bold text-primary">{{
                        money(total)
                    }}</span>
                </div>

                <!-- Cash payment -->
                <div v-if="paymentMethod === 'cash'">
                    <div class="form-control">
                        <label class="label py-0.5"
                            ><span class="label-text text-xs"
                                >Amount Paid (₱)</span
                            ></label
                        >
                        <input
                            v-model.number="amountPaid"
                            type="number"
                            min="0"
                            step="0.01"
                            class="input input-sm input-bordered w-full text-sm font-bold"
                        />
                    </div>

                    <!-- Quick cash buttons -->
                    <div class="mt-2 flex flex-wrap gap-1">
                        <button
                            type="button"
                            class="btn btn-xs btn-outline"
                            @click="quickCash(total)"
                        >
                            Exact
                        </button>
                        <button
                            type="button"
                            class="btn btn-xs btn-outline"
                            @click="quickCash(Math.ceil(total / 100) * 100)"
                        >
                            {{ money(Math.ceil(total / 100) * 100) }}
                        </button>
                        <button
                            type="button"
                            class="btn btn-xs btn-outline"
                            @click="quickCash(Math.ceil(total / 500) * 500)"
                        >
                            {{ money(Math.ceil(total / 500) * 500) }}
                        </button>
                        <button
                            type="button"
                            class="btn btn-xs btn-outline"
                            @click="quickCash(Math.ceil(total / 1000) * 1000)"
                        >
                            {{ money(Math.ceil(total / 1000) * 1000) }}
                        </button>
                    </div>

                    <div
                        class="mt-3 flex justify-between rounded-lg bg-success/10 px-3 py-2 text-sm"
                    >
                        <span class="font-semibold">Change</span>
                        <span class="font-bold text-success">{{
                            money(change)
                        }}</span>
                    </div>

                    <!-- Denomination toggle -->
                    <div class="mt-3">
                        <label class="flex cursor-pointer items-center gap-2">
                            <input
                                v-model="showDenominationStep"
                                type="checkbox"
                                class="checkbox checkbox-sm"
                            />
                            <span class="text-xs">Record denominations</span>
                        </label>

                        <div
                            v-if="showDenominationStep"
                            class="mt-2 rounded-lg bg-base-200/40 p-2"
                        >
                            <div class="grid grid-cols-3 gap-2 sm:grid-cols-3">
                                <div
                                    v-for="d in denominations"
                                    :key="d"
                                    class="form-control"
                                >
                                    <label class="label py-0"
                                        ><span
                                            class="label-text-alt text-[10px]"
                                            >₱{{ d }}</span
                                        ></label
                                    >
                                    <input
                                        v-model.number="denominationInputs[d]"
                                        type="number"
                                        min="0"
                                        class="input input-xs input-bordered w-full text-xs"
                                    />
                                </div>
                            </div>
                            <div class="mt-2 flex justify-between text-xs">
                                <span class="opacity-60">Counted total</span>
                                <span
                                    class="font-semibold"
                                    :class="
                                        denominationTotal === Number(amountPaid)
                                            ? 'text-success'
                                            : 'text-warning'
                                    "
                                >
                                    {{ money(denominationTotal) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Non-cash: assume amount paid equals total -->
                <div v-else>
                    <div class="rounded-lg bg-base-200/40 px-3 py-2 text-xs">
                        Non-cash payment — amount paid is assumed equal to the
                        total.
                    </div>
                </div>

                <div class="form-control">
                    <label class="label py-0.5"
                        ><span class="label-text text-xs"
                            >Notes (optional)</span
                        ></label
                    >
                    <input
                        v-model="notes"
                        type="text"
                        class="input input-sm input-bordered w-full text-xs"
                        placeholder="e.g. Senior citizen discount"
                    />
                </div>
            </div>

            <div class="modal-action mt-4">
                <button
                    type="button"
                    class="btn btn-xs sm:btn-sm btn-ghost"
                    @click="closeCheckout"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    class="btn btn-xs sm:btn-sm btn-primary"
                    :disabled="
                        paymentMethod === 'cash' && Number(amountPaid) < total
                    "
                    @click="submitCheckout"
                >
                    Complete Sale
                </button>
            </div>
        </div>

        <form method="dialog" class="modal-backdrop">
            <button @click="closeCheckout">close</button>
        </form>
    </dialog>

    <!-- =============================== Receipt Modal =============================== -->
    <dialog class="modal" :class="{ 'modal-open': showReceipt }">
        <div
            id="receipt-box"
            class="modal-box w-11/12 max-w-sm p-4 sm:p-6 max-h-[90vh] overflow-y-auto"
        >
            <div v-if="loadingReceipt" class="flex justify-center py-8">
                <span class="loading loading-spinner loading-md"></span>
            </div>

            <div v-else-if="receiptData" id="receipt-content">
                <div class="text-center">
                    <h3 class="text-base font-bold">MI BELLA - VIDA</h3>
                    <p class="text-[10px] opacity-60">Beauty Salon and Spa</p>
                </div>
                <div class="divider my-2"></div>

                <div class="space-y-0.5 text-[10px]">
                    <div class="flex justify-between">
                        <span>Ref:</span
                        ><span class="font-semibold">{{
                            receiptData.reference_no
                        }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Date:</span
                        ><span>{{ receiptData.business_date }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Cashier:</span
                        ><span>{{ receiptData.cashier.name }}</span>
                    </div>
                </div>

                <div class="divider my-2"></div>

                <div class="space-y-1 text-[11px]">
                    <div
                        v-for="(item, i) in receiptData.items"
                        :key="i"
                        class="flex justify-between gap-2"
                    >
                        <div class="min-w-0 flex-1">
                            <p class="truncate">{{ item.item_name }}</p>
                            <p class="text-[9px] opacity-60">
                                {{ item.quantity }} ×
                                {{ money(item.unit_price) }}
                            </p>
                        </div>
                        <span class="font-semibold">{{
                            money(item.line_total)
                        }}</span>
                    </div>
                </div>

                <div class="divider my-2"></div>

                <div class="space-y-1 text-[11px]">
                    <div class="flex justify-between">
                        <span>Subtotal</span
                        ><span>{{ money(receiptData.subtotal) }}</span>
                    </div>
                    <div
                        v-if="receiptData.discount > 0"
                        class="flex justify-between"
                    >
                        <span>Discount</span
                        ><span>-{{ money(receiptData.discount) }}</span>
                    </div>
                    <div class="flex justify-between text-sm font-bold">
                        <span>TOTAL</span
                        ><span>{{ money(receiptData.total) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Paid</span
                        ><span>{{ money(receiptData.amount_paid) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Change</span
                        ><span>{{ money(receiptData.change_due) }}</span>
                    </div>
                </div>

                <div class="divider my-2"></div>
                <p class="text-center text-[10px] opacity-60">
                    Thank you for your visit!
                </p>
            </div>

            <div class="modal-action mt-4 receipt-actions">
                <button
                    type="button"
                    class="btn btn-xs sm:btn-sm btn-outline"
                    @click="printReceipt"
                >
                    Print
                </button>
                <button
                    type="button"
                    class="btn btn-xs sm:btn-sm btn-primary"
                    @click="closeReceipt"
                >
                    Done
                </button>
            </div>
        </div>

        <form method="dialog" class="modal-backdrop">
            <button @click="closeReceipt">close</button>
        </form>
    </dialog>

    <!-- =============================== Denomination Modal =============================== -->
    <dialog class="modal" :class="{ 'modal-open': showDenominationModal }">
        <div class="modal-box w-11/12 max-w-md p-4 sm:p-6">
            <div class="flex items-center justify-between mb-1">
                <h3 class="text-sm font-bold capitalize">
                    {{ denominationType.replace("_", " ") }}
                </h3>
                <button
                    type="button"
                    class="btn btn-xs btn-circle btn-ghost"
                    @click="showDenominationModal = false"
                >
                    ✕
                </button>
            </div>
            <div class="divider my-1"></div>

            <div
                v-if="denominationType === 'drawer_count'"
                class="mb-3 rounded-lg bg-info/10 px-3 py-2 text-xs text-info"
            >
                Enter the physical cash you counted. Expected:
                <strong>{{ money(expectedDrawer) }}</strong>
            </div>

            <div class="grid grid-cols-3 gap-2">
                <div v-for="d in denominations" :key="d" class="form-control">
                    <label class="label py-0"
                        ><span class="label-text-alt text-[10px]"
                            >₱{{ d }}</span
                        ></label
                    >
                    <input
                        v-model.number="manualDenomination[d]"
                        type="number"
                        min="0"
                        class="input input-sm input-bordered w-full text-xs"
                    />
                </div>
            </div>

            <div
                class="mt-3 flex justify-between rounded-lg bg-base-200/40 px-3 py-2 text-sm"
            >
                <span class="font-semibold">Total</span>
                <span class="font-bold text-primary">{{
                    money(manualDenominationTotal)
                }}</span>
            </div>

            <div class="modal-action mt-4">
                <button
                    type="button"
                    class="btn btn-xs sm:btn-sm btn-ghost"
                    @click="showDenominationModal = false"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    class="btn btn-xs sm:btn-sm"
                    :class="{
                        'btn-error': denominationType === 'cash_out',
                        'btn-primary': denominationType === 'drawer_count',
                        'btn-success':
                            denominationType !== 'cash_out' &&
                            denominationType !== 'drawer_count',
                    }"
                    :disabled="manualDenominationTotal <= 0"
                    @click="submitDenomination"
                >
                    Confirm
                </button>
            </div>
        </div>

        <form method="dialog" class="modal-backdrop">
            <button @click="showDenominationModal = false">close</button>
        </form>
    </dialog>

    <!-- =============================== Clear Cart Modal =============================== -->
    <dialog class="modal" :class="{ 'modal-open': showClearCartModal }">
        <div class="modal-box w-11/12 max-w-md p-4 sm:p-6">
            <div class="flex items-start gap-3">
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-warning/10 text-warning"
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
                    <h3 class="text-base font-bold">Clear the cart?</h3>
                    <p class="mt-1 text-xs opacity-70">
                        You are about to remove
                        <span class="font-semibold">{{ cart.length }}</span>
                        {{ cart.length === 1 ? "item" : "items" }} from the
                        cart. This action cannot be undone.
                    </p>
                </div>
            </div>

            <div class="modal-action mt-5">
                <button
                    type="button"
                    class="btn btn-xs sm:btn-sm btn-ghost"
                    @click="closeClearCartModal"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    class="btn btn-xs sm:btn-sm btn-warning"
                    @click="confirmClearCart"
                >
                    Yes, clear
                </button>
            </div>
        </div>

        <form method="dialog" class="modal-backdrop">
            <button @click="closeClearCartModal">close</button>
        </form>
    </dialog>
</template>
