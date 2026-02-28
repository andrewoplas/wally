import 'reflect-metadata';
import { ExecutionContext, UnauthorizedException, ForbiddenException } from '@nestjs/common';
import { AuthGuard } from './auth.guard';

// Minimal stubs to avoid instantiating real NestJS services
const mockLogger = {
  logWithMeta: jest.fn(),
} as unknown as import('../logger/wally-logger.service').WallyLoggerService;

function makeContext(headers: Record<string, string>, body: Record<string, unknown> = {}, skipValidation = true) {
  const configService = {
    get: jest.fn().mockReturnValue(skipValidation),
  };

  const guard = new AuthGuard(configService as unknown as import('@nestjs/config').ConfigService, mockLogger);

  const mockRequest = { headers, body, ip: '127.0.0.1' };
  const context = {
    switchToHttp: () => ({
      getRequest: () => mockRequest,
    }),
  } as unknown as ExecutionContext;

  return { guard, context, request: mockRequest };
}

describe('AuthGuard', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('allows request with valid headers in dev mode', () => {
    const { guard, context } = makeContext({
      'x-site-id': 'site-001',
      'x-api-key': 'key-abc',
    });
    expect(guard.canActivate(context)).toBe(true);
  });

  it('attaches siteId and apiKey to the request', () => {
    const { guard, context, request } = makeContext({
      'x-site-id': 'site-001',
      'x-api-key': 'key-abc',
    });
    guard.canActivate(context);
    expect((request as Record<string, unknown>)['siteId']).toBe('site-001');
    expect((request as Record<string, unknown>)['apiKey']).toBe('key-abc');
  });

  it('reads credentials from request body when headers are absent', () => {
    const { guard, context, request } = makeContext(
      {},
      { site_id: 'site-body', api_key: 'key-body' },
    );
    guard.canActivate(context);
    expect((request as Record<string, unknown>)['siteId']).toBe('site-body');
  });

  it('throws UnauthorizedException when site_id is missing', () => {
    const { guard, context } = makeContext({ 'x-api-key': 'key-abc' });
    expect(() => guard.canActivate(context)).toThrow(UnauthorizedException);
  });

  it('throws UnauthorizedException when api_key is missing', () => {
    const { guard, context } = makeContext({ 'x-site-id': 'site-001' });
    expect(() => guard.canActivate(context)).toThrow(UnauthorizedException);
  });

  it('throws ForbiddenException when license validation is enabled', () => {
    const { guard, context } = makeContext(
      { 'x-site-id': 'site-001', 'x-api-key': 'key-abc' },
      {},
      false, // skipValidation = false → production mode
    );
    expect(() => guard.canActivate(context)).toThrow(ForbiddenException);
  });
});
