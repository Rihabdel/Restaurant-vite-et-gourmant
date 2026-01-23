<?php

namespace App\Entity;

enum TypeDishes: string
{
    case entree = 'entree';
    case plat = 'plat';
    case dessert = 'dessert';

    public function label(): string
    {
        return match ($this) {
            self::entree => 'Entrée',
            self::plat => 'Plat',
            self::dessert => 'Dessert',
        };
    }
    public static function choices(): array
    {
        $choices = [];
        foreach (self::cases() as $case) {
            $choices[$case->label()] = $case;
        }
        return $choices;
    }
    public function est_entree(): bool
    {
        return $this === self::entree;
    }
    public function est_plat(): bool
    {
        return $this === self::plat;
    }
    public function est_dessert(): bool
    {
        return $this === self::dessert;
    }
}
