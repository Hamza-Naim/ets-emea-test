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

    public function testDeleteSession(): void
    {
        $this->createUser();
        $session = $this->createSession();
        $token = $this->login();

        $response = $this->jsonRequest('DELETE', '/api/sessions/' . $session->getId(), $token);
        $this->assertSame(200, $response['status']);
    }
}