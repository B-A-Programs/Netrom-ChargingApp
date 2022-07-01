<?php

namespace App\Controller;

use App\Entity\Location;
use App\Entity\Station;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class MainController extends AbstractController
{
    #[Route("/", name: "index")]
    public function index(ManagerRegistry $doctrine, string $city = null): Response {
        if(array_key_exists('city', $_GET) && array_key_exists('charger', $_GET)) {
            $locs = $doctrine->getRepository(Location::class)->findBy(array('city' => $_GET['city']));
            $locations = [];
            foreach($locs as $location)
            {
                $stations = $location->getStations();
                foreach($stations as $station)
                {
                    if($station->getType() == $_GET['charger'])
                    {
                        $locations[] = $location;
                        break;
                    }
                }
            }
            $title = 'Locations in ' . $_GET['city'] . ' with charger type ' . $_GET['charger'];
        }
        elseif(array_key_exists('city', $_GET)) {
            $locations = $doctrine->getRepository(Location::class)->findBy(array('city' => $_GET['city']));
            $title = 'Locations in ' . $_GET['city'];
        }
        else {
            $locations = $doctrine->getRepository(Location::class)->findAll();
            $title = 'All locations';
        }

        $cities = $doctrine->getRepository(Location::class)->findCities();

        return $this->render('index.html.twig', [
            'locations'=>$locations,
            'title'=>$title,
            'cities'=>$cities
        ]);
    }

    #[Route("/location/{name}", name: "location")]
    public function location(ManagerRegistry $doctrine, $name): Response {
        $location = $doctrine->getRepository(Location::class)->findOneBy(array('name' => $name));

        return $this->render('location.html.twig', [
            'location'=>$location,
        ]);
    }
}
