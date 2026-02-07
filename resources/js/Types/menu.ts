export interface MenuItem {
  label: string;
  icon: string;
  to?: string;
  route?: string;
  can?: string;
}

export interface MenuGroup {
  label: string;
  icon: string;
  type: "single" | "multiple";
  to?: string;
  route?: string;
  items?: MenuItem[];
}

export interface UserAction {
  label: string;
  icon: string;
  command: () => void;
}
