/** @type {import('tailwindcss').Config} */
export default {
  darkMode: 'class',
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  theme: {
    extend: {
      colors: {
        brand: {
          emerald: '#059669',
          dark: '#0F172A',
          card: '#1E293B',
          shopee: '#EE4D2D',
        }
      }
    },
  },
  plugins: [],
}
