<?php

namespace App\Entity;

use App\Repository\MenusRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Enum\Theme;
use App\Enum\Diet;
use PhpParser\Builder\Enum_;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use Symfony\Component\Serializer\Attribute\Groups;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use ContainerTMkOneg\getDataCollector_Request_SessionCollectorService;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MenusRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(

            normalizationContext: ['groups' => ['menu:list']]
        ),
        new Get(

            normalizationContext: ['groups' => ['menu:detail']]
        ),

        new Post(
            security: 'is_granted("ROLE_ADMIN") or is_granted("ROLE_EMPLOYEE")',
            denormalizationContext: ['groups' => ['menu:write']]
        ),
        new Put(
            security: 'is_granted("ROLE_ADMIN") or is_granted("ROLE_EMPLOYEE")',
            denormalizationContext: ['groups' => ['menu:write']]
        ),
        new Delete(
            security: 'is_granted("ROLE_ADMIN") or is_granted("ROLE_EMPLOYEE")'
        ),
    ]
)]

class Menus
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['menu_dish:read', 'menu:read', 'menu:list', 'order:read'])]
    private ?int $id = null;

    #[Groups(['menu_dish:read', 'menu:read', 'menu:write', 'menu:list', 'menu:detail', 'order:read'])]
    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[Groups(['menu:read', 'menu:list', 'menu:write', 'menu:detail', 'order:read'])]
    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[Groups(['menu:read', 'menu:list', 'menu:detail', 'order:read'])]
    #[ORM\Column(type: Types::BLOB, nullable: true)]
    private mixed $picture = null;


    #[Assert\Positive]
    #[Groups(['menu:read', 'menu:list', 'menu:write', 'menu:detail', 'order:read'])]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $minPeople = null;

    #[Groups(['menu:read', 'menu:list', 'menu:write', 'menu:detail', 'order:read'])]
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $price = null;

    #[Groups(['menu:read', 'menu:list', 'menu:write', 'menu:detail'])]
    #[ORM\Column(type: Types::TEXT)]
    private ?string $conditions = null;

    #[Groups(['menu:read', 'menu:list', 'menu:write', 'menu:detail', 'order:read'])]
    #[ORM\Column]
    private ?int $stock = null;

    #[Groups(['menu:read'])]
    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[Groups(['menu:read'])]
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[Groups(['menu:read', 'menu:detail'])]
    #[ORM\Column(type: "string", enumType: Theme::class, nullable: true)]
    private ?Theme $themeMenu = null;

    #[Groups(['menu:read', 'menu:detail'])]
    #[ORM\Column(type: "string", enumType: Diet::class, nullable: true)]
    private ?Diet $dietMenu = null;
    /**
     * @var Collection<int, MenusDishes>
     */
    #[ORM\OneToMany(targetEntity: MenusDishes::class, mappedBy: 'menu', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $menusDishes;

    /**
     * @var Collection<int, Orders>
     */
    #[ORM\OneToMany(targetEntity: Orders::class, mappedBy: 'menu', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $orders;


    public function __construct()
    {
        $this->menusDishes = new ArrayCollection();
        $this->orders = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }
    #[Groups(['menu_dish:list'])]
    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }
    #[Groups(['menu_dish:list'])]
    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPicture(): mixed
    {
        return $this->picture;
    }

    public function setPicture(mixed $picture): static
    {
        $this->picture = $picture;

        return $this;
    }
    public function getThemeMenu(): ?Theme
    {
        return $this->themeMenu;
    }
    public function setThemeMenu(?Theme $themeMenu): static
    {
        $this->themeMenu = $themeMenu;
        return $this;
    }
    public function getDietMenu(): ?Diet
    {
        return $this->dietMenu;
    }
    public function setDietMenu(?Diet $dietMenu): static
    {
        $this->dietMenu = $dietMenu;
        return $this;
    }
    #[Groups(['menu_dish:list'])]
    public function getMinPeople(): ?int
    {
        return $this->minPeople;
    }

    public function setMinPeople(int $minPeople): static
    {
        $this->minPeople = $minPeople;

        return $this;
    }
    #[Groups(['menu_dish:list'])]
    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;

        return $this;
    }
    #[Groups(['menu_dish:list'])]
    public function getConditions(): ?string
    {
        return $this->conditions;
    }

    public function setConditions(?string $conditions): static
    {
        $this->conditions = $conditions;

        return $this;
    }

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(int $stock): static
    {
        $this->stock = $stock;

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
            $menusDish->setMenu($this);
        }

        return $this;
    }
    //remove un plat d'un menu
    public function removeMenusDish(MenusDishes $menusDish): static
    {
        if ($this->menusDishes->removeElement($menusDish)) {
            // set the owning side to null (unless already changed)
            if ($menusDish->getMenu() === $this) {
                $menusDish->setMenu(null);
            }
        }

        return $this;
    }
    public function is_available(): bool
    {
        return $this->stock > 0;
    }
    public function calculate_total_price(int $numberofPeople): float
    {
        return $this->price * $numberofPeople;
        if ($numberofPeople < $this->minPeople) {
            throw new \InvalidArgumentException(sprintf('Le nombre de personnes (%d) est insuffisant pour ce menu (minimum: %d)', $numberofPeople, $this->minPeople));
        } else if ($numberofPeople >= $this->minPeople + 5) {
            return $this->price * 0.9 * $numberofPeople;
        } else {
            return $this->price * $numberofPeople;
        }
    }
    // Get all allergenes from the dishes in the menu

    public function getAllAllergenes(): array
    {
        $allergenes = [];
        foreach ($this->menusDishes as $menuDish) {
            $dish = $menuDish->getDish();
            if ($dish) {
                foreach ($dish->getDishAllergens() as $allergene) {
                    $allergenes[$allergene->getId()] = $allergene;
                }
            }
        }
        return array_values($allergenes);
    }
    #[Groups(['menu:list', 'menu:detail'])]
    public function getDishCount(): int
    {
        return $this->menusDishes->count();
    }

    #[Groups(['menu:detail'])]
    public function getCategories(): array
    {
        $categories = [];
        foreach ($this->menusDishes as $menuDish) {
            $dish = $menuDish->getDish();
            if ($dish) {
                $category = $dish->getCategory();
                if (!in_array($category, $categories)) {
                    $categories[] = $category;
                }
            }
        }
        return $categories;
    }

    /**
     * @return Collection<int, Orders>
     */
    public function getOrders(): Collection
    {
        return $this->orders;
    }

    public function addOrder(Orders $order): static
    {
        if (!$this->orders->contains($order)) {
            $this->orders->add($order);
            $order->setMenu($this);
        }

        return $this;
    }

    public function removeOrder(Orders $order): static
    {
        if ($this->orders->removeElement($order)) {
            // set the owning side to null (unless already changed)
            if ($order->getMenu() === $this) {
                $order->setMenu(null);
            }
        }

        return $this;
    }
    //liste des plats d'un menu
    #[Groups(['menu_dish:detail', 'menu_dish:list'])]
    public function getListOfDishesFromMenu(): array
    {
        $dishes = [];
        foreach ($this->menusDishes as $menuDish) {
            $dishes[] = $menuDish->getDish();
        }
        return $dishes;
    }
}
