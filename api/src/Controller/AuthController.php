<?php

namespace App\Controller;

use App\Document\User;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AuthController extends AbstractController
{
    #[Route('/api/register', methods: ['POST'])]
    public function register(
        Request $request,
        DocumentManager $dm,
        UserPasswordHasherInterface $hasher,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }

        $email = trim($data['email'] ?? '');
        $name = trim($data['name'] ?? '');
        $password = $data['password'] ?? '';

        if ($password === '' || strlen($password) < 6) {
            return $this->json(['error' => 'Password must be at least 6 characters'], 400);
        }

        // Vérifier que l'email n'est pas déjà pris
        $existing = $dm->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existing) {
            return $this->json(['error' => 'Email already registered'], 409);
        }

        $user = new User();
        $user->setEmail($email);
        $user->setName($name);
        $user->setPassword($hasher->hashPassword($user, $password));

        // Validation Symfony
        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $messages], 400);
        }

        $dm->persist($user);
        $dm->flush();

        return $this->json([
            'message' => 'User registered successfully',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
            ],
        ], 201);
    }

    #[Route('/api/me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'name' => $user->getName(),
            'roles' => $user->getRoles(),
        ]);
    }
#[Route('/api/me', methods: ['PUT'])]
    public function updateMe(
        Request $request,
        DocumentManager $dm,
        ValidatorInterface $validator
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }

        if (isset($data['name'])) {
            $user->setName(trim($data['name']));
        }
        if (isset($data['email'])) {
            $newEmail = trim($data['email']);
            if ($newEmail !== $user->getEmail()) {
                $existing = $dm->getRepository(User::class)->findOneBy(['email' => $newEmail]);
                if ($existing) {
                    return $this->json(['error' => 'Email already used'], 409);
                }
                $user->setEmail($newEmail);
            }
        }

        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $e) {
                $messages[$e->getPropertyPath()] = $e->getMessage();
            }
            return $this->json(['errors' => $messages], 400);
        }

        $dm->flush();

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'name' => $user->getName(),
        ]);
    }
#[Route('/api/login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        // Cette méthode ne sera jamais exécutée.
        // Le firewall json_login intercepte la requête avant.
        throw new \LogicException('This method is intercepted by the firewall.');
    }
}