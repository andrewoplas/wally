import { NextResponse } from 'next/server';
import { createClient } from '@/lib/supabase/server';

export async function GET() {
  const supabase = await createClient();
  const { data: { user } } = await supabase.auth.getUser();

  if (!user) {
    return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });
  }

  let { data: license } = await supabase
    .from('license_keys')
    .select('*')
    .eq('user_id', user.id)
    .maybeSingle();

  if (!license) {
    const newKey = `wally_live_sk_${crypto.randomUUID().replace(/-/g, '')}`;
    const { data: created } = await supabase
      .from('license_keys')
      .insert({ user_id: user.id, key: newKey, tier: 'free', max_sites: 1 })
      .select()
      .single();
    license = created;
  }

  if (!license) {
    return NextResponse.json({
      id: null, key: null, tier: 'free', max_sites: 1,
      expires_at: null, status: 'active', activated_count: 0,
    });
  }

  const { count } = await supabase
    .from('sites')
    .select('*', { count: 'exact', head: true })
    .eq('license_key_id', license.id)
    .eq('is_active', true);

  return NextResponse.json({ ...license, activated_count: count ?? 0 });
}
