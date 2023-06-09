<?php

declare(strict_types=1);

namespace App\Entity;

enum MeetupStatus : string
{
    case Scheduled = 'Planifiée';
    case Open = 'Ouverte';
    case Closed = 'Fermée';
    case Ongoing = 'En cours';
    case Concluded = 'Terminée';
    case Cancelled = 'Annulée';
}
