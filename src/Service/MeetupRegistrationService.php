<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Meetup;
use App\Entity\User;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

readonly class MeetupRegistrationService
{
    public function __construct (
        private EntityManagerInterface $entityManager
    ) {}

    public function register (Meetup $meetup, User $user) : bool
    {
        return $this->lock($meetup, fn (Meetup $refresh) => $this->registerUser($refresh, $user));
    }

    public function cancel (Meetup $meetup, User $user) : bool
    {
        return $this->lock($meetup, fn (Meetup $refresh) => $this->cancelUser($refresh, $user));
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
        if ( $meetup->canRegister($user) )
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
        if ( $meetup->canCancel($user) )
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