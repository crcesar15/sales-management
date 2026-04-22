import { computed, ref, watch } from "vue";
import { usePage } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useI18n } from "vue-i18n";
import type { SidebarMenuItem } from "../Types/menu";

// Permission caching for performance
const permissionCache = new Map<string, boolean>();

export function useMenuItems() {
  const page = usePage();
  const { t } = useI18n();

  // ========================================
  // Menu Items Definition
  // ========================================
  const menuItems = computed<SidebarMenuItem[]>(() => [
    // ========== OVERVIEW ==========
    {
      key: "dashboard",
      label: t("Dashboard"),
      icon: "fa fa-gauge",
      to: "home",
    },
    {
      key: "pos",
      label: t("Point of Sale"),
      icon: "fa fa-cash-register",
      to: "home",
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
      key: "inventory",
      label: t("Inventory"),
      icon: "fa fa-warehouse",
      items: [
        {
          key: "inventory-variants",
          label: t("Inventory"),
          icon: "fa fa-boxes-stacked",
          to: "inventory.variants",
          can: "inventory.view",
          routeUrl: route("inventory.variants"),
        },
        {
          key: "inventory-stores",
          label: t("Stores"),
          icon: "fa fa-store",
          to: "stores",
          can: "store.view",
          routeUrl: route("stores"),
        },
        {
          key: "inventory-stock-levels",
          label: t("Stock Levels"),
          icon: "fa fa-boxes-stacked",
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
    {
      key: "reports",
      label: t("Reports"),
      icon: "fa fa-chart-line",
      to: "home",
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
          can: "setting.manage",
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

  // ========================================
  // Permission Filtering
  // ========================================
  const userPermissions = computed<string[]>(() => (page.props.auth?.user?.permissions || []) as string[]);

  const permissionSet = computed(() => new Set(userPermissions.value));

  function hasPermission(permission?: string): boolean {
    if (!permission) return true;

    const cacheKey = `${permission}-${userPermissions.value.join(",")}`;
    if (!permissionCache.has(cacheKey)) {
      permissionCache.set(cacheKey, permissionSet.value.has(permission));
    }
    return permissionCache.get(cacheKey) ?? false;
  }

  const filteredMenuItems = computed<SidebarMenuItem[]>(() => {
    return menuItems.value.reduce((acc, group) => {
      if (!group.items) {
        return hasPermission(group.can) ? [...acc, group] : acc;
      }

      const visibleChildren = group.items.filter((child) => hasPermission(child.can));

      if (visibleChildren.length === 0) return acc;

      return [...acc, { ...group, items: visibleChildren }];
    }, [] as SidebarMenuItem[]);
  });

  // ========================================
  // Active Route Detection
  // ========================================
  function getPathname(url: string): string {
    try {
      return new URL(url).pathname;
    } catch {
      return url;
    }
  }

  function isActiveRoute(item: SidebarMenuItem): boolean {
    if (!item.to) return false;
    try {
      const pathname = getPathname(route(item.to));
      return page.url.startsWith(pathname);
    } catch {
      return false;
    }
  }

  function computeExpandedKeys(): Record<string, boolean> {
    const keys: Record<string, boolean> = {};
    for (const group of filteredMenuItems.value) {
      if (group.items) {
        for (const child of group.items) {
          if (isActiveRoute(child) && group.key) {
            keys[group.key] = true;
            break;
          }
        }
      }
    }
    return keys;
  }

  const expandedKeys = ref<Record<string, boolean>>(computeExpandedKeys());

  watch(
    () => page.url,
    () => {
      const keys = computeExpandedKeys();
      expandedKeys.value = { ...expandedKeys.value, ...keys };
    },
  );

  return {
    menuItems,
    filteredMenuItems,
    expandedKeys,
    isActiveRoute,
    hasPermission,
  };
}
