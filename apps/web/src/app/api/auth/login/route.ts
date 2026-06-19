import { NextRequest, NextResponse } from 'next/server';

export async function POST(req: NextRequest) {
  const apiUrl = process.env.API_URL ?? 'http://localhost:3001';
  const body = await req.json();

  const res = await fetch(`${apiUrl}/auth/login`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
  });

  const data = await res.json();
  return NextResponse.json(data, { status: res.status });
}
