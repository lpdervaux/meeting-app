<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\MeetupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MeetupRepository::class)]
class Meetup
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(options: ['unsigned' => true])]
    private ?int $capacity = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Location $location = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Campus $campus = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $coordinator = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private ?\DateTimeImmutable $start = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private ?\DateTimeImmutable $end = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private ?\DateTimeImmutable $registrationStart = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private ?\DateTimeImmutable $registrationEnd = null;

    #[ORM\Column]
    private ?bool $cancelled = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $cancellationDate = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $cancellationReason = null;

    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'meetups')]
    private Collection $attendees;

    public function __construct()
    {
        $this->attendees = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getCapacity(): ?int
    {
        return $this->capacity;
    }

    public function setCapacity(int $capacity): self
    {
        $this->capacity = $capacity;

        return $this;
    }

    public function getLocation(): ?Location
    {
        return $this->location;
    }

    public function setLocation(?Location $location): self
    {
        $this->location = $location;

        return $this;
    }

    public function getCampus(): ?Campus
    {
        return $this->campus;
    }

    public function setCampus(?Campus $campus): self
    {
        $this->campus = $campus;

        return $this;
    }

    public function getCoordinator(): ?User
    {
        return $this->coordinator;
    }

    public function setCoordinator(?User $coordinator): self
    {
        $this->coordinator = $coordinator;

        return $this;
    }

    public function getStart(): ?\DateTimeImmutable
    {
        return $this->start;
    }

    public function setStart(\DateTimeImmutable $start): self
    {
        $this->start = $start;

        return $this;
    }

    public function getEnd(): ?\DateTimeImmutable
    {
        return $this->end;
    }

    public function setEnd(\DateTimeImmutable $end): self
    {
        $this->end = $end;

        return $this;
    }

    public function getRegistrationStart(): ?\DateTimeImmutable
    {
        return $this->registrationStart;
    }

    public function setRegistrationStart(\DateTimeImmutable $registrationStart): self
    {
        $this->registrationStart = $registrationStart;

        return $this;
    }

    public function getRegistrationEnd(): ?\DateTimeImmutable
    {
        return $this->registrationEnd;
    }

    public function setRegistrationEnd(\DateTimeImmutable $registrationEnd): self
    {
        $this->registrationEnd = $registrationEnd;

        return $this;
    }

    public function isCancelled(): ?bool
    {
        return $this->cancelled;
    }

    public function setCancelled(bool $cancelled): self
    {
        $this->cancelled = $cancelled;

        return $this;
    }

    public function getCancellationDate(): ?\DateTimeImmutable
    {
        return $this->cancellationDate;
    }

    public function setCancellationDate(?\DateTimeImmutable $cancellationDate): self
    {
        $this->cancellationDate = $cancellationDate;

        return $this;
    }

    public function getCancellationReason(): ?string
    {
        return $this->cancellationReason;
    }

    public function setCancellationReason(?string $cancellationReason): self
    {
        $this->cancellationReason = $cancellationReason;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getAttendees(): Collection
    {
        return $this->attendees;
    }

    public function addAttendee(User $attendee): self
    {
        if (!$this->attendees->contains($attendee)) {
            $this->attendees->add($attendee);
        }

        return $this;
    }

    public function removeAttendee(User $attendee): self
    {
        $this->attendees->removeElement($attendee);

        return $this;
    }

    public function getStatus (?\DateTimeImmutable $from) : MeetupStatus
    {
        if ( $this->isCancelled() )
            $status = MeetupStatus::Cancelled;
        else
        {
            $from = ( $from ) ?: new \DateTimeImmutable();
            $status = match (true)
            {
                ( $from < $this->registrationStart ) => MeetupStatus::Scheduled,
                ( $from < $this->registrationEnd ) => MeetupStatus::Open,
                ( $from < $this->start ) => MeetupStatus::Closed,
                ( $from < $this->end ) => MeetupStatus::Ongoing,
                default => MeetupStatus::Concluded
            };
        }

        return $status;
    }
}
