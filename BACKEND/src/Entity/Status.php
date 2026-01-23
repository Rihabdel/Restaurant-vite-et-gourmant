<?php

namespace App\Entity;

enum Status: string
{
    case en_attente = 'en attente';
    case accepte = 'accepté';
    case en_preparation = 'en préparation';
    case livraison = 'livraison';
    case en_attente_de_retour = 'en attente de retour';
    case termine = 'terminé';
    case annule = 'annulé';

    public static function getValues(): array
    {
        return array_map(fn(Status $status) => $status->value, self::cases());
    }
    public function label(): string
    {
        return match ($this) {
            self::en_attente => 'En attente',
            self::accepte => 'Accepté',
            self::en_preparation => 'En préparation',
            self::livraison => 'Livraison',
            self::en_attente_de_retour => 'En attente de retour',
            self::termine => 'Terminé',
            self::annule => 'Annulé',
        };
    }
    public static function fromLabel(string $label): ?self
    {
        return match ($label) {
            'En attente' => self::en_attente,
            'Accepté' => self::accepte,
            'En préparation' => self::en_preparation,
            'Livraison' => self::livraison,
            'En attente de retour' => self::en_attente_de_retour,
            'Terminé' => self::termine,
            'Annulé' => self::annule,
            default => null,
        };
    }
    public function color(): string
    {
        return match ($this) {
            self::en_attente => 'gray',
            self::accepte => 'blue',
            self::en_preparation => 'orange',
            self::livraison => 'purple',
            self::en_attente_de_retour => 'teal',
            self::termine => 'green',
            self::annule => 'red',
        };
    }
}
