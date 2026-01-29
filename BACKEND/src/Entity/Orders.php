<?php

namespace App\Entity;

use App\Repository\OrdersRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Enum\Status;


#[ORM\Entity(repositoryClass: OrdersRepository::class)]
class Orders
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    private ?string $orderNumber = null;

    #[ORM\Column]
    private ?int $peopleCount = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $deleveryDate = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTime $deleveryTime = null;

    #[ORM\Column(length: 100)]
    private ?string $deleveryAdress = null;

    #[ORM\Column(length: 100)]
    private ?string $deleveryCity = null;

    #[ORM\Column(length: 10)]
    private ?string $deleveryPostalCode = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $menuPrice = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $deleveryFee = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $discount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $totalPrice = null;

    #[ORM\Column(length: 50)]
    private ?string $concaledBy = null;

    #[ORM\Column(length: 100)]
    private ?string $cancelReason = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(enumType: Status::class, nullable: false)]
    private ?Status $status = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    private ?Menus $menu = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    private ?User $user = null;


    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?Reviews $review = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrderNumber(): ?string
    {
        return $this->orderNumber;
    }

    public function setOrderNumber(string $orderNumber): static
    {
        $this->orderNumber = $orderNumber;

        return $this;
    }

    public function getPeopleCount(): ?int
    {
        return $this->peopleCount;
    }

    public function setPeopleCount(int $peopleCount): static
    {
        $this->peopleCount = $peopleCount;

        return $this;
    }

    public function getDeleveryDate(): ?\DateTime
    {
        return $this->deleveryDate;
    }

    public function setDeleveryDate(\DateTime $deleveryDate): static
    {
        $this->deleveryDate = $deleveryDate;

        return $this;
    }

    public function getDeleveryTime(): ?\DateTime
    {
        return $this->deleveryTime;
    }

    public function setDeleveryTime(\DateTime $deleveryTime): static
    {
        $this->deleveryTime = $deleveryTime;

        return $this;
    }

    public function getDeleveryAdress(): ?string
    {
        return $this->deleveryAdress;
    }

    public function setDeleveryAdress(string $deleveryAdress): static
    {
        $this->deleveryAdress = $deleveryAdress;

        return $this;
    }

    public function getDeleveryCity(): ?string
    {
        return $this->deleveryCity;
    }

    public function setDeleveryCity(string $deleveryCity): static
    {
        $this->deleveryCity = $deleveryCity;

        return $this;
    }

    public function getDeleveryPostalCode(): ?string
    {
        return $this->deleveryPostalCode;
    }

    public function setDeleveryPostalCode(string $deleveryPostalCode): static
    {
        $this->deleveryPostalCode = $deleveryPostalCode;

        return $this;
    }

    public function getMenuPrice(): ?string
    {
        return $this->menuPrice;
    }

    public function setMenuPrice(string $menuPrice): static
    {
        $this->menuPrice = $menuPrice;

        return $this;
    }

    public function getDeleveryFee(): ?string
    {
        return $this->deleveryFee;
    }

    public function setDeleveryFee(?string $deleveryFee): static
    {
        $this->deleveryFee = $deleveryFee;

        return $this;
    }

    public function getDiscount(): ?string
    {
        return $this->discount;
    }

    public function setDiscount(?string $discount): static
    {
        $this->discount = $discount;

        return $this;
    }

    public function getTotalPrice(): ?string
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(string $totalPrice): static
    {
        $this->totalPrice = $totalPrice;

        return $this;
    }

    public function getConcaledBy(): ?string
    {
        return $this->concaledBy;
    }

    public function setConcaledBy(string $concaledBy): static
    {
        $this->concaledBy = $concaledBy;

        return $this;
    }

    public function getCancelReason(): ?string
    {
        return $this->cancelReason;
    }

    public function setCancelReason(string $cancelReason): static
    {
        $this->cancelReason = $cancelReason;

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

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
    public function getStatus(): ?Status
    {
        return $this->status;
    }
    public function setStatus(Status $status): static
    {
        $this->status = $status;

        return $this;
    }
    public function getStatusLabel(): ?string
    {
        return $this->status?->label();
    }
    public function getStatusColor(): ?string
    {
        return $this->status?->color();
    }

    public function getMenu(): ?Menus
    {
        return $this->menu;
    }

    public function setMenu(?Menus $menu): static
    {
        $this->menu = $menu;

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


    public function getReview(): ?Reviews
    {
        return $this->review;
    }

    public function setReview(?Reviews $review): static
    {
        $this->review = $review;

        return $this;
    }
}
