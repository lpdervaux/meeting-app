<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Campus;
use App\Entity\Location;
use App\Entity\Meetup;
use App\Entity\MeetupStatus;
use App\Entity\User;
use App\Service\FakerService;
use App\Service\MeetupScheduleGeneratorService;
use DateTimeInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class MeetupFixtures
    extends FakerFixtures
    implements DependentFixtureInterface
{
    public const COUNT = 200;

    public function __construct (
        FakerService $fakerService,
        private readonly MeetupScheduleGeneratorService $scheduleGenerator
    ) {
        parent::__construct($fakerService);
    }

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

        $now = new \DateTimeImmutable();

        $this->fakeSample($now, attendee: $this->getReference(UserFixtures::DEFAULT_USER));
        for ( $i = 0; $i < CampusFixtures::COUNT ; $i++ )
            $this->fakeSample($now, campus: $this->getReference(CampusFixtures::class . $i));

        $this->fakeMany(self::COUNT);

        $manager->flush();
    }

    private function fakeSample (\DateTimeImmutable $now, mixed ...$options) : void
    {
        $this->fakeMany(
            5,
            entityGenerator: fn () : Meetup => $this->generateStatus(MeetupStatus::Scheduled, $now, ...$options),
            forget: true
        );

        $this->fakeMany(
            5,
            entityGenerator: fn () : Meetup => $this->generateStatus(MeetupStatus::Open, $now, ...$options),
            forget: true
        );

        $this->fakeMany(
            5,
            entityGenerator: fn () : Meetup => $this->generateStatus(MeetupStatus::Closed, $now, ...$options),
            forget: true
        );

        $this->fakeMany(
            5,
            entityGenerator: fn () : Meetup => $this->generateStatus(MeetupStatus::Ongoing, $now, ...$options),
            forget: true
        );

        $this->fakeMany(
            5,
            entityGenerator: fn () : Meetup => $this->generateStatus(MeetupStatus::Concluded, $now, ...$options),
            forget: true
        );

        // open and full
        $this->fakeMany(
            5,
            entityGenerator:
            fn () : Meetup =>
            $this->generateStatus(
                MeetupStatus::Open,
                $now,
                ...$options,
                capacity: 10,
                attendeeCount: 10,
            ),
            forget: true
        );

        // cancelled and open
        $this->fakeMany(
            5,
            entityGenerator:
            function () use ($now, $options) : Meetup
            {
                $meetup = $this->generateStatus(
                    MeetupStatus::Open,
                    $now,
                    ...$options,
                    cancelled: true
                );
                $meetup->setCancellationDate(
                    $this->dateTimeImmutableBetween(
                        $meetup->getRegistrationStart(),
                        $now
                    )
                );

                return $meetup;
            },
            forget: true
        );

        // cancelled and closed
        $this->fakeMany(
            5,
            entityGenerator:
            function () use ($now, $options) : Meetup
            {
                $meetup = $this->generateStatus(
                    MeetupStatus::Closed,
                    $now,
                    ...$options,
                    cancelled: true
                );
                $meetup->setCancellationDate(
                    $this->dateTimeImmutableBetween(
                        $meetup->getRegistrationEnd(),
                        $now
                    )
                );

                return $meetup;
            },
            forget: true
        );
    }

    protected function generate (
        ?string $name = null,
        ?string $description = null,
        ?int $capacity = null,
        ?Location $location = null,
        ?User $coordinator = null,
        ?Campus $campus = null,
        ?\DateTimeImmutable $registrationStart = null,
        ?\DateTimeImmutable $registrationEnd = null,
        ?\DateTimeImmutable $start = null,
        ?\DateTimeImmutable $end = null,
        ?bool $cancelled = null,
        ?\DateTimeImmutable $cancellationDate = null,
        ?string $cancellationReason = null,

        ?int $attendeeCount = null,
        ?User $attendee = null,

        ?\DateTimeImmutable $now = null
    ) : Meetup
    {
        $now ??= new \DateTimeImmutable();

        $name ??= $this->generator->sentence();
        $description ??= $this->generator->paragraph();
        $capacity ??= $this->generator->numberBetween(5, 50);

        $location ??= $this->getReference(
            LocationFixtures::class . mt_rand(0, LocationFixtures::COUNT -1)
        );
        $coordinator ??= $this->getReference(
            UserFixtures::class . mt_rand(0, UserFixtures::COUNT - 1)
        );
        $campus ??= $this->getReference(
            CampusFixtures::class . mt_rand(0, CampusFixtures::COUNT - 1)
        );

        $registrationStart ??= $this->dateTimeImmutableBetween(
            $now->modify('-6 months'),
            $now->modify('+2 months')
        );
        $registrationEnd ??= $this->scheduleGenerator->registrationEndFromRegistrationStart($registrationStart);
        $start ??= $this->scheduleGenerator->startFromRegistrationEnd($registrationEnd);
        $end ??= $this->scheduleGenerator->endFromStart($start);

        $cancelled ??= (bool) mt_rand(0, 1);
        if ( $cancelled )
        {
            $cancellationDate ??= $this->dateTimeImmutableBetween(
                $registrationStart->modify('-7 days'),
                $start->modify('-1 hour')
            );
            $cancellationReason ??= $this->generator->paragraph();
        }

        $attendeeCount ??= mt_rand(0, $capacity);

        // entity creation
        $meetup = new Meetup();
        $meetup
            ->setName($name)
            ->setDescription($description)
            ->setCapacity($capacity)
            ->setLocation($location)
            ->setCoordinator($coordinator)
            ->setCampus($campus)
            ->setStart($start)
            ->setEnd($end)
            ->setRegistrationStart($registrationStart)
            ->setRegistrationEnd($registrationEnd)
            ->setCancelled($cancelled);

        if ( $cancelled )
        {
            $meetup->setCancellationDate($cancellationDate);
            $meetup->setCancellationReason($cancellationReason);
        }

        if ( $registrationStart < $now )
        {
            if ( $attendeeCount > UserFixtures::COUNT )
                throw new \LogicException('User fixtures cannot support requested attendee count');
            if ( $attendee )
                $meetup->addAttendee($attendee);
            while ( $meetup->getAttendees()->count() < $attendeeCount )
            {
                $meetup->addAttendee(
                    $this->getReference(UserFixtures::class . mt_rand(0, UserFixtures::COUNT -1))
                );
            }
        }

        return $meetup;
    }

    private function generateStatus (
        MeetupStatus $status,
        ?\DateTimeImmutable $on = null,
        mixed ...$options
    ) : Meetup
    {
        $on ??= new \DateTimeImmutable();

        switch ( $status )
        {
            case MeetupStatus::Scheduled:
                $registrationStart = $this->dateTimeImmutableBetween(
                    $on->modify('+7 days'),
                    $on->modify('+14 days')
                );
                break;
            case MeetupStatus::Open:
                $registrationStart = $this->dateTimeImmutableBetween(
                    $on->modify('-8 days'),
                    $on->modify('-1 day')
                );
                break;
            case MeetupStatus::Closed:
                $registrationEnd = $this->dateTimeImmutableBetween(
                    $on->modify('-8 days'),
                    $on->modify('-1 day')
                );
                $registrationStart = $this->scheduleGenerator->registrationStartFromRegistrationEnd($registrationEnd);
                break;
            case MeetupStatus::Ongoing:
                $start = $this->dateTimeImmutableBetween(
                    $on->modify('-30 minutes'),
                    $on
                );
                $registrationEnd = $this->scheduleGenerator->registrationEndFromStart($start);
                $registrationStart = $this->scheduleGenerator->registrationStartFromRegistrationEnd($registrationEnd);
                break;
            case MeetupStatus::Concluded:
                $end = $this->dateTimeImmutableBetween(
                    $on->modify('-8 days'),
                    $on->modify('-1 day')
                );
                $start = $this->scheduleGenerator->startFromEnd($end);
                $registrationEnd = $this->scheduleGenerator->registrationEndFromStart($start);
                $registrationStart = $this->scheduleGenerator->registrationStartFromRegistrationEnd($registrationEnd);
                break;
            default:
                throw new \LogicException('Status not supported');
        }

        return $this->generate(
            ...[
                'now' => $on,

                'registrationStart' => $registrationStart,
                'registrationEnd' => $registrationEnd ?? null,
                'start' => $start ?? null,
                'end' => $end ?? null,

                ...$options
            ]
        );
    }

    private function dateTimeImmutableBetween (\DateTimeImmutable $earlier, \DateTimeImmutable $later) : \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromMutable(
            $this->generator->dateTimeBetween(
                $earlier->format(DateTimeInterface::ATOM),
                $later->format(DateTimeInterface::ATOM)
            )
        );
    }
}
