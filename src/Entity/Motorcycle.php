<?php

namespace App\Entity;

use App\Enum\MotorcycleType;
use App\Repository\MotorcycleRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MotorcycleRepository::class)]
class Motorcycle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20, enumType:
    MotorcycleType::class)]
    private ?MotorcycleType $type = null;

    #[ORM\Column]
    private ?int $displacement = null;

    #[ORM\Column(nullable: true)]
    private ?int $autonomy = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?MotorcycleType
    {
        return $this->type;
    }

    public function setType(MotorcycleType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getDisplacement(): ?int
    {
        return $this->displacement;
    }

    public function setDisplacement(int $displacement): static
    {
        $this->displacement = $displacement;

        return $this;
    }

    public function getAutonomy(): ?int
    {
        return $this->autonomy;
    }

    public function setAutonomy(?int $autonomy): static
    {
        $this->autonomy = $autonomy;

        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): static
    {
        $this->photo = $photo;

        return $this;
    }
}
