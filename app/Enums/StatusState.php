<?php

namespace App\Enums;

enum StatusState: string
{
    use HasOptions;

    case Operational = 'operational';
    case Degraded = 'degraded';
    case Outage = 'outage';
    case Maintenance = 'maintenance';

    public function label(): string
    {
        return match ($this) {
            self::Operational => 'Opérationnel',
            self::Degraded => 'Performances dégradées',
            self::Outage => 'Incident en cours',
            self::Maintenance => 'Maintenance',
        };
    }
}
