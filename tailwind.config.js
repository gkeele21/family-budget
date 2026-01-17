import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['IBM Plex Sans', ...defaultTheme.fontFamily.sans],
                mono: ['IBM Plex Mono', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                // Budget App Color Palette
                'budget-primary': {
                    light: '#8bd93e',
                    DEFAULT: '#76cd26',
                    bg: '#edfce0',
                },
                'budget-header': '#2c2c2e',
                'budget-expense': '#c0392b',
                'budget-income': '#76cd26',
                'budget-transfer': '#888888',
                'budget-text': '#333333',
                'budget-text-secondary': '#888888',
                'budget-background': '#f5f5f5',
                'budget-card': '#ffffff',
            },
            borderRadius: {
                'card': '12px',
            },
        },
    },

    plugins: [forms],
};
