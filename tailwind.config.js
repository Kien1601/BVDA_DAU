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
                // màu nhấn, cố định ở cả hai chế độ
                vermilion: '#e0231c',
                ember: '#ff5a3c',

                // màu theo vai trò, giá trị đổi theo data-theme (resources/css/app.css)
                page: 'rgb(var(--t-page) / <alpha-value>)',
                card: {
                    DEFAULT: 'rgb(var(--t-card) / <alpha-value>)',
                    2: 'rgb(var(--t-card-2) / <alpha-value>)',
                },
                fg: {
                    DEFAULT: 'rgb(var(--t-fg) / <alpha-value>)',
                    soft: 'rgb(var(--t-fg-soft) / <alpha-value>)',
                    muted: 'rgb(var(--t-fg-muted) / <alpha-value>)',
                },
                edge: {
                    DEFAULT: 'rgb(var(--t-edge) / var(--t-edge-a))',
                    soft: 'rgb(var(--t-edge) / var(--t-edge-soft-a))',
                    strong: 'rgb(var(--t-edge) / var(--t-edge-strong-a))',
                },
                solid: 'rgb(var(--t-solid) / <alpha-value>)',
                'on-solid': 'rgb(var(--t-on-solid) / <alpha-value>)',
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