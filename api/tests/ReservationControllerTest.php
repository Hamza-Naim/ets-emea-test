<?php

namespace App\Tests;

use App\Document\TestSession;

class ReservationControllerTest extends ApiTestCase
{
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