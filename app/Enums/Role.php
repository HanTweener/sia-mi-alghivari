<?php

namespace App\Enums;

enum Role: string
{
    case MASTER_ADMIN = 'master_admin';
    case ACADEMIC_ADMIN = 'academic_admin';
    case DATA_ENTRY_ADMIN = 'data_entry_admin';
    case TEACHER = 'teacher';
    case PRINCIPAL = 'principal';
    case GUARDIAN = 'guardian';

    public function label(): string
    {
        return match ($this) {
            self::MASTER_ADMIN => 'Master Admin',
            self::ACADEMIC_ADMIN => 'Admin Akademik',
            self::DATA_ENTRY_ADMIN => 'Admin Data Entry',
            self::TEACHER => 'Guru',
            self::PRINCIPAL => 'Kepala Sekolah',
            self::GUARDIAN => 'Orang Tua / Wali',
        };
    }
}
