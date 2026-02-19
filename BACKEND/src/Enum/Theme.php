<?PHP


namespace App\Enum;


enum Theme: string
{
    case classique = 'classique';
    case anniversaire = 'anniversaire';
    case mariage = 'mariage';
    case entreprise = 'entreprise';
    case fete_des_meres = 'fete_des_meres';
    case noel = 'noel';
    case saint_valentin = 'saint_valentin';
    case halloween = 'halloween';
    case paques = 'paques';
    case autres = 'autres';

    public function label(): string
    {
        return match ($this) {
            self::classique => 'Classique',
            self::anniversaire => 'Anniversaire',
            self::mariage => 'Mariage',
            self::entreprise => 'Entreprise',
            self::fete_des_meres => 'Fête des Mères',
            self::noel => 'Noël',
            self::saint_valentin => 'Saint-Valentin',
            self::halloween => 'Halloween',
            self::paques => 'Pâques',
            self::autres => 'Autres',
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
    public function est_anniversaire(): bool
    {
        return $this === self::anniversaire;
    }
    public function est_mariage(): bool
    {
        return $this === self::mariage;
    }
    public function est_entreprise(): bool
    {
        return $this === self::entreprise;
    }
    public function est_fete_des_meres(): bool
    {
        return $this === self::fete_des_meres;
    }
    public function est_noel(): bool
    {
        return $this === self::noel;
    }
    public function est_saint_valentin(): bool
    {
        return $this === self::saint_valentin;
    }
    public function est_halloween(): bool
    {
        return $this === self::halloween;
    }
    public function est_paques(): bool
    {
        return $this === self::paques;
    }
    public function est_autres(): bool
    {
        return $this === self::autres;
    }
}
