import "../bootstrap";

import { createApp } from "vue";
import PrimeVue from "primevue/config";
import InputText from "primevue/inputtext";
import PPassword from "primevue/password";
import Checkbox from "primevue/checkbox";
import PButton from "primevue/button";
import ToastService from "primevue/toastservice";
import Toast from "primevue/toast";

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
      password: "",
      password_confirmation: "",
      remember: false,
      btnLoading: false,
    };
  },
  methods: {
    login() {
      axios
        .post(`${window.location.origin}/login`, {
          email: this.email,
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
    redirect(url) {
      window.location.href = url;
    },
  },
});

app.use(PrimeVue).use(ToastService);

app.mount("#app");
