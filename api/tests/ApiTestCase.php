<?php

namespace App\Tests;

use App\Document\TestSession;
use App\Document\User;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

abstract class ApiTestCase extends WebTestCase
{
    protected KernelBrowser $client;
    protected DocumentManager $dm;

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