/**
 * ToolCallbackStore
 *
 * Singleton in-memory store for pending tool execution Promises.
 *
 * When the MCP tool handler fires (Agent SDK loop), it registers a callId here
 * and awaits the Promise. When the WordPress plugin POSTs the tool result to
 * /api/v1/tool-callback, the controller calls resolve() to unblock the handler.
 */

import { Injectable, Logger, OnModuleDestroy } from '@nestjs/common';

interface PendingCallback {
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  resolve: (value: any) => void;
  reject: (reason: Error) => void;
  timeout: NodeJS.Timeout;
}

const DEFAULT_TIMEOUT_MS = 120_000; // 2 minutes

@Injectable()
export class ToolCallbackStore implements OnModuleDestroy {
  private readonly logger = new Logger(ToolCallbackStore.name);
  private readonly pending = new Map<string, PendingCallback>();

  /**
   * Register a new pending callback for the given callId.
   * Returns a Promise that resolves when the plugin POSTs the result, or
   * rejects after timeoutMs milliseconds.
   */
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  register(callId: string, timeoutMs = DEFAULT_TIMEOUT_MS): Promise<any> {
    return new Promise((resolve, reject) => {
      const timeout = setTimeout(() => {
        if (this.pending.delete(callId)) {
          reject(new Error(`Tool callback timeout after ${timeoutMs}ms for callId: ${callId}`));
        }
      }, timeoutMs);

      this.pending.set(callId, { resolve, reject, timeout });
      this.logger.debug(`Registered callback for callId: ${callId}`);
    });
  }

  /**
   * Resolve the pending Promise for callId with the given result.
   * Returns true if the callId was found; false otherwise.
   */
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  resolve(callId: string, result: any): boolean {
    const entry = this.pending.get(callId);
    if (!entry) return false;

    clearTimeout(entry.timeout);
    this.pending.delete(callId);
    entry.resolve(result);
    this.logger.debug(`Resolved callback for callId: ${callId}`);
    return true;
  }

  /**
   * Reject the pending Promise for callId with the given error message.
   * Returns true if the callId was found; false otherwise.
   */
  reject(callId: string, error: string): boolean {
    const entry = this.pending.get(callId);
    if (!entry) return false;

    clearTimeout(entry.timeout);
    this.pending.delete(callId);
    entry.reject(new Error(error));
    this.logger.debug(`Rejected callback for callId: ${callId}`);
    return true;
  }

  /**
   * Check whether a pending callback exists for the given callId.
   */
  hasPending(callId: string): boolean {
    return this.pending.has(callId);
  }

  /**
   * Reject all pending callbacks and clear the store (called on shutdown).
   */
  cleanup(): void {
    for (const [callId, entry] of this.pending.entries()) {
      clearTimeout(entry.timeout);
      entry.reject(new Error(`ToolCallbackStore cleanup: service shutting down (callId: ${callId})`));
    }
    this.pending.clear();
    this.logger.log('Cleaned up all pending tool callbacks');
  }

  onModuleDestroy(): void {
    this.cleanup();
  }
}
