<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use App\Enum\DriverLevel;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'Il existe déjà un compte avec cette email')]
#[UniqueEntity(fields: ['pseudo'], message: 'Ce pseudo est déjà utilisé')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank(message: 'L\'email est obligatoire')]
    #[Assert\Email(message: 'L\'email n\'est pas valide')]
    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column]
    private bool $isVerified = false;

    #[Assert\NotBlank(message: 'Choisis un pseudo')]
    #[Assert\Length(
        min: 3,
        max: 100,
        minMessage: 'Le pseudo doit contenir au moins {{ limit }} caractères',
        maxMessage: 'Le pseudo ne peut pas dépasser {{ limit }} caractères',
    )]
    #[Assert\Regex(
        // Tiret echappe : les navigateurs compilent l'attribut pattern en mode
        // strict, ou un tiret non echappe dans une classe est refuse
        pattern: '/^[a-zA-Z0-9_\-]+$/',
        message: 'Le pseudo ne peut contenir que des lettres, chiffres, - et _',
    )]
    #[ORM\Column(length: 100, unique: true)]
    private ?string $pseudo = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatar = null;

    #[Assert\LessThanOrEqual('today', message: 'La date de naissance ne peut pas être dans le futur')]
    #[Assert\LessThanOrEqual('-16 years', message: 'Vous devez avoir au moins 16 ans pour vous inscrire')]
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $birthdate = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $about = null;

    #[Assert\Regex(
        pattern: '/^(?:\+33|0)\s?[1-9](\s?\d{2}){4}$/',
        message: 'Le numéro de téléphone n\'est pas valide, ex : 0612345678 ou +33612345678',
    )]
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phoneNumber = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $bannedDate = null;

    #[ORM\Column(length: 20, nullable: true, enumType: DriverLevel::class)]
    private ?DriverLevel $driverLvl = null;

    #[ORM\Column]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, Motorcycle>
     */
    #[ORM\OneToMany(targetEntity: Motorcycle::class, mappedBy: 'user')]
    private Collection $motorcycles;

    /**
     * @var Collection<int, Ride>
     */
    #[ORM\OneToMany(targetEntity: Ride::class, mappedBy: 'user')]
    private Collection $rides;

    /**
     * @var Collection<int, Strikes>
     */
    #[ORM\OneToMany(targetEntity: Strikes::class, mappedBy: 'user')]
    private Collection $strikes;

    /**
     * @var Collection<int, Comment>
     */
    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'user')]
    private Collection $comments;

    /**
     * @var Collection<int, Ride>
     */
    #[ORM\ManyToMany(targetEntity: Ride::class, mappedBy: 'participants')]
    private Collection $ridesParticipated;

    /**
     * @var Collection<int, Ride>
     */
    #[ORM\ManyToMany(targetEntity: Ride::class, mappedBy: 'likedBy')]
    private Collection $ridesLiked;

    public function __construct()
    {
        $this->motorcycles = new ArrayCollection();
        $this->rides = new ArrayCollection();
        $this->strikes = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->ridesParticipated = new ArrayCollection();
        $this->ridesLiked = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function getPseudo(): ?string
    {
        return $this->pseudo;
    }

    public function setPseudo(string $pseudo): static
    {
        $this->pseudo = $pseudo;

        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): static
    {
        $this->avatar = $avatar;

        return $this;
    }

    public function getBirthdate(): ?\DateTime
    {
        return $this->birthdate;
    }

    public function setBirthdate(?\DateTime $birthdate): static
    {
        $this->birthdate = $birthdate;

        return $this;
    }

    public function getAbout(): ?string
    {
        return $this->about;
    }

    public function setAbout(?string $about): static
    {
        $this->about = $about;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): static
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    public function getBannedDate(): ?\DateTime
    {
        return $this->bannedDate;
    }

    public function setBannedDate(?\DateTime $bannedDate): static
    {
        $this->bannedDate = $bannedDate;

        return $this;
    }

    public function getDriverLvl() : ?DriverLevel
    {
        return $this->driverLvl;
    }

    public function setDriverLvl(?DriverLevel $driverLvl) : static
    {
        $this->driverLvl = $driverLvl;

        return $this;
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
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
            $motorcycle->setUser($this);
        }

        return $this;
    }

    public function removeMotorcycle(Motorcycle $motorcycle): static
    {
        if ($this->motorcycles->removeElement($motorcycle)) {
            // set the owning side to null (unless already changed)
            if ($motorcycle->getUser() === $this) {
                $motorcycle->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Ride>
     */
    public function getRides(): Collection
    {
        return $this->rides;
    }

    public function addRide(Ride $ride): static
    {
        if (!$this->rides->contains($ride)) {
            $this->rides->add($ride);
            $ride->setUser($this);
        }

        return $this;
    }

    public function removeRide(Ride $ride): static
    {
        if ($this->rides->removeElement($ride)) {
            // set the owning side to null (unless already changed)
            if ($ride->getUser() === $this) {
                $ride->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Strikes>
     */
    public function getStrikes(): Collection
    {
        return $this->strikes;
    }

    public function addStrike(Strikes $strike): static
    {
        if (!$this->strikes->contains($strike)) {
            $this->strikes->add($strike);
            $strike->setUser($this);
        }

        return $this;
    }

    public function removeStrike(Strikes $strike): static
    {
        if ($this->strikes->removeElement($strike)) {
            // set the owning side to null (unless already changed)
            if ($strike->getUser() === $this) {
                $strike->setUser(null);
            }
        }

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
            $comment->setUser($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            // set the owning side to null (unless already changed)
            if ($comment->getUser() === $this) {
                $comment->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Ride>
     */
    public function getRidesParticipated(): Collection
    {
        return $this->ridesParticipated;
    }

    public function addRidesParticipated(Ride $ridesParticipated): static
    {
        if (!$this->ridesParticipated->contains($ridesParticipated)) {
            $this->ridesParticipated->add($ridesParticipated);
            $ridesParticipated->addParticipant($this);
        }

        return $this;
    }

    public function removeRidesParticipated(Ride $ridesParticipated): static
    {
        if ($this->ridesParticipated->removeElement($ridesParticipated)) {
            $ridesParticipated->removeParticipant($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Ride>
     */
    public function getRidesLiked(): Collection
    {
        return $this->ridesLiked;
    }

    public function addRidesLiked(Ride $ridesLiked): static
    {
        if (!$this->ridesLiked->contains($ridesLiked)) {
            $this->ridesLiked->add($ridesLiked);
            $ridesLiked->addLikedBy($this);
        }

        return $this;
    }

    public function removeRidesLiked(Ride $ridesLiked): static
    {
        if ($this->ridesLiked->removeElement($ridesLiked)) {
            $ridesLiked->removeLikedBy($this);
        }

        return $this;
    }
}
