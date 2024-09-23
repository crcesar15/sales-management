/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

import axios from "axios";

window.axios = axios;

window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

const token = document.head.querySelector("meta[name=\"csrf-token\"]");

if (token) {
  window.axios.defaults.headers.common["X-XSRF-TOKEN"] = token.content;
} else {
  console.error("CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token");
}

const user = document.head.querySelector("meta[name=\"user\"]");

if (user) {
  window.user = JSON.parse(user.content);
} else {
  window.user = null;
}

window.axios.defaults.baseURL = `${window.location.protocol}//${window.location.hostname}/api/`;
window.axios.defaults.withCredentials = true;
window.axios.defaults.withXSRFToken = true;
