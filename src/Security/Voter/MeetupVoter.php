<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Meetup;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\CacheableVoterInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class MeetupVoter
    extends Voter
    implements CacheableVoterInterface
{
    public const VIEW = 'view';
    public const CANCEL = 'cancel';

    public function __construct (readonly private Security $security) {}

    public function supportsAttribute (string $attribute) : bool
    {
        return ( $attribute === self::VIEW )
            || ( $attribute === self::CANCEL );
    }

    public function supportsType (string $subjectType) : bool
    {
        return ( $subjectType === Meetup::class );
    }

    protected function supports (string $attribute, mixed $subject): bool
    {
        return $this->supportsAttribute($attribute)
            && $subject instanceof Meetup;
    }

    protected function voteOnAttribute (
        string $attribute,
        mixed $subject,
        TokenInterface $token
    ) : bool
    {
        $user = $token->getUser();

        return match ( $attribute )
        {
            self::VIEW =>
                ( $subject->getEnd() < (new \DateTimeImmutable())->modify('-1 month') ),
            self::CANCEL =>
                ($user === $subject->getCoordinator())
                || ($this->security->isGranted('ROLE_ADMINISTRATOR')),
            default => false
        };
    }
}
