import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.tsx',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    50:  '#e6f5fb',
                    100: '#cceaf7',
                    200: '#99d5ef',
                    300: '#66c0e7',
                    400: '#33abdf',
                    500: '#0095c7',
                    600: '#0077a0',
                    700: '#005979',
                    800: '#003c52',
                    900: '#001e2b',
                },
            },
        },
    },

    plugins: [forms],
};
