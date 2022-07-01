<?php

namespace App\Form;

use App\Entity\Location;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FilterFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('cities', ChoiceType::class, [
            'choice_loader' => new CallbackChoiceLoader(function() {
                return ['Select city' => -1, 'Barcelona'=>'Barcelona', 'Craiova'=>'Craiova', 'Lisabona'=>'Lisabona', 'Monaco'=>'Monaco'];
            }),
        ]) ->add('type', ChoiceType::class, [
            'choices' => ['Select charging type' => -1, '0' => 0, '1' => 1, '2' => 2]
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
