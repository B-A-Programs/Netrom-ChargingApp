<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\Car;
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
use Symfony\Component\Security\Core\Security;

class MainController extends AbstractController
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

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

    #[Route("/profile", name: "profile")]
    public function profile(ManagerRegistry $doctrine): Response {
        $user = $this->security->getUser();

        if(!$user)
        {
            return $this->render('index.html.twig', [
                'form'=>$form = $this->createForm(FilterFormType::class)->createView(),
                'locations'=>$doctrine->getRepository(Location::class)->findAll(),
                'title'=>'All locations',
                'message'=>'You must be logged in to access profile page!'
            ]);
        }

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $license = $_REQUEST['license'];
            $type = $_REQUEST['chrtype'];

            if(!$license || $type == "er") {
                return $this->render('profile.html.twig', [
                    'bookings'=>$doctrine->getRepository(Booking::class)->getUserBookings($user),
                    'message'=>'Wrong input. Please select charging type and include a valid license plate.'
                ]);
            }
            $cars = $doctrine->getRepository(Car::class)->findAll();
            foreach($cars as $car)
            {
                if($car->getLicensePlate() == $license)
                {
                    return $this->render('profile.html.twig', [
                        'bookings'=>$doctrine->getRepository(Booking::class)->getUserBookings($user),
                        'message'=>'A car with that license plate already exists. Make sure you typed it correctly.'
                    ]);
                }
            }

            $car = new Car();
            $car->setLicensePlate($license);
            $car->setChargeType("Type " . $type);
            $car->setUser($user);
            $doctrine->getManager()->persist($car);
            $doctrine->getManager()->flush();
        }

        return $this->render('profile.html.twig', [
            'bookings'=>$doctrine->getRepository(Booking::class)->getUserBookings($user),
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

        $bookings = $doctrine->getRepository(Booking::class)->getActiveBookings($id);


        if($form->isSubmitted() && $form->isValid())
        {
            $start = $form->getData()['start'];
            $end = $form->getData()['end'];
            $car = $doctrine->getRepository(Car::class)->findOneBy(array('license_plate'=>$form->getData()['car']));

            $totalsecdifference = strtotime($end->format('Y-m-d h:i:s')) - strtotime($start->format('Y-m-d h:i:s'));
            if($totalsecdifference <= 0 || $totalsecdifference > 5400)
            {
                return $this->render('station.html.twig', [
                    'station'=>$station,
                    'form'=>$form->createView(),
                    'bookings'=>$bookings,
                    'message'=>"Start time must be less than end time and reservations can't exceed an hour and a half."
                ]);
            }
            if($start < new \DateTimeImmutable())
            {
                return $this->render('station.html.twig', [
                    'station'=>$station,
                    'form'=>$form->createView(),
                    'bookings'=>$bookings,
                    'message'=>'Booking must not be made in the past.'
                ]);
            }

            foreach($bookings as $booking)
            {
                $bstart = $booking->getChargestart();
                $bend = $booking->getChargeend();
                if(($bstart <= $start && $bend >= $start) || ($bstart <= $end && $bend >= $end))
                {
                    return $this->render('station.html.twig', [
                        'station'=>$station,
                        'form'=>$form->createView(),
                        'bookings'=>$bookings,
                        'message'=>'There is another booking in that timeframe!'
                    ]);
                }
            }

            if($car->getChargeType() != $station->getType())
            {
                return $this->render('station.html.twig', [
                    'station'=>$station,
                    'form'=>$form->createView(),
                    'bookings'=>$bookings,
                    'message'=>'This car has a different charging type from the station. Please select a different station or a car with charging ' . $station->getType(),
                ]);
            }

            $booking = new Booking();
            $booking->setChargestart($start);
            $booking->setChargeend($end);
            $booking->setStation($station);
            $booking->setCar($car);

            $doctrine->getManager()->persist($booking);
            $doctrine->getManager()->flush();
            $bookings = $doctrine->getRepository(Booking::class)->getActiveBookings($id);
        }

        return $this->render('station.html.twig', [
            'station'=>$station,
            'form'=>$form->createView(),
            'bookings'=>$bookings,
            'message'=>'Nonexistent'
        ]);
    }
}
