<?php

namespace App\Entity;

use App\Repository\BookingRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BookingRepository::class)]
class Booking
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\ManyToOne(targetEntity: Car::class, inversedBy: 'bookings')]
    #[ORM\JoinColumn(nullable: true)]
    private $car;

    #[ORM\ManyToOne(targetEntity: Station::class, inversedBy: 'bookings')]
    #[ORM\JoinColumn(nullable: false)]
    private $station;

    #[ORM\Column(type: 'datetime')]
    private $chargestart;

    #[ORM\Column(type: 'datetime')]
    private $chargeend;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCar(): ?Car
    {
        return $this->car;
    }

    public function setCar(?Car $car): self
    {
        $this->car = $car;

        return $this;
    }

    public function getStation(): ?Station
    {
        return $this->station;
    }

    public function setStation(?Station $station): self
    {
        $this->station = $station;

        return $this;
    }

    public function getChargestart(): ?\DateTimeInterface
    {
        return $this->chargestart;
    }

    public function setChargestart(\DateTimeInterface $chargestart): self
    {
        $this->chargestart = $chargestart;

        return $this;
    }

    public function getChargeend(): ?\DateTimeInterface
    {
        return $this->chargeend;
    }

    public function setChargeend(\DateTimeInterface $chargeend): self
    {
        $this->chargeend = $chargeend;

        return $this;
    }
}
