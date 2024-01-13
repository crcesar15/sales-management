import "../bootstrap";

import { createApp } from "vue";
import PrimeVue from "primevue/config";
import InputText from "primevue/inputtext";
import PPassword from "primevue/password";
import Checkbox from "primevue/checkbox";
import PButton from "primevue/button";

const app = createApp({
  components: {
    InputText,
    PPassword,
    Checkbox,
    PButton,
  },
  data() {
    return {
      email: "",
      password: "",
      remember: false,
      pizza: "",
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
          console.log(error);
        });
    },
    redirect(url) {
      window.location.href = url;
    },
  },
});

app.use(PrimeVue);

app.mount("#app");
