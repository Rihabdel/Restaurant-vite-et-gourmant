<?php

namespace App\Entity;

use App\Repository\MenusDishesRepository;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use PhpParser\Node\Stmt\Unset_;
use Symfony\Component\Serializer\Attribute\Groups;

use function Symfony\Component\String\u;

#[ORM\Entity(repositoryClass: MenusDishesRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => ['menu_dish:list']]
        ),
        new Get(
            normalizationContext: ['groups' => ['menu_dish:read']]
        ),
        new Post(
            normalizationContext: ['groups' => ['menu_dish:read']],
            denormalizationContext: ['groups' => ['menu_dish:write']]
        ),
        new Put(
            normalizationContext: ['groups' => ['menu_dish:read']],
            denormalizationContext: ['groups' => ['menu_dish:write']]

        ),
        new Delete(),
    ]
)]

class MenusDishes
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'menusDishes')]

    #[ORM\JoinColumn(onDelete: "CASCADE")]
    private ?Menus $menu = null;

    #[ORM\ManyToOne(targetEntity: Dishes::class, inversedBy: 'menusDishes')]

    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Dishes $dish = null;

    #[ORM\Column]

    private ?int $display_order = null;

    public function getId(): ?int
    {
        return $this->id;
    }
    #[Groups(['menu_dish:list'])]
    public function getMenu(): ?Menus
    {
        return $this->menu;
    }

    public function setMenu(?Menus $menu): static
    {
        $this->menu = $menu;

        return $this;
    }

    public function getDish(): ?Dishes
    {
        return $this->dish;
    }

    public function setDish(?Dishes $dish): static
    {
        $this->dish = $dish;

        return $this;
    }


    public function getDisplayOrder(): ?int
    {
        return $this->display_order;
    }

    public function setDisplayOrder(int $display_order): static
    {
        $this->display_order = $display_order;

        return $this;
    }

    #[Groups(['menu_dish:detail'])]
    public function getMenuDetails(): ?array
    {
        if (!$this->menu) {
            return null;
        }

        return [
            'id' => $this->menu->getId(),
            'title' => $this->menu->getTitle(),
            'description' => $this->menu->getDescription(),
            'theme' => $this->menu->getThemeMenu(),
            'diet' => $this->menu->getDietMenu(),
            'min_people' => $this->menu->getMinPeople(),
            'price' => $this->menu->getPrice(),
        ];
    }

    #[Groups(['menu_dish:detail'])]
    public function getDishDetails(): ?array
    {
        if (!$this->dish) {
            return null;
        }

        return [
            'id' => $this->dish->getId(),
            'name' => $this->dish->getName(),
            'description' => $this->dish->getDescription(),
            'category' => $this->dish->getCategory(),
            'price' => $this->dish->getPrice()
        ];
    }
    #[Groups(['menu_dish:detail', 'menu_dish:list'])]
    public function getMenuDishList(): ?string
    {
        if (!$this->menu || !$this->dish) {
            return null;
        }
        return 'Menu: ' . $this->menu->getTitle();
    }
}
