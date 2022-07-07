<?php

namespace App\Form;

use App\Entity\Car;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;

class BookingFormType extends AbstractType
{
    private $entityManager, $security;

    public function __construct(EntityManagerInterface $entityManager, Security $security)
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('start', DateTimeType::class, array(
                'widget' => 'single_text',))
            ->add('end', DateTimeType::class, array(
                'widget' => 'single_text',))
            ->add('car', ChoiceType::class, [
                'choice_loader' => new CallbackChoiceLoader(function() {
                    $cars = $this->entityManager->getRepository(Car::class)->findBy(array('user'=>$this->security->getUser()));
                    $cars_dict = ['Select car' => '-1'];
                    foreach($cars as $car) {
                        $cars_dict[$car->getLicensePlate()] = $car->getLicensePlate();
                    }
                    return $cars_dict;
                }),
            ])
            ->add('book', SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
