<?php

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
    public const CANCEL = 'cancel';

    public function __construct (readonly private Security $security) {}

    public function supportsAttribute (string $attribute) : bool
    {
        return ( $attribute === self::CANCEL );
    }

    public function supportsType (string $subjectType) : bool
    {
        return ( $subjectType === Meetup::class );
    }

    protected function supports (string $attribute, mixed $subject): bool
    {
        return $attribute === self::CANCEL
            && $subject instanceof Meetup;
    }

    protected function voteOnAttribute (
        string $attribute,
        mixed $subject,
        TokenInterface $token
    ) : bool
    {
        $user = $token->getUser();

        return ( $user === $subject->getCoordinator() )
            || ( $this->security->isGranted('ROLE_ADMINISTRATOR') );
    }
}
