<template>
  <app-layout>
    <slot />
    <Toast />
  </app-layout>
</template>
<script lang="ts">
import axios from "axios";
import Toast from "primevue/toast";
import AppLayout from "./components/AppLayout.vue";

export default {
  components: {
    AppLayout,
    Toast,
  },
  provide() {
    return {
      menuitems: this.menuitems,
    };
  },
  data() {
    return {
      menuitems: [
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
              route: route("products"),
            },
            {
              label: "Categories",
              icon: "fa fa-layer-group",
              to: "categories",
              can: "categories-view",
              route: route("categories"),
            },
            {
              label: "Brands",
              icon: "fa fa-copyright",
              to: "brands",
              can: "brands-view",
              route: route("brands"),
            },
            {
              label: "Measurement Units",
              icon: "fa fa-weight-hanging",
              to: "measurement-units",
              can: "measurement-units-view",
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
              can: "users-view",
              route: route("users"),
            },
            {
              label: "Roles",
              icon: "fa fa-user-tag",
              to: "roles",
              can: "roles-view",
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
      ],
      sidebarVisibility: false,
      userActions: [
        {
          label: "Profile",
          icon: "fa fa-user",
          command: () => {
            this.$inertia.visit("/profile");
          },
        },
        {
          label: "Logout",
          icon: "fa fa-sign-out-alt",
          command: () => {
            const domain = window.location.origin;

            axios.post(`${domain}/logout`).then(() => {
              window.location = "/";
            });
          },
        },
      ],
    };
  },
  methods: {
    toggleSidebar() {
      this.sidebarVisibility = !this.sidebarVisibility;
    },
    toggleUserActions(event) {
      this.$refs.userActions.toggle(event);
    },
    redirect(path) {
      this.$router.push(path);
    },
  },
};
</script>
<style>
.sidebar-icons {
  border-radius: 5px;
  padding: 2px;
  border: solid 1px #4b5563;
  width: 28px;
  text-align: center;
  height: 28px;
}

.sidebar-icons::before {
  vertical-align: sub;
}

.hidden-sidebar {
  display: none;
}

.visible-sidebar {
  display: block;
}
</style>
