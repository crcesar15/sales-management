import { computed, shallowRef, watch } from "vue";
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
    {
      key: "pos",
      label: t("Point of Sale"),
      icon: "fa fa-cash-register",
      to: "pos",
      can: "pos.access",
      routeUrl: route("pos"),
    },
    {
      key: "dashboard",
      label: t("Dashboard"),
      icon: "fa fa-gauge",
      to: "home",
      routeUrl: route("home"),
    },
    {
      key: "inventory",
      label: t("Inventory"),
      icon: "fa fa-warehouse",
      items: [
        {
          key: "inventory-variants",
          label: t("Stock Overview"),
          icon: "fa fa-boxes-stacked",
          to: "inventory.variants",
          can: "inventory.view",
          routeUrl: route("inventory.variants"),
        },
        {
          key: "inventory-alerts",
          label: t("Stock Alerts"),
          icon: "fa fa-bell",
          to: "inventory.alerts",
          can: "stock_alert.view",
          routeUrl: route("inventory.alerts"),
        },
        {
          key: "inventory-stock-transfers",
          label: t("Stock Transfers"),
          icon: "fa fa-truck-ramp-box",
          to: "stock-transfers",
          can: "stock_transfer.view",
          routeUrl: route("stock-transfers"),
        },
        {
          key: "inventory-adjustments",
          label: t("Stock Adjustments"),
          icon: "fa fa-sliders",
          to: "stock-adjustments",
          can: "stock.adjust",
          routeUrl: route("stock-adjustments"),
        },
        {
          key: "inventory-batches",
          label: t("Batches"),
          icon: "fa fa-layer-group",
          to: "batches",
          can: "batch.view",
          routeUrl: route("batches"),
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
          to: "sales-orders",
          can: "sales.view",
          routeUrl: route("sales-orders"),
        },
        {
          key: "sales-customers",
          label: t("Customers"),
          icon: "fa fa-users",
          to: "customers",
          can: "customer.view",
          routeUrl: route("customers"),
        },
        {
          key: "sales-shifts",
          label: t("Shifts"),
          icon: "fa fa-clock",
          to: "shifts",
          can: "shift.view",
          routeUrl: route("shifts"),
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
          can: "purchase_order.view",
          routeUrl: route("purchase-orders"),
        },
        {
          key: "purchases-receptions",
          label: t("Reception Orders"),
          icon: "fa fa-truck-ramp-box",
          to: "reception-orders",
          can: "reception_order.view",
          routeUrl: route("reception-orders"),
        },
        {
          key: "purchases-vendors",
          label: t("Vendors"),
          icon: "fa fa-truck-field",
          to: "vendors",
          can: "vendor.view",
          routeUrl: route("vendors"),
        },
        {
          key: "purchases-catalog",
          label: t("Supplier Catalog"),
          icon: "fa fa-tags",
          to: "catalog",
          can: "catalog.view",
          routeUrl: route("catalog"),
        },
      ],
    },
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
          label: t("Measurement Units"),
          icon: "fa fa-weight-hanging",
          to: "measurement-units",
          can: "measurement_unit.view",
          routeUrl: route("measurement-units"),
        },
      ],
    },
    {
      key: "administration",
      label: t("Administration"),
      icon: "fa fa-gear",
      items: [
        {
          key: "administration-users",
          label: t("Users"),
          icon: "fa fa-user",
          to: "users",
          can: "user.view",
          routeUrl: route("users"),
        },
        {
          key: "administration-roles",
          label: t("Roles"),
          icon: "fa fa-user-tag",
          to: "roles",
          can: "role.view",
          routeUrl: route("roles"),
        },
        {
          key: "administration-stores",
          label: t("Stores"),
          icon: "fa fa-store",
          to: "stores",
          can: "store.view",
          routeUrl: route("stores"),
        },
        {
          key: "administration-cash-registers",
          label: t("Cash Registers"),
          icon: "fa fa-cash-register",
          to: "cash-registers",
          can: "cash_register.view",
          routeUrl: route("cash-registers"),
        },
        {
          key: "administration-activity-logs",
          label: t("Activity Log"),
          icon: "fa fa-clock-rotate-left",
          to: "activity-logs",
          can: "activity_log.view",
          routeUrl: route("activity-logs"),
        },
        {
          key: "administration-settings",
          label: t("Settings"),
          icon: "fa fa-sliders",
          to: "settings",
          can: "setting.manage",
          routeUrl: route("settings"),
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

  const directMenuItems = computed<SidebarMenuItem[]>(() => filteredMenuItems.value.filter((item) => !item.items));

  const groupedMenuItems = computed<SidebarMenuItem[]>(() => filteredMenuItems.value.filter((item) => item.items));

  // ========================================
  // Active Route Detection
  // ========================================
  function getPathname(url: string): string {
    try {
      const pathname = new URL(url, "http://localhost").pathname;

      return pathname === "/" ? pathname : pathname.replace(/\/+$/, "");
    } catch {
      return url.split(/[?#]/)[0] || "/";
    }
  }

  function itemMatchesCurrentPath(item: SidebarMenuItem, currentPathname: string): boolean {
    if (!item.routeUrl && !item.to) return false;

    const routePathname = getPathname(item.routeUrl ?? route(item.to as string));

    return currentPathname === routePathname || (routePathname !== "/" && currentPathname.startsWith(`${routePathname}/`));
  }

  const visibleLeafItems = computed<SidebarMenuItem[]>(() => filteredMenuItems.value.flatMap((item) => (item.items ? item.items : [item])));

  const activeMenuItemKey = computed<string | null>(() => {
    const currentPathname = getPathname(page.url);
    const matchingItems = visibleLeafItems.value
      .filter((item) => itemMatchesCurrentPath(item, currentPathname))
      .sort((first, second) => {
        const firstPathLength = getPathname(first.routeUrl ?? route(first.to as string)).length;
        const secondPathLength = getPathname(second.routeUrl ?? route(second.to as string)).length;

        return secondPathLength - firstPathLength;
      });

    return typeof matchingItems[0]?.key === "string" ? matchingItems[0].key : null;
  });

  function isActiveRoute(item: SidebarMenuItem): boolean {
    return typeof item.key === "string" && activeMenuItemKey.value === item.key;
  }

  function isActiveGroup(item: SidebarMenuItem): boolean {
    return item.items?.some((child) => isActiveRoute(child)) ?? false;
  }

  const activeGroupKey = computed<string | null>(() => {
    const activeGroup = groupedMenuItems.value.find((item) => isActiveGroup(item));

    return typeof activeGroup?.key === "string" ? activeGroup.key : null;
  });

  const expandedKeys = shallowRef<Record<string, boolean>>({});

  function visibleGroupKeys(): Set<string> {
    return new Set(groupedMenuItems.value.map((item) => item.key).filter((key): key is string => typeof key === "string"));
  }

  function expandedGroupKey(keys: Record<string, boolean>, groupKeys: Set<string>): string | null {
    return Object.keys(keys).find((key) => keys[key] && groupKeys.has(key)) ?? null;
  }

  function synchronizeExpandedKeys(): void {
    expandedKeys.value = activeGroupKey.value ? { [activeGroupKey.value]: true } : {};
  }

  function updateExpandedKeys(nextKeys: Record<string, boolean>): void {
    const groupKeys = visibleGroupKeys();
    const currentGroupKey = expandedGroupKey(expandedKeys.value, groupKeys);
    const requestedGroupKeys = Object.keys(nextKeys).filter((key) => nextKeys[key] && groupKeys.has(key));
    const newlyOpenedGroupKey = requestedGroupKeys.find((key) => key !== currentGroupKey);
    const nextGroupKey = newlyOpenedGroupKey ?? (currentGroupKey && requestedGroupKeys.includes(currentGroupKey) ? currentGroupKey : null);

    expandedKeys.value = nextGroupKey ? { [nextGroupKey]: true } : {};
  }

  watch([() => page.url, groupedMenuItems], synchronizeExpandedKeys, { immediate: true });

  return {
    menuItems,
    filteredMenuItems,
    directMenuItems,
    groupedMenuItems,
    expandedKeys,
    updateExpandedKeys,
    isActiveRoute,
    isActiveGroup,
    hasPermission,
  };
}
