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
        $locations = $manager->getRepository(Location::class)->find(2);
        var_export($locations);

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
