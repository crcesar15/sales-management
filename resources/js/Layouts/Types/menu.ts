import type { MenuItem } from "primevue/menuitem";

export interface SidebarMenuItem extends MenuItem {
  to?: string;
  routeUrl?: string;
  can?: string;
  items?: SidebarMenuItem[];
  separator?: boolean; // Visual separator before this item
}

export interface UserAction {
  label: string;
  icon: string;
  command: () => void;
}
