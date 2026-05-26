import { api } from './api';
import type { TestSession } from './sessions';

export interface Reservation {
  id: string;
  reservedAt: string;
  session: Pick<TestSession, 'id' | 'language' | 'date' | 'time' | 'location'> | null;
}

export interface ReservationsResponse {
  count: number;
  data: Reservation[];
}

export async function fetchMyReservations(): Promise<ReservationsResponse> {
  const res = await api.get<ReservationsResponse>('/api/reservations');
  return res.data;
}

export async function createReservation(sessionId: string): Promise<Reservation> {
  const res = await api.post<Reservation>('/api/reservations', { sessionId });
  return res.data;
}

export async function cancelReservation(reservationId: string): Promise<void> {
  await api.delete(`/api/reservations/${reservationId}`);
}