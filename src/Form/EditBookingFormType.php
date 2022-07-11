<?php

namespace App\Form;

use App\Entity\Booking;
use App\Entity\Car;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;

class EditBookingFormType extends AbstractType
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
            ->add('chargestart', DateTimeType::class, array('widget' => 'single_text',))
            ->add('chargeend', DateTimeType::class, array('widget' => 'single_text',))
            ->add('car', EntityType::class, [
                'class' => Car::class,
                'choice_loader' => new CallbackChoiceLoader(function () {
                    $cars = $this->entityManager->getRepository(Car::class)->findBy(array('user' => $this->security->getUser()));
                    return $cars;
                }),
            ])
            ->add('book', SubmitType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Booking::class,
        ]);
    }
}
