<?php

namespace App\DataFixtures;

use App\Entity\Location;
use App\Entity\Station;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use function Symfony\Component\Translation\t;

class StationFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        for ($i = 1; $i <= 9; $i++) {
            $location = new Location();
            $location->setName("Craiova Kaufland" . $i);
            $location->setPrice(4.55);
            $location->setLat(41.40338 - $i);
            $location->setLon(2.17401 + $i);
            $location->setStationsNumber(0);
            $manager->persist($location);
        }
        $manager->flush();

        $locations = $manager->getRepository(Location::class)->findAll();

        for ($i = 1; $i <= 100; $i++) {
            $station = new Station();
            $station->setLocation($locations[$i%9]);
            $locations[$i%9]->setStationsNumber($locations[$i%9]->getStationsNumber() + 1);

            $station->setPower(55);
            $station->setType("Type 1");
            $manager->persist($locations[$i%9]);
            $manager->persist($station);
        }

        $manager->flush();
    }
}
