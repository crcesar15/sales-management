import type { AuthState } from "./auth-types";
import type { DefineComponent } from "vue";

// Extend the Inertia PageProps interface
declare module "@inertiajs/core" {
  interface PageProps {
    auth: {
      user: AuthState["user"];
      settings: AuthState["settings"];
    };
    appConfig?: {
      name?: string;
    };
  }
}

declare module "*.vue" {
  const component: DefineComponent<unknown, unknown, unknown>;
  export default component;
}
