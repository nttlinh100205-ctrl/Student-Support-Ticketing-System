<?php

namespace App\Enums;

enum RequestStatus: string
{
    case New = 'new';
    case Received = 'received';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Cancelled = 'cancelled';
}