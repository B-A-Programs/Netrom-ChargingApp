<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\Car;
use App\Entity\Location;
use App\Entity\Review;
use App\Entity\Station;
use App\Form\BookingFormType;
use App\Form\EditBookingFormType;
use App\Form\FilterFormType;;
use Doctrine\Persistence\ManagerRegistry;
use Geocoder\Provider\FreeGeoIp\FreeGeoIp;
use Http\Adapter\Guzzle7\Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Geocoder\Query\GeocodeQuery;
use Geocoder\Query\ReverseQuery;

function vincentyGreatCircleDistance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000): float|int
{
    // convert from degrees to radians
    $latFrom = deg2rad($latitudeFrom);
    $lonFrom = deg2rad($longitudeFrom);
    $latTo = deg2rad($latitudeTo);
    $lonTo = deg2rad($longitudeTo);

    $lonDelta = $lonTo - $lonFrom;
    $a = pow(cos($latTo) * sin($lonDelta), 2) +
        pow(cos($latFrom) * sin($latTo) - sin($latFrom) * cos($latTo) * cos($lonDelta), 2);
    $b = sin($latFrom) * sin($latTo) + cos($latFrom) * cos($latTo) * cos($lonDelta);

    $angle = atan2(sqrt($a), $b);
    return $angle * $earthRadius;
}

