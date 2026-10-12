import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            // "blue" es el color de la clínica (Configuración > Clínica): los tonos llegan como variables CSS
            colors: {
                blue: Object.fromEntries([50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950]
                    .map((t) => [t, `rgb(var(--marca-${t}) / <alpha-value>)`])),
            },
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
