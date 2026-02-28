import 'reflect-metadata';
import { IntentClassifierService } from './intent-classifier.service';

describe('IntentClassifierService', () => {
  let service: IntentClassifierService;

  beforeEach(() => {
    service = new IntentClassifierService();
  });

  describe('classifyIntent', () => {
    it('always includes general intent', () => {
      const intents = service.classifyIntent('hello there');
      expect(intents).toContain('general');
    });

    it('classifies Elementor messages', () => {
      const intents = service.classifyIntent('update my elementor page layout');
      expect(intents).toContain('elementor');
    });

    it('classifies content management messages', () => {
      const intents = service.classifyIntent('create a new post about spring gardening');
      expect(intents).toContain('content');
    });

    it('classifies plugin messages', () => {
      const intents = service.classifyIntent('install the WooCommerce plugin');
      expect(intents).toContain('plugins');
      expect(intents).toContain('woocommerce');
    });

    it('classifies search/replace messages', () => {
      const intents = service.classifyIntent('find and replace all instances of "old text"');
      expect(intents).toContain('search');
    });

    it('classifies SEO messages', () => {
      const intents = service.classifyIntent('update Yoast SEO meta description for home page');
      expect(intents).toContain('yoast-seo');
    });

    it('caps results at MAX_INTENTS (4)', () => {
      // Message that triggers many intents
      const intents = service.classifyIntent(
        'update elementor plugin page with Yoast SEO settings and WooCommerce products',
      );
      expect(intents.length).toBeLessThanOrEqual(4);
    });

    it('picks up intent from conversation context', () => {
      const intents = service.classifyIntent(
        'change the title',
        ['what about the elementor section layout?'],
      );
      expect(intents).toContain('elementor');
    });

    it('returns only general for an unrecognized message', () => {
      const intents = service.classifyIntent('thanks for your help!');
      expect(intents).toEqual(['general']);
    });

    it('deduplicates intents from context and message', () => {
      const intents = service.classifyIntent(
        'update my elementor hero section',
        ['fix the elementor widget'],
      );
      // elementor should appear exactly once
      expect(intents.filter((i) => i === 'elementor').length).toBe(1);
    });

    it('prioritises current-message intents over context intents', () => {
      const intents = service.classifyIntent(
        'update the site tagline',
        ['install the WooCommerce plugin'],
      );
      // settings should be in the result (current message)
      expect(intents).toContain('settings');
    });
  });
});
