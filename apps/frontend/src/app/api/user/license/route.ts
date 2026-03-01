import { NextResponse } from 'next/server';
import { createClient } from '@/lib/supabase/server';

export async function GET() {
  const supabase = await createClient();
  const { data: { session } } = await supabase.auth.getSession();

  if (!session) {
    return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });
  }

  const res = await fetch(`${process.env.BACKEND_URL}/v1/user/license`, {
    headers: { Authorization: `Bearer ${session.access_token}` },
  });

  const data = await res.json();
  return NextResponse.json(data, { status: res.status });
}
