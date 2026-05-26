'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/context/AuthContext';
import { fetchMyReservations, cancelReservation, type Reservation } from '@/lib/reservations';
import Navbar from '@/components/Navbar';
import ConfirmModal from '@/components/ConfirmModal';

export default function ReservationsPage() {
  const { user, loading: authLoading } = useAuth();
  const router = useRouter();

  const [reservations, setReservations] = useState<Reservation[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [cancelingId, setCancelingId] = useState<string | null>(null);
  const [toCancel, setToCancel] = useState<Reservation | null>(null);

  useEffect(() => {
    if (!authLoading && !user) router.replace('/login');
  }, [user, authLoading, router]);

  async function refresh() {
    setLoading(true);
    try {
      const res = await fetchMyReservations();
      setReservations(res.data);
    } catch (e: any) {
      setError(e?.response?.data?.message || 'Erreur de chargement');
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    if (user) refresh();
  }, [user]);

  async function confirmCancel() {
    if (!toCancel) return;
    const id = toCancel.id;
    setToCancel(null);
    setCancelingId(id);
    setError(null);
    try {
      await cancelReservation(id);
      setReservations((rs) => rs.filter((r) => r.id !== id));
    } catch (e: any) {
      setError(e?.response?.data?.error || 'Erreur lors de l\'annulation');
    } finally {
      setCancelingId(null);
    }
  }

  if (authLoading || !user) {
    return <div className="p-8 text-center text-gray-500">Chargement...</div>;
  }

  return (
    <>
      <Navbar />
      <main className="max-w-4xl mx-auto p-6">
        <div className="mb-6">
          <h1 className="text-2xl font-bold text-gray-800">Mes réservations</h1>
          <p className="text-gray-500 text-sm mt-1">
            {reservations.length} réservation{reservations.length > 1 ? 's' : ''}
          </p>
        </div>

        {error && (
          <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
            ❌ {error}
          </div>
        )}

        {loading && <div className="text-gray-500">Chargement...</div>}

        {!loading && reservations.length === 0 && (
          <div className="bg-white p-8 text-center rounded-xl shadow-sm border border-gray-200">
            <p className="text-gray-500 mb-4">Vous n'avez encore aucune réservation.</p>
            <a
              href="/sessions"
              className="inline-block px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg"
            >
              Voir les sessions disponibles
            </a>
          </div>
        )}

        {reservations.length > 0 && (
          <div className="space-y-3">
            {reservations.map((r) => (
              <div
                key={r.id}
                className="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex items-center justify-between"
              >
                <div>
                  <h3 className="font-bold text-gray-800">
                    {r.session?.language || 'Session supprimée'}
                  </h3>
                  {r.session && (
                    <p className="text-sm text-gray-600 mt-1">
                      📅 {new Date(r.session.date).toLocaleDateString('fr-FR')} à {r.session.time} · 📍 {r.session.location}
                    </p>
                  )}
                  <p className="text-xs text-gray-400 mt-2">
                    Réservée le {new Date(r.reservedAt).toLocaleDateString('fr-FR')}
                  </p>
                </div>
                <button
                  onClick={() => setToCancel(r)}
                  disabled={cancelingId === r.id}
                  className="px-4 py-2 bg-red-50 hover:bg-red-100 text-red-700 font-medium rounded-lg text-sm disabled:opacity-50"
                >
                  {cancelingId === r.id ? 'Annulation...' : 'Annuler'}
                </button>
              </div>
            ))}
          </div>
        )}
      </main>

      <ConfirmModal
        open={!!toCancel}
        title="Annuler la réservation ?"
        message={
          toCancel?.session
            ? `Voulez-vous vraiment annuler votre réservation pour ${toCancel.session.language} le ${new Date(toCancel.session.date).toLocaleDateString('fr-FR')} à ${toCancel.session.time} ?`
            : 'Voulez-vous vraiment annuler cette réservation ?'
        }
        confirmText="Oui, annuler"
        cancelText="Garder ma réservation"
        variant="danger"
        onConfirm={confirmCancel}
        onCancel={() => setToCancel(null)}
      />
    </>
  );
}