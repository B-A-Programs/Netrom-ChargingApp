<?php

namespace App\DataFixtures;

use App\Entity\Location;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class LocationFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        /*for ($i = 1; $i <= 9; $i++) {
            $location = new Location();
            $location->setName("Craiova Kaufland" . $i);
            $location->setPrice(4.55);
            $location->setLat(41.40338 - $i);
            $location->setLon(2.17401 + $i);
            $location->setStationsNumber(0);
            $manager->persist($location);
        }*/

        $manager->flush();
    }
}
