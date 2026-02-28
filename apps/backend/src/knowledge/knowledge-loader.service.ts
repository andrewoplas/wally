/**
 * KnowledgeLoaderService
 *
 * Loads WordPress knowledge chunks from markdown files at startup.
 * Files are cached in memory; `getKnowledgeForIntents()` retrieves
 * the relevant chunks for prompt injection.
 *
 * The knowledge directory is resolved in this order:
 *   1. `KNOWLEDGE_DIR` environment variable (absolute path)
 *   2. Sibling `knowledge/` directory relative to this file (at runtime)
 *   3. Project-root-relative fallback paths for development
 */

import { Injectable, Logger, OnModuleInit } from '@nestjs/common';
import { readFileSync, existsSync } from 'fs';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';

// ─── Knowledge Domain Registry ────────────────────────────────────────────────

const KNOWLEDGE_FILES: string[] = [
  // Core WP content & structure
  'general',
  'content',
  'elementor',
  'acf',
  'plugins',
  'menus',
  'media',
  'settings',
  'users',
  'taxonomies',
  'theme',
  'search',

  // E-commerce
  'woocommerce',
  'ecommerce-plugins',

  // SEO
  'yoast-seo',
  'rank-math',

  // Forms
  'gravity-forms',
  'contact-form-7',
  'forms-general',

  // Performance & caching
  'caching-plugins',

  // Security
  'security-plugins',

  // Backup & migration
  'backup-plugins',

  // Page builders (non-Elementor)
  'page-builders',

  // Multilingual
  'multilingual-plugins',

  // Email
  'wp-mail-smtp',

  // Analytics
  'analytics-plugins',

  // Jetpack
  'jetpack',

  // Redirection / broken links
  'redirection',

  // Other SEO (AIOSEO, SEOPress)
  'seo-other',

  // GDPR / cookie consent
  'gdpr-compliance',

  // Events
  'events-plugins',

  // Image optimization
  'image-optimization',

  // Email marketing & popups
  'email-marketing',

  // Elementor addons
  'elementor-addons',

  // Slider plugins
  'slider-plugins',

  // Gutenberg block addons
  'gutenberg-addons',

  // Theme extensions
  'theme-extensions',

  // WooCommerce extensions (payments)
  'woocommerce-extensions',

  // Social media & sharing
  'social-plugins',

  // Content display (tables, popular posts)
  'content-plugins',

  // Development utilities
  'dev-utility-plugins',

  // Audit & logging
  'audit-logging',

  // Media management
  'media-plugins',

  // Hosting-specific plugins
  'hosting-plugins',

  // Niche utility plugins
  'niche-utility-plugins',

  // WordPress core APIs & features
  'wp-enqueue',
  'wp-ajax',
  'wp-http',
  'wp-i18n',
  'wp-conditionals',
  'wp-theme-json',
  'wp-rest-api',
  'wp-hooks',
  'wp-database',
  'wp-cron',
  'wp-security',
  'wp-transients',
  'wp-gutenberg',
  'wp-multisite',
  'wp-shortcodes',
  'wp-widgets',
  'wp-comments',
  'wp-rewrite',
];

// ─── Service ──────────────────────────────────────────────────────────────────

@Injectable()
export class KnowledgeLoaderService implements OnModuleInit {
  private readonly logger = new Logger(KnowledgeLoaderService.name);
  private readonly cache = new Map<string, string>();

  onModuleInit(): void {
    this.loadAll();
  }

  /**
   * Returns combined knowledge text for the given intent keys.
   * Intents that have no cached file are silently skipped.
   */
  getKnowledgeForIntents(intents: string[]): string {
    const chunks: string[] = [];
    for (const intent of intents) {
      const content = this.cache.get(intent);
      if (content) chunks.push(content);
    }
    return chunks.join('\n\n');
  }

  hasKnowledge(key: string): boolean {
    return this.cache.has(key);
  }

  // ─── Private ──────────────────────────────────────────────────────────────

  private loadAll(): void {
    const dir = this.resolveKnowledgeDir();
    if (!dir) {
      this.logger.warn('Could not locate knowledge directory — knowledge injection disabled');
      return;
    }

    for (const name of KNOWLEDGE_FILES) {
      try {
        const filePath = join(dir, `${name}.md`);
        const content = readFileSync(filePath, 'utf-8');
        this.cache.set(name, content.trim());
      } catch {
        // Non-fatal: log and continue without this chunk
        this.logger.warn(`Could not load ${name}.md`);
      }
    }

    this.logger.log(
      `Loaded ${this.cache.size}/${KNOWLEDGE_FILES.length} knowledge chunks`,
    );
  }

  /**
   * Resolve the knowledge directory from multiple candidate locations.
   *
   * Priority:
   *  1. KNOWLEDGE_DIR env var (absolute path — best for dev/docker)
   *  2. Sibling `knowledge/` directory next to this compiled file
   *  3. Project-root-relative fallback (Nx monorepo development)
   */
  private resolveKnowledgeDir(): string | null {
    const candidates: string[] = [];

    // 1. Env-configured override
    if (process.env['KNOWLEDGE_DIR']) {
      candidates.push(process.env['KNOWLEDGE_DIR']);
    }

    // 2. Compiled file location — relative to this source/dist file (ESM)
    const thisFileDir = dirname(fileURLToPath(import.meta.url));
    candidates.push(join(thisFileDir, 'knowledge'));
    candidates.push(thisFileDir);

    // 3. Process-cwd-relative fallbacks (Nx monorepo dev)
    const cwd = process.cwd();
    candidates.push(join(cwd, 'apps/backend/src/knowledge'));

    for (const candidate of candidates) {
      if (existsSync(join(candidate, 'general.md'))) {
        this.logger.log(`Knowledge directory: ${candidate}`);
        return candidate;
      }
    }

    return null;
  }
}