class MainController extends AbstractController
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    #[Route("/proximity", name: "proximity")]
    public function proximity(ManagerRegistry $doctrine)
    {
        $httpClient = new Client();
        $response = $httpClient->sendRequest(New \GuzzleHttp\Psr7\Request('GET', 'https://api.ipbase.com/v2/info?apikey=ISh4pHR7dmSclZFiETUMvq9Tme8SnQdCOL5Nb9LN&ip=82.77.165.60'));
        $result = json_decode($response->getBody()->getContents(), true);
        $lat = $result["data"]["location"]["latitude"];
        $lon = $result["data"]["location"]["longitude"];

        $locations = $doctrine->getRepository(Location::class)->findAll();
        $validLocations = [];
        foreach($locations as $location)
        {
            if(vincentyGreatCircleDistance($lat, $lon, $location->getLat(), $location->getLon()) < 20000)
                $validLocations[] = $location;
        }
        return $this->render('index.html.twig', [
            'message' => 'Nonexistent',
            'title' => 'Locations in close proximity to you.',
            'locations' => $validLocations,
            'form' => $this->createForm(FilterFormType::class)->createView()
        ]);
    }

    #[Route("/", name: "index")]
    public function index(Request $request, ManagerRegistry $doctrine): Response
    {
        $form = $this->createForm(FilterFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $filter_city = $form->getData()['cities'];
            $filter_charger = $form->getData()['type'];
            if ($filter_city == "-1" && $filter_charger == "-1") {
                return $this->render('index.html.twig', [
                    'message' => 'You must select a city and/or a charging type when filtering',
                    'title' => 'All locations',
                    'locations' => $doctrine->getRepository(Location::class)->findAll(),
                    'form' => $form->createView()
                ]);
            }
            elseif ($filter_city != "-1" && $filter_charger == "-1") {
                return $this->render('index.html.twig', [
                    'title' => 'Locations in ' . $filter_city,
                    'locations' => $doctrine->getRepository(Location::class)->findBy(['city'=>$filter_city]),
                    'form' => $form->createView(),
                    'message' => 'Nonexistent'
                ]);
            }
            elseif ($filter_charger != "-1" && $filter_city == "-1") {
                return $this->render('index.html.twig', [
                    'title' => 'Locations with chargers of ' . $filter_charger,
                    'locations' => $doctrine->getRepository(Location::class)->filterCharger($filter_charger),
                    'form' => $form->createView(),
                    'message' => 'Nonexistent'
                ]);
            }

            $locations = $doctrine->getRepository(Location::class)->filterCityCharger($filter_city, $filter_charger);
            $title = 'Locations in ' . $filter_city . ' with charger type ' . $filter_charger;
        } else {
            $locations = $doctrine->getRepository(Location::class)->findAll();
            $title = 'All locations';
        }

        return $this->render('index.html.twig', [
            'form' => $form->createView(),
            'locations' => $locations,
            'title' => $title,
            'message' => 'Nonexistent'
        ]);
    }

    #[Route("/profile", name: "profile", methods: ['GET'])]
    public function profile(ManagerRegistry $doctrine): Response
    {
        $user = $this->security->getUser();

        if (!$user) {
            return $this->render('index.html.twig', [
                'form' => $form = $this->createForm(FilterFormType::class)->createView(),
                'locations' => $doctrine->getRepository(Location::class)->findAll(),
                'title' => 'All locations',
                'message' => 'You must be logged in to access profile page!'
            ]);
        }

        return $this->render('profile.html.twig', [
            'bookings' => $doctrine->getRepository(Booking::class)->getUserBookings($user),
            'message' => 'Nonexistent'
        ]);
    }

    #[Route("/profile", name: "addcar", methods: ['POST'])]
    public function profileAddCar(ManagerRegistry $doctrine, Request $request): Response
    {
        $user = $this->security->getUser();

        $license = $request->get('license');
        $type = $request->get('chrtype');

        if (!$license || $type == "er") {
            return $this->render('profile.html.twig', [
                'bookings' => $doctrine->getRepository(Booking::class)->getUserBookings($user),
                'message' => 'Wrong input. Please select charging type and include a valid license plate.'
            ]);
        }
        $cars = $doctrine->getRepository(Car::class)->findAll();
        foreach ($cars as $car) {
            if ($car->getLicensePlate() == $license) {
                return $this->render('profile.html.twig', [
                    'bookings' => $doctrine->getRepository(Booking::class)->getUserBookings($user),
                    'message' => 'A car with that license plate already exists. Make sure you typed it correctly.'
                ]);
            }
        }

        $car = new Car();
        $car->setLicensePlate($license);
        $car->setChargeType("Type " . $type);
        $car->setUser($user);
        $doctrine->getManager()->persist($car);
        $doctrine->getManager()->flush();

        return $this->redirectToRoute('profile');
    }

    #[Route("/location/{name}", name: "location")]
    public function location(ManagerRegistry $doctrine, $name): Response
    {
        $location = $doctrine->getRepository(Location::class)->findOneBy(array('name' => $name));

        return $this->render('location.html.twig', [
            'location' => $location,
        ]);
    }

    #[Route("/station/{id}", name: "station")]
    public function station(Request $request, ManagerRegistry $doctrine, ValidatorInterface $validator, $id): Response
    {
        $station = $doctrine->getRepository(Station::class)->findOneBy(array('id' => $id));

        $form = $this->createForm(BookingFormType::class);
        $form->handleRequest($request);

        $bookings = $doctrine->getRepository(Booking::class)->getActiveBookings($id);


        if ($form->isSubmitted() && $form->isValid()) {
            $start = $form->getData()['start'];
            $end = $form->getData()['end'];
            $car = $doctrine->getRepository(Car::class)->findOneBy(array('license_plate' => $form->getData()['car']));

            if(!$car == null) {
                $overlap = $doctrine->getRepository(Booking::class)->getOverlappingBookings($station, $start, $end);
                if(count($overlap) > 0)
                {
                    return $this->render('station.html.twig', [
                            'station' => $station,
                            'form' => $form->createView(),
                            'bookings' => $bookings,
                            'message' => 'There is another booking in that timeframe!',
                            'reviews' => $doctrine->getRepository(Review::class)->findBy(array('station' => $station), array('id' => 'DESC')),
                            'errors' => []
                        ]);
                }

                $overlap = $doctrine->getRepository(Booking::class)->getCarOverlap($car, $start, $end);
                if(count($overlap) > 0)
                {
                    return $this->render('station.html.twig', [
                        'station' => $station,
                        'form' => $form->createView(),
                        'bookings' => $bookings,
                        'message' => 'You have another booking for this car in the same time. Try deleting it before!',
                        'reviews' => $doctrine->getRepository(Review::class)->findBy(array('station' => $station), array('id' => 'DESC')),
                        'errors' => []
                    ]);
                }
            }

            $booking = new Booking();
            $booking->setChargestart($start);
            $booking->setChargeend($end);
            $booking->setStation($station);
            $booking->setCar($car);

            $errors = $validator->validate($booking);
            if (count($errors) > 0)
            {
                return $this->render('station.html.twig', [
                    'station' => $station,
                    'form' => $form->createView(),
                    'bookings' => $bookings,
                    'errors' => $errors,
                    'message' => 'Nonexistent',
                    'reviews' => $doctrine->getRepository(Review::class)->findBy(array('station'=>$station), array('id'=>'DESC'))
                ]);
            }

            $doctrine->getManager()->persist($booking);
            $doctrine->getManager()->flush();
            $bookings = $doctrine->getRepository(Booking::class)->getActiveBookings($id);
        }

        return $this->render('station.html.twig', [
            'station' => $station,
            'form' => $form->createView(),
            'bookings' => $bookings,
            'message' => 'Nonexistent',
            'reviews' => $doctrine->getRepository(Review::class)->findBy(array('station'=>$station), array('id'=>'DESC')),
            'errors' => []
        ]);
    }

    #[Route('/deleteBooking/{id}', name: "deleteBooking")]
    public function deleteBooking(ManagerRegistry $doctrine, $id): Response
    {
        $booking = $doctrine->getRepository(Booking::class)->findOneBy(array('id'=>$id));
        if(!$this->security->getUser() || $booking->getCar()->getUser() !== $this->security->getUser())
        {
            return $this->render('index.html.twig', [
                'form' => $form = $this->createForm(FilterFormType::class)->createView(),
                'locations' => $doctrine->getRepository(Location::class)->findAll(),
                'title' => 'All locations',
                'message' => 'The booking you are trying to delete is not yours!'
            ]);
        }

        $doctrine->getManager()->remove($booking);
        $doctrine->getManager()->flush();

        return $this->redirectToRoute('profile');
    }

    #[Route('/deleteCar/{id}', name: "deleteCar")]
    public function deleteCar(ManagerRegistry $doctrine, $id): Response
    {
        $car = $doctrine->getRepository(Car::class)->findOneBy(array('id'=>$id));
        if($this->security->getUser() !== $car->getUser())
        {
            return $this->render('index.html.twig', [
                'form' => $form = $this->createForm(FilterFormType::class)->createView(),
                'locations' => $doctrine->getRepository(Location::class)->findAll(),
                'title' => 'All locations',
                'message' => 'The car you are trying to remove is not yours!'
            ]);
        }

        $doctrine->getManager()->remove($car);
        $doctrine->getManager()->flush();

        return $this->redirectToRoute('profile');
    }

    #[Route('/editBooking/{id}', name: "editBooking")]
    public function editBooking(Request $request, ManagerRegistry $doctrine, ValidatorInterface $validator, Booking $booking): Response
    {
        if(!$this->security->getUser())
        {
            return $this->render('index.html.twig', [
                'form' => $form = $this->createForm(FilterFormType::class)->createView(),
                'locations' => $doctrine->getRepository(Location::class)->findAll(),
                'title' => 'All locations',
                'message' => 'The booking you are trying to edit is not yours!'
            ]);
        }

        $station = $booking->getStation();

        $form = $this->createForm(EditBookingFormType::class, $booking);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Booking  $newbooking */
            $newbooking = $form->getData();
            $start = $newbooking->getChargestart();
            $end = $newbooking->getChargeend();
            $car = $newbooking->getCar();

            $overlap = $doctrine->getRepository(Booking::class)->getCarOverlap($car, $start, $end);
            if(count($overlap) > 0)
            {
                if($overlap[0] != $booking || count($overlap) > 1)
                    return $this->render('edit.html.twig', [
                        'booking'=>$booking,
                        'form' => $form->createView(),
                        'message' => 'You have another booking for this car in the same time. Try deleting it before!',
                        'errors' => []
                    ]);
            }

            $bookings = $doctrine->getRepository(Booking::class)->getActiveBookings($station->getId());
            $overlap = $doctrine->getRepository(Booking::class)->getOverlappingBookings($station, $start, $end);
            if(count($overlap) > 0)
            {
                if($overlap[0] != $booking || count($overlap) > 1)
                    return $this->render('edit.html.twig', [
                        'booking'=>$booking,
                        'form' => $form->createView(),
                        'message' => 'There is another booking in that timeframe!',
                        'errors' => []
                    ]);
            }

            $booking->setChargestart($start); $booking->setChargeend($end); $booking->setCar($car);

            $doctrine->getManager()->persist($booking);
            $doctrine->getManager()->flush();
            return $this->redirectToRoute('profile');
        }
        return $this->render('edit.html.twig', [
            'booking'=>$booking,
            'form'=>$form->createView(),
            'message'=>'Nonexistent',
            'errors' => []
        ]);
    }

    #[Route("/review/{id}", name: "review", methods: ["POST"])]
    public function review(Request $request, ManagerRegistry $doctrine, $id): Response
    {
        $user = $this->security->getUser();

        $rating = $request->get('rating');
        $review = $request->get('review');
        $station = $doctrine->getRepository(Station::class)->findOneBy(array('id'=>$id));

        if(!$user || $review == '' || $rating == null)
        {
            return $this->redirectToRoute('station', ['id'=>$station->getId()]);
        }

        $rev = new Review();
        $rev->setUser($user);
        $rev->setStation($station);
        $rev->setRating($rating);
        $rev->setText($review);

        $doctrine->getManager()->persist($rev);
        $doctrine->getManager()->flush();

        return $this->redirectToRoute('station', ['id'=>$station->getId()]);
    }
}
