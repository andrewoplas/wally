import './global.css';
import { Inter, Plus_Jakarta_Sans, JetBrains_Mono } from 'next/font/google';

const inter = Inter({
  subsets: ['latin'],
  variable: '--font-inter',
  display: 'swap',
});

const plusJakartaSans = Plus_Jakarta_Sans({
  subsets: ['latin'],
  variable: '--font-plus-jakarta-sans',
  display: 'swap',
});

const jetBrainsMono = JetBrains_Mono({
  subsets: ['latin'],
  variable: '--font-jetbrains-mono',
  display: 'swap',
});

export const metadata = {
  metadataBase: new URL(process.env.NEXT_PUBLIC_SITE_URL ?? 'https://www.wallychat.com'),
  title: {
    default: 'Wally — AI WordPress Assistant',
    template: '%s | Wally',
  },
  description: 'Manage your WordPress site by just asking. Wally is an AI chat assistant inside wp-admin that handles your site tasks — no menus, no tickets, no tech skills needed.',
  keywords: [
    'WordPress AI assistant',
    'WordPress automation',
    'AI WordPress plugin',
    'WordPress site management',
    'natural language WordPress',
    'wp-admin AI',
    'WordPress chatbot',
    'Wally',
  ],
  openGraph: {
    siteName: 'Wally',
    type: 'website',
    images: [{ url: '/site-og.png', width: 1200, height: 630, alt: 'Wally — AI WordPress Assistant' }],
  },
  twitter: {
    card: 'summary_large_image',
    images: ['/site-og.png'],
  },
  robots: {
    index: true,
    follow: true,
    googleBot: { index: true, follow: true },
  },
  icons: {
    icon: [
      { url: '/favicon.ico', sizes: '16x16 32x32 48x48' },
      { url: '/favicon.svg', type: 'image/svg+xml' },
    ],
    apple: '/apple-icon.png',
  },
};

export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <html lang="en" className={`${inter.variable} ${plusJakartaSans.variable} ${jetBrainsMono.variable}`}>
      <body className="bg-background text-foreground font-sans antialiased">
        {children}
      </body>
    </html>
  );
}
