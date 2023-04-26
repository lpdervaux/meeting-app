<?php

declare(strict_types=1);

namespace App\Entity;

enum MeetupStatus
{
    case Scheduled;
    case Open;
    case Closed;
    case Ongoing;
    case Concluded;
    case Cancelled;
}
