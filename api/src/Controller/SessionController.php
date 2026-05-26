<?php

namespace App\Controller;

use App\Document\TestSession;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/sessions')]
class SessionController extends AbstractController
{
    private function serialize(TestSession $s): array
    {
        return [
            'id' => $s->getId(),
            'language' => $s->getLanguage(),
            'date' => $s->getDate()->format('Y-m-d'),
            'time' => $s->getTime(),
            'location' => $s->getLocation(),
            'totalSeats' => $s->getTotalSeats(),
            'availableSeats' => $s->getAvailableSeats(),
        ];
    }

    #[Route('', methods: ['GET'])]
    public function list(Request $request, DocumentManager $dm): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, min(50, (int) $request->query->get('limit', 10)));

        $repo = $dm->getRepository(TestSession::class);

        $total = $repo->createQueryBuilder()->count()->getQuery()->execute();

        $sessions = $repo->createQueryBuilder()
            ->sort('date', 'asc')
            ->skip(($page - 1) * $limit)
            ->limit($limit)
            ->getQuery()
            ->toArray();

        return $this->json([
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => (int) ceil($total / $limit),
            'data' => array_map(fn(TestSession $s) => $this->serialize($s), array_values($sessions)),
        ]);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(string $id, DocumentManager $dm): JsonResponse
    {
        $session = $dm->getRepository(TestSession::class)->find($id);
        if (!$session) {
            return $this->json(['error' => 'Session not found'], 404);
        }
        return $this->json($this->serialize($session));
    }

    #[Route('', methods: ['POST'])]
    public function create(
        Request $request,
        DocumentManager $dm,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }

        try {
            $date = new \DateTimeImmutable($data['date'] ?? '');
        } catch (\Exception) {
            return $this->json(['error' => 'Invalid date format (use YYYY-MM-DD)'], 400);
        }

        $session = new TestSession();
        $session->setLanguage(trim($data['language'] ?? ''));
        $session->setDate($date);
        $session->setTime(trim($data['time'] ?? ''));
        $session->setLocation(trim($data['location'] ?? ''));
        $session->setTotalSeats((int) ($data['totalSeats'] ?? 0));
        $session->setAvailableSeats((int) ($data['totalSeats'] ?? 0));

        $errors = $validator->validate($session);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $messages], 400);
        }

        $dm->persist($session);
        $dm->flush();

        return $this->json($this->serialize($session), 201);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(
        string $id,
        Request $request,
        DocumentManager $dm,
        ValidatorInterface $validator
    ): JsonResponse {
        $session = $dm->getRepository(TestSession::class)->find($id);
        if (!$session) {
            return $this->json(['error' => 'Session not found'], 404);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }

        if (isset($data['language'])) $session->setLanguage(trim($data['language']));
        if (isset($data['time'])) $session->setTime(trim($data['time']));
        if (isset($data['location'])) $session->setLocation(trim($data['location']));
        if (isset($data['date'])) {
            try {
                $session->setDate(new \DateTimeImmutable($data['date']));
            } catch (\Exception) {
                return $this->json(['error' => 'Invalid date format'], 400);
            }
        }
        if (isset($data['totalSeats'])) {
            $newTotal = (int) $data['totalSeats'];
            $reserved = $session->getTotalSeats() - $session->getAvailableSeats();
            if ($newTotal < $reserved) {
                return $this->json(['error' => "Cannot reduce total seats below already reserved ($reserved)"], 400);
            }
            $session->setTotalSeats($newTotal);
            $session->setAvailableSeats($newTotal - $reserved);
        }

        $errors = $validator->validate($session);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $messages], 400);
        }

        $dm->flush();
        return $this->json($this->serialize($session));
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(string $id, DocumentManager $dm): JsonResponse
    {
        $session = $dm->getRepository(TestSession::class)->find($id);
        if (!$session) {
            return $this->json(['error' => 'Session not found'], 404);
        }

        $dm->remove($session);
        $dm->flush();

        return $this->json(['message' => 'Session deleted']);
    }
}