import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                tinto: {
                    50: '#fdf2f4',
                    100: '#fbe5e9',
                    200: '#f7cfd6',
                    300: '#efa9b6',
                    400: '#e4768b',
                    500: '#d44a66',
                    600: '#b72b49',
                    700: '#8b1c1c',
                    800: '#78182a',
                    900: '#5a1420',
                    950: '#380710',
                },
                oro: {
                    50: '#fbf9f1',
                    100: '#f6f0dd',
                    200: '#eddcb7',
                    300: '#e1c38a',
                    400: '#d4a856',
                    500: '#c5a059',
                    600: '#ad8342',
                    700: '#8b6434',
                    800: '#72512f',
                    900: '#5f442b',
                    950: '#362415',
                },
                nayarit: {
                    50: '#f0fdf4',
                    100: '#dcfce7',
                    200: '#bbf7d0',
                    300: '#86efac',
                    400: '#4ade80',
                    500: '#22c55e',
                    600: '#16a34a',
                    700: '#15803d',
                    800: '#166534',
                    900: '#14532d',
                    950: '#052e16',
                },
            },
            boxShadow: {
                'tinto-sm': '0 4px 15px -3px rgba(120, 24, 42, 0.15)',
                'tinto': '0 10px 25px -5px rgba(120, 24, 42, 0.25)',
                'tinto-lg': '0 20px 35px -5px rgba(120, 24, 42, 0.35)',
                'oro-sm': '0 4px 15px -3px rgba(197, 160, 89, 0.2)',
                'oro': '0 10px 25px -5px rgba(197, 160, 89, 0.3)',
            }
        },
    },

    plugins: [forms],
};
