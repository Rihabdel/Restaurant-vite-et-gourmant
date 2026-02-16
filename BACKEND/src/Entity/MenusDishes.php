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



#[ORM\Entity(repositoryClass: MenusDishesRepository::class)]
#[ApiResource(

    operations: []
)]

class MenusDishes
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'menusDishes')]
    #[Groups(['menu_dish:list'])]
    #[ORM\JoinColumn(onDelete: "CASCADE")]
    private ?Menus $menu = null;
    #[Groups(['menu_dish:list'])]
    #[ORM\ManyToOne(targetEntity: Dishes::class, inversedBy: 'menusDishes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Dishes $dish = null;

    #[ORM\Column]
    #[Groups(['menu_dish:list'])]
    private ?int $displayOrder = null;

    public function getId(): ?int
    {
        return $this->id;
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
        return $this->displayOrder;
    }

    public function setDisplayOrder(int $displayOrder): static
    {
        $this->displayOrder = $displayOrder;

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
            'description' => $this->menu->getDescriptionMenu(),
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

    public function getMenuDishList(): ?string
    {
        if (!$this->menu || !$this->dish) {
            return null;
        }
        return 'Menu: ' . $this->menu->getTitle();
    }
}
