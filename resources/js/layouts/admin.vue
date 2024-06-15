<template>
  <div>
    <div id="navbar">
      <div>
        <Sidebar v-model:visible="sidebarVisibility">
          <template #container="{ closeCallback }">
            <div class="flex flex-column h-full">
              <div class="flex align-items-center justify-content-between px-4 pt-3 flex-shrink-0">
                <span class="inline-flex align-items-center gap-2">
                  <span class="font-semibold text-2xl text-primary">Laravel</span>
                </span>
                <span>
                  <p-button
                    type="button"
                    icon="fa fa-arrow-left"
                    @click="toggleSidebar"
                  />
                </span>
              </div>
              <div class="overflow-y-auto">
                <ul class="list-none p-3 m-0">
                  <div
                    v-for="item in menuitems"
                    :key="item.label"
                  >
                    <div v-if="item.type === 'single'">
                      <Link
                        :key="item.label"
                        :href="route('products')"
                        style="text-decoration: none"
                      >
                        <li>
                          <a
                            v-ripple
                            class="p-3 flex align-items-center hover:surface-100 text-600 cursor-pointer p-ripple"
                          >
                            <i
                              :class="item.icon"
                              class="mr-2 sidebar-icons"
                            />
                            <span class="font-medium">{{
                              item.label
                            }}</span>
                          </a>
                        </li>
                      </Link>
                    </div>
                    <div v-else-if="item.type === 'multiple'">
                      <li>
                        <div
                          v-ripple
                          v-styleclass="{
                            selector: '@next',
                            enterClass: 'hidden',
                            enterActiveClass:
                              'slidedown',
                            leaveToClass: 'hidden',
                            leaveActiveClass:
                              'slideup',
                          }"
                          class="p-3 flex align-items-center justify-content-between text-600 cursor-pointer p-ripple"
                        >
                          <span class="font-medium">
                            <i
                              :class="item.icon"
                              class="mr-2 sidebar-icons"
                            />
                            {{ item.label }}
                          </span>
                          <i class="fa fa-angle-down" />
                        </div>
                        <ul class="list-none py-0 pl-3 pr-0 m-0 overflow-hidden">
                          <Link
                            v-for="subitem in item.items"
                            :key="subitem.label"
                            :href="subitem.to"
                            style="
                                                            text-decoration: none;
                                                        "
                          >
                            <li>
                              <a
                                v-ripple
                                class="flex align-items-center cursor-pointer p-3 border-round text-700 hover:surface-100 transition-duration-150 transition-colors p-ripple"
                              >
                                <i
                                  :class="subitem.icon
                                  "
                                  class="mr-2 sidebar-icons"
                                />
                                <span class="font-medium">{{
                                  subitem.label
                                }}</span>
                              </a>
                            </li>
                          </Link>
                        </ul>
                      </li>
                    </div>
                  </div>
                </ul>
              </div>
            </div>
          </template>
        </Sidebar>
        <Menubar
          class="col-11"
          :model="[]"
          style="
                          position: fixed;
                          width: 100%;
                          z-index: 1000;
                          left: 0;
                          top: 0;
                      "
        >
          <template #start>
            <div class="flex flex-row flex-wrap">
              <div class="flex">
                <p-button
                  icon="fa fa-bars"
                  @click="toggleSidebar"
                />
              </div>
            </div>
          </template>
          <template #end>
            <div class="card flex justify-content-center">
              <p-button
                type="button"
                icon="fa fa-user"
                aria-haspopup="true"
                aria-controls="overlay_menu"
                @click="toggleUserActions"
              />
            </div>
            <p-menu
              id="overlay_menu"
              ref="userActions"
              :model="userActions"
              :popup="true"
            />
          </template>
        </Menubar>
      </div>
    </div>
    <div
      class="grid"
      style="margin-top: 50px !important"
    >
      <div class="col-2">
        <PMenu
          :model="menuitems"
        >
          <template #item="{item, props}">
            <Link
              :key="item.label"
              :href="route('products')"
              style="text-decoration: none"
            >
              <li>
                <a
                  v-ripple
                  class="p-3 flex align-items-center hover:surface-100 text-600 cursor-pointer p-ripple"
                >
                  <i
                    :class="item.icon"
                    class="mr-2 sidebar-icons"
                  />
                  <span class="font-medium">{{
                    item.label
                  }}</span>
                </a>
              </li>
            </Link>
          </template>
        </PMenu>
      </div>
      <div class="col-10">
        <main
          class="md:m-3 m-0 layout-wrapper layout-news-active p-ripple-disabled layout-light"
        >
          <slot />
        </main>
      </div>
    </div>
  </div>
</template>
<script>
import { Link } from "@inertiajs/inertia-vue3";
import Sidebar from "primevue/sidebar";
import Menubar from "primevue/menubar";
import PMenu from "primevue/menu";
import axios from "axios";

export default {
  components: {
    Link,
    PMenu,
    Sidebar,
    Menubar,
  },
  data() {
    return {
      menuitems: [
        {
          label: "Dashboard",
          icon: "fa fa-gauge",
          type: "single",
          to: "/home",
        },
        {
          label: "Products",
          icon: "fa fa-cubes",
          type: "multiple",
          items: [
            {
              label: "Products",
              icon: "fa fa-list",
              to: "/products",
            },
            {
              label: "Categories",
              icon: "fa fa-layer-group",
              to: "/categories",
            },
            {
              label: "Brands",
              icon: "fa fa-copyright",
              to: "/brands",
            },
            {
              label: "Units",
              icon: "fa fa-weight-hanging",
              to: "/units",
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
              to: "/sales/pos",
            },
            {
              label: "Orders",
              icon: "fa fa-file-lines",
              to: "/sales/orders",
            },
            {
              label: "Customers",
              icon: "fa fa-users",
              to: "/customers",
            },
          ],
        },
        {
          label: "Purchases",
          icon: "fas fa-coins",
          type: "multiple",
          items: [
            {
              label: "Catalog",
              icon: "fa fa-tags",
              to: "/catalog",
            },
            {
              label: "Orders",
              icon: "fa fa-file-lines",
              to: "/purchases",
            },
            {
              label: "Suppliers",
              icon: "fa fa-truck-field",
              to: "/suppliers",
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
              to: "/inventory",
            },
            {
              label: "Stores",
              icon: "fa fa-warehouse",
              to: "/stocks",
            },
            {
              label: "Stocks",
              icon: "fa fa-cubes-stacked",
              to: "/stocks",
            },
          ],
        },
        {
          label: "Reports",
          icon: "fa fa-chart-line",
          type: "single",
          to: "/reports",
        },
        {
          label: "Settings",
          icon: "fa fa-cogs",
          type: "multiple",
          items: [
            {
              label: "Users",
              icon: "fa fa-user",
              to: "/users",
            },
            {
              label: "Roles",
              icon: "fa fa-user-tag",
              to: "/roles",
            },
            {
              label: "Permissions",
              icon: "fa fa-user-lock",
              to: "/permissions",
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
</style>
