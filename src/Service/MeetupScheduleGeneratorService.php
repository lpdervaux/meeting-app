<?php

declare(strict_types=1);

namespace App\Service;

readonly class MeetupScheduleGeneratorService
{
    public const OPEN_DEVIATION_MINIMUM = 7;
    public const OPEN_DEVIATION_MAXIMUM = 21;

    public const CLOSED_DEVIATION_MINIMUM = 7;
    public const CLOSED_DEVIATION_MAXIMUM = 21;

    public const ONGOING_DEVIATION_MINIMUM = 1;
    public const ONGOING_DEVIATION_MAXIMUM = 12;

    static private function openDeviation () : int
    {
        return mt_rand(self::OPEN_DEVIATION_MINIMUM, self::OPEN_DEVIATION_MAXIMUM);
    }

    static private function closedDeviation () : int
    {
        return mt_rand(self::CLOSED_DEVIATION_MINIMUM, self::CLOSED_DEVIATION_MAXIMUM);
    }

    static private function ongoingDeviation () : int
    {
        return mt_rand(self::ONGOING_DEVIATION_MINIMUM, self::ONGOING_DEVIATION_MAXIMUM);
    }

    public function registrationEndFromRegistrationStart (\DateTimeImmutable $registrationStart) : \DateTimeImmutable
    {
        return $registrationStart->modify('+' . self::openDeviation() . ' days');
    }

    public function startFromRegistrationEnd (\DateTimeImmutable $registrationEnd) : \DateTimeImmutable
    {
        return $registrationEnd->modify('+' . self::closedDeviation() . ' days');
    }

    public function endFromStart (\DateTimeImmutable $start) : \DateTimeImmutable
    {
        return $start->modify('+' . self::ongoingDeviation() . ' hours');
    }

    public function startFromEnd (\DateTimeImmutable $end) : \DateTimeImmutable
    {
        return $end->modify('-' . self::ongoingDeviation() . ' hours');
    }

    public function registrationEndFromStart (\DateTimeImmutable $start) : \DateTimeImmutable
    {
        return $start->modify('-' . self::closedDeviation() . ' days');
    }

    public function registrationStartFromRegistrationEnd (\DateTimeImmutable $registrationEnd) : \DateTimeImmutable
    {
        return $registrationEnd->modify('-' . self::openDeviation() . ' days');
    }
}