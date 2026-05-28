<?php

namespace App\Tests;

use App\Document\TestSession;
use App\Document\User;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Classe de base abstraite pour tous les tests fonctionnels de l'API.
 *
 * Fournit l'infrastructure commune nécessaire à tous les tests :
 * - Un client HTTP simulé pour appeler les endpoints Symfony
 * - Un accès direct au DocumentManager MongoDB
 * - Des helpers pour créer des utilisateurs, des sessions et se connecter
 * - Une réinitialisation complète de la base de données avant chaque test
 *
 * Toutes les classes de test concrètes (AuthControllerTest,
 * SessionControllerTest, ReservationControllerTest...) héritent
 * de cette classe pour bénéficier de cette infrastructure partagée.
 */
abstract class ApiTestCase extends WebTestCase
{
    /**
     * Client HTTP Symfony utilisé pour envoyer des requêtes
     * aux endpoints de l'API durant les tests.
     */
    protected KernelBrowser $client;

    /**
     * Gestionnaire de documents MongoDB, utilisé pour manipuler
     * directement la base de données dans les tests (création
     * d'entités, requêtes, vérifications de persistance).
     */
    protected DocumentManager $dm;

    /**
     * Initialise l'environnement avant chaque test.
     *
     * - Crée un client HTTP frais pour isoler les tests entre eux
     * - Récupère le DocumentManager depuis le container Symfony
     * - Vide TOUTE la base MongoDB de test pour garantir un état propre
     * - Vide la mémoire interne du DocumentManager (cache d'entités)
     */
    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->dm = static::getContainer()->get('test.doctrine_mongodb.odm.document_manager');

        // Vider TOUTES les collections de la DB de test
        $database = $this->dm->getDocumentDatabase(\App\Document\User::class);
        $database->drop();

        // Vider aussi la mémoire interne du DocumentManager
        $this->dm->clear();
    }

    /**
     * Crée et persiste un utilisateur en base avec un mot de passe hashé.
     *
     * Utilise le service de hashing officiel de Symfony (bcrypt) pour
     * respecter le mécanisme de sécurité réel de l'application, ce qui
     * permet ensuite à la méthode login() de fonctionner correctement.
     *
     * @param string $email    Email de l'utilisateur (unique en base)
     * @param string $password Mot de passe en clair (sera hashé)
     * @param string $name     Nom affiché de l'utilisateur
     *
     * @return User L'utilisateur créé, déjà persisté en base
     */
    protected function createUser(string $email = 'test@test.com', string $password = 'password123', string $name = 'Test User'): User
    {
        $hasher = static::getContainer()->get('test.password_hasher');

        $user = new User();
        $user->setEmail($email);
        $user->setName($name);
        $user->setPassword($hasher->hashPassword($user, $password));

        $this->dm->persist($user);
        $this->dm->flush();

        return $user;
    }

    /**
     * Crée et persiste une session de test en base.
     *
     * Par défaut, la session est programmée pour le lendemain à 10h
     * à Paris, avec 10 places disponibles sur 10 au total. Les paramètres
     * permettent de simuler facilement différents scénarios (session
     * pleine, session avec des places réservées, etc.).
     *
     * @param string $language       Langue du test (English, Spanish, etc.)
     * @param int    $availableSeats Places encore disponibles
     * @param int    $totalSeats     Capacité totale de la session
     *
     * @return TestSession La session créée, déjà persistée en base
     */
    protected function createSession(string $language = 'English', int $availableSeats = 10, int $totalSeats = 10): TestSession
    {
        $session = new TestSession();
        $session->setLanguage($language);
        $session->setDate(new \DateTimeImmutable('+1 day'));
        $session->setTime('10:00');
        $session->setLocation('Paris');
        $session->setTotalSeats($totalSeats);
        $session->setAvailableSeats($availableSeats);

        $this->dm->persist($session);
        $this->dm->flush();

        return $session;
    }

    /**
     * Authentifie un utilisateur via /api/login et retourne son token JWT.
     *
     * Lève une exception explicite si le login échoue, ce qui rend
     * les erreurs de configuration des tests faciles à diagnostiquer
     * (au lieu de retourner null silencieusement).
     *
     * @param string $email    Email de l'utilisateur à connecter
     * @param string $password Mot de passe en clair
     *
     * @return string Le token JWT à utiliser pour les requêtes authentifiées
     *
     * @throws \RuntimeException Si le login échoue (mauvais credentials, etc.)
     */
    protected function login(string $email = 'test@test.com', string $password = 'password123'): string
    {
        $this->client->request(
            'POST',
            '/api/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['email' => $email, 'password' => $password])
        );

        $data = json_decode($this->client->getResponse()->getContent(), true);

        if (!isset($data['token'])) {
            throw new \RuntimeException(sprintf(
                'Login failed for %s. Status: %d, Response: %s',
                $email,
                $this->client->getResponse()->getStatusCode(),
                $this->client->getResponse()->getContent()
            ));
        }

        return $data['token'];
    }

    /**
     * Envoie une requête HTTP JSON à l'API et retourne la réponse parsée.
     *
     * Helper central qui simplifie l'écriture des tests en gérant :
     * - Le header Content-Type pour JSON
     * - L'authentification Bearer optionnelle via le token JWT
     * - L'encodage JSON du corps de requête
     * - Le décodage de la réponse JSON
     *
     * @param string      $method Méthode HTTP (GET, POST, PUT, DELETE)
     * @param string      $url    URL de l'endpoint (ex: '/api/sessions')
     * @param string|null $token  JWT optionnel pour les routes protégées
     * @param array       $body   Corps de la requête (sera converti en JSON)
     *
     * @return array{status: int, data: mixed} Statut HTTP et données décodées
     */
    protected function jsonRequest(string $method, string $url, ?string $token = null, array $body = []): array
    {
        $server = ['CONTENT_TYPE' => 'application/json'];
        if ($token) {
            $server['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
        }

        $this->client->request(
            $method,
            $url,
            server: $server,
            content: empty($body) ? null : json_encode($body)
        );

        return [
            'status' => $this->client->getResponse()->getStatusCode(),
            'data' => json_decode($this->client->getResponse()->getContent(), true),
        ];
    }
}