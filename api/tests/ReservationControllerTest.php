<?php

namespace App\Tests;

use App\Document\TestSession;

class ReservationControllerTest extends ApiTestCase
{
    /**
     * Vérifie que la création d'une réservation décrémente correctement
     * le nombre de places disponibles de la session associée.
     * Avec 5 places initiales et 1 réservation, il doit rester 4 places.
     */
    public function testCreateReservationDecrementsSeats(): void
    {
        $this->createUser();
        $session = $this->createSession('Spanish', 5);
        $token = $this->login();

        $response = $this->jsonRequest('POST', '/api/reservations', $token, [
            'sessionId' => $session->getId(),
        ]);

        $this->assertSame(201, $response['status']);

        $this->dm->clear();
        $refreshed = $this->dm->getRepository(TestSession::class)->find($session->getId());
        $this->assertSame(4, $refreshed->getAvailableSeats());
    }

    /**
     * Vérifie qu'un utilisateur ne peut pas réserver deux fois
     * la même session. La première réservation réussit (201),
     * la seconde tentative est rejetée (409 Conflict).
     */
    public function testCannotBookSameSessionTwice(): void
    {
        $this->createUser();
        $session = $this->createSession();
        $token = $this->login();
        $body = ['sessionId' => $session->getId()];

        $first = $this->jsonRequest('POST', '/api/reservations', $token, $body);
        $this->assertSame(201, $first['status']);

        $second = $this->jsonRequest('POST', '/api/reservations', $token, $body);
        $this->assertSame(409, $second['status']);
    }

    /**
     * Vérifie qu'une réservation est refusée lorsque la session n'a
     * plus de places disponibles (0 sièges restants). Le serveur
     * retourne un 409 Conflict.
     */
    public function testCannotBookWhenNoSeatsAvailable(): void
    {
        $this->createUser();
        $session = $this->createSession('English', 0);
        $token = $this->login();

        $response = $this->jsonRequest('POST', '/api/reservations', $token, [
            'sessionId' => $session->getId(),
        ]);

        $this->assertSame(409, $response['status']);
    }

    /**
     * Vérifie que l'annulation d'une réservation libère bien la place
     * dans la session associée. La place doit revenir à son niveau
     * initial une fois la réservation supprimée.
     */
    public function testCancelReservationFreesUpSeat(): void
    {
        $this->createUser();
        $session = $this->createSession('French', 10);
        $token = $this->login();

        $create = $this->jsonRequest('POST', '/api/reservations', $token, [
            'sessionId' => $session->getId(),
        ]);
        $reservationId = $create['data']['id'];

        $cancel = $this->jsonRequest('DELETE', '/api/reservations/' . $reservationId, $token);
        $this->assertSame(200, $cancel['status']);

        $this->dm->clear();
        $after = $this->dm->getRepository(TestSession::class)->find($session->getId());
        $this->assertSame(10, $after->getAvailableSeats());
    }

    /**
     * Vérifie qu'un utilisateur ne peut pas annuler la réservation
     * d'un autre utilisateur. Alice crée une réservation, Bob tente
     * de l'annuler et reçoit un 403 Forbidden (vérification d'ownership).
     */
    public function testCannotCancelOthersReservation(): void
    {
        $this->createUser('alice@test.com', 'password123');
        $session = $this->createSession();
        $aliceToken = $this->login('alice@test.com', 'password123');
        $create = $this->jsonRequest('POST', '/api/reservations', $aliceToken, [
            'sessionId' => $session->getId(),
        ]);
        $reservationId = $create['data']['id'];

        $this->createUser('bob@test.com', 'password123');
        $bobToken = $this->login('bob@test.com', 'password123');

        $response = $this->jsonRequest('DELETE', '/api/reservations/' . $reservationId, $bobToken);
        $this->assertSame(403, $response['status']);
    }
}