/** @type {import('tailwindcss').Config} */
module.exports = {
    // Scan all JSX/JS source files for class usage
    content: ['./src/**/*.{js,jsx}'],

    // Scope all utilities under #wpaia-chat-root to avoid leaking into wp-admin
    important: '#wpaia-chat-root',

    corePlugins: {
        // CRITICAL: disables Tailwind's base reset so it doesn't clobber wp-admin styles
        preflight: false,
    },

    theme: {
        extend: {
            colors: {
                'wpaia-primary':        '#8B5CF6',
                'wpaia-primary-hover':  '#7C3AED',
                'wpaia-primary-light':  '#EDE9FE',
                'wpaia-bg':             '#F4F4F5',
                'wpaia-panel':          '#FFFFFF',
                'wpaia-border':         '#E4E4E7',
                'wpaia-text':           '#18181B',
                'wpaia-muted':          '#71717A',
                'wpaia-hint':           '#A1A1AA',
                'wpaia-tool-bg':        '#F4F4F5',
                'wpaia-error-bg':       '#FEF2F2',
                'wpaia-error-border':   '#FCA5A5',
                'wpaia-error-text':     '#DC2626',
                'wpaia-warning-bg':     '#FFFBEB',
                'wpaia-warning-border': '#FCD34D',
                'wpaia-warning-text':   '#D97706',
                'wpaia-success-bg':     '#F0FDF4',
                'wpaia-success-border': '#86EFAC',
                'wpaia-success-text':   '#16A34A',
            },
            borderRadius: {
                'wpaia-panel':  '24px',
                'wpaia-card':   '12px',
                'wpaia-bubble': '18px',
            },
            fontFamily: {
                sans: ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
                heading: ['"Plus Jakarta Sans"', 'Inter', 'system-ui', 'sans-serif'],
                mono: ['Fira Code', 'monospace'],
            },
        },
    },

    plugins: [],
};
