<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

#[MongoDB\Document(collection: 'reservations')]
#[MongoDB\UniqueIndex(keys: ['user.$id' => 'asc', 'session.$id' => 'asc'])]
class Reservation
{
    #[MongoDB\Id]
    private ?string $id = null;

    #[MongoDB\ReferenceOne(targetDocument: User::class)]
    private ?User $user = null;

    #[MongoDB\ReferenceOne(targetDocument: TestSession::class)]
    private ?TestSession $session = null;

    #[MongoDB\Field(type: 'date')]
    private \DateTimeInterface $reservedAt;

    public function __construct()
    {
        $this->reservedAt = new \DateTimeImmutable();
    }

    public function getId(): ?string { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(User $u): self { $this->user = $u; return $this; }

    public function getSession(): ?TestSession { return $this->session; }
    public function setSession(TestSession $s): self { $this->session = $s; return $this; }

    public function getReservedAt(): \DateTimeInterface { return $this->reservedAt; }
}