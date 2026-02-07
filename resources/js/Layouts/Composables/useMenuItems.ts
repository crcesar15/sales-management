import { computed } from "vue";
import type { MenuItemType, UserAction } from "../Types/menu";
import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";

export function useMenuItems() {
  const menuItems = computed<MenuItemType[]>(() => [
    {
      label: "Dashboard",
      icon: "fa fa-gauge",
      type: "single",
      items: [
        {
          label: "Dashboard",
          icon: "fa fa-chart-line",
          to: "home",
        },
      ],
    },
    {
      label: "Products",
      icon: "fa fa-cubes",
      type: "multiple",
      items: [
        {
          label: "Products",
          icon: "fa fa-list",
          to: "products",
          can: "product.view",
          route: route("products"),
        },
        {
          label: "Categories",
          icon: "fa fa-layer-group",
          to: "categories",
          can: "category.view",
          route: route("categories"),
        },
        {
          label: "Brands",
          icon: "fa fa-copyright",
          to: "brands",
          can: "brand.view",
          route: route("brands"),
        },
        {
          label: "Measurement Units",
          icon: "fa fa-weight-hanging",
          to: "measurement-units",
          can: "measurement_unit.view",
          route: route("measurement-units"),
        },
      ],
    },
    {
      label: "Sales",
      icon: "fa fa-dollar-sign",
      type: "multiple",
      items: [
        {
          label: "Point of Sale",
          icon: "fa fa-cash-register",
          to: "home",
        },
        {
          label: "Orders",
          icon: "fa fa-file-lines",
          to: "home",
        },
        {
          label: "Customers",
          icon: "fa fa-users",
          to: "home",
        },
      ],
    },
    {
      label: "Purchases",
      icon: "fas fa-coins",
      type: "multiple",
      items: [
        {
          label: "Orders",
          icon: "fa fa-file-lines",
          to: "purchase-orders",
          route: route("purchase-orders"),
        },
        {
          label: "Product Catalog",
          icon: "fa fa-tags",
          to: "catalog",
          route: route("catalog"),
        },
        {
          label: "Vendors",
          icon: "fa fa-truck-field",
          to: "vendors",
          route: route("vendors"),
        },
      ],
    },
    {
      label: "Inventory",
      icon: "fa fa-box-open",
      type: "multiple",
      items: [
        {
          label: "Inventory",
          icon: "fa fa-boxes-stacked",
          to: "home",
        },
        {
          label: "Stores",
          icon: "fa fa-warehouse",
          to: "home",
        },
        {
          label: "Stocks",
          icon: "fa fa-cubes-stacked",
          to: "home",
        },
      ],
    },
    {
      label: "Reports",
      icon: "fa fa-chart-line",
      type: "single",
      to: "home",
    },
    {
      label: "Admin",
      icon: "fa fa-cogs",
      type: "multiple",
      items: [
        {
          label: "Users",
          icon: "fa fa-user",
          to: "users",
          can: "user.view",
          route: route("users"),
        },
        {
          label: "Roles",
          icon: "fa fa-user-tag",
          to: "roles",
          can: "role.view",
          route: route("roles"),
        },
        {
          label: "Permissions",
          icon: "fa fa-user-lock",
          to: "home",
        },
        {
          label: "Settings",
          icon: "fa fa-cog",
          to: "settings",
          route: route("settings"),
        },
      ],
    },
  ]);

  const userActions = computed<UserAction[]>(() => [
    {
      label: "Profile",
      icon: "fa fa-user",
      command: () => {
        router.visit(route("profile"));
      },
    },
    {
      label: "Logout",
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
