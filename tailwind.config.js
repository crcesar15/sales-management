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
  plugins: [
    require("tailwindcss-primeui"),
  ],
};
