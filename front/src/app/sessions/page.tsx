'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/context/AuthContext';
import { fetchSessions, type TestSession, type SessionsResponse } from '@/lib/sessions';
import { createReservation } from '@/lib/reservations';
import Navbar from '@/components/Navbar';

export default function SessionsPage() {
  const { user, loading: authLoading } = useAuth();
  const router = useRouter();

  const [data, setData] = useState<SessionsResponse | null>(null);
  const [page, setPage] = useState(1);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [reservingId, setReservingId] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  useEffect(() => {
    if (!authLoading && !user) {
      router.replace('/login');
    }
  }, [user, authLoading, router]);

  useEffect(() => {
    if (!user) return;
    setLoading(true);
    fetchSessions(page, 10)
      .then(setData)
      .catch((e) => setError(e?.response?.data?.message || 'Erreur de chargement'))
      .finally(() => setLoading(false));
  }, [user, page]);

  async function handleReserve(sessionId: string) {
    setReservingId(sessionId);
    setError(null);
    setSuccess(null);
    try {
      await createReservation(sessionId);
      setSuccess('Réservation effectuée !');
      const fresh = await fetchSessions(page, 10);
      setData(fresh);
    } catch (e: any) {
      setError(e?.response?.data?.error || 'Erreur lors de la réservation');
    } finally {
      setReservingId(null);
    }
  }

  if (authLoading || !user) {
    return <div className="p-8 text-center text-gray-500">Chargement...</div>;
  }

  return (
    <>
      <Navbar />
      <main className="max-w-6xl mx-auto p-6">
        <div className="mb-6">
          <h1 className="text-2xl font-bold text-gray-800">Sessions disponibles</h1>
          <p className="text-gray-500 text-sm mt-1">
            Choisissez une session de test à réserver
          </p>
        </div>

        {success && (
          <div className="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4">
            ✅ {success}
          </div>
        )}

        {error && (
          <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
            ❌ {error}
          </div>
        )}

        {loading && <div className="text-gray-500">Chargement des sessions...</div>}

        {data && data.data.length === 0 && !loading && (
          <div className="bg-gray-100 p-8 text-center rounded-lg text-gray-500">
            Aucune session disponible pour le moment.
          </div>
        )}

        {data && data.data.length > 0 && (
          <>
            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
              {data.data.map((s) => (
                <SessionCard
                  key={s.id}
                  session={s}
                  onReserve={() => handleReserve(s.id)}
                  isReserving={reservingId === s.id}
                />
              ))}
            </div>

            <div className="flex items-center justify-center gap-2 mt-8">
              <button
                onClick={() => setPage((p) => Math.max(1, p - 1))}
                disabled={page === 1}
                className="px-4 py-2 bg-white border border-gray-300 rounded-lg disabled:opacity-50 hover:bg-gray-50"
              >
                ← Précédent
              </button>
              <span className="text-sm text-gray-600 px-4">
                Page {data.page} / {data.pages}
              </span>
              <button
                onClick={() => setPage((p) => Math.min(data.pages, p + 1))}
                disabled={page >= data.pages}
                className="px-4 py-2 bg-white border border-gray-300 rounded-lg disabled:opacity-50 hover:bg-gray-50"
              >
                Suivant →
              </button>
            </div>
          </>
        )}
      </main>
    </>
  );
}

function SessionCard({
  session,
  onReserve,
  isReserving,
}: {
  session: TestSession;
  onReserve: () => void;
  isReserving: boolean;
}) {
  const noSeats = session.availableSeats <= 0;
  const lowSeats = session.availableSeats > 0 && session.availableSeats <= 3;

  return (
    <div className="bg-white rounded-xl shadow-sm border border-gray-200 p-5 hover:shadow-md transition">
      <div className="flex items-center justify-between mb-3">
        <h3 className="font-bold text-lg text-gray-800">{session.language}</h3>
        <span
          className={`text-xs px-2 py-1 rounded-full font-medium ${
            noSeats
              ? 'bg-red-100 text-red-700'
              : lowSeats
              ? 'bg-orange-100 text-orange-700'
              : 'bg-green-100 text-green-700'
          }`}
        >
          {noSeats ? 'Complet' : `${session.availableSeats}/${session.totalSeats} places`}
        </span>
      </div>
      <div className="text-sm text-gray-600 space-y-1 mb-4">
        <p>📅 {new Date(session.date).toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })}</p>
        <p>🕒 {session.time}</p>
        <p>📍 {session.location}</p>
      </div>
      <button
        onClick={onReserve}
        disabled={noSeats || isReserving}
        className="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-medium py-2 rounded-lg transition"
      >
        {isReserving ? 'Réservation...' : noSeats ? 'Indisponible' : 'Réserver'}
      </button>
    </div>
  );
}