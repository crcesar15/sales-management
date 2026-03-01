import { computed } from "vue";
import type { SidebarMenuItem, UserAction } from "../Types/menu";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useI18n } from "vue-i18n";

export function useMenuItems() {
  const { t } = useI18n();

  const menuItems = computed<SidebarMenuItem[]>(() => [
    // ========== OVERVIEW ==========
    {
      key: "dashboard",
      label: t("Dashboard"),
      icon: "fa fa-gauge",
      to: "home",
    },
    // ========== OPERATIONS (Most Used) ==========
    {
      key: "pos",
      label: t("Point of Sale"),
      icon: "fa fa-cash-register",
      to: "home",
      separator: true, // Visual separator after Dashboard
    },
    {
      key: "inventory",
      label: t("Inventory"),
      icon: "fa fa-warehouse",
      items: [
        {
          key: "inventory-stock-levels",
          label: t("Stock Levels"),
          icon: "fa fa-boxes-stacked",
          to: "home",
        },
        {
          key: "inventory-stores",
          label: t("Stores"),
          icon: "fa fa-store",
          to: "home",
        },
        {
          key: "inventory-adjustments",
          label: t("Adjustments"),
          icon: "fa fa-sliders",
          to: "home",
        },
      ],
    },
    // ========== MANAGEMENT ==========
    {
      key: "products",
      label: t("Products"),
      icon: "fa fa-box",
      items: [
        {
          key: "products-list",
          label: t("All Products"),
          icon: "fa fa-list",
          to: "products",
          can: "product.view",
          routeUrl: route("products"),
        },
        {
          key: "products-categories",
          label: t("Categories"),
          icon: "fa fa-layer-group",
          to: "categories",
          can: "category.view",
          routeUrl: route("categories"),
        },
        {
          key: "products-brands",
          label: t("Brands"),
          icon: "fa fa-tag",
          to: "brands",
          can: "brand.view",
          routeUrl: route("brands"),
        },
        {
          key: "products-measurement-units",
          label: t("Units"),
          icon: "fa fa-weight-hanging",
          to: "measurement-units",
          can: "measurement_unit.view",
          routeUrl: route("measurement-units"),
        },
      ],
    },
    {
      key: "sales",
      label: t("Sales"),
      icon: "fa fa-receipt",
      items: [
        {
          key: "sales-orders",
          label: t("Sales Orders"),
          icon: "fa fa-file-invoice-dollar",
          to: "home",
        },
        {
          key: "sales-customers",
          label: t("Customers"),
          icon: "fa fa-users",
          to: "home",
        },
      ],
    },
    {
      key: "purchases",
      label: t("Purchases"),
      icon: "fa fa-cart-shopping",
      items: [
        {
          key: "purchases-orders",
          label: t("Purchase Orders"),
          icon: "fa fa-file-invoice",
          to: "purchase-orders",
          routeUrl: route("purchase-orders"),
        },
        {
          key: "purchases-catalog",
          label: t("Supplier Catalog"),
          icon: "fa fa-tags",
          to: "catalog",
          routeUrl: route("catalog"),
        },
        {
          key: "purchases-vendors",
          label: t("Vendors"),
          icon: "fa fa-truck-field",
          to: "vendors",
          routeUrl: route("vendors"),
        },
      ],
    },
    // ========== ANALYTICS & ADMIN ==========
    {
      key: "reports",
      label: t("Reports"),
      icon: "fa fa-chart-line",
      to: "home",
      separator: true, // Visual separator before Analytics section
    },
    {
      key: "admin",
      label: t("Admin"),
      icon: "fa fa-gear",
      items: [
        {
          key: "admin-users",
          label: t("Users"),
          icon: "fa fa-user",
          to: "users",
          can: "user.view",
          routeUrl: route("users"),
        },
        {
          key: "admin-roles",
          label: t("Roles"),
          icon: "fa fa-user-tag",
          to: "roles",
          can: "role.view",
          routeUrl: route("roles"),
        },
        {
          key: "admin-permissions",
          label: t("Permissions"),
          icon: "fa fa-shield-halved",
          to: "home",
        },
        {
          key: "admin-settings",
          label: t("Settings"),
          icon: "fa fa-sliders",
          to: "settings",
          routeUrl: route("settings"),
        },
        {
          key: "admin-activity-logs",
          label: t("Activity Log"),
          icon: "fa fa-clock-rotate-left",
          to: "activity-logs",
          can: "activity_log.view",
          routeUrl: route("activity-logs"),
        },
      ],
    },
  ]);

  const userActions = computed<UserAction[]>(() => [
    {
      label: t("Profile"),
      icon: "fa fa-user",
      command: () => {
        router.visit(route("profile"));
      },
    },
    {
      label: t("Logout"),
      icon: "fa fa-sign-out-alt",
      command: () => {
        router.post(route("logout"));
      },
    },
  ]);

  return {
    menuItems,
    userActions,
  };
}
