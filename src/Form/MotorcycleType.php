<?php

namespace App\Form;

use App\Entity\Brand;
use App\Entity\Motorcycle;
use App\Enum\MotorcycleCategory;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MotorcycleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', EnumType::class, [
                'label' => 'Type de moto',
                'class' => MotorcycleCategory::class,
                'choice_label' => fn (MotorcycleCategory $c) => $c->label(),
            ])
            ->add('displacement', null, [
                'label' => 'Cylindrée (cm³)',
                'attr' => ['min' => 125],
            ])
            ->add('autonomy', null, [
                'label' => 'Autonomie (km)',
                'required' => false,
                'attr' => ['min' => 0],
            ])
            ->add('brand', EntityType::class, [
                'label' => 'Marque',
                'class' => Brand::class,
                'choice_label' => 'name',
                'placeholder' => 'Choisis une marque',
                'required' => false,
            ])
            ->add('photoFile', FileType::class, [
                'label' => 'Photo de la moto',
                'mapped' => false,
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Motorcycle::class,
        ]);
    }
}
