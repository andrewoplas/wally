/**
 * ResponseValidatorService
 *
 * Heuristic validator for LLM response text.
 *
 * Detects known bad patterns that indicate the LLM received wrong context,
 * generated contradictory messaging, or violated tool-flow instructions.
 */

import { Injectable } from '@nestjs/common';

export type ToolStatus = 'success' | 'pending' | 'error' | null;

export interface ValidationResult {
  valid: boolean;
  issues: string[];
}

@Injectable()
export class ResponseValidatorService {
  /**
   * Validate an LLM text response for known anti-patterns.
   *
   * @param text       The LLM's text response
   * @param toolStatus Current tool execution status
   */
  validateResponse(text: string, toolStatus: ToolStatus): ValidationResult {
    const issues: string[] = [];

    // Bug A regression: LLM narrated action ("Updating now!") then also asked
    // for confirmation ("Would you like me to update it?") in the same message.
    if (
      /\bnow[!.]/i.test(text) &&
      /(would you like|shall i|want me to)/i.test(text)
    ) {
      issues.push('CONTRADICTORY_PREACTION_AND_ASK');
    }

    // LLM claimed a tool was "triggered unexpectedly" — indicates wrong context.
    if (/triggered unexpectedly/i.test(text)) {
      issues.push('UNEXPECTED_TRIGGER_LANGUAGE');
    }

    // After a successful tool execution the LLM should never ask the user to
    // confirm/approve — the action already completed.
    if (
      toolStatus === 'success' &&
      /(confirm|approve|reject|buttons below)/i.test(text)
    ) {
      issues.push('CONFIRMATION_LANGUAGE_ON_SUCCESS');
    }

    // While an action is pending confirmation the UI already shows confirm/reject
    // buttons — asking again via text is redundant.
    if (toolStatus === 'pending' && /(would you like|shall i)/i.test(text)) {
      issues.push('REDUNDANT_CONFIRMATION_ASK');
    }

    // LLM introduced itself — violates the no-greet / no-preamble rule.
    if (/\bi('m| am) (wp ai|your ai|an ai)/i.test(text)) {
      issues.push('SELF_INTRODUCTION');
    }

    return { valid: issues.length === 0, issues };
  }
}
