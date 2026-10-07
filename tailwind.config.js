import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** Màu và chữ theo docs/design/huong-dan-giao-dien.md */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/views/**/*.js',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Onest', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                ink: { DEFAULT: '#05070a', 2: '#0a0e12' },
                bone: { DEFAULT: '#dfe7e0', dim: '#aab4ad' },
                muted: '#78837c',
                paper: '#f4f6f3',
                vermilion: '#e0231c',
                ember: '#ff5a3c',
                line: 'rgba(223,231,224,.13)',
                'line-soft': 'rgba(223,231,224,.07)',
            },
            letterSpacing: {
                label: '.2em',
            },
            transitionTimingFunction: {
                soft: 'cubic-bezier(.16,1,.3,1)',
            },
        },
    },

    plugins: [forms],
};