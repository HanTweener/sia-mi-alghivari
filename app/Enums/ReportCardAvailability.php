<?php

namespace App\Enums;

enum ReportCardAvailability: string
{
    case CURRENT = 'current';
    case NEEDS_REVISION = 'needs_revision';
    case SUPERSEDED = 'superseded';
}
