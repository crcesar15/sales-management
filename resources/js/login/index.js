import "../bootstrap";

import { createApp } from "vue";
import { definePreset } from "@primeuix/themes";
import PrimeVue from "primevue/config";
import InputText from "primevue/inputtext";
import PPassword from "primevue/password";
import Checkbox from "primevue/checkbox";
import PButton from "primevue/button";
import ToastService from "primevue/toastservice";
import Toast from "primevue/toast";
import Aura from "@primeuix/themes/aura";

const app = createApp({
  components: {
    InputText,
    PPassword,
    Checkbox,
    PButton,
    Toast,
  },
  data() {
    return {
      name: "",
      email: "",
      token: "",
      password: "",
      password_confirmation: "",
      remember: false,
      btnLoading: false,
    };
  },
  mounted() {
    this.token = document.getElementById("token")?.value ?? "";
  },
  methods: {
    login() {
      axios
        .post(`${window.location.origin}/login`, {
          username: this.username,
          password: this.password,
          remember: this.remember,
        })
        .then(() => {
          this.redirect("/");
        })
        .catch((error) => {
          this.$toast.add({
            severity: "error",
            summary: "Error",
            detail: error.response.data.message,
          });
        });
    },
    sentResetLink() {
      this.btnLoading = true;
      axios
        .post(`${window.location.origin}/password/email`, {
          email: this.email,
        })
        .then(() => {
          this.btnLoading = false;
          this.$toast.add({
            severity: "success",
            summary: "Success",
            detail: "Reset link sent to your email.",
            life: 3000,
          });
        })
        .catch((error) => {
          this.btnLoading = false;
          this.$toast.add({
            severity: "error",
            summary: "Error",
            detail: error.response.data.message,
            life: 3000,
          });
        });
    },
    register() {
      axios.post(`${window.location.origin}/register`, {
        name: this.name,
        email: this.email,
        password: this.password,
        password_confirmation: this.password_confirmation,
      })
        .then(() => {
          this.redirect("/");
        })
        .catch((error) => {
          this.$toast.add({
            severity: "error",
            summary: "Error",
            detail: error.response.data.message,
          });
        });
    },
    resetPassword() {
      this.btnLoading = true;
      axios
        .post(`${window.location.origin}/password/reset`, {
          token: this.token,
          email: this.email,
          password: this.password,
          password_confirmation: this.password_confirmation,
        })
        .then(() => {
          this.btnLoading = false;
          this.redirect("/");
        })
        .catch((error) => {
          this.btnLoading = false;
          this.$toast.add({
            severity: "error",
            summary: "Error",
            detail: error.response.data.message,
          });
        });
    },
    redirect(url) {
      window.location.href = url;
    },
  },
});

const Noir = definePreset(Aura, {
  semantic: {
    primary: {
      50: "{zinc.50}",
      100: "{zinc.100}",
      200: "{zinc.200}",
      300: "{zinc.300}",
      400: "{zinc.400}",
      500: "{zinc.500}",
      600: "{zinc.600}",
      700: "{zinc.700}",
      800: "{zinc.800}",
      900: "{zinc.900}",
      950: "{zinc.950}",
    },
    colorScheme: {
      light: {
        primary: {
          color: "{zinc.950}",
          inverseColor: "#ffffff",
          hoverColor: "{zinc.900}",
          activeColor: "{zinc.800}",
        },
        highlight: {
          background: "{zinc.950}",
          focusBackground: "{zinc.700}",
          color: "#ffffff",
          focusColor: "#ffffff",
        },
      },
      dark: {
        primary: {
          color: "{zinc.50}",
          inverseColor: "{zinc.950}",
          hoverColor: "{zinc.100}",
          activeColor: "{zinc.200}",
        },
        highlight: {
          background: "rgba(250, 250, 250, .16)",
          focusBackground: "rgba(250, 250, 250, .24)",
          color: "rgba(255,255,255,.87)",
          focusColor: "rgba(255,255,255,.87)",
        },
      },
    },
  },
});

app.use(PrimeVue, {
  ripple: true,
  theme: {
    preset: Noir,
    options: {
      darkModeSelector: ".app-dark",
    },
  },
}).use(ToastService);

app.mount("#app");
