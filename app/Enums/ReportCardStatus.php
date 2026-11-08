<?php

namespace App\Enums;

enum ReportCardStatus: string
{
    case DRAFT = 'draft';
    case FINALIZED = 'finalized';
    case PUBLISHED = 'published';
    case REVOKED = 'revoked';
}
