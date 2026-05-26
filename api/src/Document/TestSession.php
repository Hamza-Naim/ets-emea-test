<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;

#[MongoDB\Document(collection: 'sessions')]
class TestSession
{
    #[MongoDB\Id]
    private ?string $id = null;

    #[MongoDB\Field(type: 'string')]
    #[Assert\NotBlank]
    private string $language = '';

    #[MongoDB\Field(type: 'date')]
    #[Assert\NotNull]
    private \DateTimeInterface $date;

    #[MongoDB\Field(type: 'string')]
    #[Assert\NotBlank]
    private string $time = '';

    #[MongoDB\Field(type: 'string')]
    #[Assert\NotBlank]
    private string $location = '';

    #[MongoDB\Field(type: 'int')]
    #[Assert\Positive]
    private int $totalSeats = 0;

    #[MongoDB\Field(type: 'int')]
    private int $availableSeats = 0;

    public function getId(): ?string { return $this->id; }

    public function getLanguage(): string { return $this->language; }
    public function setLanguage(string $v): self { $this->language = $v; return $this; }

    public function getDate(): \DateTimeInterface { return $this->date; }
    public function setDate(\DateTimeInterface $v): self { $this->date = $v; return $this; }

    public function getTime(): string { return $this->time; }
    public function setTime(string $v): self { $this->time = $v; return $this; }

    public function getLocation(): string { return $this->location; }
    public function setLocation(string $v): self { $this->location = $v; return $this; }

    public function getTotalSeats(): int { return $this->totalSeats; }
    public function setTotalSeats(int $v): self { $this->totalSeats = $v; return $this; }

    public function getAvailableSeats(): int { return $this->availableSeats; }
    public function setAvailableSeats(int $v): self { $this->availableSeats = $v; return $this; }
}