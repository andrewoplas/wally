import { NextRequest, NextResponse } from 'next/server';

function isValidEmail(email: string): boolean {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

export async function POST(req: NextRequest) {
  try {
    const body = await req.json();
    const email = (body.email ?? '').trim().toLowerCase();
    const source = (body.source ?? 'landing').trim();
    const challenges: string[] = Array.isArray(body.challenges) ? body.challenges : [];

    if (!email || !isValidEmail(email)) {
      return NextResponse.json(
        { error: 'Please provide a valid email address.' },
        { status: 400 }
      );
    }

    const apiKey = process.env.LOOPS_API_KEY;
    if (!apiKey) {
      console.error('LOOPS_API_KEY is not configured');
      return NextResponse.json(
        { error: 'Waitlist is temporarily unavailable. Please try again later.' },
        { status: 500 }
      );
    }

    const res = await fetch('https://app.loops.so/api/v1/contacts/create', {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${apiKey}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        email,
        source,
        userGroup: 'waitlist',
        ...(challenges.length > 0 && { challenges: challenges.join(', ') }),
      }),
    });

    if (!res.ok) {
      // Loops returns 409 for duplicate contacts — treat as success
      if (res.status === 409) {
        return NextResponse.json({ success: true });
      }
      console.error('Loops API error:', res.status, await res.text());
      return NextResponse.json(
        { error: 'Something went wrong. Please try again.' },
        { status: 500 }
      );
    }

    return NextResponse.json({ success: true }, { status: 201 });
  } catch (err) {
    console.error('Waitlist route error:', err);
    return NextResponse.json(
      { error: 'Invalid request.' },
      { status: 400 }
    );
  }
}
