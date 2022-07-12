<?php

namespace App\Entity;

use App\Repository\BookingRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BookingRepository::class)]
class Booking
{
    #[Assert\IsTrue(message: 'You must select a car to make your reservation.')]
    public function isCarPresent(): bool
    {
        return $this->car != null;
    }

    #[Assert\IsTrue(message: "Start time must be less than end time, reservations can't exceed an hour and a half and bookings cannot be made in the past.")]
    public function isTimeCorrect(): bool
    {
        $totalsecdifference = strtotime($this->chargeend->format('Y-m-d h:i:s')) - strtotime($this->getChargestart()->format('Y-m-d h:i:s'));
        return $this->chargestart < $this->chargeend && $totalsecdifference < 5400 && $this->chargestart > new DateTimeImmutable();
    }

    #[Assert\IsTrue(message: "Car and station have different charging types.")]
    public function isTypeSimilar(): bool
    {
        return $this->getCar()->getChargeType() == $this->getStation()->getType();
    }

//    #[Assert\IsTrue(message: "You have another booking for this car in the same time. Try deleting it before!")]
//    public function isOnlyBooking(ManagerRegistry $doctrine): bool
//    {
//        $userbookings = $doctrine->getRepository(Booking::class)->getUserBookings($this->car->getUser());
//        foreach($userbookings as $booking)
//        {
//            if($booking->getCar() != $this->car)
//                continue;
//            $bstart = $booking->getChargestart();
//            $bend = $booking->getChargeend();
//            if(($bstart <= $this->getChargestart() && $bend >= $this->getChargestart()) || ($bstart <= $this->getChargeend() && $bend >= $this->getChargeend()) || ($bstart >= $this->getChargestart() && $bend <= $this->getChargeend())) {
//                return false;
//            }
//        }
//        return true;
//    }


    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\ManyToOne(targetEntity: Car::class, inversedBy: 'bookings')]
    #[ORM\JoinColumn(nullable: false)]
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
