import { NextRequest, NextResponse } from 'next/server';

const VALID_CATEGORIES = ['bug', 'feature', 'general'];

export async function POST(req: NextRequest) {
  try {
    const body = await req.json();
    const message = (body.message ?? '').trim();
    const category = (body.category ?? 'general').trim();
    const email = (body.email ?? '').trim();
    const name = (body.name ?? '').trim();

    if (!message) {
      return NextResponse.json(
        { error: 'Message is required.' },
        { status: 400 },
      );
    }

    if (message.length > 5000) {
      return NextResponse.json(
        { error: 'Message is too long (max 5000 characters).' },
        { status: 400 },
      );
    }

    if (!VALID_CATEGORIES.includes(category)) {
      return NextResponse.json(
        { error: 'Invalid category.' },
        { status: 400 },
      );
    }

    const backendUrl = process.env.BACKEND_URL;
    if (!backendUrl) {
      console.error('BACKEND_URL is not configured');
      return NextResponse.json(
        { error: 'Feedback is temporarily unavailable. Please try again later.' },
        { status: 500 },
      );
    }

    const res = await fetch(`${backendUrl}/v1/feedback`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        message,
        category,
        ...(email && { email }),
        ...(name && { name }),
      }),
    });

    if (!res.ok) {
      console.error('Backend feedback error:', res.status, await res.text());
      return NextResponse.json(
        { error: 'Something went wrong. Please try again.' },
        { status: 500 },
      );
    }

    return NextResponse.json({ success: true }, { status: 201 });
  } catch (err) {
    console.error('Feedback route error:', err);
    return NextResponse.json(
      { error: 'Invalid request.' },
      { status: 400 },
    );
  }
}
