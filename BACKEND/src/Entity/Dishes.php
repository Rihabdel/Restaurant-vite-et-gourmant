<?php

namespace App\Entity;

use App\Repository\DishesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Enum\CategoryDishes;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping\JoinColumn;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Config\SecurityConfig;

#[ORM\Entity(repositoryClass: DishesRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => ['dish:detail', 'menu:detail', 'dish:list']]
        ),
        new Get(
            normalizationContext: ['groups' => ['dish:detail']]
        ),

        new Post(

            denormalizationContext: ['groups' => ['dish:write']]
        ),
        new Put(

            denormalizationContext: ['groups' => ['dish:write']]
        ),
        new Delete(),
    ]
)]

class Dishes
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['dish:read', 'dish:detail', 'menu:detail', 'menu_dish:read', 'dish:list'])]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Groups(['menu_dish:read', 'dish:read', 'dish:list', 'dish:detail', 'dish:write', 'menu:detail'])]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['dish:list', 'dish:detail', 'dish:write', 'menu:detail'])]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups(['menu_dish:read', 'dish:list', 'dish:detail', 'dish:write', 'menu:detail'])]
    private ?string $price = null;

    #[Groups(['menu_dish:read', 'dish:list', 'dish:detail', 'menu:detail'])]
    #[ORM\Column(enumType: CategoryDishes::class, nullable: false)]
    private ?CategoryDishes $category = null;

    #[Groups(['dish:detail'])]
    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, MenusDishes>
     */
    #[ORM\OneToMany(targetEntity: MenusDishes::class, mappedBy: 'dish', cascade: ['persist', 'remove'])]
    private Collection $menusDishes;

    /**
     * @var Collection<int, DishAllergen>
     */
    #[Groups(['menu_dish:read', 'dish:list', 'dish:detail', 'dish:write', 'menu:detail'])]
    #[ORM\OneToMany(targetEntity: DishAllergen::class, mappedBy: 'dish')]
    private Collection $dishAllergens;

    public function __construct()
    {
        $this->menusDishes = new ArrayCollection();
        $this->dishAllergens = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
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
    public function getCategory(): ?CategoryDishes
    {
        return $this->category;
    }
    public function setCategory(CategoryDishes $category): static
    {
        $this->category = $category;
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
    public function updateDishAllergens(array $allergens): static
    {
        // Supprimer les allergènes existants
        foreach ($this->dishAllergens as $dishAllergen) {
            $this->removeDishAllergen($dishAllergen);
        }

        // Ajouter les nouveaux allergènes
        foreach ($allergens as $allergen) {
            $dishAllergen = new DishAllergen();
            $dishAllergen->setAllergen($allergen);
            $this->addDishAllergen($dishAllergen);
        }

        return $this;
    }

    public function getMenuCount(): int
    {
        return $this->menusDishes->count();
    }
}
