<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Meetup;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

readonly class MeetupCancellationService
{
    public function __construct (
        private AuthorizationCheckerInterface $authorizationChecker,
        private EntityManagerInterface $entityManager
    ) {}

    public function isCancellable (Meetup $meetup, ?\DateTimeImmutable $on = null) : bool
    {
        $on ??= new \DateTimeImmutable();

        return ( ! $meetup->isCancelled() )
            && ( $on < $meetup->getStart() )
            && ( $this->authorizationChecker->isGranted('cancel', $meetup) );
    }

    public function cancel (Meetup $meetup, ?string $cancellationReason = null, ?FlashBagInterface $flash = null) : bool
    {
        $now = new \DateTimeImmutable();

        if ( $this->isCancellable($meetup, $now) )
        {
            $meetup->setCancelled(true);
            $meetup->setCancellationDate($now);
            if ( $cancellationReason )
                $meetup->setCancellationReason($cancellationReason);
            $this->entityManager->persist($meetup);
            $this->entityManager->flush();

            $success = true;
        }
        else
            $success = false;

        if ( $flash )
        {
            if ( $success )
                $flash->add('success', "{$meetup->getName()} : Sortie annulée");
            else
                $flash->add('warning', "{$meetup->getName()} : Annulation échouée");
        }

        return $success;
    }
}