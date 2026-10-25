<?php

namespace App\Enums;

enum Timeline: string
{
    use HasOptions;

    case Asap = 'asap';
    case Quarter = 'quarter';
    case Semester = 'semester';
    case Later = 'later';

    public function label(): string
    {
        return match ($this) {
            self::Asap => 'Dès que possible',
            self::Quarter => 'Dans les 3 mois',
            self::Semester => 'Dans les 6 mois',
            self::Later => 'Plus tard / exploration',
        };
    }
}
