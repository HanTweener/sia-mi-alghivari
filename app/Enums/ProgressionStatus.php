<?php

namespace App\Enums;

enum ProgressionStatus: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case APPLIED = 'applied';
    case CANCELLED = 'cancelled';
    case NEEDS_REVIEW = 'needs_review';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draf Usulan',
            self::SUBMITTED => 'Diajukan',
            self::APPROVED => 'Disetujui',
            self::REJECTED => 'Ditolak',
            self::APPLIED => 'Diterapkan',
            self::CANCELLED => 'Dibatalkan',
            self::NEEDS_REVIEW => 'Perlu Ditinjau Ulang',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::APPLIED, self::CANCELLED, self::REJECTED], true);
    }

    public function canBeEdited(): bool
    {
        return in_array($this, [self::DRAFT, self::NEEDS_REVIEW], true);
    }
}
