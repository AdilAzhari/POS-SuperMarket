import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.ts',
    ],
    
    darkMode: 'class', // Enable class-based dark mode

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Custom color variables for themes
                primary: 'var(--color-primary)',
                secondary: 'var(--color-secondary)', 
            },
            animation: {
                'fade-in': 'fadeIn var(--animation-duration, 200ms) ease-in-out',
                'slide-up': 'slideUp var(--animation-duration, 200ms) ease-out',
                'scale-in': 'scaleIn var(--animation-duration, 200ms) ease-out',
            },
            keyframes: {
                fadeIn: {
                  '0%': { opacity: '0' },
                  '100%': { opacity: '1' },
                },
                slideUp: {
                  '0%': { transform: 'translateY(10px)', opacity: '0' },
                  '100%': { transform: 'translateY(0)', opacity: '1' },
                },
                scaleIn: {
                  '0%': { transform: 'scale(0.95)', opacity: '0' },
                  '100%': { transform: 'scale(1)', opacity: '1' },
                },
            },
        },
    },

    plugins: [forms],
};
