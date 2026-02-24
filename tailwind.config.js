import rtl from "tailwindcss-rtl";

/** @type {import('tailwindcss').Config} */
export default {
  darkMode: "class",
  content: ["./index.html", "./src/**/*.{vue,js,ts,jsx,tsx}"],
  theme: {
    extend: {
      colors: {
        ink: "#0f172a",
        sky: "#0ea5e9",
        mist: "#f1f5f9",
        leaf: "#16a34a"
      }
    }
  },
  plugins: [rtl]
};
