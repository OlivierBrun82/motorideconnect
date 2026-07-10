<?php

namespace App\Entity;

use App\Repository\BrandRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BrandRepository::class)]
class Brand
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    /**
     * @var Collection<int, Motorcycle>
     */
    #[ORM\OneToMany(targetEntity: Motorcycle::class, mappedBy: 'brand')]
    private Collection $motorcycles;

    public function __construct()
    {
        $this->motorcycles = new ArrayCollection();
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
            $motorcycle->setBrand($this);
        }

        return $this;
    }

    public function removeMotorcycle(Motorcycle $motorcycle): static
    {
        if ($this->motorcycles->removeElement($motorcycle)) {
            // set the owning side to null (unless already changed)
            if ($motorcycle->getBrand() === $this) {
                $motorcycle->setBrand(null);
            }
        }

        return $this;
    }
}
