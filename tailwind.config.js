import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        // Le moteur de match construit une partie de son HTML en JS (ui.js, dice.js…) :
        // sans ce chemin, les classes Tailwind écrites là ne seraient pas générées.
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            // Apparition « rebond » (annonce d'un pick au draft).
            keyframes: {
                'bounce-in': {
                    '0%':   { transform: 'scale(0.5)', opacity: '0' },
                    '60%':  { transform: 'scale(1.05)' },
                    '100%': { transform: 'scale(1)', opacity: '1' },
                },
            },
            animation: {
                'bounce-in': 'bounce-in 0.5s ease-out',
            },
        },
    },

    plugins: [forms],
};
