<?php

namespace App\Entity;

use App\Repository\OrdersRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use Symfony\Component\Serializer\Attribute\Groups;



#[ORM\Entity(repositoryClass: OrdersRepository::class)]
#[ApiResource(
    operations: []
)]
class Orders
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['orders:read'])]
    private ?int $id = null;

    #[ORM\Column]
    #[Groups(['orders:read', 'orders:write'])]
    private ?int $numberOfPeople = null;

    #[ORM\Column]
    #[Groups(['orders:read'])]
    private ?float $totalPrice = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 0, nullable: true)]
    #[Groups(['orders:read', 'orders:write'])]
    private ?string $deliveryCost = null;

    #[ORM\Column(length: 255)]
    #[Groups(['orders:read', 'orders:write'])]
    private ?string $deliveryAddress = null;

    #[ORM\Column]
    #[Groups(['orders:read', 'orders:write'])]
    private ?\DateTime $deliveryDate = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    #[Groups(['orders:read', 'orders:write'])]
    private ?\DateTime $deliveryTime = null;

    #[ORM\Column(length: 50)]
    #[Groups(['orders:read', 'orders:write'])]
    private ?string $status = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['orders:write'])]
    private ?string $cancellationReason = null;

    #[ORM\Column]
    #[Groups(['orders:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;
    #[Groups(['orders:read'])]
    #[ORM\ManyToOne(inversedBy: 'orders')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Menus $menu = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?Reviews $review = null;
    #[Groups(['orders:read'])]
    #[ORM\Column(length: 50)]
    private ?string $deliveryCity = null;
    #[Groups(['orders:read'])]
    #[ORM\Column]
    private ?int $deliveryPostalCode = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $concaledBy = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumberOfPeople(): ?int
    {
        return $this->numberOfPeople;
    }

    public function setNumberOfPeople(int $numberOfPeople): static
    {
        $this->numberOfPeople = $numberOfPeople;
        return $this;
    }
    #[Groups(['orders:read'])]
    public function getTotalPrice(): ?float
    {
        return $this->totalPrice;
    }
    #[Groups(['orders:read'])]
    public function setTotalPrice(float $totalPrice): static
    {
        $this->totalPrice = $totalPrice;

        return $this;
    }

    public function getDeliveryCost(): ?string
    {
        return $this->deliveryCost;
    }

    public function setDeliveryCost(?string $deliveryCost): static
    {
        $this->deliveryCost = $deliveryCost;

        return $this;
    }

    public function getDeliveryAddress(): ?string
    {
        return $this->deliveryAddress;
    }

    public function setDeliveryAddress(string $deliveryAddress): static
    {
        $this->deliveryAddress = $deliveryAddress;

        return $this;
    }

    public function getDeliveryDate(): ?\DateTime
    {
        return $this->deliveryDate;
    }

    public function setDeliveryDate(\DateTime $deliveryDate): static
    {
        $this->deliveryDate = $deliveryDate;

        return $this;
    }

    public function getDeliveryTime(): ?\DateTime
    {
        return $this->deliveryTime;
    }

    public function setDeliveryTime(\DateTime $deliveryTime): static
    {
        $this->deliveryTime = $deliveryTime;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCancellationReason(): ?string
    {
        return $this->cancellationReason;
    }

    public function setCancellationReason(?string $cancellationReason): static
    {
        $this->cancellationReason = $cancellationReason;

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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

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
    #[Groups(['orders:read'])]
    public function getMenu(): ?Menus
    {
        return $this->menu;
    }

    public function setMenu(?Menus $menu): static
    {
        $this->menu = $menu;

        return $this;
    }

    public function getReview(): ?Reviews
    {
        return $this->review;
    }

    public function setReview(?Reviews $review): static
    {
        $this->review = $review;

        return $this;
    }

    public function getDeliveryCity(): ?string
    {
        return $this->deliveryCity;
    }

    public function setDeliveryCity(string $deliveryCity): static
    {
        $this->deliveryCity = $deliveryCity;

        return $this;
    }

    public function getDeliveryPostalCode(): ?int
    {
        return $this->deliveryPostalCode;
    }

    public function setDeliveryPostalCode(int $deliveryPostalCode): static
    {
        $this->deliveryPostalCode = $deliveryPostalCode;
        if (!preg_match('/^\d{5}$/', (string)$deliveryPostalCode)) {
            throw new \InvalidArgumentException('Le code postal doit être un nombre à 5 chiffres');
        }

        return $this;
    }

    public function getConcaledBy(): ?string
    {
        return $this->concaledBy;
    }

    public function setConcaledBy(?string $concaledBy): static
    {
        $this->concaledBy = $concaledBy;

        return $this;
    }
    public function cancelOrder(string $reason, string $cancelledBy): void
    {
        $this->setStatus('annulée');
        $this->setCancellationReason($reason);
        $this->setConcaledBy($cancelledBy);
    }
    public function isCancellable(): bool
    {
        $cancellableStatuses = ['en attente'];
        return in_array($this->status, $cancellableStatuses);
    }
    public function isDeliverable(): bool
    {
        $deliverableStatuses = ['en attente', 'confirmée'];
        return in_array($this->status, $deliverableStatuses);
    }
    public function isReviewable(): bool
    {
        $reviewableStatuses = ['livrée'];
        return in_array($this->status, $reviewableStatuses);
    }
    public function canBeCancelledByUser(User $user): bool
    {
        return $this->isCancellable() && $this->getUser() === $user;
    }
    public function canBeCancelledByAdmin(): bool
    {
        return $this->isCancellable();
    }
    public function canBeDelivered(): bool
    {
        return $this->isDeliverable();
    }
}
