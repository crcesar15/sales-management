import type { MenuItem } from "primevue/menuitem";

export interface SidebarMenuItem extends MenuItem {
  to?: string;
  routeUrl?: string;
  can?: string;
  items?: SidebarMenuItem[];
}

export interface UserAction {
  label: string;
  icon: string;
  command: () => void;
}
