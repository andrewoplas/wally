import { NextRequest, NextResponse } from 'next/server';
import { createHmac, timingSafeEqual } from 'crypto';

const SESSION_PAYLOAD = 'wally-admin-session';

export function computeAdminToken(password: string): string {
  return createHmac('sha256', password).update(SESSION_PAYLOAD).digest('hex');
}

export function validateAdminToken(token: string): boolean {
  const adminPassword = process.env.ADMIN_PASSWORD;
  if (!adminPassword || !token) return false;

  const expected = computeAdminToken(adminPassword);
  const a = Buffer.from(token);
  const b = Buffer.from(expected);
  return a.length === b.length && timingSafeEqual(a, b);
}

export async function POST(req: NextRequest) {
  try {
    const { password } = await req.json();
    const adminPassword = process.env.ADMIN_PASSWORD;

    if (!adminPassword) {
      return NextResponse.json({ error: 'Admin not configured.' }, { status: 500 });
    }

    if (!password || typeof password !== 'string') {
      return NextResponse.json({ error: 'Password required.' }, { status: 400 });
    }

    const a = Buffer.from(password);
    const b = Buffer.from(adminPassword);
    const valid = a.length === b.length && timingSafeEqual(a, b);

    if (!valid) {
      return NextResponse.json({ error: 'Invalid password.' }, { status: 401 });
    }

    return NextResponse.json({ token: computeAdminToken(adminPassword) });
  } catch {
    return NextResponse.json({ error: 'Invalid request.' }, { status: 400 });
  }
}
