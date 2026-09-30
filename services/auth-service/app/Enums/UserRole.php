<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case DEPARTMENT_HEAD = 'department_head';
    case STAFF = 'staff';
    case STUDENT = 'student';
}