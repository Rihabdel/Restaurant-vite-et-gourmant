<?php

namespace App\Entity;

use App\Repository\AllergensRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AllergensRepository::class)]
class Allergens
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $icon = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * @var Collection<int, DishAllergen>
     */
    #[ORM\OneToMany(targetEntity: DishAllergen::class, mappedBy: 'allergen')]
    private Collection $dishAllergens;

    public function __construct()
    {
        $this->dishAllergens = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

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

    /**
     * @return Collection<int, DishAllergen>
     */
    public function getDishAllergens(): Collection
    {
        return $this->dishAllergens;
    }

    public function addDishAllergen(DishAllergen $dishAllergen): static
    {
        if (!$this->dishAllergens->contains($dishAllergen)) {
            $this->dishAllergens->add($dishAllergen);
            $dishAllergen->setAllergen($this);
        }

        return $this;
    }

    public function removeDishAllergen(DishAllergen $dishAllergen): static
    {
        if ($this->dishAllergens->removeElement($dishAllergen)) {
            // set the owning side to null (unless already changed)
            if ($dishAllergen->getAllergen() === $this) {
                $dishAllergen->setAllergen(null);
            }
        }

        return $this;
    }
}
