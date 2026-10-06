// resources\js\app.js
import "../css/app.css";
import "vue-sonner/style.css";
import { createApp, h } from "vue";
import { createInertiaApp, Head, Link } from "@inertiajs/vue3";
import { ZiggyVue } from "ziggy-js";
import Toaster from "@/Components/Toaster.vue";

createInertiaApp({
    title: (title) => (title ? `${title}` : "Salon Management System"),
    pages: {
        path: "./Pages",
        lazy: true,
    },

    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .component("Toaster", Toaster)
            .component("Head", Head)
            .component("Link", Link)
            .mount(el);
    },
});
