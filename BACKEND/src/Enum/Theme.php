<?PHP


namespace App\Enum;


enum Theme: string
{
    case classique = 'classique';
    case anniversaire = 'anniversaire';
    case mariage = 'mariage';
    case bapteme = 'bapteme';
    case entreprise = 'entreprise';
    case familiale = 'familiale';
    case noel = 'noel';
    case halloween = 'halloween';
    case paques = 'paques';
    case autres = 'autres';

    public function label(): string
    {
        return match ($this) {
            self::classique => 'Classique',
            self::anniversaire => 'Anniversaire',
            self::mariage => 'Mariage',
            self::bapteme => 'Baptême',
            self::entreprise => 'Entreprise',
            self::familiale => 'Familiale',
            self::noel => 'Noël',
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
    public function est_familiale(): bool
    {
        return $this === self::familiale;
    }
    public function est_noel(): bool
    {
        return $this === self::noel;
    }
    public function est_bapteme(): bool
    {
        return $this === self::bapteme;
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
