<?php

namespace App\Form;

use App\Enum\DriverLevel;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Validator\Constraints\Length;
use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProfileFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Adresse email',
                'attr' => ['autocomplete' => 'email'],
            ])
            ->add('pseudo', null, [
                'label' => 'Pseudo',
                'attr' => ['autocomplete' => 'username'],
            ])
            ->add('avatarFile', FileType::class, [
                'label' => 'Photo de profil',
                'mapped' => false,
                'required' => false,
            ])
            ->add('birthdate', DateType::class, [
                'label' => 'Date de naissance',
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'max' => (new \DateTimeImmutable('-16 years'))->format('Y-m-d'),
                ],
            ])
            ->add('about', TextareaType::class, [
                'label' => 'À propos',
                'required' => false,
                'attr' => [
                    'rows' => 5,
                    'placeholder' => 'Parle-nous de toi ...',
                ],
            ])
            ->add('phoneNumber', TelType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'help' => 'Format : 0612345678 ou +33612345678',
                'attr' => [
                    'placeholder' => 'Ton numéro de téléphone',
                    'autocomplete' => 'tel',
                    'inputmode' => 'tel',
                ],
            ])
            ->add('driverLvl', EnumType::class, [
                'label' => 'Niveau de pilotage',
                'class' => DriverLevel::class,
                'required' => false,
                'placeholder' => 'Quel est ton niveau de pilotage ?',
                'choice_label' => fn (DriverLevel $level) => $level->label(),
            ])
            ->add('currentPassword', PasswordType::class, [
                'label' => 'Mot de passe actuel',
                'mapped' => false,
                'required' => false,
                'attr' => ['autocomplete' => 'current-password'],
                'help' => 'Requis uniquement si tu changes ton mot de passe',
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'required' => false,
                'invalid_message' => 'Les deux mots de passe ne correspondent pas',
                'first_options' => [
                    'label' => 'Nouveau mot de passe',
                    'attr' => ['autocomplete' => 'new-password'],
                ],
                'second_options' => [
                    'label' => 'Confirmer le mot de passe',
                    'attr' => ['autocomplete' => 'new-password'],
                ],
                'constraints' => [
                    new Length(
                        min: 8,
                        minMessage: 'Ton mot de passe doit faire au moins {{ limit }} caractères',
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
