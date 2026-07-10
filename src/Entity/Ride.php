<?php

namespace App\Entity;

use App\Enum\RideRhythm;
use App\Enum\DriverLevel;
use App\Enum\RideStatus;
use App\Repository\RideRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

    #[ORM\ManyToOne(inversedBy: 'rides')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    /**
     * @var Collection<int, Comment>
     */
    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'ride')]
    private Collection $comments;

    /**
     * @var Collection<int, Motorcycle>
     */
    #[ORM\ManyToMany(targetEntity: Motorcycle::class, inversedBy: 'rides')]
    private Collection $motorcycles;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'ridesParticipated')]
    #[ORM\JoinTable(name: 'ride_participant')]
    private Collection $participants;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'ridesLiked')]
    #[ORM\JoinTable(name: 'ride_like')]
    private Collection $likedBy;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->motorcycles = new ArrayCollection();
        $this->participants = new ArrayCollection();
        $this->likedBy = new ArrayCollection();
    }

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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setRide($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            // set the owning side to null (unless already changed)
            if ($comment->getRide() === $this) {
                $comment->setRide(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Motorcycle>
     */
    public function getMotorcycles(): Collection
    {
        return $this->motorcycles;
    }

    public function addMotorcycle(Motorcycle $motorcycle): static
    {
        if (!$this->motorcycles->contains($motorcycle)) {
            $this->motorcycles->add($motorcycle);
        }

        return $this;
    }

    public function removeMotorcycle(Motorcycle $motorcycle): static
    {
        $this->motorcycles->removeElement($motorcycle);

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getParticipants(): Collection
    {
        return $this->participants;
    }

    public function addParticipant(User $participant): static
    {
        if (!$this->participants->contains($participant)) {
            $this->participants->add($participant);
        }

        return $this;
    }

    public function removeParticipant(User $participant): static
    {
        $this->participants->removeElement($participant);

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getLikedBy(): Collection
    {
        return $this->likedBy;
    }

    public function addLikedBy(User $likedBy): static
    {
        if (!$this->likedBy->contains($likedBy)) {
            $this->likedBy->add($likedBy);
        }

        return $this;
    }

    public function removeLikedBy(User $likedBy): static
    {
        $this->likedBy->removeElement($likedBy);

        return $this;
    }
}
