<?php

namespace App\Entity;

use App\Repository\DishAllergenRepository;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: DishAllergenRepository::class)]
#[ApiResource(
    operations: []
)]
class DishAllergen
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[Groups(['dish_allergen:read', 'dish:list', 'dish:detail'])]
    #[ORM\Column]
    private ?int $id = null;

    #[Groups(['dish_allergen:detail', 'dish_allergen:read', 'dish:list', 'dish:detail'])]
    #[ORM\ManyToOne(inversedBy: 'dishAllergens')]
    private ?Dishes $dish = null;

    #[Groups(['dish:list', 'dish:detail'])]
    #[ORM\ManyToOne(inversedBy: 'dishAllergens')]
    private ?Allergens $allergen = null;


    public function getDish(): ?Dishes
    {
        return $this->dish;
    }
    public function setDish(?Dishes $dish): static
    {
        $this->dish = $dish;

        return $this;
    }

    public function getAllergen(): ?Allergens
    {
        return $this->allergen;
    }
    public function setAllergen(?Allergens $allergen): static
    {
        $this->allergen = $allergen;

        return $this;
    }


    public function getId(): ?int
    {
        return $this->id;
    }
}
