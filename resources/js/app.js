/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

import "./bootstrap";

import { createApp } from "vue";
import Ripple from "primevue/ripple";
import StyleClass from "primevue/styleclass";
import PrimeVue from "primevue/config";
import Menubar from "primevue/menubar";
import PMenu from "primevue/menu";
import PButton from "primevue/button";
import Sidebar from "primevue/sidebar";

const app = createApp({
  components: {
    Menubar,
    PMenu,
    PButton,
    Sidebar,
  },
  data() {
    return {
      userActions: [
        {
          label: "Logout",
          icon: "fa fa-fw fa-sign-out",
          command: () => {
            this.logout();
          },
        },
      ],
      sidebarVisibility: false,
    };
  },
  mounted() {
    this.$primevue.config.ripple = true;
  },
  methods: {
    toggleUserActions(event) {
      this.$refs.userActions.toggle(event);
    },
    toggleSidebar() {
      this.sidebarVisibility = !this.sidebarVisibility;
    },
    redirect(url) {
      window.location.href = url;
    },
    logout() {
      axios.post(`${window.location.origin}/logout`).then(() => {
        window.location.href = "/";
      });
    },
  },
});

app.use(PrimeVue, { ripple: true });
app.directive("ripple", Ripple);
app.directive("styleclass", StyleClass);

app.mount("#navbar");
