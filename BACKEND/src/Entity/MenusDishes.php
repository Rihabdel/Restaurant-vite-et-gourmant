<?php

namespace App\Entity;

use App\Repository\MenusDishesRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MenusDishesRepository::class)]
class MenusDishes
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'menusDishes')]
    private ?Menus $menu = null;

    #[ORM\ManyToOne(inversedBy: 'menusDishes')]
    private ?Dishes $dish = null;

    #[ORM\Column]
    private ?int $display_order = null;

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
        return $this->display_order;
    }

    public function setDisplayOrder(int $display_order): static
    {
        $this->display_order = $display_order;

        return $this;
    }
}
