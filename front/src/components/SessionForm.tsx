'use client';

import { useEffect, useState, FormEvent } from 'react';
import type { TestSession, SessionInput } from '@/lib/sessions';

interface Props {
  session?: TestSession | null;
  onSubmit: (data: SessionInput) => Promise<void>;
  onCancel: () => void;
}

export default function SessionForm({ session, onSubmit, onCancel }: Props) {
  const [language, setLanguage] = useState('');
  const [date, setDate] = useState('');
  const [time, setTime] = useState('');
  const [location, setLocation] = useState('');
  const [totalSeats, setTotalSeats] = useState(10);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (session) {
      setLanguage(session.language);
      setDate(session.date);
      setTime(session.time);
      setLocation(session.location);
      setTotalSeats(session.totalSeats);
    } else {
      setLanguage('');
      setDate('');
      setTime('');
      setLocation('');
      setTotalSeats(10);
    }
  }, [session]);

  async function handleSubmit(e: FormEvent) {
    e.preventDefault();
    setError(null);
    setSubmitting(true);
    try {
      await onSubmit({ language, date, time, location, totalSeats });
    } catch (err: any) {
      setError(err?.response?.data?.error || 'Erreur lors de l\'enregistrement');
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
      onClick={onCancel}
    >
      <form
        onSubmit={handleSubmit}
        onClick={(e) => e.stopPropagation()}
        className="bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6 space-y-4"
      >
        <h2 className="text-xl font-bold text-gray-800">
          {session ? 'Modifier la session' : 'Nouvelle session'}
        </h2>

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">Langue</label>
          <input
            type="text"
            value={language}
            onChange={(e) => setLanguage(e.target.value)}
            required
            placeholder="Anglais, Français, Espagnol..."
            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"
          />
        </div>

        <div className="grid grid-cols-2 gap-3">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Date</label>
            <input
              type="date"
              value={date}
              onChange={(e) => setDate(e.target.value)}
              required
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Heure</label>
            <input
              type="time"
              value={time}
              onChange={(e) => setTime(e.target.value)}
              required
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"
            />
          </div>
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">Lieu</label>
          <input
            type="text"
            value={location}
            onChange={(e) => setLocation(e.target.value)}
            required
            placeholder="Paris, Londres, Casablanca..."
            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"
          />
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Nombre de places
          </label>
          <input
            type="number"
            value={totalSeats}
            onChange={(e) => setTotalSeats(parseInt(e.target.value) || 0)}
            required
            min={1}
            max={100}
            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none"
          />
        </div>

        {error && (
          <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-2 rounded-lg text-sm">
            {error}
          </div>
        )}

        <div className="flex gap-3 justify-end pt-2">
          <button
            type="button"
            onClick={onCancel}
            className="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg"
          >
            Annuler
          </button>
          <button
            type="submit"
            disabled={submitting}
            className="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white font-medium rounded-lg"
          >
            {submitting ? 'Enregistrement...' : session ? 'Modifier' : 'Créer'}
          </button>
        </div>
      </form>
    </div>
  );
}