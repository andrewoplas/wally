'use client';

import { motion } from 'framer-motion';
import { MessageCircle, Twitter, Github } from 'lucide-react';
import { AnimatedSection, StaggerContainer, StaggerItem } from './shared/animated-section';
import { Container } from './shared/container';

const linkColumns = [
  {
    title: 'Product',
    links: ['Features', 'Pricing', 'Changelog', 'Download'],
  },
  {
    title: 'Resources',
    links: ['Documentation', 'Support', 'API Reference', 'Status'],
  },
  {
    title: 'Legal',
    links: ['Privacy Policy', 'Terms of Service', 'Cookie Policy'],
  },
];

export function Footer() {
  return (
    <footer className="bg-lp-footer-dark py-20 pb-10">
      <Container>
        {/* Top row */}
        <div className="flex flex-col gap-10 lg:flex-row lg:justify-between">
          {/* Brand */}
          <AnimatedSection className="max-w-[320px]">
            <div className="flex items-center gap-2.5">
              <motion.div
                initial={{ y: -4 }}
                whileInView={{ y: 0 }}
                viewport={{ once: true }}
                transition={{ type: 'spring', stiffness: 400, damping: 15, delay: 0.1 }}
              >
                <MessageCircle className="h-7 w-7 text-primary" />
              </motion.div>
              <span className="font-heading text-2xl font-extrabold text-white">
                Wally
              </span>
            </div>
            <p className="mt-5 text-sm leading-[1.6] text-lp-text-muted">
              The AI-powered WordPress assistant that lets you manage your site
              through natural conversation.
            </p>
          </AnimatedSection>

          {/* Link columns */}
          <StaggerContainer
            className="flex flex-wrap gap-20"
            staggerDelay={0.1}
          >
            {linkColumns.map((col) => (
              <StaggerItem key={col.title}>
                <div className="flex flex-col gap-4">
                  <span className="text-sm font-bold tracking-[0.5px] text-white">
                    {col.title}
                  </span>
                  {col.links.map((link, i) => (
                    <motion.a
                      key={link}
                      href="#"
                      className="text-sm text-lp-text-muted transition-all hover:translate-x-[3px] hover:text-white"
                      initial={{ opacity: 0 }}
                      whileInView={{ opacity: 1 }}
                      viewport={{ once: true }}
                      transition={{ delay: 0.2 + i * 0.03 }}
                    >
                      {link}
                    </motion.a>
                  ))}
                </div>
              </StaggerItem>
            ))}
          </StaggerContainer>
        </div>

        {/* Divider */}
        <div className="relative mt-16 h-px">
          <motion.div
            className="absolute inset-y-0 left-0 bg-lp-footer-border"
            initial={{ width: '0%' }}
            whileInView={{ width: '100%' }}
            viewport={{ once: true }}
            transition={{ duration: 0.6, ease: 'easeOut', delay: 0.3 }}
          />
        </div>

        {/* Bottom bar */}
        <motion.div
          className="mt-8 flex flex-col items-center justify-between gap-4 sm:flex-row"
          initial={{ opacity: 0 }}
          whileInView={{ opacity: 1 }}
          viewport={{ once: true }}
          transition={{ delay: 0.6, duration: 0.4 }}
        >
          <span className="text-[13px] text-lp-text-body">
            &copy; 2026 Wally. All rights reserved.
          </span>

          <div className="flex items-center gap-6">
            <span className="text-[13px] text-lp-text-body">v1.0.0</span>
            <span className="text-[13px] text-lp-text-body">
              Last updated Feb 2026
            </span>
            <div className="flex items-center gap-4">
              <motion.a
                href="#"
                className="text-lp-text-body transition-colors hover:scale-[1.15] hover:text-primary"
                initial={{ scale: 0 }}
                whileInView={{ scale: 1 }}
                viewport={{ once: true }}
                transition={{
                  type: 'spring',
                  stiffness: 400,
                  damping: 15,
                  delay: 0.7,
                }}
              >
                <Twitter className="h-[18px] w-[18px]" />
              </motion.a>
              <motion.a
                href="#"
                className="text-lp-text-body transition-colors hover:scale-[1.15] hover:text-primary"
                initial={{ scale: 0 }}
                whileInView={{ scale: 1 }}
                viewport={{ once: true }}
                transition={{
                  type: 'spring',
                  stiffness: 400,
                  damping: 15,
                  delay: 0.8,
                }}
              >
                <Github className="h-[18px] w-[18px]" />
              </motion.a>
            </div>
          </div>
        </motion.div>
      </Container>
    </footer>
  );
}
