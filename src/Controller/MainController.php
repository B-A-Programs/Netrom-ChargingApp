<?php

namespace App\Controller;

use App\Entity\Location;
use App\Entity\Station;
use App\Form\BookingFormType;
use App\Form\FilterFormType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use phpDocumentor\Reflection\Types\Null_;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class MainController extends AbstractController
{
    #[Route("/", name: "index")]
    public function index(Request $request, ManagerRegistry $doctrine): Response {
        $form = $this->createForm(FilterFormType::class);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            $filter_city = $form->getData()['cities'];
            $filter_charger = $form->getData()['type'];
            if($filter_city == "-1" || $filter_charger == "-1")
            {
                return $this->render('index.html.twig', [
                    'message' => 'You must select both a city and a charging type when filtering',
                    'title' => 'All locations',
                    'locations' => $doctrine->getRepository(Location::class)->findAll(),
                    'form' => $form->createView()
                ]);
            }

            $locations = $doctrine->getRepository(Location::class)->filterCityCharger($filter_city, $filter_charger);
            $title = 'Locations in ' . $filter_city . ' with charger type ' . $filter_charger;
        }
        else
        {
            $locations = $doctrine->getRepository(Location::class)->findAll();
            $title = 'All locations';
        }

        return $this->render('index.html.twig', [
            'form'=>$form->createView(),
            'locations'=>$locations,
            'title'=>$title,
            'message'=>'Nonexistent'
        ]);
    }

    #[Route("/location/{name}", name: "location")]
    public function location(ManagerRegistry $doctrine, $name): Response {
        $location = $doctrine->getRepository(Location::class)->findOneBy(array('name' => $name));

        return $this->render('location.html.twig', [
            'location'=>$location,
        ]);
    }

    #[Route("/station/{id}", name: "station")]
    public function station(Request $request, ManagerRegistry $doctrine, $id): Response {
        $station = $doctrine->getRepository(Station::class)->findOneBy(array('id' => $id));

        $form = $this->createForm(BookingFormType::class);
        $form->handleRequest($request);

        return $this->render('station.html.twig', [
            'station'=>$station,
            'form'=>$form->createView()
        ]);
    }
}
