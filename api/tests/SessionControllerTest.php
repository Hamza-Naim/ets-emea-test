<?php

namespace App\Tests;

class SessionControllerTest extends ApiTestCase
{
    public function testListRequiresAuth(): void
    {
        $response = $this->jsonRequest('GET', '/api/sessions');
        $this->assertSame(401, $response['status']);
    }

    public function testListReturnsPaginatedResults(): void
    {
        $this->createUser();
        for ($i = 0; $i < 15; $i++) {
            $this->createSession("Lang$i");
        }

        $token = $this->login();
        $response = $this->jsonRequest('GET', '/api/sessions?page=1&limit=10', $token);

        $this->assertSame(200, $response['status']);
        $this->assertSame(15, $response['data']['total']);
        $this->assertCount(10, $response['data']['data']);
    }

    public function testCreateSession(): void
    {
        $this->createUser();
        $token = $this->login();

        $response = $this->jsonRequest('POST', '/api/sessions', $token, [
            'language' => 'Japanese',
            'date' => '2026-12-15',
            'time' => '14:00',
            'location' => 'Tokyo',
            'totalSeats' => 12,
        ]);

        $this->assertSame(201, $response['status']);
        $this->assertSame(12, $response['data']['availableSeats']);
    }

    public function testUpdateSessionChangesData(): void
    {
        $this->createUser();
        $session = $this->createSession('English', 10, 10);
        $token = $this->login();

        // Modifier la session
        $response = $this->jsonRequest('PUT', '/api/sessions/' . $session->getId(), $token, [
            'language' => 'Spanish',
            'location' => 'Madrid',
            'time' => '15:30',
        ]);

        $this->assertSame(200, $response['status']);
        $this->assertSame('Spanish', $response['data']['language']);
        $this->assertSame('Madrid', $response['data']['location']);
        $this->assertSame('15:30', $response['data']['time']);

        // Vérifier que les changements sont bien persistés en base
        $this->dm->clear();
        $refreshed = $this->dm->getRepository(\App\Document\TestSession::class)->find($session->getId());
        $this->assertSame('Spanish', $refreshed->getLanguage());
        $this->assertSame('Madrid', $refreshed->getLocation());
    }

    public function testUpdateSessionPreservesReservedSeats(): void
    {
        // Crée une session avec 10 places, en réserve 2, donc 8 dispo
        $this->createUser();
        $session = $this->createSession('English', 8, 10);
        $token = $this->login();

        // Modifier la session pour avoir 15 places total
        $response = $this->jsonRequest('PUT', '/api/sessions/' . $session->getId(), $token, [
            'totalSeats' => 15,
        ]);

        $this->assertSame(200, $response['status']);
        // Maintenant on devrait avoir 13 places dispo (15 total - 2 déjà réservées)
        $this->assertSame(15, $response['data']['totalSeats']);
        $this->assertSame(13, $response['data']['availableSeats']);
    }
    public function testDeleteSession(): void
    {
        $this->createUser();
        $session = $this->createSession();
        $token = $this->login();

        $response = $this->jsonRequest('DELETE', '/api/sessions/' . $session->getId(), $token);
        $this->assertSame(200, $response['status']);
    }
    public function testDeleteSessionCascadesReservations(): void
    {
        // Setup : 2 users, 1 session, 2 réservations
        $alice = $this->createUser('alice@test.com', 'password123');
        $bob = $this->createUser('bob@test.com', 'password123');
        $session = $this->createSession('French', 10);

        $aliceToken = $this->login('alice@test.com', 'password123');
        $bobToken = $this->login('bob@test.com', 'password123');

        // Alice et Bob réservent la session
        $aliceRes = $this->jsonRequest('POST', '/api/reservations', $aliceToken, [
            'sessionId' => $session->getId(),
        ]);
        $bobRes = $this->jsonRequest('POST', '/api/reservations', $bobToken, [
            'sessionId' => $session->getId(),
        ]);
        $this->assertSame(201, $aliceRes['status']);
        $this->assertSame(201, $bobRes['status']);

        // Vérifier qu'il y a 2 réservations en base
        $this->dm->clear();
        $countBefore = $this->dm->getRepository(\App\Document\Reservation::class)
            ->createQueryBuilder()
            ->count()
            ->getQuery()
            ->execute();
        $this->assertSame(2, $countBefore);

        // Supprimer la session
        $deleteResponse = $this->jsonRequest('DELETE', '/api/sessions/' . $session->getId(), $aliceToken);
        $this->assertSame(200, $deleteResponse['status']);
        $this->assertSame(2, $deleteResponse['data']['cancelledReservations']);

        // Vérifier que les 2 réservations sont supprimées
        $this->dm->clear();
        $countAfter = $this->dm->getRepository(\App\Document\Reservation::class)
            ->createQueryBuilder()
            ->count()
            ->getQuery()
            ->execute();
        $this->assertSame(0, $countAfter);

        // Vérifier qu'Alice ne voit plus sa réservation
        $aliceReservations = $this->jsonRequest('GET', '/api/reservations', $aliceToken);
        $this->assertSame(0, $aliceReservations['data']['count']);

        // Idem pour Bob
        $bobReservations = $this->jsonRequest('GET', '/api/reservations', $bobToken);
        $this->assertSame(0, $bobReservations['data']['count']);
    }
}