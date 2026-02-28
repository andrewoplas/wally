/** @type {import('tailwindcss').Config} */
module.exports = {
  darkMode: ['class'],
  content: [
    './{src,pages,components,app}/**/*.{ts,tsx,js,jsx,html}',
    '!./{src,pages,components,app}/**/*.{stories,spec}.{ts,tsx,js,jsx,html}',
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          DEFAULT: 'hsl(var(--primary))',
          foreground: 'hsl(var(--primary-foreground))',
          50: 'hsl(var(--primary-50))',
          100: 'hsl(var(--primary-100))',
          200: 'hsl(var(--primary-200))',
          300: 'hsl(var(--primary-300))',
          400: 'hsl(var(--primary-400))',
          500: 'hsl(var(--primary-500))',
          600: 'hsl(var(--primary-600))',
          700: 'hsl(var(--primary-700))',
          800: 'hsl(var(--primary-800))',
        },
        secondary: {
          DEFAULT: 'hsl(var(--secondary))',
          foreground: 'hsl(var(--secondary-foreground))',
        },
        background: 'hsl(var(--background))',
        foreground: 'hsl(var(--foreground))',
        border: 'hsl(var(--border))',
        input: 'hsl(var(--input))',
        ring: 'hsl(var(--ring))',
        muted: {
          DEFAULT: 'hsl(var(--muted))',
          foreground: 'hsl(var(--muted-foreground))',
        },
        accent: {
          DEFAULT: 'hsl(var(--accent))',
          foreground: 'hsl(var(--accent-foreground))',
        },
        card: {
          DEFAULT: 'hsl(var(--card))',
          foreground: 'hsl(var(--card-foreground))',
        },
        popover: {
          DEFAULT: 'hsl(var(--popover))',
          foreground: 'hsl(var(--popover-foreground))',
        },
        disabled: {
          DEFAULT: 'hsl(var(--disabled))',
          foreground: 'hsl(var(--disabled-foreground))',
        },
        surface: {
          divider: 'hsl(var(--surface-divider))',
          subtle: 'hsl(var(--surface-subtle))',
        },
        success: {
          DEFAULT: 'hsl(var(--color-success))',
          foreground: 'hsl(var(--color-success-foreground))',
          subtle: 'hsl(var(--color-success-subtle))',
          border: 'hsl(var(--color-success-border))',
          indicator: 'hsl(var(--color-success-indicator))',
          text: 'hsl(var(--color-success-text))',
        },
        warning: {
          DEFAULT: 'hsl(var(--color-warning))',
          foreground: 'hsl(var(--color-warning-foreground))',
          subtle: 'hsl(var(--color-warning-subtle))',
          indicator: 'hsl(var(--color-warning-indicator))',
          text: 'hsl(var(--color-warning-text))',
        },
        error: {
          DEFAULT: 'hsl(var(--color-error))',
          foreground: 'hsl(var(--color-error-foreground))',
        },
        info: {
          DEFAULT: 'hsl(var(--color-info))',
          foreground: 'hsl(var(--color-info-foreground))',
        },
        destructive: {
          DEFAULT: 'hsl(var(--destructive))',
          foreground: 'hsl(var(--destructive-foreground))',
          text: 'hsl(var(--color-destructive-text))',
        },
        sidebar: {
          DEFAULT: 'hsl(var(--sidebar))',
          foreground: 'hsl(var(--sidebar-foreground))',
          primary: {
            DEFAULT: 'hsl(var(--sidebar-primary))',
            foreground: 'hsl(var(--sidebar-primary-foreground))',
          },
          accent: {
            DEFAULT: 'hsl(var(--sidebar-accent))',
            foreground: 'hsl(var(--sidebar-accent-foreground))',
          },
          border: 'hsl(var(--sidebar-border))',
          ring: 'hsl(var(--sidebar-ring))',
        },
        tile: 'hsl(var(--tile))',
        lp: {
          'body-dark': 'hsl(var(--lp-body-dark))',
          'hero-dark': 'hsl(var(--lp-hero-dark))',
          'hero-muted': 'hsl(var(--lp-hero-muted))',
          'muted-body': 'hsl(var(--lp-muted-body))',
          'purple-light': 'hsl(var(--lp-purple-light))',
          'section-light': 'hsl(var(--lp-section-light))',
          'problem-bg': 'hsl(var(--lp-problem-bg))',
          'pricing-bg': 'hsl(var(--lp-pricing-bg))',
          'text-body': 'hsl(var(--lp-text-body))',
          'text-muted': 'hsl(var(--lp-text-muted))',
          'footer-dark': 'hsl(var(--lp-footer-dark))',
          'footer-border': 'hsl(var(--lp-footer-border))',
          check: 'hsl(var(--lp-check))',
          'badge-purple': 'hsl(var(--lp-badge-purple))',
          'wp-admin': 'hsl(var(--lp-wp-admin))',
        },
      },
      fontFamily: {
        sans: ['var(--font-inter)', 'ui-sans-serif', 'system-ui', 'sans-serif'],
        heading: [
          'var(--font-plus-jakarta-sans)',
          'ui-sans-serif',
          'system-ui',
          'sans-serif',
        ],
      },
      fontSize: {
        h1: ['2rem', { lineHeight: '1.5', fontWeight: '700' }],
        h2: ['1.5rem', { lineHeight: '1.5', fontWeight: '600' }],
        h3: ['1.125rem', { lineHeight: '1.5', fontWeight: '500' }],
        h4: ['1rem', { lineHeight: '1.5', fontWeight: '400' }],
        h5: ['0.875rem', { lineHeight: '1.5', fontWeight: '500' }],
        h6: ['0.75rem', { lineHeight: '1.5', fontWeight: '400' }],
      },
      borderRadius: {
        none: '0px',
        xs: '6px',
        m: '24px',
        l: '40px',
        pill: '999px',
      },
      animation: {
        'pulse-dot': 'pulse-dot 2s ease-in-out infinite',
        float: 'float 4s ease-in-out infinite',
        'float-slow': 'float 6s ease-in-out infinite',
        'float-delayed': 'float 5s ease-in-out 1.5s infinite',
        'float-delayed-2': 'float 4.5s ease-in-out 0.8s infinite',
        'float-delayed-3': 'float 5.5s ease-in-out 2.2s infinite',
        shimmer: 'shimmer 3s ease-in-out infinite',
        'pulse-glow': 'pulse-glow 2s ease-in-out infinite',
        'hue-shift': 'hue-shift 8s ease-in-out infinite',
        'draw-line': 'draw-line 0.6s ease-out forwards',
      },
      keyframes: {
        'pulse-dot': {
          '0%, 100%': { boxShadow: '0 0 0 0 hsl(var(--color-success-indicator) / 0.4)' },
          '50%': { boxShadow: '0 0 0 4px hsl(var(--color-success-indicator) / 0)' },
        },
        float: {
          '0%, 100%': {
            transform:
              'translateY(0) rotate(var(--float-rotate, 0deg))',
          },
          '50%': {
            transform:
              'translateY(var(--float-y, -8px)) rotate(var(--float-rotate-mid, 0deg))',
          },
        },
        shimmer: {
          '0%': { backgroundPosition: '-200% center' },
          '100%': { backgroundPosition: '200% center' },
        },
        'pulse-glow': {
          '0%, 100%': { boxShadow: '0 0 20px 0 rgba(255, 255, 255, 0)' },
          '50%': { boxShadow: '0 0 20px 4px rgba(255, 255, 255, 0.25)' },
        },
        'hue-shift': {
          '0%, 100%': { backgroundColor: 'hsl(var(--primary))' },
          '50%': { backgroundColor: 'hsl(258 72% 53%)' },
        },
        'draw-line': {
          '0%': { width: '0%' },
          '100%': { width: '100%' },
        },
      },
    },
  },
  plugins: [],
};
