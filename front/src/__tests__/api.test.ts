import { api } from '@/lib/api';

describe('API client', () => {
  beforeEach(() => {
    localStorage.clear();
  });

  it('should have a base URL configured', () => {
    expect(api.defaults.baseURL).toBeTruthy();
  });

  it('should set Content-Type to application/json', () => {
    expect(api.defaults.headers['Content-Type']).toBe('application/json');
  });

  it('should attach JWT token from localStorage to requests', async () => {
    localStorage.setItem('jwt_token', 'fake-token-123');

    const handler = (api.interceptors.request as any).handlers[0];
    const config = await handler.fulfilled({ headers: {} });

    expect(config.headers.Authorization).toBe('Bearer fake-token-123');
  });

  it('should not attach Authorization header when no token', async () => {
    const handler = (api.interceptors.request as any).handlers[0];
    const config = await handler.fulfilled({ headers: {} });

    expect(config.headers.Authorization).toBeUndefined();
  });
});