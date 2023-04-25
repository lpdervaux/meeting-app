<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Meetup;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class MeetupFixtures
    extends FakerFixtures
    implements DependentFixtureInterface
{
    public const COUNT = 100;

    public function getDependencies () : array
    {
        return [
            UserFixtures::class,
            CampusFixtures::class,
            LocationFixtures::class
        ];
    }

    public function load (ObjectManager $manager) : void
    {
        parent::load($manager);

        // TODO?: ensure certain kinds of meetups (open, closed, passed, occurring etc)
        $this->fakeMany(self::COUNT);

        $manager->flush();
    }

    // TODO: tidy up
    protected function generate (
        \DateTimeImmutable $start = null,
        \DateTimeImmutable $end = null,
        \DateTimeImmutable $registrationStart = null,
        \DateTimeImmutable $registrationEnd = null,
    ) : Meetup
    {
        try {
            $start = ( $start ) ?:
                \DateTimeImmutable::createFromMutable($this->generator->dateTimeBetween('-1 year', '+6 months'));
            $end = ( $end ) ?:
                $start->add(new \DateInterval('PT' . mt_rand(1, 4) . 'H'));
            $registrationEnd = ( $registrationEnd ) ?:
                $start->sub(new \DateInterval('P' . mt_rand(7, 21) . 'D'));
            $registrationStart = ( $registrationStart ) ?:
                $registrationEnd->sub(new \DateInterval('P' . mt_rand(30, 60) . 'D'));

            $cancelled = (bool) mt_rand(0, 1);

            $meetup = new Meetup();
            $meetup
                ->setName($this->generator->sentence())
                ->setDescription($this->generator->paragraph())
                ->setCapacity($this->generator->numberBetween(10, 30))
                ->setLocation($this->getReference(LocationFixtures::class . mt_rand(0, LocationFixtures::COUNT -1)))
                ->setCoordinator($this->getReference(UserFixtures::class . mt_rand(0, UserFixtures::COUNT - 1)))
                ->setCampus($this->getReference(CampusFixtures::class . mt_rand(0, CampusFixtures::COUNT - 1)))
                ->setStart($start)
                ->setEnd($end)
                ->setRegistrationStart($registrationStart)
                ->setRegistrationEnd($registrationEnd)
                ->setCancelled($cancelled);

            if ( $cancelled )
            {
                $meetup->setCancellationDate($start->sub(new \DateInterval('P' . mt_rand(1, 60) . 'D')));
                $meetup->setCancellationReason($this->generator->paragraph());
            }

            $now = new \DateTimeImmutable('now');
            if ( $registrationStart < $now )
            {
                for (
                    $i = 0;
                    $i < mt_rand(0, $meetup->getCapacity());
                    $i++
                ) {
                    $meetup->addAttendee($this->getReference(UserFixtures::class . mt_rand(0, UserFixtures::COUNT -1)));
                }
            }

            return $meetup;
        }
        catch ( \Exception $e ) {
            // PHP DateTime function may throw generic exceptions on string parsing but offer no other way of interaction
            throw new \LogicException(previous: $e);
        }
    }
}
