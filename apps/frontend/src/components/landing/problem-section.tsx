'use client';

import { useRef } from 'react';
import {
  motion,
  useScroll,
  useTransform,
  useInView,
} from 'framer-motion';
import {
  Flame,
  Search,
  Bug,
  Timer,
  TriangleAlert,
  Code,
  Compass,
  Plug,
  Frown,
  MonitorX,
  CircleAlert,
  RefreshCw,
  Shield,
  HardDrive,
  Mail,
  Lock,
  Trash2,
  ImageOff,
  Wrench,
  FileWarning,
  Zap,
  Globe,
  Loader,
  Ban,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';

/*──────────────────────────────────────────────
  Pill data — extracted from Pencil design
  Positions are percentages of a 1440 × 520 canvas
──────────────────────────────────────────────*/

interface Pill {
  text: string;
  x: number;
  y: number;
  rotation: number;
  opacity: number;
  icon?: LucideIcon;
  iconColor?: string;
  textColor?: string;
  size: 'sm' | 'md';
  intense?: boolean;
  /** parallax scroll multiplier */
  speed: number;
  /** continuous float duration (seconds) */
  floatDuration: number;
  /** float Y offset (px) */
  floatY: number;
  /** float mid-point rotation */
  floatRotateMid: number;
}

const PILLS: Pill[] = [
  // ── Icon pills — high visibility ──────────────────────────────
  { text: 'where is that setting??', x: 2.1, y: 19.2, rotation: -4, opacity: 0.7, icon: Search, iconColor: '#A8A29E', textColor: '#78716C', size: 'md', speed: 0.3, floatDuration: 4.2, floatY: -7, floatRotateMid: -3 },
  { text: 'white screen of death', x: 81.9, y: 23.1, rotation: 3, opacity: 0.65, icon: Bug, iconColor: '#EF4444', textColor: '#78716C', size: 'md', speed: -0.25, floatDuration: 5.1, floatY: -9, floatRotateMid: 4 },
  { text: '3 hours to change a button color', x: 3.5, y: 59.6, rotation: -2, opacity: 0.55, icon: Timer, iconColor: '#F59E0B', textColor: '#A8A29E', size: 'md', speed: 0.35, floatDuration: 4.6, floatY: -6, floatRotateMid: -1 },
  { text: 'PLUGIN CONFLICT', x: 83.3, y: 65.4, rotation: 2, opacity: 0.55, icon: TriangleAlert, iconColor: '#EF4444', textColor: '#EF444480', size: 'md', intense: true, speed: -0.3, floatDuration: 3.8, floatY: -8, floatRotateMid: 3 },
  { text: 'wp_enqueue_what???', x: 12.5, y: 11.5, rotation: -3, opacity: 0.5, icon: Code, iconColor: '#A8A29E', textColor: '#A8A29E', size: 'md', speed: 0.2, floatDuration: 5.4, floatY: -6, floatRotateMid: -2 },
  { text: 'which menu is this under??', x: 70.8, y: 9.6, rotation: 2.5, opacity: 0.6, icon: Compass, iconColor: '#F59E0B', textColor: '#78716C', size: 'md', speed: -0.2, floatDuration: 4.8, floatY: -7, floatRotateMid: 4 },
  { text: '429 plugins and counting', x: 4.2, y: 85.6, rotation: -1.5, opacity: 0.45, icon: Plug, iconColor: '#A8A29E', textColor: '#A8A29E', size: 'md', speed: 0.4, floatDuration: 5.2, floatY: -5, floatRotateMid: -1 },
  { text: 'I miss Squarespace honestly', x: 73.6, y: 87.5, rotation: 3, opacity: 0.5, icon: Frown, iconColor: '#EF4444', textColor: '#78716C', size: 'md', speed: -0.35, floatDuration: 4.4, floatY: -8, floatRotateMid: 4 },
  { text: 'site hacked AGAIN', x: 46.5, y: 3.8, rotation: -1.5, opacity: 0.6, icon: Shield, iconColor: '#EF4444', textColor: '#EF444490', size: 'md', intense: true, speed: 0.18, floatDuration: 3.9, floatY: -8, floatRotateMid: -2 },
  { text: 'ran out of disk space', x: 92.4, y: 30.8, rotation: 4, opacity: 0.45, icon: HardDrive, iconColor: '#A8A29E', textColor: '#78716C', size: 'md', speed: -0.22, floatDuration: 5.0, floatY: -6, floatRotateMid: 5 },
  { text: 'contact form not sending emails', x: 1.4, y: 48.1, rotation: -3, opacity: 0.5, icon: Mail, iconColor: '#F59E0B', textColor: '#78716C', size: 'md', speed: 0.28, floatDuration: 4.4, floatY: -7, floatRotateMid: -2 },
  { text: 'locked out of my own site', x: 81.9, y: 50.0, rotation: 2.5, opacity: 0.6, icon: Lock, iconColor: '#EF4444', textColor: '#78716C', size: 'md', speed: -0.32, floatDuration: 4.1, floatY: -8, floatRotateMid: 3 },
  { text: 'accidentally deleted the homepage', x: 18.1, y: 76.9, rotation: -2, opacity: 0.55, icon: Trash2, iconColor: '#EF4444', textColor: '#EF444480', size: 'md', intense: true, speed: 0.34, floatDuration: 3.7, floatY: -9, floatRotateMid: -3 },
  { text: 'images not showing', x: 85.4, y: 80.8, rotation: 3.5, opacity: 0.45, icon: ImageOff, iconColor: '#A8A29E', textColor: '#A8A29E', size: 'md', speed: -0.26, floatDuration: 5.3, floatY: -5, floatRotateMid: 4 },
  { text: 'maintenance mode won\'t turn off', x: 60.4, y: 78.8, rotation: -1, opacity: 0.48, icon: Wrench, iconColor: '#F59E0B', textColor: '#78716C', size: 'md', speed: -0.29, floatDuration: 4.8, floatY: -6, floatRotateMid: -2 },
  { text: 'fatal error after update', x: 36.8, y: 82.7, rotation: 2, opacity: 0.5, icon: FileWarning, iconColor: '#EF4444', textColor: '#EF444480', size: 'md', intense: true, speed: 0.36, floatDuration: 3.5, floatY: -8, floatRotateMid: 3 },
  { text: 'page speed score: 12', x: 87.5, y: 15.4, rotation: -3, opacity: 0.55, icon: Zap, iconColor: '#F59E0B', textColor: '#78716C', size: 'md', speed: -0.18, floatDuration: 5.2, floatY: -7, floatRotateMid: -4 },
  { text: 'mixed content warnings', x: 8.3, y: 69.2, rotation: 1.5, opacity: 0.42, icon: Globe, iconColor: '#A8A29E', textColor: '#A8A29E', size: 'md', speed: 0.3, floatDuration: 4.6, floatY: -5, floatRotateMid: 2 },
  { text: 'infinite loading spinner', x: 66.7, y: 5.8, rotation: -2.5, opacity: 0.48, icon: Loader, iconColor: '#A8A29E', textColor: '#78716C', size: 'md', speed: -0.15, floatDuration: 5.6, floatY: -6, floatRotateMid: 4 },
  { text: 'blocked by security plugin', x: 3.5, y: 53.8, rotation: 3, opacity: 0.45, icon: Ban, iconColor: '#EF4444', textColor: '#78716C', size: 'md', speed: 0.24, floatDuration: 4.3, floatY: -7, floatRotateMid: -1 },

  // ── Medium text pills ─────────────────────────────────────────
  { text: 'Gutenberg is so confusing', x: 14.6, y: 33.7, rotation: 2, opacity: 0.65, textColor: '#78716C', size: 'md', speed: 0.25, floatDuration: 5.0, floatY: -6, floatRotateMid: 3 },
  { text: 'WHO THE F**K DESIGNED THIS', x: 82.6, y: 33.7, rotation: -2, opacity: 0.6, textColor: '#EF4444', size: 'md', intense: true, speed: -0.28, floatDuration: 3.6, floatY: -7, floatRotateMid: -3 },
  { text: 'why are there 6 editors??', x: 13.9, y: 65.4, rotation: 1, opacity: 0.5, textColor: '#78716C', size: 'md', speed: 0.32, floatDuration: 4.5, floatY: -5, floatRotateMid: 2 },
  { text: "can't find media library", x: 83.3, y: 65.4, rotation: -2.5, opacity: 0.48, textColor: '#A8A29E', size: 'md', speed: -0.3, floatDuration: 5.3, floatY: -7, floatRotateMid: -2 },
  { text: 'what is a hook??', x: 22.2, y: 15.4, rotation: -2.5, opacity: 0.6, textColor: '#78716C', size: 'md', speed: 0.18, floatDuration: 4.3, floatY: -5, floatRotateMid: -1.5 },
  { text: "why won't this save", x: 56.9, y: 13.5, rotation: 1.8, opacity: 0.55, textColor: '#A8A29E', size: 'md', speed: -0.22, floatDuration: 5.6, floatY: -6, floatRotateMid: 3 },
  { text: 'update broke everything', x: 40.3, y: 6.7, rotation: -3.5, opacity: 0.45, textColor: '#EF444490', size: 'md', intense: true, speed: 0.15, floatDuration: 4.0, floatY: -8, floatRotateMid: -4 },
  { text: 'need a developer for THIS?', x: 32.6, y: 91.3, rotation: -1.5, opacity: 0.5, textColor: '#78716C', size: 'md', speed: 0.38, floatDuration: 5.1, floatY: -5, floatRotateMid: -1 },
  { text: 'how do I undo this', x: 89.6, y: 75, rotation: -5, opacity: 0.42, textColor: '#78716C', size: 'md', speed: -0.33, floatDuration: 4.7, floatY: -7, floatRotateMid: -4 },
  { text: 'googling at 2am', x: 20.8, y: 89.4, rotation: -1, opacity: 0.55, icon: Timer, iconColor: '#F59E0B', textColor: '#78716C', size: 'md', speed: 0.36, floatDuration: 5.5, floatY: -6, floatRotateMid: 0 },
  { text: 'dashboard moved again', x: 61.1, y: 90.4, rotation: 1.5, opacity: 0.5, icon: RefreshCw, iconColor: '#A8A29E', textColor: '#A8A29E', size: 'md', speed: -0.34, floatDuration: 4.2, floatY: -7, floatRotateMid: 3 },
  { text: 'my client is going to kill me', x: 48.6, y: 95.2, rotation: -2, opacity: 0.55, textColor: '#EF444490', size: 'md', intense: true, speed: 0.32, floatDuration: 3.8, floatY: -7, floatRotateMid: -2 },
  { text: 'where did my sidebar go', x: 30.6, y: 8.7, rotation: 2.5, opacity: 0.5, textColor: '#78716C', size: 'md', speed: 0.14, floatDuration: 5.2, floatY: -5, floatRotateMid: 2 },
  { text: "it worked yesterday I swear", x: 80.6, y: 57.7, rotation: -1.5, opacity: 0.52, textColor: '#78716C', size: 'md', speed: -0.26, floatDuration: 4.9, floatY: -6, floatRotateMid: -3 },
  { text: "spent $200 on plugins that don't work", x: 3.5, y: 36.5, rotation: -3, opacity: 0.48, textColor: '#78716C', size: 'md', speed: 0.22, floatDuration: 5.4, floatY: -5, floatRotateMid: -1 },
  { text: 'why is the editor blank??', x: 81.3, y: 44.2, rotation: 3, opacity: 0.52, textColor: '#78716C', size: 'md', speed: -0.24, floatDuration: 4.7, floatY: -7, floatRotateMid: 4 },
  { text: 'Elementor or Divi or Beaver??', x: 17.4, y: 44.2, rotation: -1, opacity: 0.45, textColor: '#A8A29E', size: 'md', speed: 0.2, floatDuration: 5.1, floatY: -5, floatRotateMid: 0 },
  { text: 'just want to change the font', x: 82.6, y: 22.1, rotation: 2, opacity: 0.48, textColor: '#78716C', size: 'md', speed: -0.2, floatDuration: 4.5, floatY: -6, floatRotateMid: 3 },
  { text: "backup? what backup?", x: 40.3, y: 80.8, rotation: -3.5, opacity: 0.55, textColor: '#EF444490', size: 'md', intense: true, speed: 0.28, floatDuration: 3.9, floatY: -8, floatRotateMid: -4 },
  { text: 'WooCommerce settings are a maze', x: 88.2, y: 57.7, rotation: 4, opacity: 0.42, textColor: '#A8A29E', size: 'md', speed: -0.3, floatDuration: 5.3, floatY: -5, floatRotateMid: 5 },
  { text: 'the preview looks nothing like live', x: 23.6, y: 23.1, rotation: -2, opacity: 0.55, textColor: '#78716C', size: 'md', speed: 0.16, floatDuration: 4.8, floatY: -6, floatRotateMid: -2 },
  { text: 'I just wanted a simple blog', x: 68.1, y: 82.7, rotation: 1.5, opacity: 0.5, textColor: '#78716C', size: 'md', speed: -0.36, floatDuration: 5.0, floatY: -7, floatRotateMid: 2 },
  { text: 'the theme customizer crashed', x: 9.7, y: 92.3, rotation: -1, opacity: 0.42, textColor: '#A8A29E', size: 'md', speed: 0.34, floatDuration: 4.4, floatY: -5, floatRotateMid: -1 },
  { text: '$5k on a site that takes 9 seconds to load', x: 5.6, y: 44.2, rotation: -2, opacity: 0.45, textColor: '#78716C', size: 'md', speed: -0.18, floatDuration: 5.7, floatY: -6, floatRotateMid: -3 },
  { text: 'asked ChatGPT and it made it worse', x: 10.4, y: 59.6, rotation: 2, opacity: 0.5, textColor: '#78716C', size: 'md', speed: 0.26, floatDuration: 4.6, floatY: -5, floatRotateMid: 1 },

  // ── Small keyword/short pills ─────────────────────────────────
  { text: 'site is down', x: 88.9, y: 42.3, rotation: 5, opacity: 0.4, icon: MonitorX, iconColor: '#A8A29E', textColor: '#A8A29E', size: 'sm', speed: -0.2, floatDuration: 5.8, floatY: -4, floatRotateMid: 6 },
  { text: 'PHP error line 247', x: 0.7, y: 40.4, rotation: -4.5, opacity: 0.4, icon: CircleAlert, iconColor: '#EF4444', textColor: '#A8A29E', size: 'sm', speed: 0.22, floatDuration: 4.9, floatY: -5, floatRotateMid: -3 },
  { text: '404 again', x: 52.8, y: 86.5, rotation: 4, opacity: 0.35, textColor: '#A8A29E', size: 'sm', speed: -0.28, floatDuration: 5.2, floatY: -4, floatRotateMid: 5 },
  { text: 'cache??', x: 91.7, y: 11.5, rotation: 3.5, opacity: 0.38, textColor: '#A8A29E', size: 'sm', speed: -0.15, floatDuration: 4.4, floatY: -3, floatRotateMid: 4 },
  { text: 'shortcodes', x: 36.1, y: 20.2, rotation: -2, opacity: 0.3, textColor: '#A8A29E', size: 'sm', speed: 0.12, floatDuration: 6.0, floatY: -3, floatRotateMid: -1 },
  { text: '.htaccess', x: 57.6, y: 20.2, rotation: 3, opacity: 0.28, textColor: '#A8A29E', size: 'sm', speed: -0.14, floatDuration: 5.4, floatY: -4, floatRotateMid: 4 },
  { text: 'cPanel??', x: 29.2, y: 78.8, rotation: -1, opacity: 0.3, textColor: '#A8A29E', size: 'sm', speed: 0.26, floatDuration: 4.6, floatY: -3, floatRotateMid: 0 },
  { text: 'wp-config.php', x: 61.8, y: 80.8, rotation: 2.5, opacity: 0.25, textColor: '#A8A29E', size: 'sm', speed: -0.24, floatDuration: 5.7, floatY: -4, floatRotateMid: 3 },
  { text: 'REST API', x: 6.3, y: 30.8, rotation: -4, opacity: 0.28, textColor: '#A8A29E', size: 'sm', speed: 0.16, floatDuration: 4.8, floatY: -3, floatRotateMid: -3 },
  { text: 'child theme', x: 90.3, y: 55.8, rotation: 3.5, opacity: 0.25, textColor: '#A8A29E', size: 'sm', speed: -0.18, floatDuration: 5.3, floatY: -4, floatRotateMid: 5 },
  { text: 'permalink', x: 48.6, y: 26, rotation: -3, opacity: 0.22, textColor: '#A8A29E', size: 'sm', speed: 0.1, floatDuration: 6.2, floatY: -3, floatRotateMid: -2 },
  { text: 'multisite??', x: 6.9, y: 74, rotation: 2, opacity: 0.22, textColor: '#A8A29E', size: 'sm', speed: 0.2, floatDuration: 5.0, floatY: -3, floatRotateMid: 3 },
  { text: 'taxonomy??', x: 18.1, y: 24.0, rotation: -3, opacity: 0.35, textColor: '#A8A29E', size: 'sm', speed: 0.14, floatDuration: 4.5, floatY: -4, floatRotateMid: -2 },
  { text: 'permissions broken', x: 68.1, y: 26, rotation: 3, opacity: 0.4, textColor: '#EF444480', size: 'sm', speed: -0.16, floatDuration: 5.1, floatY: -3, floatRotateMid: 4 },
  { text: 'css not loading', x: 42.4, y: 93.3, rotation: 4.5, opacity: 0.32, textColor: '#A8A29E', size: 'sm', speed: 0.3, floatDuration: 4.3, floatY: -4, floatRotateMid: 5 },
  { text: 'CORS error', x: 19.4, y: 57.7, rotation: -3, opacity: 0.3, textColor: '#A8A29E', size: 'sm', speed: 0.18, floatDuration: 5.5, floatY: -3, floatRotateMid: -2 },
  { text: 'jQuery??', x: 44.4, y: 15.4, rotation: 2, opacity: 0.28, textColor: '#A8A29E', size: 'sm', speed: -0.12, floatDuration: 6.1, floatY: -3, floatRotateMid: 3 },
  { text: 'SMTP', x: 94.4, y: 48.1, rotation: -4, opacity: 0.25, textColor: '#A8A29E', size: 'sm', speed: -0.2, floatDuration: 4.7, floatY: -3, floatRotateMid: -4 },
  { text: 'nonce expired', x: 22.9, y: 76.9, rotation: 3.5, opacity: 0.3, textColor: '#A8A29E', size: 'sm', speed: 0.22, floatDuration: 5.0, floatY: -4, floatRotateMid: 4 },
  { text: 'autoload bloat', x: 72.2, y: 15.4, rotation: -2, opacity: 0.25, textColor: '#A8A29E', size: 'sm', speed: -0.14, floatDuration: 5.8, floatY: -3, floatRotateMid: -1 },
  { text: 'XML-RPC', x: 15.3, y: 82.7, rotation: 1, opacity: 0.22, textColor: '#A8A29E', size: 'sm', speed: 0.16, floatDuration: 4.4, floatY: -3, floatRotateMid: 2 },
  { text: 'the loop??', x: 50.7, y: 22.1, rotation: -2.5, opacity: 0.28, textColor: '#A8A29E', size: 'sm', speed: -0.1, floatDuration: 6.3, floatY: -3, floatRotateMid: -3 },
  { text: 'wp_cron', x: 83.3, y: 92.3, rotation: 3, opacity: 0.22, textColor: '#A8A29E', size: 'sm', speed: -0.28, floatDuration: 5.1, floatY: -3, floatRotateMid: 4 },
  { text: 'revisions', x: 2.8, y: 55.8, rotation: -1.5, opacity: 0.25, textColor: '#A8A29E', size: 'sm', speed: 0.2, floatDuration: 4.9, floatY: -4, floatRotateMid: -1 },
  { text: 'SSL issue', x: 88.2, y: 44.2, rotation: 4, opacity: 0.32, textColor: '#EF444480', size: 'sm', speed: -0.22, floatDuration: 5.4, floatY: -3, floatRotateMid: 5 },
  { text: 'transients', x: 16.7, y: 48.1, rotation: -3.5, opacity: 0.2, textColor: '#A8A29E', size: 'sm', speed: 0.12, floatDuration: 6.0, floatY: -3, floatRotateMid: 0 },
  { text: 'oh god the spam', x: 78.5, y: 76.9, rotation: -2, opacity: 0.35, textColor: '#A8A29E', size: 'sm', speed: -0.26, floatDuration: 4.5, floatY: -4, floatRotateMid: -3 },
  { text: 'functions.php', x: 85.4, y: 74.0, rotation: 3, opacity: 0.22, textColor: '#A8A29E', size: 'sm', speed: -0.16, floatDuration: 5.6, floatY: -3, floatRotateMid: 4 },
  { text: 'admin-ajax.php', x: 10.4, y: 26, rotation: -2, opacity: 0.25, textColor: '#A8A29E', size: 'sm', speed: 0.14, floatDuration: 4.8, floatY: -3, floatRotateMid: -2 },
  { text: 'deprecation notice', x: 82.6, y: 69.2, rotation: 2.5, opacity: 0.3, textColor: '#A8A29E', size: 'sm', speed: -0.18, floatDuration: 5.2, floatY: -4, floatRotateMid: 3 },
];

/*──────────────────────────────────────────────
  FloatingPill — pop-in then continuous float
──────────────────────────────────────────────*/

function FloatingPill({
  pill,
  index,
  scrollY,
  isVisible,
}: {
  pill: Pill;
  index: number;
  scrollY: ReturnType<typeof useTransform>;
  isVisible: boolean;
}) {
  const parallaxY = useTransform(scrollY, (v: number) => v * pill.speed);

  const enterDelay = 0.15 + index * 0.025;
  const floatStartDelay = enterDelay + 0.4;
  const isSmall = pill.size === 'sm';

  return (
    /* Outer wrapper handles parallax scroll (style.y) */
    <motion.div
      className="pointer-events-none absolute"
      style={{
        left: `${pill.x}%`,
        top: `${pill.y}%`,
        y: parallaxY,
      }}
    >
      {/* Inner element handles pop-in + continuous float */}
      <motion.div
        className={`flex items-center gap-1.5 border border-[#E7E5E4] bg-white ${
          isSmall ? 'rounded-full px-2.5 py-1.5' : 'rounded-[14px] px-3.5 py-2.5'
        }`}
        initial={{ opacity: 0, scale: 0.85, rotate: pill.rotation }}
        animate={
          isVisible
            ? {
                opacity: pill.opacity,
                scale: 1,
                rotate: [pill.rotation, pill.floatRotateMid, pill.rotation],
                y: [0, pill.floatY, 0],
              }
            : { opacity: 0, scale: 0.85, rotate: pill.rotation }
        }
        transition={
          isVisible
            ? {
                opacity: { delay: enterDelay, duration: 0.5 },
                scale: { delay: enterDelay, duration: 0.5 },
                rotate: {
                  delay: floatStartDelay,
                  duration: pill.floatDuration,
                  repeat: Infinity,
                  ease: 'easeInOut',
                },
                y: {
                  delay: floatStartDelay,
                  duration: pill.floatDuration,
                  repeat: Infinity,
                  ease: 'easeInOut',
                },
              }
            : { delay: enterDelay, duration: 0.5 }
        }
      >
        {pill.icon && (
          <pill.icon
            className={isSmall ? 'h-[11px] w-[11px] shrink-0' : 'h-3 w-3 shrink-0'}
            style={{ color: pill.iconColor }}
          />
        )}
        <span
          className={`whitespace-nowrap font-medium ${
            isSmall ? 'text-[8px] sm:text-[9px]' : 'text-[9px] sm:text-[10px] md:text-[11px]'
          } ${pill.intense ? 'font-bold' : ''}`}
          style={{ color: pill.textColor }}
        >
          {pill.text}
        </span>
      </motion.div>
    </motion.div>
  );
}

/*──────────────────────────────────────────────
  ProblemSection
──────────────────────────────────────────────*/

export function ProblemSection() {
  const sectionRef = useRef<HTMLElement>(null);
  const isVisible = useInView(sectionRef, { once: true, margin: '-80px' });

  const { scrollYProgress } = useScroll({
    target: sectionRef,
    offset: ['start end', 'end start'],
  });

  const scrollY = useTransform(scrollYProgress, [0, 1], [0, 200]);

  return (
    <section
      ref={sectionRef}
      className="relative -mt-32 overflow-hidden pt-44 pb-28 sm:-mt-40 sm:pt-56 sm:pb-36 md:-mt-52 md:pt-72 md:pb-44"
    >
      {/* Background: transparent at top (hero gradient shows through), fades to #FAFAFA */}
      <div
        className="pointer-events-none absolute inset-0"
        style={{
          background:
            'linear-gradient(to bottom, transparent 0%, rgba(250,250,250,0.4) 15%, rgba(250,250,250,0.75) 30%, #FAFAFA 50%)',
        }}
      />

      {/* Floating frustration pills — hidden on small mobile */}
      <div className="pointer-events-none absolute inset-0 hidden sm:block">
        {PILLS.map((pill, i) => (
          <FloatingPill
            key={i}
            pill={pill}
            index={i}
            scrollY={scrollY}
            isVisible={isVisible}
          />
        ))}
      </div>

      {/* Content */}
      <div className="relative z-10 flex flex-col items-center px-4 text-center">
        <motion.span
          className="inline-flex items-center gap-2 rounded-full bg-red-500/10 px-5 py-2 text-[13px] font-semibold text-red-500"
          initial={{ opacity: 0, scale: 0.8 }}
          whileInView={{ opacity: 1, scale: 1 }}
          viewport={{ once: true }}
          transition={{ type: 'spring', stiffness: 300, damping: 20, delay: 0.1 }}
        >
          <Flame className="h-4 w-4" />
          This hurts
        </motion.span>

        <motion.h2
          className="mt-8 font-heading text-4xl font-extrabold leading-[1.1] text-[#18181B] sm:text-5xl md:text-[52px]"
          initial={{ opacity: 0, y: 24 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          transition={{ duration: 0.5, delay: 0.15 }}
        >
          WordPress shouldn&apos;t
        </motion.h2>

        <motion.h2
          className="mt-2 font-heading text-4xl font-extrabold leading-[1.1] text-red-500 sm:text-5xl md:text-[52px]"
          initial={{ opacity: 0, y: 24 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true }}
          transition={{ duration: 0.5, delay: 0.2 }}
        >
          feel this chaotic.
        </motion.h2>
      </div>
    </section>
  );
}
