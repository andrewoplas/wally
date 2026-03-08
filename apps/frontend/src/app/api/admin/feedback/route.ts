import { NextRequest, NextResponse } from 'next/server';
import { getFeedbackControllerListFeedbackUrl } from '@wally/api-client';
import type { FeedbackControllerListFeedbackParams } from '@wally/api-client';
import { validateAdminToken } from '../auth/route';

export async function GET(req: NextRequest) {
  const token = req.headers.get('x-admin-token') ?? '';
  if (!validateAdminToken(token)) {
    return NextResponse.json({ error: 'Unauthorized.' }, { status: 401 });
  }

  const backendUrl = process.env.BACKEND_URL;
  const adminApiKey = process.env.ADMIN_API_KEY;

  if (!backendUrl || !adminApiKey) {
    return NextResponse.json({ error: 'Admin not configured.' }, { status: 500 });
  }

  const { searchParams } = req.nextUrl;
  const params: Partial<FeedbackControllerListFeedbackParams> = {};
  if (searchParams.get('type')) params.type = searchParams.get('type')!;
  if (searchParams.get('source')) params.source = searchParams.get('source')!;
  if (searchParams.get('category')) params.category = searchParams.get('category')!;
  if (searchParams.get('limit')) params.limit = searchParams.get('limit')!;
  if (searchParams.get('offset')) params.offset = searchParams.get('offset')!;

  // Orval URL builder produces '/api/v1/feedback?...'
  // BACKEND_URL already includes '/api', so strip the leading '/api' from the generated path
  const orvalPath = getFeedbackControllerListFeedbackUrl(
    params as FeedbackControllerListFeedbackParams,
  );
  const url = `${backendUrl}${orvalPath.replace(/^\/api/, '')}`;

  try {
    const res = await fetch(url, { headers: { 'X-Admin-Key': adminApiKey } });

    if (!res.ok) {
      console.error('Backend admin feedback error:', res.status, await res.text());
      return NextResponse.json({ error: 'Failed to fetch feedback.' }, { status: 502 });
    }

    return NextResponse.json(await res.json());
  } catch (err) {
    console.error('Admin feedback proxy error:', err);
    return NextResponse.json({ error: 'Internal error.' }, { status: 500 });
  }
}
