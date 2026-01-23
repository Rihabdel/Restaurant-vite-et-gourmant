<?php

namespace App\Entity;

enum Diet: string
{
    case classique = 'classique';
    case vegetarien = 'vegetarien';
    case vegan = 'vegan';
    case sans_gluten = 'sans_gluten';
    case autres = 'autres';

    public function label(): string
    {
        return match ($this) {
            self::classique => 'Classique',
            self::vegetarien => 'Végétarien',
            self::sans_gluten => 'Sans Gluten',
            self::autres => 'Autres',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::classique => '🍽️',
            self::vegetarien => '🥗',
            self::sans_gluten => '🚫🌾',
            self::autres => '🍴',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::classique => 'Menu traditionnel avec une variété d\'options.',
            self::vegetarien => 'Menu sans viande, riche en légumes et protéines végétales.',
            self::sans_gluten => 'Menu adapté aux personnes intolérantes au gluten.',
            self::autres => 'Menu avec des options spéciales selon les besoins.',
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
    public function est_classique(): bool
    {
        return $this === self::classique;
    }
    public function est_vegetarien(): bool
    {
        return $this === self::vegetarien;
    }
    public function est_sans_gluten(): bool
    {
        return $this === self::sans_gluten;
    }
    public function est_autres(): bool
    {
        return $this === self::autres;
    }
}
