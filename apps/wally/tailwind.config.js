/** @type {import('tailwindcss').Config} */
module.exports = {
    // Scan all JSX/JS source files for class usage
    content: ['./src/**/*.{js,jsx}'],

    // Scope all utilities under #wally-chat-root to avoid leaking into wp-admin
    important: '#wally-chat-root',

    corePlugins: {
        // CRITICAL: disables Tailwind's base reset so it doesn't clobber wp-admin styles
        preflight: false,
    },

    theme: {
        extend: {
            colors: {
                'wally-primary':        '#8B5CF6',
                'wally-primary-hover':  '#7C3AED',
                'wally-primary-light':  '#EDE9FE',
                'wally-bg':             '#F4F4F5',
                'wally-panel':          '#FFFFFF',
                'wally-border':         '#E4E4E7',
                'wally-text':           '#18181B',
                'wally-muted':          '#71717A',
                'wally-hint':           '#A1A1AA',
                'wally-tool-bg':        '#F4F4F5',
                'wally-error-bg':       '#FEF2F2',
                'wally-error-border':   '#FCA5A5',
                'wally-error-text':     '#DC2626',
                'wally-warning-bg':     '#FFFBEB',
                'wally-warning-border': '#FCD34D',
                'wally-warning-text':   '#D97706',
                'wally-success-bg':     '#F0FDF4',
                'wally-success-border': '#86EFAC',
                'wally-success-text':   '#16A34A',
            },
            borderRadius: {
                'wally-panel':  '24px',
                'wally-card':   '12px',
                'wally-bubble': '18px',
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
