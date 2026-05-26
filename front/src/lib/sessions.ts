import { api } from './api';

export interface TestSession {
  id: string;
  language: string;
  date: string;
  time: string;
  location: string;
  totalSeats: number;
  availableSeats: number;
}

export interface SessionsResponse {
  page: number;
  limit: number;
  total: number;
  pages: number;
  data: TestSession[];
}

export interface SessionInput {
  language: string;
  date: string;
  time: string;
  location: string;
  totalSeats: number;
}

export async function fetchSessions(page = 1, limit = 10): Promise<SessionsResponse> {
  const res = await api.get<SessionsResponse>('/api/sessions', {
    params: { page, limit },
  });
  return res.data;
}

export async function fetchSession(id: string): Promise<TestSession> {
  const res = await api.get<TestSession>(`/api/sessions/${id}`);
  return res.data;
}

export async function createSession(data: SessionInput): Promise<TestSession> {
  const res = await api.post<TestSession>('/api/sessions', data);
  return res.data;
}

export async function updateSession(id: string, data: Partial<SessionInput>): Promise<TestSession> {
  const res = await api.put<TestSession>(`/api/sessions/${id}`, data);
  return res.data;
}

export async function deleteSession(id: string): Promise<void> {
  await api.delete(`/api/sessions/${id}`);
}