import 'reflect-metadata';
import { ExecutionContext, UnauthorizedException, ForbiddenException } from '@nestjs/common';
import { AuthGuard } from './auth.guard';

// Minimal stubs to avoid instantiating real NestJS services
const mockLogger = {
  logWithMeta: jest.fn(),
} as unknown as import('../logger/wally-logger.service').WallyLoggerService;

const mockSupabase = {
  client: {
    from: jest.fn().mockReturnValue({
      select: jest.fn().mockReturnValue({
        eq: jest.fn().mockReturnValue({
          eq: jest.fn().mockReturnValue({
            single: jest.fn().mockResolvedValue({ data: null, error: { message: 'not found' } }),
          }),
          single: jest.fn().mockResolvedValue({ data: null, error: { message: 'not found' } }),
        }),
        single: jest.fn().mockResolvedValue({ data: null, error: { message: 'not found' } }),
      }),
    }),
  },
} as unknown as import('../../supabase/supabase.service').SupabaseService;

function makeContext(headers: Record<string, string>, body: Record<string, unknown> = {}, skipValidation = true) {
  const configService = {
    get: jest.fn().mockReturnValue(skipValidation),
  };

  const guard = new AuthGuard(
    configService as unknown as import('@nestjs/config').ConfigService,
    mockSupabase,
    mockLogger,
  );

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

  it('allows request with valid headers in dev mode', async () => {
    const { guard, context } = makeContext({
      'x-site-id': 'site-001',
      'x-license-key': 'key-abc',
    });
    await expect(guard.canActivate(context)).resolves.toBe(true);
  });

  it('attaches siteId and licenseKey to the request', async () => {
    const { guard, context, request } = makeContext({
      'x-site-id': 'site-001',
      'x-license-key': 'key-abc',
    });
    await guard.canActivate(context);
    expect((request as Record<string, unknown>)['siteId']).toBe('site-001');
    expect((request as Record<string, unknown>)['licenseKey']).toBe('key-abc');
  });

  it('reads credentials from request body when headers are absent', async () => {
    const { guard, context, request } = makeContext(
      {},
      { site_id: 'site-body', license_key: 'key-body' },
    );
    await guard.canActivate(context);
    expect((request as Record<string, unknown>)['siteId']).toBe('site-body');
  });

  it('throws UnauthorizedException when site_id is missing', async () => {
    const { guard, context } = makeContext({ 'x-license-key': 'key-abc' });
    await expect(guard.canActivate(context)).rejects.toThrow(UnauthorizedException);
  });

  it('throws UnauthorizedException when license_key is missing', async () => {
    const { guard, context } = makeContext({ 'x-site-id': 'site-001' });
    await expect(guard.canActivate(context)).rejects.toThrow(UnauthorizedException);
  });

  it('throws ForbiddenException when license key not found in production mode', async () => {
    const { guard, context } = makeContext(
      { 'x-site-id': 'site-001', 'x-license-key': 'key-abc' },
      {},
      false, // skipValidation = false → production mode
    );
    await expect(guard.canActivate(context)).rejects.toThrow(ForbiddenException);
  });
});
