import { api } from '@/lib/api';

/**
 * Tests du client API (axios) qui gère la communication
 * entre le frontend Next.js et le backend Symfony.
 *
 * Vérifie la configuration de base et le bon fonctionnement
 * de l'intercepteur qui attache automatiquement le JWT
 * stocké dans localStorage à chaque requête sortante.
 */
describe('API client', () => {
  /**
   * Vide le localStorage avant chaque test pour garantir
   * un état propre et éviter les interférences entre tests
   * (un token résiduel pourrait fausser les résultats).
   */
  beforeEach(() => {
    localStorage.clear();
  });

  /**
   * Vérifie que le client axios a bien une URL de base
   * configurée. C'est essentiel pour que tous les appels
   * relatifs (ex: '/api/sessions') soient préfixés correctement.
   */
  it('should have a base URL configured', () => {
    expect(api.defaults.baseURL).toBeTruthy();
  });

  /**
   * Vérifie que le header Content-Type est configuré à
   * 'application/json' par défaut, ce qui permet au backend
   * Symfony de parser correctement les requêtes POST/PUT.
   */
  it('should set Content-Type to application/json', () => {
    expect(api.defaults.headers['Content-Type']).toBe('application/json');
  });

  /**
   * Vérifie que l'intercepteur de requêtes ajoute bien le header
   * "Authorization: Bearer <token>" quand un JWT est présent
   * dans le localStorage. C'est ce mécanisme qui permet aux
   * appels d'être authentifiés automatiquement.
   */
  it('should attach JWT token from localStorage to requests', async () => {
    localStorage.setItem('jwt_token', 'fake-token-123');

    const handler = (api.interceptors.request as any).handlers[0];
    const config = await handler.fulfilled({ headers: {} });

    expect(config.headers.Authorization).toBe('Bearer fake-token-123');
  });

  /**
   * Vérifie qu'aucun header Authorization n'est ajouté quand
   * aucun JWT n'est stocké. C'est important pour ne pas envoyer
   * de header vide qui pourrait être mal interprété par le backend.
   */
  it('should not attach Authorization header when no token', async () => {
    const handler = (api.interceptors.request as any).handlers[0];
    const config = await handler.fulfilled({ headers: {} });

    expect(config.headers.Authorization).toBeUndefined();
  });
});