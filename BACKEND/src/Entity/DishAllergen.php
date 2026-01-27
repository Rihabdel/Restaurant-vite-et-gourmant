<?php

namespace App\Entity;

use App\Repository\DishAllergenRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DishAllergenRepository::class)]
class DishAllergen
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    #[ORM\ManyToOne(inversedBy: 'dishAllergens')]
    private ?Dishes $dish = null;
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
