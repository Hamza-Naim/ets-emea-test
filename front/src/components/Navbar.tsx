'use client';

import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { useAuth } from '@/context/AuthContext';

export default function Navbar() {
  const { user, logout } = useAuth();
  const pathname = usePathname();

  if (!user) return null;

  const linkClass = (path: string) =>
    `px-3 py-2 rounded-lg text-sm font-medium transition ${
      pathname === path
        ? 'bg-blue-100 text-blue-700'
        : 'text-gray-600 hover:bg-gray-100'
    }`;

  return (
    <nav className="bg-white shadow-sm border-b border-gray-200">
      <div className="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
        <div className="flex items-center gap-6">
          <Link href="/sessions" className="text-xl font-bold text-gray-800">
            ETS EMEA
          </Link>
          <div className="flex gap-1">
            <Link href="/sessions" className={linkClass('/sessions')}>
              Sessions
            </Link>
            <Link href="/reservations" className={linkClass('/reservations')}>
              Mes réservations
            </Link>
              <Link href="/admin/sessions" className={linkClass('/admin/sessions')}>
              Admin
            </Link>
            <Link href="/account" className={linkClass('/account')}>
              Mon compte
            </Link>
          </div>
        </div>
        <div className="flex items-center gap-3">
          <span className="text-sm text-gray-600 hidden sm:inline">
            {user.name}
          </span>
          <button
            onClick={logout}
            className="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm font-medium"
          >
            Déconnexion
          </button>
        </div>
      </div>
    </nav>
  );
}