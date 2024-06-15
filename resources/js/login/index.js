import "../bootstrap";

import { createApp } from "vue";
import { createVuetify } from "vuetify";
import {
  VBtn, VContainer, VCol, VRow, VCard, VTextField,
} from "vuetify/components";
import { aliases, fa } from "vuetify/iconsets/fa";

const vuetify = createVuetify(
  {
    components: {
      VBtn,
      VContainer,
      VCol,
      VRow,
      VCard,
      VTextField,
    },
    icons: {
      defaultSet: "fa",
      aliases,
      sets: {
        fa,
      },
    },
  },
);

const app = createApp({
  data() {
    return {
      name: "",
      email: "",
      token: "",
      password: "",
      password_confirmation: "",
      remember: false,
      btnLoading: false,
      password_visible: false,
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

app.use(vuetify);

app.mount("#app");
