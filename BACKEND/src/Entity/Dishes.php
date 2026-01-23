<?php

namespace App\Entity;

use App\Repository\DishesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\TypeDishes;

#[ORM\Entity(repositoryClass: DishesRepository::class)]
class Dishes
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $price = null;

    #[ORM\Column(enumType: TypeDishes::class, nullable: false)]
    private ?TypeDishes $type = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, MenusDishes>
     */
    #[ORM\OneToMany(targetEntity: MenusDishes::class, mappedBy: 'dish')]
    private Collection $menusDishes;

    /**
     * @var Collection<int, DishAllergen>
     */
    #[ORM\OneToMany(targetEntity: DishAllergen::class, mappedBy: 'dish')]
    private Collection $dishAllergens;

    public function __construct()
    {
        $this->menusDishes = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;

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
    public function getType(): ?TypeDishes
    {
        return $this->type;
    }
    public function setType(TypeDishes $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return Collection<int, MenusDishes>
     */
    public function getMenusDishes(): Collection
    {
        return $this->menusDishes;
    }

    public function addMenusDish(MenusDishes $menusDish): static
    {
        if (!$this->menusDishes->contains($menusDish)) {
            $this->menusDishes->add($menusDish);
            $menusDish->setDish($this);
        }

        return $this;
    }

    public function removeMenusDish(MenusDishes $menusDish): static
    {
        if ($this->menusDishes->removeElement($menusDish)) {
            // set the owning side to null (unless already changed)
            if ($menusDish->getDish() === $this) {
                $menusDish->setDish(null);
            }
        }

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
            $dishAllergen->setDish($this);
        }

        return $this;
    }

    public function removeDishAllergen(DishAllergen $dishAllergen): static
    {
        if ($this->dishAllergens->removeElement($dishAllergen)) {
            // set the owning side to null (unless already changed)
            if ($dishAllergen->getDish() === $this) {
                $dishAllergen->setDish(null);
            }
        }

        return $this;
    }
}
