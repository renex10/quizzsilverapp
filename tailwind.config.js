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
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                admin: {
                    primary: '#89216B',
                    secondary: '#DA4453',
                    dark: '#6B1A56',
                    light: '#E85A6A',
                },
            },
            backgroundImage: {
                'admin-gradient': 'linear-gradient(to bottom, #89216B, #DA4453)',
                'admin-gradient-hover': 'linear-gradient(to bottom, #6B1A56, #C53A48)',
            },
            boxShadow: {
                'sidebar': '4px 0 15px -3px rgba(137, 33, 107, 0.2), 2px 0 6px -2px rgba(218, 68, 83, 0.1)',
                'sidebar-item': '0 2px 4px rgba(0, 0, 0, 0.15)',
            },
            transitionProperty: {
                'width': 'width',
                'spacing': 'margin, padding',
            },
            spacing: {
                'sidebar': '16rem',
                'sidebar-collapsed': '4.5rem',
            },
            zIndex: {
                '60': '60',
                '70': '70',
            },
        },
    },

    plugins: [forms],
};