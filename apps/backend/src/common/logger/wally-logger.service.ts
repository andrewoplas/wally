import { Injectable, LoggerService, Scope } from '@nestjs/common';
import { ConfigService } from '@nestjs/config';

type LogLevel = 'error' | 'warn' | 'info' | 'debug';

interface LogEntry {
  timestamp: string;
  level: LogLevel;
  message: string;
  context?: string;
  [key: string]: unknown;
}

const LOG_LEVEL_ORDER: Record<LogLevel, number> = {
  error: 0,
  warn: 1,
  info: 2,
  debug: 3,
};

/**
 * Structured JSON logger for Wally backend.
 * Wraps NestJS LoggerService interface with JSON output and level filtering.
 */
@Injectable({ scope: Scope.DEFAULT })
export class WallyLoggerService implements LoggerService {
  private readonly currentLevel: number;

  constructor(private readonly configService: ConfigService) {
    const nodeEnv = this.configService.get<string>('nodeEnv', 'development');
    this.currentLevel =
      nodeEnv === 'production' ? LOG_LEVEL_ORDER.info : LOG_LEVEL_ORDER.debug;
  }

  private write(level: LogLevel, message: string, context?: string, meta?: Record<string, unknown>): void {
    if (LOG_LEVEL_ORDER[level] > this.currentLevel) return;

    const entry: LogEntry = {
      timestamp: new Date().toISOString(),
      level,
      message,
      ...(context ? { context } : {}),
      ...(meta ?? {}),
    };

    const output = JSON.stringify(entry);
    if (level === 'error') {
      console.error(output);
    } else if (level === 'warn') {
      console.warn(output);
    } else {
      console.log(output);
    }
  }

  log(message: string, context?: string): void {
    this.write('info', message, context);
  }

  error(message: string, trace?: string, context?: string): void {
    this.write('error', message, context, trace ? { trace } : undefined);
  }

  warn(message: string, context?: string): void {
    this.write('warn', message, context);
  }

  debug(message: string, context?: string): void {
    this.write('debug', message, context);
  }

  verbose(message: string, context?: string): void {
    this.write('debug', message, context);
  }

  /** Log with arbitrary metadata fields. */
  logWithMeta(level: LogLevel, message: string, meta: Record<string, unknown>, context?: string): void {
    this.write(level, message, context, meta);
  }
}
