<?php

namespace App\Controller;

use App\Entity\Location;
use App\Entity\Station;
use App\Form\FilterFormType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    #[Route("/{city}", name: "index")]
    public function index(ManagerRegistry $doctrine, string $city = null): Response {
        if($city) {
            $locations = $doctrine->getRepository(Location::class)->findBy(array('city' => $city));
            $title = 'Locations in ' . $city;
        }
        else {
            $locations = $doctrine->getRepository(Location::class)->findAll();
            $title = 'All locations';
        }

        $form = $this->createForm(FilterFormType::class);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
        }

        $cities = $doctrine->getRepository(Location::class)->findCities();

        return $this->render('index.html.twig', [
            'locations'=>$locations,
            'title'=>$title,
            'cities'=>$cities,
            'form'=> $form->createView()
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
