<?php

namespace App\Enums;

enum AuditVisibility: string
{
    case INTERNAL = 'internal';
    case SUBJECT_VISIBLE = 'subject_visible';
}
