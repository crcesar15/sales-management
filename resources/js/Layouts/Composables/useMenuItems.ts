import { computed } from "vue";
import type { MenuItemType, UserAction } from "../Types/menu";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useI18n } from "vue-i18n";

export function useMenuItems() {
  const { t } = useI18n();

  const menuItems = computed<MenuItemType[]>(() => [
    {
      label: t("Dashboard"),
      icon: "fa fa-gauge",
      type: "single",
      items: [
        {
          label: t("Dashboard"),
          icon: "fa fa-chart-line",
          to: "home",
        },
      ],
    },
    {
      label: t("Products"),
      icon: "fa fa-cubes",
      type: "multiple",
      items: [
        {
          label: t("Products"),
          icon: "fa fa-list",
          to: "products",
          can: "product.view",
          route: route("products"),
        },
        {
          label: t("Categories"),
          icon: "fa fa-layer-group",
          to: "categories",
          can: "category.view",
          route: route("categories"),
        },
        {
          label: t("Brands"),
          icon: "fa fa-copyright",
          to: "brands",
          can: "brand.view",
          route: route("brands"),
        },
        {
          label: t("Measurement Units"),
          icon: "fa fa-weight-hanging",
          to: "measurement-units",
          can: "measurement_unit.view",
          route: route("measurement-units"),
        },
      ],
    },
    {
      label: t("Sales"),
      icon: "fa fa-dollar-sign",
      type: "multiple",
      items: [
        {
          label: t("Point of Sale"),
          icon: "fa fa-cash-register",
          to: "home",
        },
        {
          label: t("Orders"),
          icon: "fa fa-file-lines",
          to: "home",
        },
        {
          label: t("Customers"),
          icon: "fa fa-users",
          to: "home",
        },
      ],
    },
    {
      label: t("Purchases"),
      icon: "fas fa-coins",
      type: "multiple",
      items: [
        {
          label: t("Orders"),
          icon: "fa fa-file-lines",
          to: "purchase-orders",
          route: route("purchase-orders"),
        },
        {
          label: t("Product Catalog"),
          icon: "fa fa-tags",
          to: "catalog",
          route: route("catalog"),
        },
        {
          label: t("Vendors"),
          icon: "fa fa-truck-field",
          to: "vendors",
          route: route("vendors"),
        },
      ],
    },
    {
      label: t("Inventory"),
      icon: "fa fa-box-open",
      type: "multiple",
      items: [
        {
          label: t("Inventory"),
          icon: "fa fa-boxes-stacked",
          to: "home",
        },
        {
          label: t("Stores"),
          icon: "fa fa-warehouse",
          to: "home",
        },
        {
          label: t("Stocks"),
          icon: "fa fa-cubes-stacked",
          to: "home",
        },
      ],
    },
    {
      label: t("Reports"),
      icon: "fa fa-chart-line",
      type: "single",
      to: "home",
    },
    {
      label: t("Admin"),
      icon: "fa fa-cogs",
      type: "multiple",
      items: [
        {
          label: t("Users"),
          icon: "fa fa-user",
          to: "users",
          can: "user.view",
          route: route("users"),
        },
        {
          label: t("Roles"),
          icon: "fa fa-user-tag",
          to: "roles",
          can: "role.view",
          route: route("roles"),
        },
        {
          label: t("Permissions"),
          icon: "fa fa-user-lock",
          to: "home",
        },
        {
          label: t("Settings"),
          icon: "fa fa-cog",
          to: "settings",
          route: route("settings"),
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
