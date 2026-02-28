'use client';

import { useState } from 'react';
import { Play } from 'lucide-react';
import { SectionBadge } from './shared/section-badge';
import { AnimatedSection } from './shared/animated-section';
import { Container } from './shared/container';

export function VideoDemoSection() {
  const [playing, setPlaying] = useState(false);

  return (
    <section className="bg-lp-hero-dark py-24">
      <Container className="flex flex-col items-center text-center">
        <AnimatedSection>
          <SectionBadge icon={Play} variant="dark">
            See it in action
          </SectionBadge>
        </AnimatedSection>

        <AnimatedSection delay={0.1} className="mt-4">
          <h2 className="max-w-[740px] font-heading text-2xl font-bold text-white sm:text-3xl md:text-4xl">
            Watch it handle a task your client would have ticketed.
          </h2>
        </AnimatedSection>

        <AnimatedSection delay={0.15} className="mt-4">
          <p className="max-w-[580px] text-[15px] leading-[1.5] text-lp-text-muted md:text-[17px]">
            60 seconds. No setup. No learning curve. Just ask — and watch it
            happen.
          </p>
        </AnimatedSection>

        <AnimatedSection delay={0.2} className="mt-12 w-full max-w-[960px]">
          <div
            className="relative aspect-video w-full cursor-pointer overflow-hidden rounded-[20px] border-2 border-primary/25 bg-lp-body-dark"
            onClick={() => setPlaying(true)}
          >
            {playing ? (
              <iframe
                className="absolute inset-0 h-full w-full"
                src="https://www.youtube.com/embed/dQw4w9WgXcQ?autoplay=1"
                allow="autoplay; fullscreen"
                allowFullScreen
                title="Wally Demo"
              />
            ) : (
              <>
                {/* Placeholder thumbnail */}
                <div className="absolute inset-0 bg-gradient-to-br from-lp-body-dark via-primary/20 to-lp-body-dark" />

                {/* Decorative lines */}
                <div className="absolute inset-0 flex items-center justify-center">
                  <div className="grid w-3/4 gap-3 opacity-20">
                    {Array.from({ length: 8 }).map((_, i) => (
                      <div
                        key={i}
                        className="h-2 rounded bg-white/20"
                        style={{ width: `${60 + Math.sin(i) * 30}%` }}
                      />
                    ))}
                  </div>
                </div>

                {/* Play button */}
                <div className="absolute inset-0 flex items-center justify-center">
                  <div className="flex h-20 w-20 items-center justify-center rounded-full bg-white/[0.12] backdrop-blur-sm transition-transform hover:scale-110">
                    <Play className="h-8 w-8 text-white" fill="white" />
                  </div>
                </div>
              </>
            )}
          </div>
        </AnimatedSection>
      </Container>
    </section>
  );
}
