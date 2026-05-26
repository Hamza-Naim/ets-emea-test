<?php

namespace App\Controller;

use App\Document\Reservation;
use App\Document\TestSession;
use App\Document\User;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/reservations')]
class ReservationController extends AbstractController
{
    private function serialize(Reservation $r): array
    {
        $session = $r->getSession();
        return [
            'id' => $r->getId(),
            'reservedAt' => $r->getReservedAt()->format('c'),
            'session' => $session ? [
                'id' => $session->getId(),
                'language' => $session->getLanguage(),
                'date' => $session->getDate()->format('Y-m-d'),
                'time' => $session->getTime(),
                'location' => $session->getLocation(),
            ] : null,
        ];
    }

    #[Route('', methods: ['GET'])]
    public function listMine(DocumentManager $dm): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $reservations = $dm->getRepository(Reservation::class)
            ->createQueryBuilder()
            ->field('user')->references($user)
            ->sort('reservedAt', 'desc')
            ->getQuery()
            ->toArray();

        return $this->json([
            'count' => count($reservations),
            'data' => array_map(fn(Reservation $r) => $this->serialize($r), array_values($reservations)),
        ]);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request, DocumentManager $dm): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $data = json_decode($request->getContent(), true);
        if (!is_array($data) || empty($data['sessionId'])) {
            return $this->json(['error' => 'sessionId is required'], 400);
        }

        $session = $dm->getRepository(TestSession::class)->find($data['sessionId']);
        if (!$session) {
            return $this->json(['error' => 'Session not found'], 404);
        }

        // Vérifier qu'il reste des places
        if ($session->getAvailableSeats() <= 0) {
            return $this->json(['error' => 'No seats available for this session'], 409);
        }

        // Vérifier qu'on n'a pas déjà réservé cette session
        $existing = $dm->getRepository(Reservation::class)
            ->createQueryBuilder()
            ->field('user')->references($user)
            ->field('session')->references($session)
            ->getQuery()
            ->getSingleResult();

        if ($existing) {
            return $this->json(['error' => 'You have already booked this session'], 409);
        }

        // Créer la réservation
        $reservation = new Reservation();
        $reservation->setUser($user);
        $reservation->setSession($session);

        // Décrémenter les places dispo
        $session->setAvailableSeats($session->getAvailableSeats() - 1);

        $dm->persist($reservation);
        $dm->flush();

        return $this->json($this->serialize($reservation), 201);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(string $id, DocumentManager $dm): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $reservation = $dm->getRepository(Reservation::class)->find($id);

        if (!$reservation) {
            return $this->json(['error' => 'Reservation not found'], 404);
        }

        // Vérifier qu'on est bien le propriétaire
        if ($reservation->getUser()?->getId() !== $user->getId()) {
            return $this->json(['error' => 'You can only cancel your own reservations'], 403);
        }

        // Re-libérer la place
        $session = $reservation->getSession();
        if ($session) {
            $session->setAvailableSeats($session->getAvailableSeats() + 1);
        }

        $dm->remove($reservation);
        $dm->flush();

        return $this->json(['message' => 'Reservation cancelled']);
    }
}