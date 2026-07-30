<?php

namespace App\Form;

use App\Data\FrenchDepartments;
use App\Enum\DriverLevel;
use App\Enum\RideRhythm;
use App\Enum\RideStatus;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RideFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('departmentCode', ChoiceType::class, [
                'label' => 'Département',
                'choices' => FrenchDepartments::choices(),
                'autocomplete' => true,
                'required' => false,
                'placeholder' => 'Tous les départements',
            ])
            ->add('rideType', EnumType::class, [
                'label' => 'Rhytme de la balade',
                'class' => RideRhythm::class,
                'choice_label' => fn (RideRhythm $r) => $r->label(),
                'required' => false,
                'placeholder' => 'Tous les rythmes',
            ])
            ->add('pilotLevel', EnumType::class, [
                'label' => 'Niveau conseillé',
                'class' => DriverLevel::class,
                'choice_label' => fn (DriverLevel $level) => $level->label(),
                'required' => false,
                'placeholder' => 'Tous les niveaux',
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
