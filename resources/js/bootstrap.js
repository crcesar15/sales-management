/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

import axios from "axios";
import moment from "moment-timezone";

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

const currencySymbol = document.head.querySelector("meta[name=\"currency-symbol\"]");

if (currencySymbol) {
  window.currencySymbol = currencySymbol.content;
} else {
  window.currencySymbol = "$";
}

const timezone = document.head.querySelector("meta[name=\"timezone\"]");

if (timezone) {
  window.timezone = timezone.content;
} else {
  window.timezone = "UTC";
}
const datetimeFormat = document.head.querySelector("meta[name=\"datetime-format\"]");

if (datetimeFormat) {
  window.datetimeFormat = datetimeFormat.content;
} else {
  window.datetimeFormat = "YYYY-MM-DD HH:mm";
}

window.moment = moment;

window.axios.defaults.baseURL = `${window.location.protocol}//${window.location.hostname}/api/`;
window.axios.defaults.withCredentials = true;
window.axios.defaults.withXSRFToken = true;
