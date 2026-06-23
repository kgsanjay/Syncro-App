/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./core/Views/**/*.php"
  ],
  theme: {
    extend: {
      colors: {
        theme: 'var(--theme)',
        theme2: 'var(--theme2)',
        header: 'var(--header)',
        text: 'var(--text)',
        light: 'var(--light)',
        border: 'var(--border)',
        white: 'var(--white)',
      }
    },
  },
  plugins: [],
}
