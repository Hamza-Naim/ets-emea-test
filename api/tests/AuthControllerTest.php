<?php

namespace App\Tests;

class AuthControllerTest extends ApiTestCase
{
    /**
     * Vérifie qu'un nouvel utilisateur peut s'inscrire via /api/register.
     * Le serveur doit retourner un code 201 (Created) et les informations
     * de l'utilisateur créé (email visible dans la réponse).
     */
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

    /**
     * Vérifie qu'on ne peut pas créer deux comptes avec le même email.
     * Si un utilisateur avec cet email existe déjà, l'API renvoie 409 Conflict.
     */
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

    /**
     * Vérifie que l'API refuse les mots de passe trop courts
     * (moins de 6 caractères) avec un code 400 Bad Request.
     */
    public function testRegisterRejectsShortPassword(): void
    {
        $response = $this->jsonRequest('POST', '/api/register', body: [
            'email' => 'bob@test.com',
            'name' => 'Bob',
            'password' => '123',
        ]);

        $this->assertSame(400, $response['status']);
    }

    /**
     * Vérifie qu'une connexion réussie avec les bons identifiants
     * retourne un token JWT dans la réponse, utilisable pour les
     * appels authentifiés ultérieurs.
     */
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

    /**
     * Vérifie qu'une tentative de connexion avec un mauvais mot de passe
     * est rejetée par l'API avec un code 401 Unauthorized.
     */
    public function testLoginFailsWithWrongPassword(): void
    {
        $this->createUser('alice@test.com', 'password123');

        $response = $this->jsonRequest('POST', '/api/login', body: [
            'email' => 'alice@test.com',
            'password' => 'wrong',
        ]);

        $this->assertSame(401, $response['status']);
    }

    /**
     * Vérifie que l'endpoint /api/me est protégé : sans token JWT,
     * l'accès est refusé avec un code 401 Unauthorized.
     */
    public function testMeRequiresAuthentication(): void
    {
        $response = $this->jsonRequest('GET', '/api/me');
        $this->assertSame(401, $response['status']);
    }

    /**
     * Vérifie que l'endpoint /api/me retourne bien les informations
     * de l'utilisateur connecté (identifié grâce au token JWT).
     */
    public function testMeReturnsCurrentUser(): void
    {
        $this->createUser('alice@test.com', 'password123', 'Alice');
        $token = $this->login('alice@test.com', 'password123');

        $response = $this->jsonRequest('GET', '/api/me', $token);

        $this->assertSame(200, $response['status']);
        $this->assertSame('Alice', $response['data']['name']);
    }

    /**
     * Vérifie que l'utilisateur connecté peut modifier ses informations
     * personnelles (ici, le nom) via PUT /api/me. La réponse doit
     * refléter la nouvelle valeur du champ modifié.
     */
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