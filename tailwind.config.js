/** @type {import('tailwindcss').Config} */
module.exports = {
    // Every file Tailwind should scan for class names.
    content: ['./app/**/*.{php,html,js}'],
    darkMode: 'class',
    theme: {
        extend: {
            // Colors point at CSS variables defined in app/assets/css/theme.css,
            // so one class (bg-surface, text-on-surface...) works in light and dark.
            colors: {
                "primary": "rgb(var(--c-primary) / <alpha-value>)",
                "on-surface-variant": "rgb(var(--c-on-surface-variant) / <alpha-value>)",
                "on-secondary-fixed-variant": "rgb(var(--c-on-secondary-fixed-variant) / <alpha-value>)",
                "primary-fixed": "rgb(var(--c-primary-fixed) / <alpha-value>)",
                "on-tertiary-fixed": "rgb(var(--c-on-tertiary-fixed) / <alpha-value>)",
                "background": "rgb(var(--c-background) / <alpha-value>)",
                "on-surface": "rgb(var(--c-on-surface) / <alpha-value>)",
                "secondary-container": "rgb(var(--c-secondary-container) / <alpha-value>)",
                "secondary-fixed": "rgb(var(--c-secondary-fixed) / <alpha-value>)",
                "primary-container": "rgb(var(--c-primary-container) / <alpha-value>)",
                "tertiary-fixed": "rgb(var(--c-tertiary-fixed) / <alpha-value>)",
                "surface-container-low": "rgb(var(--c-surface-container-low) / <alpha-value>)",
                "on-primary-container": "rgb(var(--c-on-primary-container) / <alpha-value>)",
                "inverse-surface": "rgb(var(--c-inverse-surface) / <alpha-value>)",
                "surface-container-lowest": "rgb(var(--c-surface-container-lowest) / <alpha-value>)",
                "on-primary": "rgb(var(--c-on-primary) / <alpha-value>)",
                "error": "rgb(var(--c-error) / <alpha-value>)",
                "on-tertiary-fixed-variant": "rgb(var(--c-on-tertiary-fixed-variant) / <alpha-value>)",
                "on-tertiary-container": "rgb(var(--c-on-tertiary-container) / <alpha-value>)",
                "surface-container-highest": "rgb(var(--c-surface-container-highest) / <alpha-value>)",
                "on-tertiary": "rgb(var(--c-on-tertiary) / <alpha-value>)",
                "primary-fixed-dim": "rgb(var(--c-primary-fixed-dim) / <alpha-value>)",
                "surface-container-high": "rgb(var(--c-surface-container-high) / <alpha-value>)",
                "on-secondary": "rgb(var(--c-on-secondary) / <alpha-value>)",
                "surface-dim": "rgb(var(--c-surface-dim) / <alpha-value>)",
                "surface": "rgb(var(--c-surface) / <alpha-value>)",
                "tertiary-fixed-dim": "rgb(var(--c-tertiary-fixed-dim) / <alpha-value>)",
                "outline-variant": "rgb(var(--c-outline-variant) / <alpha-value>)",
                "on-secondary-container": "rgb(var(--c-on-secondary-container) / <alpha-value>)",
                "on-background": "rgb(var(--c-on-background) / <alpha-value>)",
                "surface-bright": "rgb(var(--c-surface-bright) / <alpha-value>)",
                "secondary-fixed-dim": "rgb(var(--c-secondary-fixed-dim) / <alpha-value>)",
                "secondary": "rgb(var(--c-secondary) / <alpha-value>)",
                "on-error-container": "rgb(var(--c-on-error-container) / <alpha-value>)",
                "inverse-primary": "rgb(var(--c-inverse-primary) / <alpha-value>)",
                "surface-tint": "rgb(var(--c-surface-tint) / <alpha-value>)",
                "surface-container": "rgb(var(--c-surface-container) / <alpha-value>)",
                "on-primary-fixed": "rgb(var(--c-on-primary-fixed) / <alpha-value>)",
                "outline": "rgb(var(--c-outline) / <alpha-value>)",
                "surface-variant": "rgb(var(--c-surface-variant) / <alpha-value>)",
                "inverse-on-surface": "rgb(var(--c-inverse-on-surface) / <alpha-value>)",
                "on-secondary-fixed": "rgb(var(--c-on-secondary-fixed) / <alpha-value>)",
                "on-error": "rgb(var(--c-on-error) / <alpha-value>)",
                "on-primary-fixed-variant": "rgb(var(--c-on-primary-fixed-variant) / <alpha-value>)",
                "tertiary": "rgb(var(--c-tertiary) / <alpha-value>)",
                "tertiary-container": "rgb(var(--c-tertiary-container) / <alpha-value>)",
                "error-container": "rgb(var(--c-error-container) / <alpha-value>)"
            },
            "borderRadius": { "DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px" },
            "spacing": { "margin": "2rem", "space-sm": "0.5rem", "space-lg": "1.5rem", "space-xl": "2.5rem", "gutter": "1.5rem", "space-md": "1rem", "space-xs": "0.25rem" },
            "fontFamily": { "body-md": ["Inter"], "label-sm": ["Inter"], "headline-sm": ["Plus Jakarta Sans"], "headline-md": ["Plus Jakarta Sans"], "label-md": ["Inter"], "display": ["Plus Jakarta Sans"], "headline-lg-mobile": ["Plus Jakarta Sans"], "display-mobile": ["Plus Jakarta Sans"], "headline-lg": ["Plus Jakarta Sans"], "body-lg": ["Inter"], "label-lg": ["Inter"], "body-sm": ["Inter"] },
            "fontSize": { "body-md": ["14px", { "lineHeight": "22px", "fontWeight": "400" }], "label-sm": ["11px", { "lineHeight": "14px", "fontWeight": "600" }], "headline-sm": ["18px", { "lineHeight": "26px", "fontWeight": "600" }], "headline-md": ["24px", { "lineHeight": "32px", "fontWeight": "600" }], "label-md": ["12px", { "lineHeight": "16px", "fontWeight": "500" }], "display": ["48px", { "lineHeight": "56px", "fontWeight": "700" }], "headline-lg-mobile": ["26px", { "lineHeight": "34px", "fontWeight": "600" }], "display-mobile": ["36px", { "lineHeight": "44px", "fontWeight": "700" }], "headline-lg": ["32px", { "lineHeight": "40px", "fontWeight": "600" }], "body-lg": ["16px", { "lineHeight": "26px", "fontWeight": "400" }], "label-lg": ["14px", { "lineHeight": "20px", "fontWeight": "500" }], "body-sm": ["12px", { "lineHeight": "18px", "fontWeight": "400" }] }
        }
    },
    plugins: [
        require('@tailwindcss/forms'),
        require('@tailwindcss/container-queries')
    ]
};