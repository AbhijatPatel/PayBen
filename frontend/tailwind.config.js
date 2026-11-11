/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        payben: {
          blue: '#1a73e8',
          darkBlue: '#1557b0',
          lightBlue: '#e8f0fe',
          green: '#34a853',
          lightGreen: '#e6f4ea',
          yellow: '#f9ab00',
          red: '#ea4335',
          dark: '#202124',
          gray: '#5f6368',
          border: '#dadce0',
          bg: '#f8fafd'
        }
      },
      fontFamily: {
        sans: ['Plus Jakarta Sans', 'Inter', 'system-ui', 'sans-serif'],
      }
    },
  },
  plugins: [],
}
