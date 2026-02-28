import 'reflect-metadata';
import { ResponseValidatorService } from './response-validator.service';

describe('ResponseValidatorService', () => {
  let service: ResponseValidatorService;

  beforeEach(() => {
    service = new ResponseValidatorService();
  });

  describe('validateResponse', () => {
    it('passes a clean response', () => {
      const result = service.validateResponse('The post has been updated.', null);
      expect(result.valid).toBe(true);
      expect(result.issues).toHaveLength(0);
    });

    it('detects CONTRADICTORY_PREACTION_AND_ASK', () => {
      const text = 'Updating now! Would you like me to proceed?';
      const result = service.validateResponse(text, null);
      expect(result.valid).toBe(false);
      expect(result.issues).toContain('CONTRADICTORY_PREACTION_AND_ASK');
    });

    it('detects UNEXPECTED_TRIGGER_LANGUAGE', () => {
      const text = 'The tool was triggered unexpectedly.';
      const result = service.validateResponse(text, null);
      expect(result.valid).toBe(false);
      expect(result.issues).toContain('UNEXPECTED_TRIGGER_LANGUAGE');
    });

    it('detects CONFIRMATION_LANGUAGE_ON_SUCCESS', () => {
      const text = 'The post was updated. Please confirm to proceed.';
      const result = service.validateResponse(text, 'success');
      expect(result.valid).toBe(false);
      expect(result.issues).toContain('CONFIRMATION_LANGUAGE_ON_SUCCESS');
    });

    it('does not flag confirmation language on non-success status', () => {
      const text = 'Click the confirm button below to approve the change.';
      const result = service.validateResponse(text, 'pending');
      expect(result.issues).not.toContain('CONFIRMATION_LANGUAGE_ON_SUCCESS');
    });

    it('detects REDUNDANT_CONFIRMATION_ASK when status is pending', () => {
      const text = 'Would you like me to delete the post?';
      const result = service.validateResponse(text, 'pending');
      expect(result.valid).toBe(false);
      expect(result.issues).toContain('REDUNDANT_CONFIRMATION_ASK');
    });

    it('detects SELF_INTRODUCTION', () => {
      const text = "Hi! I'm WP AI here to help you.";
      const result = service.validateResponse(text, null);
      expect(result.valid).toBe(false);
      expect(result.issues).toContain('SELF_INTRODUCTION');
    });

    it('can report multiple issues at once', () => {
      const text =
        "I'm WP AI. Updating now! Would you like me to also check again? It was triggered unexpectedly.";
      const result = service.validateResponse(text, null);
      expect(result.valid).toBe(false);
      expect(result.issues.length).toBeGreaterThan(1);
    });

    it('accepts a normal pending response without redundant ask', () => {
      const text = 'The deletion is awaiting your confirmation. Use the buttons below.';
      const result = service.validateResponse(text, 'pending');
      expect(result.valid).toBe(true);
    });
  });
});
