<?php

namespace App\Enum;

enum Status: string
{
    case en_attente = 'en attente';
    case accepté = 'accepté';
    case en_préparation = 'en préparation';
    case livrée = 'livrée';
    case en_attente_de_retour = 'en attente de retour';
    case terminée = 'terminée';
    case annulé = 'annulé';

    public static function getValues(): array
    {
        return array_map(fn(Status $status) => $status->value, self::cases());
    }
    public function label(): string
    {
        return match ($this) {
            self::en_attente => 'En attente',
            self::accepté => 'Accepté',
            self::en_préparation => 'En préparation',
            self::livrée => 'Livrée',
            self::en_attente_de_retour => 'En attente de retour',
            self::terminée => 'Terminée',
            self::annulé => 'Annulé',
        };
    }
    public static function fromLabel(string $label): ?self
    {
        return match ($label) {
            'En attente' => self::en_attente,
            'Accepté' => self::accepté,
            'En préparation' => self::en_préparation,
            'Livrée' => self::livrée,
            'En attente de retour' => self::en_attente_de_retour,
            'Terminée' => self::terminée,
            'Annulé' => self::annulé,
            default => null,
        };
    }
    public function color(): string
    {
        return match ($this) {
            self::en_attente => 'gray',
            self::accepté => 'blue',
            self::en_préparation => 'orange',
            self::livrée => 'purple',
            self::en_attente_de_retour => 'teal',
            self::terminée => 'green',
            self::annulé => 'red',
        };
    }
}
