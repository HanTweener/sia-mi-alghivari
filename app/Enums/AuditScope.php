<?php

namespace App\Enums;

enum AuditScope: string
{
    case STUDENT = 'student';
    case CLASSROOM = 'class';
    case ASSIGNMENT = 'assignment';
    case TERM = 'term';
    case SUBJECT = 'subject';
    case ATTENDANCE = 'attendance';
    case ASSESSMENT = 'assessment';
    case REPORT = 'report';
    case PROGRESSION = 'progression';
    case USER = 'user';
    case SYSTEM = 'system';
}
