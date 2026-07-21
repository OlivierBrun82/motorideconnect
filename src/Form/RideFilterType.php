<?php

namespace App\Form;

use App\Enum\RideRhythm;
use App\Enum\RideStatus;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RideFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('departmentCode', TextType::class, [
                'label' => 'Département',
                'required' => false,
                'attr' => ['placeholder' => 'Ex : 75'],
            ])
            ->add('rideType', EnumType::class, [
                'label' => 'Rhytme de la balade',
                'class' => RideRhythm::class,
                'choice_label' => fn (RideRhythm $r) => $r->label(),
                'required' => false,
                'placeholder' => 'Tous les rythmes',
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Satut',
                'choices' => [
                    'Ouverte' => RideStatus::Open,
                    'Complète' => RideStatus::Full,
                    'Annulée' => RideStatus::Canceled,
                ],
                'required' => false,
                'placeholder' => 'Tous les statuts',
            ])
            ->add('dateFrom', DateType::class, [
                'label' => 'À partir du',
                'widget' => 'single_text',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
    }
}
