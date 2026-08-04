<?php

namespace App\Form;

use App\Data\FrenchDepartments;
use App\Entity\Motorcycle;
use App\Entity\Ride;
use App\Enum\DriverLevel;
use App\Enum\RideRhythm;
use App\Repository\MotorcycleRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\Range;

class RideType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, [
                'label' => 'Nomme ta balade',
            ])
            ->add('descriptionMessage', null, [
                'label' => 'Donne une description de ta balade',
                'required' => false,
            ])
            ->add('departmentCode', ChoiceType::class, [
                'label' => 'Département',
                'choices' => FrenchDepartments::choices(),
                'autocomplete' => true,
                'placeholder' => 'Choisis un département',
            ])
            ->add('meetingDatetime', DateTimeType::class, [
                'label' => 'Date et heure du rendez-vous',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'constraints' => [
                    new GreaterThan(
                        value: 'now',
                        message: 'La date de rendez-vous doit être dans le futur.',
                    ),
                ],
            ])
            ->add('StartTime', TimeType::class, [
                'label' => 'Heure de départ',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
            ->add('endTime', TimeType::class, [
                'label' => "Estimation de l'heure d'arrivé",
                'widget' => 'single_text',
                'input' => 'datetime_immutable'
            ])
            ->add('meetingPlace', null, [
                'label' => 'Lieu de rendez-vous'
            ])
            ->add('endPoint', null, [
                'label' => "Lieu d'arrivé",
                'required' => false,
            ])
            ->add('distanceKm', null, [
                'label' => "Estimation de la distance",
                'required' => false,
                'attr' => ['min' => 0],
            ])
            ->add('rideType', EnumType::class, [
                'label' => 'Rhytme de la balade',
                'class' => RideRhythm::class,
                'choice_label' => fn (RideRhythm $rhytme) => $rhytme->label(),
            ])
            ->add('pilotLevel', EnumType::class, [
                'label' => 'Niveau conseillé',
                'class' => DriverLevel::class,
                'choice_label' => fn (DriverLevel $driverLevel) => $driverLevel->label(),
            ])
            ->add('capacity', null, [
                'label' => 'Nombre de places',
                'attr' => ['min' => 2, 'max' => 8],
                'constraints' => [
                    new Range(
                        notInRangeMessage: 'Le nombre de places doit être compris entre {{ min }} et {{ max }}.',
                        min: 2,
                        max: 8,
                    ),
                ],
            ])
            ->add('motorcycle', EntityType::class, [
                'label' => 'Ta moto pour cette balade',
                'class' => Motorcycle::class,
                'choice_label' => fn (Motorcycle $m) => trim(($m->getBrand()?->getName() . ' ' . $m->getType()->label())) . ' (' . $m->getDisplacement() . ' cm³)',
                // Non mappé : on choisit UNE moto, ajoutée à la main dans le contrôleur
                'mapped' => false,
                'required' => false,
                'placeholder' => 'Choisis une moto',
                // Ne propose que les motos du user connecté (passé via l'option 'owner')
                'query_builder' => fn (MotorcycleRepository $repo) => $repo->createQueryBuilder('m')
                    ->where('m.user = :owner')
                    ->setParameter('owner', $options['owner'])
                    ->orderBy('m.type', 'ASC'),
            ])
        ;

        // La capacite ne peut pas descendre sous le nombre d'inscrits deja presents.
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $ride = $event->getData();
            $registeredCount = $ride->getParticipants()->count();

            if ($ride->getCapacity() !== null && $ride->getCapacity() < $registeredCount) {
                $event->getForm()->get('capacity')->addError(new FormError(
                    sprintf('Cette balade compte déjà %d inscrit(s) : tu ne peux pas descendre en dessous.', $registeredCount)
                ));
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ride::class,
            'owner' => null,
        ]);
    }
}
