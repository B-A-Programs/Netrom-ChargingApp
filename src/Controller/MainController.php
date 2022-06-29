<?php

namespace App\Controller;

use App\Entity\Location;
use App\Entity\Station;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    /**
     * @Route("/", name="index")
     */
    public function index(ManagerRegistry $doctrine): Response {
        $locations = $doctrine->getRepository(Location::class)->findAll();
        $stations = $doctrine->getRepository(Station::class)->findAll();

        return $this->render('index.html.twig', [
            'locations'=>$locations,
            'stations'=>$stations
        ]);
    }

//    /**
//     * @Route("/location/{name}", name="location")
//     */
//    public function location(ManagerRegistry $doctrine, $name): Response {
//
//    }
}
