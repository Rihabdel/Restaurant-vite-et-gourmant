<?php

namespace App\Enum;

enum AllergenTypeEnum: string
{
    case GLUTEN = 'gluten';
    case LACTOSE = 'lactose';
    case ARACHIDES = 'arachides';
    case FRUITS_A_COQUE = 'fruits_a_coque';
    case POISSON = 'poisson';
    case CRUSTACES = 'crustaces';
    case OEUF = 'oeuf';
    case SOJA = 'soja';
    case MOUTARDE = 'moutarde';
    case SESAME = 'sesame';
    case SULFITES = 'sulfites';
    case CELERI = 'celeri';
    case LUPIN = 'lupin';
    case MOLLUSQUES = 'mollusques';

    public static function getLabels(): array
    {
        return [
            self::GLUTEN->value => 'Gluten',
            self::LACTOSE->value => 'Lactose',
            self::ARACHIDES->value => 'Arachides',
            self::FRUITS_A_COQUE->value => 'Fruits à coque',
            self::POISSON->value => 'Poisson',
            self::CRUSTACES->value => 'Crustacés',
            self::OEUF->value => 'Œuf',
            self::SOJA->value => 'Soja',
            self::MOUTARDE->value => 'Moutarde',
            self::SESAME->value => 'Sésame',
            self::SULFITES->value => 'Sulfites',
            self::CELERI->value => 'Céleri',
            self::LUPIN->value => 'Lupin',
            self::MOLLUSQUES->value => 'Mollusques',
        ];
    }
}
