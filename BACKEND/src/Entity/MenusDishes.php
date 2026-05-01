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

    #[Groups(['menu_dish:read'])]
    #[ORM\ManyToOne(inversedBy: 'menusDishes')]
    #[ORM\JoinColumn(onDelete: "CASCADE")]
    private ?Menus $menu = null;

    #[Groups(['menu_dish:read'])]
    #[ORM\ManyToOne(targetEntity: Dishes::class, inversedBy: 'menusDishes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Dishes $dish = null;

    #[Groups(['menu_dish:read'])]
    #[ORM\Column]
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
}
