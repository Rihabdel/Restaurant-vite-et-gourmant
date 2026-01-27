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

#[ORM\Entity(repositoryClass: MenusRepository::class)]
class Menus
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(type: Types::BLOB, nullable: true)]
    private mixed $picture = null;

    #[ORM\Column]
    private ?int $min_people = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $price = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $conditions = null;

    #[ORM\Column]
    private ?int $stock = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(enumType: Theme::class, nullable: true)]
    private ?Theme $theme_menu = null;
    #[ORM\Column(enumType: Diet::class, nullable: true)]
    private ?Diet $diet_menu = null;

    /**
     * @var Collection<int, MenusDishes>
     */
    #[ORM\OneToMany(targetEntity: MenusDishes::class, mappedBy: 'menu')]
    private Collection $menusDishes;

    /**
     * @var Collection<int, Orders>
     */
    #[ORM\OneToMany(targetEntity: Orders::class, mappedBy: 'menu')]
    private Collection $orders;

    /**
     * @var Collection<int, Orders>
     */
    #[ORM\OneToMany(targetEntity: Orders::class, mappedBy: 'menus')]
    private Collection $Order_menu;

    public function __construct()
    {
        $this->menusDishes = new ArrayCollection();
        $this->orders = new ArrayCollection();
        $this->Order_menu = new ArrayCollection();
    }

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
        return $this->theme_menu;
    }
    public function setThemeMenu(?Theme $theme_menu): static
    {
        $this->theme_menu = $theme_menu;

        return $this;
    }
    public function getDietMenu(): ?diet
    {
        return $this->diet_menu;
    }
    public function setDietMenu(?diet $diet_menu): static
    {
        $this->diet_menu = $diet_menu;
        return $this;
    }

    public function getMinPeople(): ?int
    {
        return $this->min_people;
    }

    public function setMinPeople(int $min_people): static
    {
        $this->min_people = $min_people;

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

    /**
     * @return Collection<int, Orders>
     */
    public function getOrderMenu(): Collection
    {
        return $this->Order_menu;
    }

    public function addOrderMenu(Orders $orderMenu): static
    {
        if (!$this->Order_menu->contains($orderMenu)) {
            $this->Order_menu->add($orderMenu);
            $orderMenu->setMenus($this);
        }

        return $this;
    }

    public function removeOrderMenu(Orders $orderMenu): static
    {
        if ($this->Order_menu->removeElement($orderMenu)) {
            // set the owning side to null (unless already changed)
            if ($orderMenu->getMenus() === $this) {
                $orderMenu->setMenus(null);
            }
        }

        return $this;
    }
    public function is_available(): bool
    {
        return $this->stock > 0;
    }
    public function calculate_total_price(int $number_of_people): float
    {
        return $this->price * $number_of_people;
        if ($number_of_people < $this->min_people) {
            throw new \InvalidArgumentException('Number of people is less than the minimum required for this menu.');
        } else if ($number_of_people >= $this->min_people + 5) {
            return $this->price * 0.9 * $number_of_people;
        } else {
            return $this->price * $number_of_people;
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
}
