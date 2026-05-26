'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/context/AuthContext';
import {
  fetchSessions,
  createSession,
  updateSession,
  deleteSession,
  type TestSession,
  type SessionsResponse,
  type SessionInput,
} from '@/lib/sessions';
import Navbar from '@/components/Navbar';
import SessionForm from '@/components/SessionForm';
import ConfirmModal from '@/components/ConfirmModal';

export default function AdminSessionsPage() {
  const { user, loading: authLoading } = useAuth();
  const router = useRouter();

  const [data, setData] = useState<SessionsResponse | null>(null);
  const [page, setPage] = useState(1);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  const [editing, setEditing] = useState<TestSession | null>(null);
  const [showForm, setShowForm] = useState(false);
  const [toDelete, setToDelete] = useState<TestSession | null>(null);

  useEffect(() => {
    if (!authLoading && !user) router.replace('/login');
  }, [user, authLoading, router]);

  async function refresh() {
    setLoading(true);
    try {
      const fresh = await fetchSessions(page, 10);
      setData(fresh);
    } catch (e: any) {
      setError(e?.response?.data?.message || 'Erreur de chargement');
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    if (user) refresh();
  }, [user, page]);

  async function handleSubmit(input: SessionInput) {
    setError(null);
    setSuccess(null);
    if (editing) {
      await updateSession(editing.id, input);
      setSuccess('Session modifiée !');
    } else {
      await createSession(input);
      setSuccess('Session créée !');
    }
    setShowForm(false);
    setEditing(null);
    await refresh();
  }

  async function confirmDelete() {
    if (!toDelete) return;
    const id = toDelete.id;
    setToDelete(null);
    setError(null);
    try {
      await deleteSession(id);
      setSuccess('Session supprimée');
      await refresh();
    } catch (e: any) {
      setError(e?.response?.data?.error || 'Erreur lors de la suppression');
    }
  }

  if (authLoading || !user) {
    return <div className="p-8 text-center text-gray-500">Chargement...</div>;
  }

  return (
    <>
      <Navbar />
      <main className="max-w-6xl mx-auto p-6">
        <div className="flex items-center justify-between mb-6">
          <div>
            <h1 className="text-2xl font-bold text-gray-800">Administration des sessions</h1>
            <p className="text-gray-500 text-sm mt-1">
              Créer, modifier et supprimer les sessions de tests
            </p>
          </div>
          <button
            onClick={() => {
              setEditing(null);
              setShowForm(true);
            }}
            className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg"
          >
            + Nouvelle session
          </button>
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

        {loading && <div className="text-gray-500">Chargement...</div>}

        {data && data.data.length > 0 && (
          <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <table className="w-full">
              <thead className="bg-gray-50 border-b border-gray-200">
                <tr>
                  <th className="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-4 py-3">Langue</th>
                  <th className="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-4 py-3">Date</th>
                  <th className="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-4 py-3">Heure</th>
                  <th className="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-4 py-3">Lieu</th>
                  <th className="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider px-4 py-3">Places</th>
                  <th className="text-right text-xs font-semibold text-gray-600 uppercase tracking-wider px-4 py-3">Actions</th>
                </tr>
              </thead>
              <tbody>
                {data.data.map((s) => (
                  <tr key={s.id} className="border-b border-gray-100 hover:bg-gray-50">
                    <td className="px-4 py-3 font-medium text-gray-800">{s.language}</td>
                    <td className="px-4 py-3 text-gray-600">
                      {new Date(s.date).toLocaleDateString('fr-FR')}
                    </td>
                    <td className="px-4 py-3 text-gray-600">{s.time}</td>
                    <td className="px-4 py-3 text-gray-600">{s.location}</td>
                    <td className="px-4 py-3 text-gray-600">
                      {s.availableSeats} / {s.totalSeats}
                    </td>
                    <td className="px-4 py-3 text-right space-x-2">
                      <button
                        onClick={() => {
                          setEditing(s);
                          setShowForm(true);
                        }}
                        className="px-3 py-1 bg-blue-50 hover:bg-blue-100 text-blue-700 text-sm font-medium rounded-lg"
                      >
                        Modifier
                      </button>
                      <button
                        onClick={() => setToDelete(s)}
                        className="px-3 py-1 bg-red-50 hover:bg-red-100 text-red-700 text-sm font-medium rounded-lg"
                      >
                        Supprimer
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>

            <div className="flex items-center justify-center gap-2 py-4 bg-gray-50">
              <button
                onClick={() => setPage((p) => Math.max(1, p - 1))}
                disabled={page === 1}
                className="px-3 py-1 bg-white border border-gray-300 rounded-lg disabled:opacity-50 text-sm"
              >
                ← Précédent
              </button>
              <span className="text-sm text-gray-600 px-3">
                Page {data.page} / {data.pages}
              </span>
              <button
                onClick={() => setPage((p) => Math.min(data.pages, p + 1))}
                disabled={page >= data.pages}
                className="px-3 py-1 bg-white border border-gray-300 rounded-lg disabled:opacity-50 text-sm"
              >
                Suivant →
              </button>
            </div>
          </div>
        )}
      </main>

      {showForm && (
        <SessionForm
          session={editing}
          onSubmit={handleSubmit}
          onCancel={() => {
            setShowForm(false);
            setEditing(null);
          }}
        />
      )}

      <ConfirmModal
        open={!!toDelete}
        title="Supprimer cette session ?"
        message={
          toDelete
            ? `Voulez-vous vraiment supprimer la session ${toDelete.language} du ${new Date(toDelete.date).toLocaleDateString('fr-FR')} ? Cette action est irréversible.`
            : ''
        }
        confirmText="Supprimer"
        cancelText="Annuler"
        variant="danger"
        onConfirm={confirmDelete}
        onCancel={() => setToDelete(null)}
      />
    </>
  );
}