/** @type {import('tailwindcss').Config} */
export default {
  content: ['./src/**/*.{vue,js}'],
  important: true,
  theme: {
    extend: {
      colors: {
        primary: {
          DEFAULT: '#0d6efd',
          50: '#eff6ff',
          100: '#dbeafe',
          200: '#bfdbfe',
          300: '#93c5fd',
          400: '#60a5fa',
          500: '#3b82f6',
          600: '#0d6efd',
          700: '#0a58ca',
          800: '#084298',
          900: '#052c65',
          950: '#031633',
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
        panel: '#ffffff',
        muted: '#6b7280',
      },
      boxShadow: {
        soft: '0 18px 50px rgba(16, 32, 51, 0.12)',
      },
    },
  },
  plugins: [],
};
