<?php

namespace App\Enum;

enum CategoryDishes: string
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
    public function entree(): bool
    {
        return $this === self::entree;
    }
    public function plat(): bool
    {
        return $this === self::plat;
    }
    public function dessert(): bool
    {
        return $this === self::dessert;
    }
}
