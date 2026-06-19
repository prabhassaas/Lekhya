import { cookies } from 'next/headers';
import { redirect } from 'next/navigation';

interface UserData {
  id: string;
  name: string;
  email: string;
  role: string;
  tenant_id: string;
}

async function getUser(token: string): Promise<UserData | null> {
  const apiUrl = process.env.API_URL ?? 'http://localhost:3001';
  try {
    const res = await fetch(`${apiUrl}/auth/me`, {
      headers: { Authorization: `Bearer ${token}` },
      cache: 'no-store',
    });
    if (!res.ok) return null;
    return res.json();
  } catch {
    return null;
  }
}

export default async function DashboardPage() {
  const cookieStore = await cookies();
  const token = cookieStore.get('token')?.value;
  if (!token) redirect('/login');

  const user = await getUser(token);
  if (!user) redirect('/login');

  return (
    <main className="min-h-screen bg-gray-50 p-8">
      <div className="max-w-4xl mx-auto space-y-6">
        <div className="bg-white rounded-2xl shadow p-6">
          <h1 className="text-2xl font-bold text-gray-900">Welcome, {user.name}</h1>
          <p className="text-gray-500 mt-1">{user.email} &bull; {user.role}</p>
        </div>
        <div className="bg-white rounded-2xl shadow p-6">
          <h2 className="text-lg font-semibold text-gray-800 mb-2">Dashboard</h2>
          <p className="text-gray-500 text-sm">
            Accounting, GST, and AI features will appear here in upcoming phases.
          </p>
        </div>
      </div>
    </main>
  );
}
