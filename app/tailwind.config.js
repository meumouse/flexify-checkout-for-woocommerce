/** @type {import('tailwindcss').Config} */
export default {
  content: ['./src/**/*.{vue,js}'],
  important: true,
  theme: {
    extend: {
      colors: {
        primary: {
          DEFAULT: '#141d26',
          50: '#f3f6f9',
          100: '#e4eaf0',
          200: '#c4d1dd',
          300: '#9badc1',
          400: '#6b84a0',
          500: '#4a6585',
          600: '#395071',
          700: '#2f415c',
          800: '#2a394e',
          900: '#1f2a3a',
          950: '#141d26',
        },
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
