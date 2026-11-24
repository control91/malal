<?php

namespace App\Enums;

enum ContractType: string
{
    use HasOptions;

    case Cdi = 'cdi';
    case Cdd = 'cdd';
    case Internship = 'internship';
    case Freelance = 'freelance';

    public function label(): string
    {
        return match ($this) {
            self::Cdi => 'CDI',
            self::Cdd => 'CDD',
            self::Internship => 'Stage',
            self::Freelance => 'Freelance',
        };
    }
}
