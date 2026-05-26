<?php

namespace App\Tests;

class AuthControllerTest extends ApiTestCase
{
    public function testRegisterCreatesUser(): void
    {
        $response = $this->jsonRequest('POST', '/api/register', body: [
            'email' => 'alice@test.com',
            'name' => 'Alice',
            'password' => 'password123',
        ]);

        $this->assertSame(201, $response['status']);
        $this->assertSame('alice@test.com', $response['data']['user']['email']);
    }

    public function testRegisterRejectsDuplicateEmail(): void
    {
        $this->createUser('alice@test.com');

        $response = $this->jsonRequest('POST', '/api/register', body: [
            'email' => 'alice@test.com',
            'name' => 'Other',
            'password' => 'password123',
        ]);

        $this->assertSame(409, $response['status']);
    }

    public function testRegisterRejectsShortPassword(): void
    {
        $response = $this->jsonRequest('POST', '/api/register', body: [
            'email' => 'bob@test.com',
            'name' => 'Bob',
            'password' => '123',
        ]);

        $this->assertSame(400, $response['status']);
    }

    public function testLoginReturnsJwtToken(): void
    {
        $this->createUser('alice@test.com', 'password123');

        $response = $this->jsonRequest('POST', '/api/login', body: [
            'email' => 'alice@test.com',
            'password' => 'password123',
        ]);

        $this->assertSame(200, $response['status']);
        $this->assertArrayHasKey('token', $response['data']);
    }

    public function testLoginFailsWithWrongPassword(): void
    {
        $this->createUser('alice@test.com', 'password123');

        $response = $this->jsonRequest('POST', '/api/login', body: [
            'email' => 'alice@test.com',
            'password' => 'wrong',
        ]);

        $this->assertSame(401, $response['status']);
    }

    public function testMeRequiresAuthentication(): void
    {
        $response = $this->jsonRequest('GET', '/api/me');
        $this->assertSame(401, $response['status']);
    }

    public function testMeReturnsCurrentUser(): void
    {
        $this->createUser('alice@test.com', 'password123', 'Alice');
        $token = $this->login('alice@test.com', 'password123');

        $response = $this->jsonRequest('GET', '/api/me', $token);

        $this->assertSame(200, $response['status']);
        $this->assertSame('Alice', $response['data']['name']);
    }

    public function testUpdateMeChangesProfile(): void
    {
        $this->createUser();
        $token = $this->login();

        $response = $this->jsonRequest('PUT', '/api/me', $token, [
            'name' => 'Updated Name',
        ]);

        $this->assertSame(200, $response['status']);
        $this->assertSame('Updated Name', $response['data']['name']);
    }
}