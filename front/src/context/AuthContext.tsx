'use client';

import { createContext, useContext, useEffect, useState, ReactNode } from 'react';
import { useRouter } from 'next/navigation';
import { api } from '@/lib/api';

interface User {
  id: string;
  email: string;
  name: string;
  roles: string[];
}

interface AuthContextType {
  user: User | null;
  loading: boolean;
  login: (email: string, password: string) => Promise<void>;
  logout: () => void;
  refreshUser: () => Promise<void>;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);
  const router = useRouter();

  // Au chargement initial, on tente de récupérer l'user via le token stocké
  useEffect(() => {
    const token = localStorage.getItem('jwt_token');
    if (token) {
      refreshUser().finally(() => setLoading(false));
    } else {
      setLoading(false);
    }
  }, []);

  async function refreshUser() {
    try {
      const res = await api.get('/api/me');
      setUser(res.data);
    } catch {
      setUser(null);
      localStorage.removeItem('jwt_token');
    }
  }

  async function login(email: string, password: string) {
    const res = await api.post('/api/login', { email, password });
    localStorage.setItem('jwt_token', res.data.token);
    await refreshUser();
    router.push('/sessions');
  }

  function logout() {
    localStorage.removeItem('jwt_token');
    setUser(null);
    router.push('/login');
  }

  return (
    <AuthContext.Provider value={{ user, loading, login, logout, refreshUser }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth must be used within an AuthProvider');
  return ctx;
}