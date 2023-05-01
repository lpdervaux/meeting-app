<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\CacheableVoterInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class UserVoter
    extends Voter
    implements CacheableVoterInterface
{
    public const CREATE = 'create';
    public const DELETE = 'delete';
    public const EDIT = 'edit';
    public const VIEW = 'view';

    public function __construct (private readonly Security $security) {}

    public function supportsAttribute (string $attribute) : bool
    {
        return ( $attribute === self::CREATE )
            || ( $attribute === self::EDIT )
            || ( $attribute === self::DELETE )
            || ( $attribute === self::VIEW );
    }

    public function supportsType (string $subjectType) : bool
    {
        return ( $subjectType === User::class );
    }

    protected function supports (string $attribute, mixed $subject) : bool
    {
        return $this->supportsAttribute($attribute)
            && $subject instanceof User;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        return match ( $attribute ) {
            self::CREATE,
            self::DELETE => $this->security->isGranted('ROLE_ADMINISTRATOR'),

            self::VIEW => ( $user === $subject )
                || $this->security->isGranted('ROLE_ADMINISTRATOR'),

            self::EDIT => ( $user === $subject ),

            default => false
        };
    }
}
