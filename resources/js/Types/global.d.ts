import { type SettingGrouped } from "./setting-types";
import { type User } from "./user-types";
import type { DefineComponent } from "vue";

// Extend the Inertia PageProps interface
declare module "@inertiajs/core" {
  interface PageProps {
    auth: {
      user: User & { permissions: string[] };
    };
    settings: SettingGrouped;
    appConfig?: {
      name?: string;
    };
  }
}

declare module "*.vue" {
  const component: DefineComponent<{}, {}, any>;
  export default component;
}
