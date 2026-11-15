/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        blue: {
          50: '#f1f5fb',
          100: '#e5edf8',
          200: '#cbd9ed',
          300: '#a6bcdd',
          400: '#7897c5',
          500: '#4f72a8',
          600: '#31588f',
          700: '#224475',
          800: '#17355f',
          900: '#102849',
          950: '#0b1c34',
        },
        payben: {
          blue: '#102f6f',
          darkBlue: '#0b2450',
          lightBlue: '#e5edf8',
          orange: '#ff6500',
          lightOrange: '#fff1e8',
          green: '#34a853',
          lightGreen: '#e6f4ea',
          yellow: '#f9ab00',
          red: '#ea4335',
          dark: '#202124',
          gray: '#5f6368',
          border: '#dadce0',
          bg: '#f6f8fc'
        }
      },
      fontFamily: {
        sans: ['Plus Jakarta Sans', 'Inter', 'system-ui', 'sans-serif'],
      }
    },
  },
  plugins: [],
}
