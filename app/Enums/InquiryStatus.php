<?php

namespace App\Enums;

enum InquiryStatus: string
{
    use HasOptions;

    case New = 'new';
    case InProgress = 'in_progress';
    case Answered = 'answered';
    case Closed = 'closed';
    case Spam = 'spam';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Nouvelle',
            self::InProgress => 'En cours',
            self::Answered => 'Répondue',
            self::Closed => 'Clôturée',
            self::Spam => 'Indésirable',
        };
    }
}
