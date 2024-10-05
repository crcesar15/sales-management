/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.vue",
    "./resources/**/*.js",
    "node_modules/tailwind-primeui/**/*.js",
  ],
  theme: {
    extend: {},
  },
  darkMode: ["class", ".app-dark"],
  plugins: [
    require("tailwindcss-primeui"),
  ],
};
