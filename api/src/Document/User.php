<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[MongoDB\Document(collection: 'users')]
#[MongoDB\UniqueIndex(keys: ['email' => 'asc'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[MongoDB\Id]
    private ?string $id = null;

    #[MongoDB\Field(type: 'string')]
    #[Assert\NotBlank]
    #[Assert\Email]
    private string $email = '';

    #[MongoDB\Field(type: 'string')]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 100)]
    private string $name = '';

    #[MongoDB\Field(type: 'string')]
    private string $password = '';

    #[MongoDB\Field(type: 'collection')]
    private array $roles = ['ROLE_USER'];

    #[MongoDB\Field(type: 'date')]
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?string { return $this->id; }

    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): self { $this->email = $email; return $this; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }

    public function getPassword(): string { return $this->password; }
    public function setPassword(string $password): self { $this->password = $password; return $this; }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }
    public function setRoles(array $roles): self { $this->roles = $roles; return $this; }

    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }

    public function getUserIdentifier(): string { return $this->email; }

    public function eraseCredentials(): void {}
}