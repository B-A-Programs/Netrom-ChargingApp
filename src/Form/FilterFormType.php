<?php

namespace App\Form;

use App\Entity\Location;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FilterFormType extends AbstractType
{
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('cities', ChoiceType::class, [
            'choice_loader' => new CallbackChoiceLoader(function() {
                $cities = $this->entityManager->getRepository(Location::class)->findCities();
                $cities_dict = ['Select city' => '-1'];
                foreach($cities as $city) {
                    $cities_dict[$city['city']] = $city['city'];
                }
                return $cities_dict;
            }),
        ]) ->add('type', ChoiceType::class, [
            'choices' => ['Select charging type' => -1, 'Type 0' => 'Type 0', 'Type 1' => 'Type 1', 'Type 2' => 'Type 2']
        ]) ->add('submit', SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
