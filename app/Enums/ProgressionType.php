<?php

namespace App\Enums;

enum ProgressionType: string
{
    case PROMOTION = 'promotion';
    case GRADUATION = 'graduation';
    case RETENTION = 'retention';

    public function label(): string
    {
        return match ($this) {
            self::PROMOTION => 'Naik Kelas',
            self::GRADUATION => 'Lulus',
            self::RETENTION => 'Tinggal Kelas',
        };
    }
}
