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
            colors: {
                brand: {
                    primary: '#5E6AD2', // Linear Blue
                    primarySoft: '#F5F6FF',
                    secondary: '#F59E0B',
                    success: '#4BCE97',
                    warning: '#F2994A',
                    danger: '#F87171',
                    neutral: '#F7F8F9', // Linear-like background
                    surface: '#FFFFFF',
                    border: '#E2E4E9',
                    text: '#171717',
                    muted: '#6E7178',
                    placeholder: '#A1A3A9',
                    sidebar: '#F7F8F9',
                    sidebarSoft: '#EEF0F2',
                },
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            boxShadow: {
                card: '0 1px 2px rgba(0,0,0,0.04), 0 0 0 1px rgba(0,0,0,0.02)',
                shell: '0 8px 16px -4px rgba(0,0,0,0.04), 0 4px 8px -4px rgba(0,0,0,0.02)',
                dropdown: '0 10px 15px -3px rgba(0,0,0,0.05), 0 4px 6px -2px rgba(0,0,0,0.02)',
                modal: '0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04)',
                focus: '0 0 0 3px rgba(94,106,210,0.15)',
            },
            borderRadius: {
                sm: '4px',
                md: '6px',
                lg: '8px',
                xl: '10px',
                '2xl': '14px',
            },
        },
    },

    plugins: [forms],
};
