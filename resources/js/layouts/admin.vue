<template>
  <div>
    <div id="navbar">
      <div>
        <Sidebar v-model:visible="sidebarVisibility">
          <template #container="{ closeCallback }">
            <div class="flex flex-column h-full">
              <div class="flex align-items-center justify-content-between px-4 pt-3 flex-shrink-0">
                <span class="inline-flex align-items-center gap-2">
                  <span
                    class="font-semibold text-2xl text-primary"
                  >Laravel</span>
                </span>
                <span>
                  <p-button
                    type="button"
                    icon="fa fa-xmark"
                    rounded
                    @click="toggleSidebar"
                  />
                </span>
              </div>
              <div class="overflow-y-auto">
                <ul class="list-none p-3 m-0">
                  <Link href="/dashboard">
                    <li>
                      <a
                        v-ripple
                        class="p-3 flex align-items-center hover:surface-100 text-600 cursor-pointer p-ripple"
                      >
                        <i class="fas fa-cubes mr-2" />
                        <span class="font-medium">DASHBORAD</span>
                      </a>
                    </li>
                  </Link>
                  <li>
                    <div
                      v-ripple
                      v-styleclass="{
                        selector: '@next',
                        enterClass: 'hidden',
                        enterActiveClass: 'slidedown',
                        leaveToClass: 'hidden',
                        leaveActiveClass: 'slideup'
                      }"
                      class="p-3 flex align-items-center justify-content-between text-600 cursor-pointer p-ripple"
                    >
                      <span class="font-medium">
                        <i class="fa fa-cube pr-2" />PRODUCTS
                      </span>
                      <i class="fa fa-angle-down" />
                    </div>
                    <ul class="list-none py-0 pl-3 pr-0 m-0 overflow-hidden">
                      <Link href="/gallery">
                        <li>
                          <a
                            v-ripple
                            class="flex align-items-center cursor-pointer p-3 border-round text-700 hover:surface-100 transition-duration-150 transition-colors p-ripple"
                          >
                            <i class="fa fa-grip mr-2" />
                            <span class="font-medium">Gallery</span>
                          </a>
                        </li>
                      </Link>
                      <Link href="/products">
                        <li>
                          <a
                            v-ripple
                            class="flex align-items-center cursor-pointer p-3 border-round text-700 hover:surface-100 transition-duration-150 transition-colors p-ripple"
                          >
                            <i class="fa fa-box-open mr-2" />
                            <span class="font-medium">Inventory</span>
                          </a>
                        </li>
                      </Link>
                      <Link href="/categories">
                        <li>
                          <a
                            v-ripple
                            class="flex align-items-center cursor-pointer p-3 border-round text-700 hover:surface-100 transition-duration-150 transition-colors p-ripple"
                          >
                            <i class="fa fa-list mr-2" />
                            <span class="font-medium">Categories</span>
                          </a>
                        </li>
                      </Link>
                    </ul>
                  </li>
                  <li>
                    <a
                      v-ripple
                      class="p-3 flex align-items-center hover:surface-100 text-600 cursor-pointer p-ripple"
                    >
                      <i class="fa fa-dollar-sign mr-2" />
                      <span class="font-medium">SALES</span>
                    </a>
                  </li>
                  <li>
                    <a
                      v-ripple
                      class="p-3 flex align-items-center hover:surface-100 text-600 cursor-pointer p-ripple"
                    >
                      <i class="fas fa-wallet mr-2" />
                      <span class="font-medium">BUYS</span>
                    </a>
                  </li>
                  <li>
                    <a
                      v-ripple
                      class="p-3 flex align-items-center hover:surface-100 text-600 cursor-pointer p-ripple"
                    >
                      <i class="fa fa-table-list mr-2" />
                      <span class="font-medium">REPORTS</span>
                    </a>
                  </li>
                  <li>
                    <div
                      v-ripple
                      v-styleclass="{
                        selector: '@next',
                        enterClass: 'hidden',
                        enterActiveClass: 'slidedown',
                        leaveToClass: 'hidden',
                        leaveActiveClass: 'slideup'
                      }"
                      class="p-3 flex align-items-center justify-content-between text-600 cursor-pointer p-ripple"
                    >
                      <span class="font-medium">
                        <i class="fa fa-gear pr-2" />ADMIN
                      </span>
                      <i class="fa fa-angle-down" />
                    </div>
                    <ul class="list-none py-0 pl-3 pr-0 m-0 overflow-hidden">
                      <Link href="/users">
                        <li>
                          <a
                            v-ripple
                            class="flex align-items-center cursor-pointer p-3 border-round text-700 hover:surface-100 transition-duration-150 transition-colors p-ripple"
                          >
                            <i class="fa fa-grip mr-2" />
                            <span class="font-medium">User</span>
                          </a>
                        </li>
                      </Link>
                      <Link href="/permissions">
                        <li>
                          <a
                            v-ripple
                            class="flex align-items-center cursor-pointer p-3 border-round text-700 hover:surface-100 transition-duration-150 transition-colors p-ripple"
                          >
                            <i class="fa fa-box-open mr-2" />
                            <span class="font-medium">Permissions</span>
                          </a>
                        </li>
                      </Link>
                    </ul>
                  </li>
                </ul>
              </div>
            </div>
          </template>
        </Sidebar>
        <Menubar :model="[]">
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
    <main class="md:m-4 m-3">
      <slot />
    </main>
  </div>
</template>
<script>
import { Link } from "@inertiajs/inertia-vue3";

export default {
  components: {
    Link,
  },
  data() {
    return {
      sidebarVisibility: false,
      userActions: [
        {
          label: "Profile",
          icon: "fa fa-user",
          command: () => {
            this.$router.push("/profile");
          },
        },
        {
          label: "Logout",
          icon: "fa fa-sign-out-alt",
          command: () => {
            this.$store.dispatch("auth/logout");
          },
        },
      ],
    };
  },
  methods: {
    toggleSidebar() {
      this.sidebarVisibility = !this.sidebarVisibility;
    },
    toggleUserActions() {
      this.$refs.userActions.toggle();
    },
    redirect(path) {
      this.$router.push(path);
    },
  },
};
</script>
