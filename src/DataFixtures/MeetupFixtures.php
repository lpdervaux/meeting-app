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
    public const COUNT = 100;

    public const SCHEDULED_COUNT = 5;
    public const SCHEDULED_PREFIX = __CLASS__ . 'scheduled';
    public const OPEN_COUNT = 5;
    public const OPEN_PREFIX = __CLASS__ . 'open';
    public const CLOSED_COUNT = 5;
    public const CLOSED_PREFIX = __CLASS__ . 'closed';
    public const ONGOING_COUNT = 5;
    public const ONGOING_PREFIX = __CLASS__ . 'ongoing';
    public const CONCLUDED_COUNT = 5;
    public const CONCLUDED_PREFIX = __CLASS__ . 'concluded';

    public const OPEN_FULL_COUNT = 3;
    public const OPEN_FULL_PREFIX = __CLASS__ . 'openFull';
    public const OPEN_CANCELLED_COUNT = 3;
    public const OPEN_CANCELLED_PREFIX = __CLASS__ . 'openCancelled';
    public const CLOSED_CANCELLED_COUNT = 3;
    public const CLOSED_CANCELLED_PREFIX = __CLASS__ . 'closedCancelled';

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

        $this->fakeMany(
            self::SCHEDULED_COUNT,
            self::SCHEDULED_PREFIX,
            fn () : Meetup => $this->generateStatus(MeetupStatus::Scheduled, $now)
        );

        $this->fakeMany(
            self::OPEN_COUNT,
            self::OPEN_PREFIX,
            fn () : Meetup => $this->generateStatus(MeetupStatus::Open, $now)
        );

        $this->fakeMany(
            self::CLOSED_COUNT,
            self::CLOSED_PREFIX,
            fn () : Meetup => $this->generateStatus(MeetupStatus::Closed, $now)
        );

        $this->fakeMany(
            self::ONGOING_COUNT,
            self::ONGOING_PREFIX,
            fn () : Meetup => $this->generateStatus(MeetupStatus::Ongoing, $now)
        );

        $this->fakeMany(
            self::CONCLUDED_COUNT,
            self::CONCLUDED_PREFIX,
            fn () : Meetup => $this->generateStatus(MeetupStatus::Concluded, $now)
        );

        $this->fakeMany(
            self::OPEN_FULL_COUNT,
            self::OPEN_FULL_PREFIX,
            fn () : Meetup =>
                $this->generateStatus(
                    MeetupStatus::Open,
                    $now,
                    capacity: 10,
                    attendeeCount: 10
                )
        );

        $this->fakeMany(
            self::OPEN_CANCELLED_COUNT,
            self::OPEN_CANCELLED_PREFIX,
            function () use ($now) : Meetup
            {
                $meetup = $this->generateStatus(
                    MeetupStatus::Open,
                    $now,
                    cancelled: true
                );
                $meetup->setCancellationDate(
                    $this->dateTimeImmutableBetween(
                        $meetup->getRegistrationStart(),
                        $now
                    )
                );

                return $meetup;
            }
        );

        $this->fakeMany(
            self::CLOSED_CANCELLED_COUNT,
            self::CLOSED_CANCELLED_PREFIX,
            function () use ($now) : Meetup
            {
                $meetup = $this->generateStatus(
                    MeetupStatus::Closed,
                    $now,
                    cancelled: true
                );
                $meetup->setCancellationDate(
                    $this->dateTimeImmutableBetween(
                        $meetup->getRegistrationEnd(),
                        $now
                    )
                );

                return $meetup;
            }
        );

        $this->fakeMany(self::COUNT);

        $manager->flush();
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
        ?\DateTimeImmutable $at = null,
        mixed ...$options
    ) : Meetup
    {
        $at ??= new \DateTimeImmutable();

        switch ( $status )
        {
            case MeetupStatus::Scheduled:
                $registrationStart = $this->dateTimeImmutableBetween(
                    $at->modify('+7 days'),
                    $at->modify('+14 days')
                );
                break;
            case MeetupStatus::Open:
                $registrationStart = $this->dateTimeImmutableBetween(
                    $at->modify('-8 days'),
                    $at->modify('-1 day')
                );
                break;
            case MeetupStatus::Closed:
                $registrationEnd = $this->dateTimeImmutableBetween(
                    $at->modify('-8 days'),
                    $at->modify('-1 day')
                );
                $registrationStart = $this->scheduleGenerator->registrationStartFromRegistrationEnd($registrationEnd);
                break;
            case MeetupStatus::Ongoing:
                $start = $this->dateTimeImmutableBetween(
                    $at->modify('-30 minutes'),
                    $at
                );
                $registrationEnd = $this->scheduleGenerator->registrationEndFromStart($start);
                $registrationStart = $this->scheduleGenerator->registrationStartFromRegistrationEnd($registrationEnd);
                break;
            case MeetupStatus::Concluded:
                $end = $this->dateTimeImmutableBetween(
                    $at->modify('-8 days'),
                    $at->modify('-1 day')
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
                'now' => $at,

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
