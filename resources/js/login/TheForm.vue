<template>
  <b-alert show>Default Alert</b-alert>
</template>

<script>
export default {
  name: "TheForm",
  data() {
    return {
      email: "",
      password: "",
      errors: []
    };
  },
  methods: {
    onSubmit() {
      axios
        .post("/login", {
          email: this.email,
          password: this.password
        })
        .then(response => {
          console.log(response);
        })
        .catch(error => {
          this.errors = error.response.data.errors;
        });
    }
  }
};
</script>