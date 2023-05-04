<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Meetup;
use App\Entity\MeetupStatus;
use App\Entity\User;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

readonly class MeetupRegistrationService
{
    public function __construct (
        private EntityManagerInterface $entityManager
    ) {}

    public function canRegister (Meetup $meetup, User $user) : bool
    {
        return ( $meetup->getStatus() === MeetupStatus::Open )
            && ( ! $meetup->getAttendees()->contains($user) )
            && ( $meetup->getAttendees()->count() < $meetup->getCapacity() );
    }

    public function canCancel (Meetup $meetup, User $user) : bool
    {
        return ( $meetup->getStatus() === MeetupStatus::Open )
            && ( $meetup->getAttendees()->contains($user) );
    }

    public function register (Meetup $meetup, User $user, ?FlashBagInterface $flash = null) : bool
    {
        $success = $this->lock($meetup, fn (Meetup $refresh) => $this->registerUser($refresh, $user));

        if ( $flash )
        {
            if ( $success )
                $flash->add('success', "{$meetup->getName()} : Inscription de {$user->getName()} {$user->getSurname()}");
            else
                $flash->add('warning', "{$meetup->getName()} : Inscription échouée");
        }

        return $success;
    }

    public function cancel (Meetup $meetup, User $user, ?FlashBagInterface $flash = null) : bool
    {
        $success = $this->lock($meetup, fn (Meetup $refresh) => $this->cancelUser($refresh, $user));

        if ( $flash )
        {
            if ( $success )
                $flash->add('success', "{$meetup->getName()} : Désinscription de {$user->getName()} {$user->getSurname()}");
            else
                $flash->add('warning', "{$meetup->getName()} : Désinscription échouée");
        }

        return $success;
    }

    private function lock (Meetup $meetup, callable $callback) : bool
    {
        $this->entityManager->beginTransaction();
        $this->entityManager->refresh($meetup, LockMode::PESSIMISTIC_READ);

        $success = $callback($meetup);

        $this->entityManager->commit();

        return $success;
    }

    private function registerUser (Meetup $meetup, User $user) : bool
    {
        if ( $this->canRegister($meetup, $user) )
        {
            $meetup->addAttendee($user);
            $this->entityManager->persist($meetup);
            $this->entityManager->flush();

            $success = true;
        }
        else
            $success = false;

        return $success;
    }

    private function cancelUser (Meetup $meetup, User $user) : bool
    {
        if ( $this->canCancel($meetup, $user) )
        {
            $meetup->removeAttendee($user);
            $this->entityManager->persist($meetup);
            $this->entityManager->flush();

            $success = true;
        }
        else
            $success = false;

        return $success;
    }
}