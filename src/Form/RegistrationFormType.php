<?php

namespace App\Form;

use App\Entity\User;
use App\Enum\DriverLevel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', null, [
                'label' => 'Adresse email',
            ])
            ->add('pseudo', null, [
                'label' => 'Pseudo',
                'constraints' => [
                    new NotBlank(message: 'Choisis un pseudo'),
                    new Length(
                        min: 3,
                        max: 100,
                        minMessage: 'Ton pseudo doit faire au moins {{ limit }} caractères',
                    ),
                ],
            ])
            ->add('birthdate', DateType::class, [
                'label' => 'Date de naissance',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('phoneNumber', null, [
                'label' => 'Téléphone',
                'required' => false,
                'help' => 'Format : 0612345678 ou +33612345678',
                'attr' => ['placeholder' => 'votre numéro de téléphone'],
            ])
            ->add('driverLvl', EnumType::class, [
                'label' => 'Niveau de pilotage',
                'class' => DriverLevel::class,
                'required' => false,
                'placeholder' => 'Quel est ton niveau de pilotage ?',
                'choice_label' => fn (DriverLevel $level) => $level->label(),
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'label' => 'J\'accepte les conditions générales',
                'mapped' => false,
                'constraints' => [
                    new IsTrue(
                        message: 'Vous devez accepter nos conditions générales.',
                    ),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Mot de passe',
                // instead of being set onto the object directly,
                // this is read and encoded in the controller
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank(
                        message: 'Veuillez entrer un mot de passe',
                    ),
                    new Length(
                        min: 6,
                        minMessage: 'Votre mot de passe doit faire au moins {{ limit }} characters',
                        // max length allowed by Symfony for security reasons
                        max: 4096,
                    ),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
