<?php

namespace App\Entity;

use App\Repository\ContactMsgRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: ContactMsgRepository::class)]
class ContactMsg
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups("contact:read")]
    private ?int $id = null;
    #[Groups("contact:read")]
    #[ORM\Column(length: 100)]
    private ?string $title = null;

    #[Groups("contact:read")]
    #[ORM\Column(type: Types::TEXT)]
    private ?string $message = null;

    #[Groups("contact:read")]
    #[ORM\Column(length: 100)]
    private ?string $email = null;

    #[Groups("contact:read")]
    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[Groups("contact:read")]
    #[ORM\ManyToOne(inversedBy: 'contactMsgs')]
    private ?User $user = null;

    #[ORM\Column(nullable: true)]
    private ?bool $traite = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }
    #[Groups("contact:read")]
    public function isTraite(): ?bool
    {
        return $this->traite;
    }

    public function setTraite(?bool $traite): static
    {
        $this->traite = $traite;

        return $this;
    }
}
