// resources\js\Data\SidebarLinks.js
export const SidebarLinks = {
    admin: [
        {
            category: "Main",
            links: [
                {
                    label: "Dashboard",
                    link: "/admin/dashboard",
                    icon: "hgi hgi-stroke hgi-rounded hgi-dashboard-square-03",
                },
                {
                    label: "Reports",
                    link: "",
                    icon: "hgi hgi-stroke hgi-rounded hgi-newspaper",
                },
            ],
        },
        {
            category: "Management",
            links: [
                {
                    label: "Manage Account",
                    link: "/manage/account",
                    icon: "hgi hgi-stroke hgi-rounded hgi-user-multiple-03",
                },
                {
                    label: "Manage Inventory",
                    link: "/manage/inventory",
                    icon: "hgi hgi-stroke hgi-rounded hgi-catalogue",
                },
                {
                    label: "Manage Services",
                    link: "/manage/services",
                    icon: "hgi hgi-stroke hgi-rounded hgi-body-soap",
                },
                {
                    label: "Manage Booking",
                    link: "/manage/booking",
                    icon: "hgi hgi-stroke hgi-rounded hgi-book-open-text",
                },
                {
                    label: "Manage Schedule",
                    link: "/manage/booking-schedule",
                    icon: "hgi hgi-stroke hgi-rounded hgi-calendar-03",
                },
            ],
        },
    ],
    staff: [
        {
            category: "Main",
            links: [
                {
                    label: "Dashboard",
                    link: "/staff/dashboard",
                    icon: "hgi hgi-stroke hgi-rounded hgi-dashboard-square-03",
                },
                {
                    label: "Reports",
                    link: "/cashier/teller/report",
                    icon: "hgi hgi-stroke hgi-rounded hgi-newspaper",
                },
            ],
        },
        {
            category: "Management",
            links: [
                {
                    label: "Manage Inventory",
                    link: "/manage/inventory",
                    icon: "hgi hgi-stroke hgi-rounded hgi-catalogue",
                },
                {
                    label: "Manage Booking",
                    link: "/manage/booking",
                    icon: "hgi hgi-stroke hgi-rounded hgi-book-open-text",
                },
                {
                    label: "Manage Schedule",
                    link: "/manage/booking-schedule",
                    icon: "hgi hgi-stroke hgi-rounded hgi-calendar-03",
                },
            ],
        },
        {
            category: "Cahier",
            links: [
                {
                    label: "Teller",
                    link: "/cashier/teller",
                    icon: "hgi hgi-stroke hgi-rounded hgi-hand-coins",
                },
            ],
        },
    ],
};
