/** @type {import('tailwindcss').Config} */
export default {
  content: ['./src/**/*.{vue,js,jsx,ts,tsx}'],
  important: true,
  theme: {
    extend: {
      colors: {
        primary: {
          DEFAULT: '#008aff',
          50: '#e6f1ff',
          100: '#cce4ff',
          200: '#99cdff',
          300: '#66b7ff',
          400: '#33a0ff',
          500: '#008aff',
          600: '#007ce6',
          700: '#006cc7',
          800: '#00569e',
          900: '#003f75',
          950: '#00294d',
        },
        brand: '#141d26',
        shell: {
          50: '#f4f7fb',
          100: '#e8eef8',
          200: '#cddcf1',
          300: '#a7c2e5',
          400: '#7aa1d1',
          500: '#4a78b4',
          600: '#32558a',
          700: '#27436b',
          800: '#1f3656',
          900: '#17253d',
        },
        ink: '#102033',
        success: '#22c55e',
        danger: '#ef4444',
        warning: '#ffba08',
        info: '#0ea5e9',
        dark: '#212529',
        panel: '#ffffff',
        muted: '#6b7280',
      },
      boxShadow: {
        soft: '0 18px 50px rgba(16, 32, 51, 0.12)',
      },
      borderRadius: {
        '2xl': '1.25rem',
      },
    },
  },
  plugins: [],
};
