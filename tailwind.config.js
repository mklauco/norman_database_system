import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
    ],

    // Safelist for display configuration classes (stored in database)
    safelist: [
        // Gray (main_record, country, data_source)
        'bg-gray-300', 'bg-gray-400', 'text-gray-900', 'text-gray-700',
        'bg-slate-100', 'bg-slate-200',
        'bg-gray-50', 'bg-gray-100',
        // Emerald (location)
        'bg-emerald-50', 'bg-emerald-100', 'text-emerald-900',
        // Teal (substance, matrix)
        'bg-teal-600', 'text-white',
        'bg-teal-50', 'bg-teal-100', 'text-teal-900',
        // Amber (analytical_method, soil)
        'bg-amber-600',
        'bg-amber-50', 'bg-amber-100', 'text-amber-900',
        // Cyan (water_quality)
        'bg-cyan-600',
        'bg-cyan-50', 'bg-cyan-100', 'text-cyan-900',
        // Rose (concentration)
        'bg-rose-600',
        'bg-rose-50', 'bg-rose-100', 'text-rose-900',
        // Violet (sampling)
        'bg-violet-600',
        'bg-violet-50', 'bg-violet-100', 'text-violet-900',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', 'ui-sans-serif', 'system-ui', 'sans-serif', '"Apple Color Emoji"', '"Segoe UI Emoji"', '"Segoe UI Symbol"', '"Noto Color Emoji"'],
            },
        },
    },

    plugins: [forms],
};
