<?php

namespace App\Entity;

use App\Enum\RideRhythm;
use App\Enum\DriverLevel;
use App\Enum\RideStatus;
use App\Repository\RideRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RideRepository::class)]
class Ride
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $descriptionMessage = null;

    #[ORM\Column(length: 5)]
    private ?string $departmentCode = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $meetingDatetime = null;

    #[ORM\Column(type: Types::TIME_IMMUTABLE)]
    private ?\DateTimeImmutable $StartTime = null;

    #[ORM\Column(type: Types::TIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $endTime = null;

    #[ORM\Column(length: 255)]
    private ?string $meetingPlace = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $endPoint = null;

    #[ORM\Column(nullable: true)]
    private ?int $distanceKm = null;

    #[ORM\Column(length: 20, enumType: RideRhythm::class)]
    private ?RideRhythm $rideType = null;

    #[ORM\Column(length: 20, enumType:DriverLevel::class)]
    private ?DriverLevel $pilotLevel = null;

    #[ORM\Column]
    private ?int $capacity = null;

    #[ORM\Column(length: 20, enumType: RideStatus::class)]
    private ?RideStatus $statut = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescriptionMessage(): ?string
    {
        return $this->descriptionMessage;
    }

    public function setDescriptionMessage(?string $descriptionMessage): static
    {
        $this->descriptionMessage = $descriptionMessage;

        return $this;
    }

    public function getDepartmentCode(): ?string
    {
        return $this->departmentCode;
    }

    public function setDepartmentCode(string $departmentCode): static
    {
        $this->departmentCode = $departmentCode;

        return $this;
    }

    public function getMeetingDatetime(): ?\DateTimeImmutable
    {
        return $this->meetingDatetime;
    }

    public function setMeetingDatetime(\DateTimeImmutable $meetingDatetime): static
    {
        $this->meetingDatetime = $meetingDatetime;

        return $this;
    }

    public function getStartTime(): ?\DateTimeImmutable
    {
        return $this->StartTime;
    }

    public function setStartTime(\DateTimeImmutable $StartTime): static
    {
        $this->StartTime = $StartTime;

        return $this;
    }

    public function getEndTime(): ?\DateTimeImmutable
    {
        return $this->endTime;
    }

    public function setEndTime(?\DateTimeImmutable $endTime): static
    {
        $this->endTime = $endTime;

        return $this;
    }

    public function getMeetingPlace(): ?string
    {
        return $this->meetingPlace;
    }

    public function setMeetingPlace(string $meetingPlace): static
    {
        $this->meetingPlace = $meetingPlace;

        return $this;
    }

    public function getEndPoint(): ?string
    {
        return $this->endPoint;
    }

    public function setEndPoint(?string $endPoint): static
    {
        $this->endPoint = $endPoint;

        return $this;
    }

    public function getDistanceKm(): ?int
    {
        return $this->distanceKm;
    }

    public function setDistanceKm(?int $distanceKm): static
    {
        $this->distanceKm = $distanceKm;

        return $this;
    }

    public function getRideType(): ?RideRhythm
    {
        return $this->rideType;
    }

    public function setRideType(RideRhythm $rideType): static
    {
        $this->rideType = $rideType;

        return $this;
    }

    public function getPilotLevel(): ?DriverLevel
    {
        return $this->pilotLevel;
    }

    public function setPilotLevel(DriverLevel $pilotLevel): static
    {
        $this->pilotLevel = $pilotLevel;

        return $this;
    }

    public function getCapacity(): ?int
    {
        return $this->capacity;
    }

    public function setCapacity(int $capacity): static
    {
        $this->capacity = $capacity;

        return $this;
    }

    public function getStatut(): ?RideStatus
    {
        return $this->statut;
    }

    public function setStatut(RideStatus $statut): static
    {
        $this->statut = $statut;

        return $this;
    }
}
